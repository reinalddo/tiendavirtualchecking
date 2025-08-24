<?php
// admin/ajax_update_media.php
session_start();
require_once '../includes/db_connection.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit();
}

$media_id = $_POST['id'] ?? 0;
$alt_text = trim($_POST['alt_text'] ?? '');

if ($media_id > 0) {
    $stmt = $pdo->prepare("UPDATE media_library SET alt_text = ? WHERE id = ?");
    $stmt->execute([$alt_text, $media_id]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos.']);
}
?>