<?php
// admin/eliminar_slide.php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connection.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || !isset($_GET['id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$slide_id = $_GET['id'];

// 1. Buscamos el nombre del archivo en la base de datos ANTES de borrarlo
$stmt_find = $pdo->prepare("SELECT imagen_url FROM hero_gallery WHERE id = ?");
$stmt_find->execute([$slide_id]);
$slide = $stmt_find->fetch(PDO::FETCH_ASSOC);

if ($slide) {
    // 2. Eliminamos el archivo físico del servidor
    $ruta_archivo = '../uploads/hero/' . $slide['imagen_url'];
    if (file_exists($ruta_archivo)) {
        unlink($ruta_archivo);
    }
}

// 3. Eliminamos el registro de la base de datos
$stmt_delete = $pdo->prepare("DELETE FROM hero_gallery WHERE id = ?");
$stmt_delete->execute([$slide_id]);

$_SESSION['mensaje_carrito'] = 'Slide eliminado correctamente.';
header('Location: ' . BASE_URL . 'admin/gestionar_galeria_inicio.php');
exit();
?>