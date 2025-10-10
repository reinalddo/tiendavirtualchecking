<?php
// admin/ajax_save_product_categories.php
require_once '../includes/config.php';
verificar_sesion_admin();

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit();
}

$producto_id = $_POST['producto_id'] ?? 0;
$categoria_ids = $_POST['categoria_ids'] ?? [];

if ($producto_id > 0) {
    try {
        $pdo->beginTransaction();

        // 1. Eliminar todas las asociaciones de categorías existentes para este producto
        $stmt_delete = $pdo->prepare("DELETE FROM producto_categorias WHERE producto_id = ?");
        $stmt_delete->execute([$producto_id]);

        // 2. Insertar las nuevas asociaciones si se seleccionó alguna
        if (!empty($categoria_ids)) {
            $sql_insert = "INSERT INTO producto_categorias (producto_id, categoria_id) VALUES (?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            foreach ($categoria_ids as $categoria_id) {
                $stmt_insert->execute([$producto_id, (int)$categoria_id]);
            }
        }

        $pdo->commit();

        // Opcional: Devolver los nombres de las nuevas categorías para actualizar la UI
        $nombres_categorias = 'Sin categoría';
        if (!empty($categoria_ids)) {
            $placeholders = implode(',', array_fill(0, count($categoria_ids), '?'));
            $stmt_nombres = $pdo->prepare("SELECT GROUP_CONCAT(nombre SEPARATOR ', ') FROM categorias WHERE id IN ($placeholders)");
            $stmt_nombres->execute($categoria_ids);
            $nombres_categorias = $stmt_nombres->fetchColumn();
        }

        echo json_encode(['success' => true, 'nombres_categorias' => $nombres_categorias]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Error en la base de datos.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID de producto no válido.']);
}
?>