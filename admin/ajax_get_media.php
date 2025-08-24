<?php
// /admin/ajax_get_media.php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connection.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit();
}

// 1. Obtenemos todos los registros de la base de datos
$media_items_raw = $pdo->query("SELECT id, nombre_archivo, alt_text FROM media_library WHERE tipo_archivo LIKE 'image/%' ORDER BY fecha_subida DESC")->fetchAll(PDO::FETCH_ASSOC);

$media_items_filtrados = [];
$upload_dir = '../uploads/';

// 2. Filtramos la lista, quedándonos solo con los que existen físicamente
foreach ($media_items_raw as $item) {
    // Comprobamos que el nombre del archivo no esté vacío y que el archivo exista en el servidor
    if (!empty($item['nombre_archivo']) && is_file($upload_dir . $item['nombre_archivo'])) {
        $media_items_filtrados[] = $item;
    }
}

// 3. Devolvemos la lista limpia en formato JSON
header('Content-Type: application/json');
echo json_encode($media_items_filtrados);
?>