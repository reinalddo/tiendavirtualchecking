<?php
// ajax_get_nuevos_mensajes.php
//session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

header('Content-Type: application/json');

// Verificación de seguridad básica
if (!isset($_SESSION['usuario_id']) || empty($_GET['conversacion_id'])) {
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

$conversacion_id = (int)$_GET['conversacion_id'];
$ultimo_id_mostrado = (int)$_GET['ultimo_id'];
$usuario_actual_id = $_SESSION['usuario_id'];

// Buscamos mensajes nuevos con un ID mayor al último que se mostró
$stmt = $pdo->prepare(
    "SELECT m.id, m.mensaje, m.remitente_id, m.fecha_envio, m.archivo_adjunto, m.nombre_original_adjunto
     FROM mensajes m
     WHERE m.conversacion_id = ? AND m.id > ? 
     ORDER BY m.fecha_envio ASC"
);
$stmt->execute([$conversacion_id, $ultimo_id_mostrado]);
$nuevos_mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($nuevos_mensajes);
?>