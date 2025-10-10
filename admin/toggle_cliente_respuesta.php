<?php
// admin/toggle_cliente_respuesta.php
require_once '../includes/config.php';
verificar_sesion_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    exit('Acceso denegado');
}

$conversacion_id = (int)$_POST['conversacion_id'];
$accion = $_POST['accion'];
$nuevo_estado = ($accion === 'bloquear') ? 0 : 1;

$sql = "UPDATE conversaciones SET cliente_puede_responder = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nuevo_estado, $conversacion_id]);

header('Location: ' . BASE_URL . 'admin/ver_conversacion.php?id=' . $conversacion_id);
exit();
?>