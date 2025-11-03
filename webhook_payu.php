<?php
// webhook_payu.php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

// Obtener ApiKey de la configuración
$stmt_config = $pdo->query("SELECT valor_setting FROM configuraciones WHERE nombre_setting = 'payu_api_key'");
$api_key = $stmt_config->fetchColumn();

// Leer los datos que envía PayU
$reference_sale = $_POST['reference_sale'] ?? '';
$value = $_POST['value'] ?? '';
$currency = $_POST['currency'] ?? '';
$state_pol = $_POST['state_pol'] ?? '';
$sign = $_POST['sign'] ?? '';

// Extraemos el ID de nuestro pedido
$reference_parts = explode('_', $reference_sale);
$pedido_id = $reference_parts[1] ?? 0;

// 1. Validar la firma de seguridad (CRÍTICO)
$new_value = number_format($value, 1, '.', ''); // Formatear el valor como lo espera PayU para la firma
$signature_to_check = md5($api_key . "~" . $_POST['merchant_id'] . "~" . $reference_sale . "~" . $new_value . "~" . $currency . "~" . $state_pol);

if (strtoupper($sign) === strtoupper($signature_to_check)) {
    // La firma es válida, podemos confiar en la notificación
    
    // 2. Comprobar el estado de la transacción
    if ($state_pol == 4) { // 4 = Transacción Aprobada
        $nuevo_estado_pedido = 'Pagado';
    } elseif ($state_pol == 6) { // 6 = Transacción Rechazada
        $nuevo_estado_pedido = 'Cancelado';
    } else {
        $nuevo_estado_pedido = 'Pendiente de Pago'; // Otro estado
    }
    
    // 3. Actualizar el pedido en la base de datos
    if ($pedido_id > 0) {
        $stmt_update = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt_update->execute([$nuevo_estado_pedido, $pedido_id]);

        // 4. (Opcional) Notificar al cliente si fue aprobado
        if ($nuevo_estado_pedido === 'Pagado') {
            $stmt_cliente = $pdo->prepare("SELECT usuario_id FROM pedidos WHERE id = ?");
            $stmt_cliente->execute([$pedido_id]);
            $cliente_id = $stmt_cliente->fetchColumn();
            generar_accesos_descarga($pdo, $pedido_id, $config);
            if ($cliente_id) {
                crear_notificacion($pdo, $cliente_id, "Tu pago para el pedido #{$pedido_id} fue aprobado por PayU.", BASE_URL . "perfil.php");
            }
        }
    }
}
// PayU no espera ninguna respuesta en el cuerpo, solo un código 200 OK.
http_response_code(200);
?>