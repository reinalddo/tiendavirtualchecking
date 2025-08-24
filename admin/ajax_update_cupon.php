<?php
// admin/ajax_update_cupon.php
session_start();
require_once '../includes/db_connection.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit();
}

$id = $_POST['id'] ?? 0;
$field = $_POST['field'] ?? '';
$value = trim($_POST['value'] ?? '');

// Lista blanca de campos permitidos para editar
$allowed_fields = ['codigo', 'valor', 'usos_maximos', 'fecha_expiracion', 'es_activo'];

if ($id > 0 && in_array($field, $allowed_fields)) {
    // Conversiones y validaciones específicas
    if ($field === 'es_activo') {
        $value = ($value === 'true') ? 1 : 0;
    }
    if ($field === 'fecha_expiracion' && empty($value)) {
        $value = null; // Permitir que la fecha sea nula
    }

    try {
        $sql = "UPDATE cupones SET $field = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$value, $id]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        // Devolvemos un mensaje de error si el código ya existe
        if ($e->getCode() == 23000) {
            echo json_encode(['error' => 'Ese código de cupón ya está en uso.']);
        } else {
            echo json_encode(['error' => 'Error en la base de datos.']);
        }
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos.']);
}
?>