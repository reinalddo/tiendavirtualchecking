<?php
// admin/ajax_upload_media.php

require_once '../includes/config.php';
verificar_sesion_admin();

// Verificación de seguridad de admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
    exit();
}

$uploads_successful = 0;

if (isset($_FILES['media_files']) && !empty($_FILES['media_files']['name'][0])) {
    //var_dump($_FILES['media_files']);exit;
    $upload_dir = '../uploads/';
    $files = $_FILES['media_files'];
    $file_count = count($files['name']);

    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $original_name = basename($files['name'][$i]);
            // Reemplazamos los espacios con guiones
            $original_name = str_replace(' ', '-', $original_name);
            $tipo_archivo = $files['type'][$i];
            
            try {
                // Insertamos un registro para obtener un ID único
                $stmt_tmp = $pdo->prepare("INSERT INTO media_library (nombre_archivo, tipo_archivo) VALUES ('temp', ?)");
                $stmt_tmp->execute([$tipo_archivo]);
                $new_id = $pdo->lastInsertId();

                // Creamos el nombre final del archivo
                $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                $nombre_final = 'media-' . $new_id . '.' . $extension;
                $ruta_destino = $upload_dir . $nombre_final;
                
                if (move_uploaded_file($files['tmp_name'][$i], $ruta_destino)) {
                    // Si el archivo se mueve, actualizamos la BD con el nombre correcto
                    $stmt_update = $pdo->prepare("UPDATE media_library SET nombre_archivo = ? WHERE id = ?");
                    $stmt_update->execute([$nombre_final, $new_id]);
                    $uploads_successful++;
                } else {
                    // Si falla, borramos el registro temporal
                    $pdo->prepare("DELETE FROM media_library WHERE id = ?")->execute([$new_id]);
                }
            } catch (PDOException $e) {
                // No hacemos nada aquí para que el bucle continúe si hay un error de BD
            }
        }
    }
}

// Devolvemos una respuesta JSON
header('Content-Type: application/json');
echo json_encode(['success' => true, 'uploaded_count' => $uploads_successful]);
?>