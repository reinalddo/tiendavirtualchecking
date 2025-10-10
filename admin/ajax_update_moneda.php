<?php
// admin/ajax_update_moneda.php
require_once '../includes/config.php';
verificar_sesion_admin();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403); exit();
}

$id = $_POST['id'] ?? 0;
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

$allowed_fields = ['nombre', 'codigo', 'simbolo', 'tasa_conversion', 'es_activa'];
if ($id > 0 && in_array($field, $allowed_fields)) {
    if ($field == 'es_activa') {
        $value = ($value == 'true') ? 1 : 0;
    }
    
    try {
        $sql = "UPDATE monedas SET $field = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$value, $id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error en la base de datos.']);
    }
} else {
    http_response_code(400);
}
?>