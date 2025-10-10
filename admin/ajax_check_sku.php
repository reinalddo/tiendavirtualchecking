<?php
// admin/ajax_check_sku.php
require_once '../includes/config.php';
verificar_sesion_admin();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || empty($_GET['sku'])) {
    http_response_code(403);
    exit();
}

$sku = trim($_GET['sku']);
$producto_id_actual = $_GET['current_id'] ?? 0;

// Buscamos un producto con ese SKU, excluyendo el producto que se está editando actualmente
$stmt = $pdo->prepare("SELECT id FROM productos WHERE sku = ? AND id != ?");
$stmt->execute([$sku, $producto_id_actual]);
$existe = $stmt->fetch();

header('Content-Type: application/json');
echo json_encode(['exists' => $existe !== false]);
?>