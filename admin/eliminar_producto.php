<?php
// admin/eliminar_producto.php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connection.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || empty($_POST['producto_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$producto_id = $_POST['producto_id'];

// (Opcional) Lógica para eliminar imágenes asociadas de la galería y la biblioteca

// Eliminar el producto de la base de datos
$stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
$stmt->execute([$producto_id]);

$_SESSION['mensaje_carrito'] = 'Producto eliminado exitosamente.';
header('Location: ' . BASE_URL . 'admin/gestionar_productos.php');
exit();
?>