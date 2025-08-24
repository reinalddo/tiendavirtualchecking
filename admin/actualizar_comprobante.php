<?php
// admin/actualizar_comprobante.php
//session_start();
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
// ... (Verificación de seguridad de admin) ...

$comprobante_id = $_GET['id'] ?? 0;
$accion = $_GET['accion'] ?? '';
$pedido_id = $_GET['pedido_id'] ?? 0;

if ($comprobante_id > 0 && $pedido_id > 0 && in_array($accion, ['aprobar', 'rechazar'])) {
    $nuevo_estado_comprobante = ($accion == 'aprobar') ? 'aprobado' : 'rechazado';
    $stmt = $pdo->prepare("UPDATE comprobantes_pago SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado_comprobante, $comprobante_id]);

    // Si se aprueba, también actualizamos el estado del pedido principal a "Pagado"
    if ($accion == 'aprobar') {
        $stmt_pedido = $pdo->prepare("UPDATE pedidos SET estado = 'Pagado' WHERE id = ?");
        $stmt_pedido->execute([$pedido_id]);
    }
}
header('Location: ' . BASE_URL . 'admin/detalle_pedido.php?id=' . $pedido_id);
exit();
?>