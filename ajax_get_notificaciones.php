<?php
// ajax_get_notificaciones.php
require_once 'includes/config.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['count' => 0, 'items' => []]);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Contar no leídas
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0");
$stmt_count->execute([$usuario_id]);
$count = $stmt_count->fetchColumn();

// Obtener las 5 más recientes
$stmt_items = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY fecha_creacion DESC");
$stmt_items->execute([$usuario_id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(['count' => $count, 'items' => $items]);
?>