<?php
// admin/eliminar_imagen_galeria.php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/config.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || !isset($_GET['id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$item_id = $_GET['id'];
$producto_id = $_GET['producto_id']; // Para redirigir de vuelta

// Opcional pero recomendado: eliminar el archivo físico si es una imagen
$stmt = $pdo->prepare("SELECT * FROM producto_galeria WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if ($item && $item['tipo'] == 'imagen') {
    $ruta_archivo = '../uploads/' . $item['url'];
    if (file_exists($ruta_archivo)) {
        unlink($ruta_archivo);
    }
}

// Eliminar el registro de la base de datos
$stmt_delete = $pdo->prepare("DELETE FROM producto_galeria WHERE id = ?");
$stmt_delete->execute([$item_id]);

// Redirigir de vuelta al formulario de edición del producto
header("Location: formulario_producto.php?id=" . $producto_id);
exit();
?>