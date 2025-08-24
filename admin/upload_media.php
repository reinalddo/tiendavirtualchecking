<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connection.php';

// Verificación de seguridad de admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado.');
}

if (isset($_FILES['media_files'])) {
    $upload_dir = '../uploads/';
    $files = $_FILES['media_files'];
    $file_count = count($files['name']);
    $uploads_successful = 0;

    // Preparamos la consulta una sola vez
    $stmt = $pdo->prepare("INSERT INTO media_library (nombre_archivo, tipo_archivo) VALUES (?, ?)");

    // Iteramos sobre cada archivo subido
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $original_name = basename($files['name'][$i]);
            $tipo_archivo = $files['type'][$i];

            // 1. Insertamos un registro temporal para obtener un ID
            $stmt_tmp = $pdo->prepare("INSERT INTO media_library (nombre_archivo, tipo_archivo) VALUES ('temp', ?)");
            $stmt_tmp->execute([$tipo_archivo]);
            $new_id = $pdo->lastInsertId();

            // 2. Creamos el nombre final del archivo usando el ID
            $extension = pathinfo($original_name, PATHINFO_EXTENSION);
            $nombre_final = 'media-' . $new_id . '.' . $extension;
            $ruta_destino = $upload_dir . $nombre_final;
            
            // 3. Movemos el archivo
            if (move_uploaded_file($files['tmp_name'][$i], $ruta_destino)) {
                // 4. Actualizamos el registro con el nombre final
                $stmt_update = $pdo->prepare("UPDATE media_library SET nombre_archivo = ? WHERE id = ?");
                $stmt_update->execute([$nombre_final, $new_id]);
                $uploads_successful++;
            } else {
                // Si falla, borramos el registro temporal
                $pdo->prepare("DELETE FROM media_library WHERE id = ?")->execute([$new_id]);
            }
        }
    }

    if ($uploads_successful > 0) {
        $_SESSION['mensaje_carrito'] = "¡Se subieron " . $uploads_successful . " archivo(s) exitosamente!";
    } else {
        $_SESSION['mensaje_carrito'] = 'Error: No se pudo subir ningún archivo.';
    }

} else {
    $_SESSION['mensaje_carrito'] = 'Error: No se recibieron archivos.';
}

header('Location: ' . BASE_URL . 'admin/gestionar_media.php');
exit();
?>