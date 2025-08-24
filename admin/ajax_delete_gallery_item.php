<?php
// admin/ajax_delete_gallery_item.php
session_start();
require_once '../includes/db_connection.php';
// ... (Verificación de seguridad de admin) ...

$gallery_id = $_POST['gallery_id'] ?? 0;

if ($gallery_id > 0) {
    // Buscamos el nombre del archivo para borrarlo del servidor
    $stmt_find = $pdo->prepare("SELECT url FROM producto_galeria WHERE id = ?");
    $stmt_find->execute([$gallery_id]);
    $filename = $stmt_find->fetchColumn();

    if ($filename) {
        $file_path = '../uploads/' . $filename;
        if (file_exists($file_path)) {
            unlink($file_path); // Borra el archivo físico
        }
    }

    // Eliminamos el registro de la tabla producto_galeria
    $stmt_delete = $pdo->prepare("DELETE FROM producto_galeria WHERE id = ?");
    $stmt_delete->execute([$gallery_id]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID no válido.']);
}
?>