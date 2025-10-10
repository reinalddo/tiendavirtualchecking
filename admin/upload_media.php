<?php
// admin/upload_media.php

require_once '../includes/config.php';
verificar_sesion_admin();

if (isset($_FILES['media_files']) && !empty($_FILES['media_files']['name'][0])) {
    $upload_dir = '../uploads/';
    $files = $_FILES['media_files'];
    $file_count = count($files['name']);
    $uploads_successful = 0;

    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $original_name = basename($files['name'][$i]);
            // Reemplazamos los espacios con guiones para asegurar un nombre de archivo válido
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
                // Manejar errores de la base de datos
                $_SESSION['mensaje_carrito'] = 'Error de base de datos: ' . $e->getMessage();
                header('Location: ' . BASE_URL . 'panel/gestionar_media');
                exit();
            }
        }
    }
    
    if ($uploads_successful > 0) {
        $_SESSION['mensaje_carrito'] = "¡Se subieron " . $uploads_successful . " archivo(s) exitosamente!";
    } else {
        $_SESSION['mensaje_carrito'] = 'Error: No se pudo subir ningún archivo válido. O también esta imagen ya se ha subido.';
    }

} else {
    $_SESSION['mensaje_carrito'] = 'Error: No se recibieron archivos o no se seleccionó ninguno.';
}

header('Location: ' . BASE_URL . 'panel/gestionar_media');
exit();
?>