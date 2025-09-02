<?php
// enviar_mensaje.php
//session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

// ... (Verificaciones de seguridad) ...
$conversacion_id = (int)$_POST['conversacion_id'];
$pedido_id = (int)$_POST['pedido_id'];
$remitente_id = $_SESSION['usuario_id'];
$mensaje = trim($_POST['mensaje']);
$archivo_adjunto_nombre = null;
$nombre_original = null;

// Lógica de subida de archivo
if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['archivo_adjunto'];
    $upload_dir = 'uploads/adjuntos/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

    $nombre_original = basename($file['name']);
    $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
    $archivo_adjunto_nombre = uniqid('msg_' . $conversacion_id . '_', true) . '.' . $extension;

    move_uploaded_file($file['tmp_name'], $upload_dir . $archivo_adjunto_nombre);
}

// El mensaje o el archivo deben existir
if (!empty($mensaje) || $archivo_adjunto_nombre) {
    $sql = "INSERT INTO mensajes (conversacion_id, remitente_id, mensaje, archivo_adjunto, nombre_original_adjunto) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $pdo->prepare($sql);
    $stmt_insert->execute([$conversacion_id, $remitente_id, $mensaje, $archivo_adjunto_nombre, $nombre_original]);
}

header('Location: ' . BASE_URL . 'mensajes_pedido.php?pedido_id=' . $pedido_id);
exit();
?>