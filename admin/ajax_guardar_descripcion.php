<?php
// admin/ajax_guardar_descripcion.php
require_once '../includes/config.php';
verificar_sesion_admin();

// Verificar si el usuario es administrador (debes implementar tu lógica de autenticación)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

if (isset($_POST['producto_id']) && isset($_POST['descripcion_html'])) {
    $producto_id = $_POST['producto_id'];
    $descripcion_html = $_POST['descripcion_html'];

    $stmt = $pdo->prepare("UPDATE productos SET descripcion_html = :descripcion WHERE id = :id");
    $stmt->bindParam(':descripcion', $descripcion_html);
    $stmt->bindParam(':id', $producto_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la descripción.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos.']);
}
?>