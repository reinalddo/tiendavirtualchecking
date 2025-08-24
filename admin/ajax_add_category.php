<?php
// admin/ajax_add_category.php
session_start();
require_once '../includes/db_connection.php';

// Verificación de seguridad y de que se envió un nombre
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || empty($_POST['nombre'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado o nombre vacío']);
    exit();
}

$nombre = trim($_POST['nombre']);
$codigo = trim($_POST['codigo']);

try {
    $stmt = $pdo->prepare("INSERT INTO categorias (nombre, codigo) VALUES (?, ?)");
    $stmt->execute([$nombre, $codigo]);
    $new_id = $pdo->lastInsertId();

    // Devolvemos el ID y el nombre de la nueva categoría en formato JSON
    header('Content-Type: application/json');
    echo json_encode(['id' => $new_id, 'nombre' => $nombre]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar en la base de datos: ' . $e->getMessage()]);
}
?>