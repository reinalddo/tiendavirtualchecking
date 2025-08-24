<?php
// /admin/ajax_upload_media.php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connection.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit();
}

if (isset($_FILES['media_files'])) {
    $upload_dir = '../uploads/';
    $files = $_FILES['media_files'];
    $file_count = count($files['name']);
    $uploads_successful = 0;

    $stmt = $pdo->prepare("INSERT INTO media_library (nombre_archivo, tipo_archivo) VALUES (?, ?)");

    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $original_name = basename($files['name'][$i]);
            $tipo_archivo = $files['type'][$i];

            // Insertamos un registro temporal para obtener un ID
            $stmt_tmp = $pdo->prepare("INSERT INTO media_library (nombre_archivo, tipo_archivo) VALUES ('temp', ?)");
            $stmt_tmp->execute([$tipo_archivo]);
            $new_id = $pdo->lastInsertId();

            // Creamos el nombre final del archivo usando el ID
            $extension = pathinfo($original_name, PATHINFO_EXTENSION);
            $nombre_final = 'media-' . $new_id . '.' . $extension;
            $ruta_destino = $upload_dir . $nombre_final;
            
            if (move_uploaded_file($files['tmp_name'][$i], $ruta_destino)) {
                // Actualizamos el registro con el nombre final
                $stmt_update = $pdo->prepare("UPDATE media_library SET nombre_archivo = ? WHERE id = ?");
                $stmt_update->execute([$nombre_final, $new_id]);
                $uploads_successful++;
            } else {
                // Si falla, borramos el registro temporal
                $pdo->prepare("DELETE FROM media_library WHERE id = ?")->execute([$new_id]);
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'uploaded_count' => $uploads_successful]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibieron archivos.']);
}
?>