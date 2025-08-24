<?php
// admin/ajax_associate_media.php
session_start();
require_once '../includes/db_connection.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit();
}

$producto_id = $_POST['producto_id'] ?? 0;
$filenames = $_POST['filenames'] ?? [];

if ($producto_id > 0 && !empty($filenames) && is_array($filenames)) {
    try {
        $upload_dir = '../uploads/';
        $sql = "INSERT INTO producto_galeria (producto_id, tipo, url) VALUES (?, 'imagen', ?)";
        $stmt = $pdo->prepare($sql);

        foreach ($filenames as $original_filename) {
            if (!empty($original_filename) && file_exists($upload_dir . $original_filename)) {
                
                // 1. Creamos un nombre de archivo nuevo y único
                $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
                $base_name = pathinfo($original_filename, PATHINFO_FILENAME);
                // Nuevo nombre: nombreoriginal-IDproducto-IDunico.extension
                $new_filename = $base_name . '-' . $producto_id . '-' . uniqid() . '.' . $extension;
                
                // 2. Hacemos una copia física del archivo
                if (copy($upload_dir . $original_filename, $upload_dir . $new_filename)) {
                    // 3. Insertamos el registro en la galería con el NUEVO nombre de archivo
                    $stmt->execute([$producto_id, $new_filename]);
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Imágenes asociadas correctamente.']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar las imágenes en la base de datos.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos.']);
}
?>