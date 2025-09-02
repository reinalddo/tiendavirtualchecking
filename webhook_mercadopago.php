<?php
// webhook_mercadopago.php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

// Obtener configuración
$stmt_config = $pdo->query("SELECT valor_setting FROM configuraciones WHERE nombre_setting = 'mercadopago_access_token'");
$access_token = $stmt_config->fetchColumn();

MercadoPago\SDK::setAccessToken($access_token);

// Leer la notificación
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (isset($data['type']) && $data['type'] === 'payment') {
    $payment_id = $data['data']['id'];
    $payment = MercadoPago\Payment::find_by_id($payment_id);

    if ($payment && $payment->status == 'approved') {
        $pedido_id = $payment->external_reference;

        // Actualizar el estado del pedido a "Pagado"
        $stmt_update = $pdo->prepare("UPDATE pedidos SET estado = 'Pagado' WHERE id = ?");
        $stmt_update->execute([$pedido_id]);

        // Notificar al cliente
        $stmt_cliente = $pdo->prepare("SELECT usuario_id FROM pedidos WHERE id = ?");
        $stmt_cliente->execute([$pedido_id]);
        $cliente_id = $stmt_cliente->fetchColumn();

        if ($cliente_id) {
            crear_notificacion($pdo, $cliente_id, "Tu pago para el pedido #{$pedido_id} fue aprobado.", BASE_URL . "perfil.php");
        }
    }
}

http_response_code(200); // Responder a Mercado Pago que recibimos la notificación
?>