<?php
// admin/ajax_update_product_category.php
session_start();
require_once '../includes/db_connection.php';
// ... (Verificación de seguridad de admin) ...

$producto_id = $_POST['producto_id'] ?? 0;
$categoria_id = $_POST['categoria_id'] ?? 0;
$checked = $_POST['checked'] ?? 'false';

if ($producto_id > 0 && $categoria_id > 0) {
    if ($checked === 'true') {
        // Añadir asociación
        $stmt = $pdo->prepare("INSERT IGNORE INTO producto_categorias (producto_id, categoria_id) VALUES (?, ?)");
        $stmt->execute([$producto_id, $categoria_id]);
    } else {
        // Quitar asociación
        $stmt = $pdo->prepare("DELETE FROM producto_categorias WHERE producto_id = ? AND categoria_id = ?");
        $stmt->execute([$producto_id, $categoria_id]);
    }
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
}
?>