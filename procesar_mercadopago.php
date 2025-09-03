<?php
// procesar_mercadopago.php
session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

// 1. OBTENER CONFIGURACIÓN
$stmt_config = $pdo->query("SELECT nombre_setting, valor_setting FROM configuraciones");
$config_list = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. VERIFICACIONES
if (empty($config['mercadopago_activo']) || !isset($_GET['pedido_id'])) {
    die('Acceso denegado.');
}

$pedido_id = (int)$_GET['pedido_id'];

// 3. OBTENER DATOS DEL PEDIDO QUE YA CREAMOS
$stmt_pedido = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt_pedido->execute([$pedido_id]);
$pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

if (!$pedido) { die('Pedido no encontrado.'); }

try {
    // 4. INICIALIZAR MERCADO PAGO SDK
    MercadoPago\SDK::setAccessToken($config['mercadopago_access_token']);

    // 5. CREAR LA PREFERENCIA DE PAGO
    $preference = new MercadoPago\Preference();
    
    // Creamos un solo ítem con el total del pedido
    $item = new MercadoPago\Item();
    $item->title = 'Compra en Mi Tienda - Pedido #' . $pedido_id;
    $item->quantity = 1;
    $item->unit_price = (float)$pedido['total']; // Usamos el total ya guardado
    $item->currency_id = 'USD'; // O 'VES' si guardas el total en Bolívares
    
    $preference->items = array($item);

    // URLs de retorno y notificación
    $preference->back_urls = array(
        "success" => BASE_URL . "gracias.php?status=success&pedido_id=" . $pedido_id,
        "failure" => BASE_URL . "checkout.php?status=failure",
        "pending" => BASE_URL . "gracias.php?status=pending&pedido_id=" . $pedido_id
    );
    $preference->auto_return = "approved";
    $preference->notification_url = BASE_URL . "webhook_mercadopago.php";
    $preference->external_reference = $pedido_id;
    
    $preference->save();

    // 6. REDIRIGIR AL CHECKOUT DE MERCADO PAGO
    if (!empty($preference->init_point)) {
        header('Location: ' . $preference->init_point);
        exit();
    } else {
        throw new Exception("No se pudo generar el enlace de pago de Mercado Pago.");
    }

} catch (Exception $e) {
    $_SESSION['mensaje_carrito'] = "Error con Mercado Pago: " . $e->getMessage();
    header('Location: ' . BASE_URL . 'checkout.php');
    exit();
}
?>