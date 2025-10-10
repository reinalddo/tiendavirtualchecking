<?php
// ajax_eliminar_notificacion.php
require_once 'includes/config.php'; // Usamos config.php que ya inicia la sesión y conecta a la BD

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit();
}

$notificacion_id = (int)$_POST['id'];
$usuario_id = $_SESSION['usuario_id'];

$sql = "DELETE FROM notificaciones WHERE id = ? AND usuario_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$notificacion_id, $usuario_id]);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>