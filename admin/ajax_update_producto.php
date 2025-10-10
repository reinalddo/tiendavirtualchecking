<?php
// admin/ajax_update_producto.php
require_once '../includes/config.php';
verificar_sesion_admin();

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit();
}

$id = $_POST['id'] ?? 0;
$field = $_POST['field'] ?? '';
$value = trim($_POST['value'] ?? '');

// Lista blanca de campos permitidos para evitar inyección SQL en nombres de columna
$allowed_fields = ['nombre', 'descripcion_html', 'precio_usd', 'stock'];

if ($id > 0 && in_array($field, $allowed_fields)) {
    try {
        $sql = "UPDATE productos SET $field = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$value, $id]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar en la base de datos.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos.']);
}
?>