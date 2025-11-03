<?php
// admin/actualizar_comprobante.php (Versión Corregida)

require_once '../includes/config.php';
verificar_sesion_admin();

$comprobante_id = $_GET['id'] ?? 0;
$accion = $_GET['accion'] ?? '';
$pedido_id = $_GET['pedido_id'] ?? 0;

// --- CORRECCIÓN: OBTENEMOS EL ID DEL CLIENTE ANTES DE HACER NADA ---
$stmt_cliente = $pdo->prepare("SELECT usuario_id FROM pedidos WHERE id = ?");
$stmt_cliente->execute([$pedido_id]);
$cliente_id = $stmt_cliente->fetchColumn();
// --- FIN DE LA CORRECCIÓN ---

if ($comprobante_id > 0 && $pedido_id > 0 && in_array($accion, ['aprobar', 'rechazar']) && $cliente_id) {
    $nuevo_estado_comprobante = ($accion == 'aprobar') ? 'aprobado' : 'rechazado';
    $stmt = $pdo->prepare("UPDATE comprobantes_pago SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado_comprobante, $comprobante_id]);

    $mensaje_cliente = '';
    $url_cliente = BASE_URL . "perfil.php";

    if ($accion == 'aprobar') {
        $stmt_pedido = $pdo->prepare("UPDATE pedidos SET estado = 'Pagado' WHERE id = ?");
        $stmt_pedido->execute([$pedido_id]);
        generar_accesos_descarga($pdo, $pedido_id, $config);
        $mensaje_cliente = "¡Tu pago para el pedido #" . $pedido_id . " ha sido aprobado! Pronto será enviado.";
    } else {
        $mensaje_cliente = "Tu comprobante para el pedido #" . $pedido_id . " fue rechazado. Por favor, sube uno nuevo.";
    }

    // Ahora la variable $cliente_id existe y la notificación se crea correctamente
    crear_notificacion($pdo, $cliente_id, $mensaje_cliente, $url_cliente);
}

header('Location: ' . BASE_URL . 'panel/pedido/' . $pedido_id);
exit();
?>