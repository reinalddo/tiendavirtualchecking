<?php
// admin/ajax_update_categoria.php
session_start();
require_once '../includes/db_connection.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403); exit();
}

$id = $_POST['id'] ?? 0;
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

$allowed_fields = ['nombre', 'codigo', 'mostrar_en_inicio'];
if ($id > 0 && in_array($field, $allowed_fields)) {
    if ($field == 'mostrar_en_inicio') {
        $value = ($value == 'true') ? 1 : 0;
    }

    try {
        $sql = "UPDATE categorias SET $field = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$value, $id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['error' => 'Ese código ya está en uso.']);
        } else {
            echo json_encode(['error' => 'Error en la base de datos.']);
        }
    }
} else {
    http_response_code(400);
}
?>