<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

// Verificaciones de seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id']) || empty($_SESSION['carrito'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

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

// 1. Calcular el subtotal en USD usando el precio con descuento si aplica
$total_usd = 0;
foreach ($carrito as $id => $cantidad) {
    $producto = $productos_db[$id];
    $precio_a_usar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0) 
                     ? $producto['precio_descuento'] 
                     : $producto['precio_usd'];
    $total_usd += $precio_a_usar * $cantidad;
}

// 2. Aplicar el cupón de descuento sobre el subtotal
$descuento_usd = 0;
$cupon_aplicado = $_SESSION['cupon'] ?? null;
$codigo_cupon_usado = $cupon_aplicado ? $cupon_aplicado['codigo'] : null;
if ($cupon_aplicado) {
    if ($cupon_aplicado['tipo_descuento'] == 'porcentaje') {
        $descuento_usd = ($total_usd * $cupon_aplicado['valor']) / 100;
    } else {
        $descuento_usd = $cupon_aplicado['valor'];
    }
}
$total_final_usd = $total_usd - $descuento_usd;

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
    
    $total_en_moneda_cliente = $total_final_usd * $moneda_seleccionada['tasa_conversion'];

    $sql_pedido = "INSERT INTO pedidos (usuario_id, direccion_envio, total, estado, metodo_pago, moneda_pedido, tasa_conversion_pedido, cupon_usado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute([$usuario_id, $direccion_envio, $total_en_moneda_cliente, $estado_pedido, $metodo_pago, $moneda_seleccionada['codigo'], $moneda_seleccionada['tasa_conversion']], $codigo_cupon_usado);
    $pedido_id = $pdo->lastInsertId();

    // =============================================================
    // === ESTA ES LA SECCIÓN CRÍTICA QUE GUARDA LOS DETALLES ===
    // =============================================================
    $sql_detalle = "INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $stmt_detalle = $pdo->prepare($sql_detalle);
    $sql_stock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
    $stmt_stock = $pdo->prepare($sql_stock);

    foreach ($carrito as $id => $cantidad) {
        // Guardamos el precio unitario en la moneda base (USD)
        $stmt_detalle->execute([$pedido_id, $id, $cantidad, $productos_db[$id]['precio_usd']]);
        $stmt_stock->execute([$cantidad, $id]);

        $detalle_id = $pdo->lastInsertId(); // Obtenemos el ID del detalle que acabamos de crear
        // Buscamos la imagen principal y la guardamos en nuestra nueva tabla
        $stmt_img = $pdo->prepare("SELECT url FROM producto_galeria WHERE producto_id = ? AND tipo = 'imagen' ORDER BY id ASC LIMIT 1");
        $stmt_img->execute([$id]);
        $imagen_principal = $stmt_img->fetchColumn();

        if ($imagen_principal) {
            $stmt_copy_img = $pdo->prepare("INSERT INTO pedido_imagenes (pedido_detalle_id, imagen_url_copia) VALUES (?, ?)");
            $stmt_copy_img->execute([$detalle_id, $imagen_principal]);
        }

    }
    // =============================================================
    // Si se usó un cupón, actualizamos su contador de usos
    if ($cupon_aplicado) {
        $stmt_cupon = $pdo->prepare("UPDATE cupones SET usos_actuales = usos_actuales + 1 WHERE id = ?");
        $stmt_cupon->execute([$cupon_aplicado['id']]);
    }

    $pdo->commit();

    // Limpiar sesión y redirigir
    unset($_SESSION['carrito'], $_SESSION['moneda_carrito'], $_SESSION['cupon']);
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