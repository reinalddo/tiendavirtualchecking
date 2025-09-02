<?php
// admin/enviar_mensaje_admin.php (Versión Corregida)
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connection.php';

// 1. Verificación de seguridad básica (ya no comprueba si el mensaje está vacío)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$conversacion_id = (int)$_POST['conversacion_id'];
$remitente_id = $_SESSION['usuario_id'];
$mensaje = trim($_POST['mensaje']);
$archivo_adjunto_nombre = null;
$nombre_original = null;

// 2. Lógica de subida de archivo (se ejecuta siempre)
if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['archivo_adjunto'];
    $upload_dir = '../uploads/adjuntos/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

    $nombre_original = basename($file['name']);
    $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
    $archivo_adjunto_nombre = uniqid('msg_' . $conversacion_id . '_', true) . '.' . $extension;

    // Mover el archivo subido
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $archivo_adjunto_nombre)) {
        $archivo_adjunto_nombre = null; // Si falla la subida, se anula.
    }
}

// 3. SOLO SI hay un mensaje de texto O un archivo subido, se guarda en la BD
if (!empty($mensaje) || !empty($archivo_adjunto_nombre)) {
    $sql = "INSERT INTO mensajes (conversacion_id, remitente_id, mensaje, archivo_adjunto, nombre_original_adjunto) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$conversacion_id, $remitente_id, $mensaje, $archivo_adjunto_nombre, $nombre_original]);
}

// 4. Redirigir siempre de vuelta a la conversación
header('Location: ' . BASE_URL . 'admin/ver_conversacion.php?id=' . $conversacion_id);
exit();
?>