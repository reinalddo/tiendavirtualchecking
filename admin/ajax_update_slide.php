<?php
// admin/ajax_update_slide.php
session_start();
require_once '../includes/db_connection.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit();
}

$slide_id = $_POST['id'] ?? 0;
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

// Lista blanca de campos permitidos para evitar inyección SQL en nombres de columna
$allowed_fields = ['titulo', 'enlace_url', 'orden'];
if ($slide_id > 0 && in_array($field, $allowed_fields)) {
    $sql = "UPDATE hero_gallery SET $field = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$value, $slide_id]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos.']);
}
?>