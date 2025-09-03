<?php
// procesar_pedido.php (Versión Completa y Actualizada con Mercado Pago)
session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

// 1. OBTENER LA CONFIGURACIÓN COMPLETA DESDE LA BASE DE DATOS
$stmt_config = $pdo->query("SELECT nombre_setting, valor_setting FROM configuraciones");
$config_list = $stmt_config->fetchAll(PDO::FETCH_ASSOC);
$config = [];
foreach ($config_list as $setting) {
    $config[$setting['nombre_setting']] = $setting['valor_setting'];
}

// 2. VERIFICACIONES DE SEGURIDAD
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id']) || empty($_SESSION['carrito'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

// 3. DATOS INICIALES DEL PEDIDO
$usuario_id = $_SESSION['usuario_id'];
$carrito = $_SESSION['carrito'];
$metodo_pago = $_POST['metodo_pago'] ?? '';
$direccion_envio = trim($_POST['direccion'] ?? '');
$estado_pedido = 'Pendiente de Pago'; // Todos los pedidos empiezan como pendientes

try {
    // --- 4. CÁLCULO DE TOTALES ---
    $subtotal_usd = 0;
    $ids = array_keys($carrito);
    if (empty($ids)) {
        throw new Exception("El carrito está vacío.");
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt_productos = $pdo->prepare("SELECT * FROM productos WHERE id IN ($placeholders)");
    $stmt_productos->execute($ids);
    $productos_db_raw = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    $productos_db = [];
    foreach($productos_db_raw as $p) { $productos_db[$p['id']] = $p; }

    foreach ($carrito as $id => $cantidad) {
        $producto = $productos_db[$id];
        $precio_a_usar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0) 
                         ? $producto['precio_descuento'] 
                         : $producto['precio_usd'];
        $subtotal_usd += $precio_a_usar * $cantidad;
    }
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
    $total_final_usd = $subtotal_usd;

    // --- 5. MANEJAR LA PASARELA DE PAGO ---
    switch ($metodo_pago) {
        case 'manual':
            if (empty($config['pago_manual_activo'])) throw new Exception("Método de pago no disponible.");
            // La lógica para guardar el pedido se hará más abajo
            break;

        case 'stripe':
            if (empty($config['stripe_activo'])) throw new Exception("Método de pago no disponible.");
            
            \Stripe\Stripe::setApiKey($config['stripe_secret_key']);
            $token = $_POST['stripeToken'] ?? '';
            if (empty($token)) throw new Exception("Token de pago no válido.");

            $charge = \Stripe\Charge::create([
                'amount' => round($total_final_usd * 100),
                'currency' => 'usd',
                'description' => 'Pedido para el usuario ' . $usuario_id,
                'source' => $token,
            ]);

            if ($charge->status == 'succeeded') {
                $estado_pedido = 'Pagado'; // El estado cambia a Pagado
            } else {
                throw new Exception("El pago con Stripe no fue exitoso.");
            }
            break;

        case 'payu':
            if (empty($config['payu_activo'])) throw new Exception("Método de pago no disponible.");
            
            // Configurar SDK de PayU
            OpenPayU_Configuration::setApiKey($config['payu_api_key']);
            OpenPayU_Configuration::setApiLogin("pRRXKOl8ikMmt9u");
            OpenPayU_Configuration::setMerchantId($config['payu_merchant_id']);
            OpenPayU_Configuration::setIsTest(!empty($config['payu_test_mode']));
            OpenPayU_Configuration::setLanguage('es');

            $referenceCode = "PEDIDO_" . $pedido_id . "_" . time();
            $signature = md5($config['payu_api_key'] . "~" . $config['payu_merchant_id'] . "~" . $referenceCode . "~" . $total_final_usd . "~USD");
            $payu_url = OpenPayU_Configuration::getWebCheckoutUrl();

            echo '<body onload="document.forms[0].submit()">
                    <p>Redirigiendo a PayU para completar el pago de forma segura...</p>
                    <form action="' . $payu_url . '" method="post">
                        <input name="merchantId" type="hidden" value="' . htmlspecialchars($config['payu_merchant_id']) . '">
                        <input name="accountId" type="hidden" value="' . htmlspecialchars($config['payu_account_id']) . '">
                        <input name="description" type="hidden" value="Compra en Mi Tienda - Pedido #' . $pedido_id . '">
                        <input name="referenceCode" type="hidden" value="' . htmlspecialchars($referenceCode) . '">
                        <input name="amount" type="hidden" value="' . htmlspecialchars($total_final_usd) . '">
                        <input name="currency" type="hidden" value="USD">
                        <input name="signature" type="hidden" value="' . htmlspecialchars($signature) . '">
                        <input name="test" type="hidden" value="' . ($config['payu_test_mode'] ? '1' : '0') . '">
                        <input name="buyerEmail" type="hidden" value="' . htmlspecialchars($email_cliente) . '">
                        <input name="responseUrl" type="hidden" value="' . BASE_URL . 'respuesta_payu.php">
                        <input name="confirmationUrl" type="hidden" value="' . BASE_URL . 'webhook_payu.php">
                    </form>
                  </body>';
            exit();
            break;
        
        default:
            throw new Exception("Por favor, selecciona un método de pago.");
    }

    // --- 6. GUARDAR PEDIDO (SOLO PARA STRIPE Y MANUAL) ---
    // Mercado Pago ya guardó su pedido y redirigió, por lo que no llegará a esta parte.
    $pdo->beginTransaction();
    $sql_pedido = "INSERT INTO pedidos (usuario_id, direccion_envio, total, estado, metodo_pago) VALUES (?, ?, ?, ?, ?)";
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute([$usuario_id, $direccion_envio, $total_final_usd, $estado_pedido, $metodo_pago]);
    $pedido_id = $pdo->lastInsertId();
    
    $sql_conversacion = "INSERT INTO conversaciones (pedido_id, cliente_id) VALUES (?, ?)";
    $stmt_conversacion = $pdo->prepare($sql_conversacion);
    $stmt_conversacion->execute([$pedido_id, $usuario_id]);

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

    unset($_SESSION['carrito'], $_SESSION['cupon']);
    header("Location: " . BASE_URL . "gracias.php?pedido_id=" . $pedido_id);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['mensaje_carrito'] = "Error al procesar tu pedido: " . $e->getMessage();
    header('Location: ' . BASE_URL . 'checkout.php');
    exit();
}
?>