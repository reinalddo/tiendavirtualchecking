<?php
// admin/ajax_update_gallery_order.php
require_once '../includes/config.php';
verificar_sesion_admin();

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit();
}

$order = $_POST['order'] ?? [];

if (!empty($order) && is_array($order)) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE producto_galeria SET orden = ? WHERE id = ?");
        // Iteramos sobre el array de IDs en su nuevo orden
        foreach ($order as $position => $item_id) {
            // La posición (0, 1, 2...) será el nuevo valor de 'orden'
            $stmt->execute([$position, (int)$item_id]);
        }
        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Error en la base de datos.']);
        exit();
    }
}

http_response_code(400);
echo json_encode(['error' => 'No se recibió un orden válido.']);
?>