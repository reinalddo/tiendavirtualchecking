<?php
// admin/ajax_rename_media.php
session_start();
require_once '../includes/db_connection.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403); exit();
}

$media_id = $_POST['id'] ?? 0;
$alt_text = trim($_POST['alt_text'] ?? '');
$old_filename = $_POST['old_filename'] ?? '';

if ($media_id > 0 && !empty($alt_text) && !empty($old_filename)) {
    // 1. Limpiar el nuevo nombre para que sea amigable con URLs
    $extension = pathinfo($old_filename, PATHINFO_EXTENSION);
    $base_name = strtolower($alt_text);
    $base_name = preg_replace('/[^a-z0-9]+/', '-', $base_name); // Reemplaza no alfanuméricos con guiones
    $base_name = trim($base_name, '-');
    $new_filename = $base_name . '.' . $extension;

    // 2. Verificar si el nuevo nombre de archivo ya existe
    $upload_dir = '../uploads/';
    if (file_exists($upload_dir . $new_filename)) {
        // Si existe, añadimos un sufijo único
        $new_filename = $base_name . '-' . $media_id . '.' . $extension;
    }

    // 3. Renombrar el archivo físico
    if (rename($upload_dir . $old_filename, $upload_dir . $new_filename)) {
        // 4. Si se renombra con éxito, actualizar la base de datos
        $stmt = $pdo->prepare("UPDATE media_library SET nombre_archivo = ?, alt_text = ? WHERE id = ?");
        $stmt->execute([$new_filename, $alt_text, $media_id]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'new_filename' => $new_filename]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo renombrar el archivo en el servidor.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos.']);
}
?>