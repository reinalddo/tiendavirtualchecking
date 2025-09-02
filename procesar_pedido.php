<?php
//session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

// Verificaciones de seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id']) || empty($_SESSION['carrito'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

// Obtener el porcentaje de IVA de la configuración
$stmt_iva = $pdo->query("SELECT valor_setting FROM configuraciones WHERE nombre_setting = 'iva_porcentaje'");
$iva_porcentaje = $stmt_iva->fetchColumn() ?: 16.00; // Por defecto 16% si no está configurado

// Recoger datos comunes
$usuario_id = $_SESSION['usuario_id'];
$direccion_envio = trim($_POST['direccion']);
$metodo_pago = $_POST['metodo_pago'] ?? '';
$carrito = $_SESSION['carrito'];
$moneda_seleccionada = $_SESSION['moneda'];

// Calcular total en USD
$ids = array_keys($carrito);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id IN ($placeholders)");
$stmt->execute($ids);
$productos_db_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$productos_db = [];
foreach ($productos_db_raw as $producto) { $productos_db[$producto['id']] = $producto; }

$subtotal_con_iva_usd = 0;
foreach ($carrito as $id => $cantidad) {
    $producto = $productos_db[$id];
    $precio_a_usar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0) 
                     ? $producto['precio_descuento'] 
                     : $producto['precio_usd'];
    $subtotal_con_iva_usd += $precio_a_usar * $cantidad;
}

// 2. Aplicar descuento sobre el total
$descuento_usd = 0;
$cupon_aplicado = $_SESSION['cupon'] ?? null;
$codigo_cupon_usado = $cupon_aplicado ? $cupon_aplicado['codigo'] : null;
if ($cupon_aplicado) {
    if ($cupon_aplicado['tipo_descuento'] == 'porcentaje') {
        $descuento_usd = ($subtotal_con_iva_usd * $cupon_aplicado['valor']) / 100;
    } else {
        $descuento_usd = $cupon_aplicado['valor'];
    }
}

// 3. Calcular el Total Final (con IVA y descuento)
$total_final_usd = $subtotal_con_iva_usd - $descuento_usd;

// 4. Desglosar el IVA del Total Final
// Formula: Base = Total / (1 + (Tasa / 100))
$base_imponible_usd = $total_final_usd / (1 + ($iva_porcentaje / 100));
$iva_total_usd = $total_final_usd - $base_imponible_usd;

try {
    $estado_pedido = '';
    
    // CASO 1: PAGO CON STRIPE
    if ($metodo_pago === 'stripe') {
        $token = $_POST['stripeToken'] ?? '';
        if (empty($token)) {
            throw new Exception("No se proporcionó un token de pago válido.");
        }
        
        $total_en_centavos = round($total_final_usd * 100);
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

        $charge = \Stripe\Charge::create([
            'amount' => $total_en_centavos,
            'currency' => 'usd',
            'description' => 'Pedido para el usuario ' . $usuario_id,
            'source' => $token,
        ]);

        if ($charge->status == 'succeeded') {
            $estado_pedido = 'Pagado';
        } else {
            throw new Exception("El pago con Stripe no fue exitoso.");
        }
    } 
    // CASO 2: PAGO MANUAL
    elseif ($metodo_pago === 'manual') {
        $estado_pedido = 'Pendiente de Pago';
    } 
    else {
        throw new Exception("Método de pago no válido.");
    }

    // --- GUARDAR EN BASE DE DATOS ---
    $pdo->beginTransaction();
    
    // Convertimos los montos finales a la moneda del cliente para guardarlos
    $total_final_cliente = $total_final_usd * $moneda_seleccionada['tasa_conversion'];
    $iva_total_cliente = $iva_total_usd * $moneda_seleccionada['tasa_conversion'];

    $sql_pedido = "INSERT INTO pedidos (usuario_id, direccion_envio, total, iva_total, estado, metodo_pago, moneda_pedido, tasa_conversion_pedido, cupon_usado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute([$usuario_id, $direccion_envio, $total_final_cliente, $iva_total_cliente, $estado_pedido, $metodo_pago, $moneda_seleccionada['codigo'], $moneda_seleccionada['tasa_conversion'], $codigo_cupon_usado]);
    $pedido_id = $pdo->lastInsertId();


    // --- NUEVO: Crear la conversación para este pedido (versión multi-admin) ---
    $sql_conversacion = "INSERT INTO conversaciones (pedido_id, cliente_id) VALUES (?, ?)";
    $stmt_conversacion = $pdo->prepare($sql_conversacion);
    $stmt_conversacion->execute([$pedido_id, $usuario_id]);
    // --- FIN DEL CÓDIGO NUEVO ---


    $sql_detalle = "INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $stmt_detalle = $pdo->prepare($sql_detalle);
    $sql_stock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
    $stmt_stock = $pdo->prepare($sql_stock);

    foreach ($carrito as $id => $cantidad) {
        $precio_unitario_guardar = (!empty($productos_db[$id]['precio_descuento']) && $productos_db[$id]['precio_descuento'] > 0)
                                   ? $productos_db[$id]['precio_descuento']
                                   : $productos_db[$id]['precio_usd'];
        $stmt_detalle->execute([$pedido_id, $id, $cantidad, $precio_unitario_guardar]);
        $stmt_stock->execute([$cantidad, $id]);
    }
    
    if ($cupon_aplicado) {
        $stmt_cupon = $pdo->prepare("UPDATE cupones SET usos_actuales = usos_actuales + 1 WHERE id = ?");
        $stmt_cupon->execute([$cupon_aplicado['id']]);
    }
    
    // --- GENERAR NOTIFICACIONES ---
    $admins_ids = obtener_admins($pdo);
    $mensaje_admin = "Nuevo pedido #" . $pedido_id . " recibido.";
    $url_admin = BASE_URL . "admin/detalle_pedido.php?id=" . $pedido_id;

    foreach ($admins_ids as $admin_id) {
        crear_notificacion($pdo, $admin_id, $mensaje_admin, $url_admin);
    }
    // --- FIN NOTIFICACIONES ---

    $pdo->commit();

    // Limpiar sesión y redirigir
    unset($_SESSION['carrito'], $_SESSION['cupon']);
    header("Location: " . BASE_URL . "gracias.php?pedido_id=" . $pedido_id);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje_carrito'] = "Error al procesar tu pedido: " . $e->getMessage();
    header('Location: ' . BASE_URL . 'checkout.php');
    exit();
}
?>