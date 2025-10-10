<?php
// admin/eliminar_producto.php
require_once '../includes/config.php';
verificar_sesion_admin();

$producto_id = $_POST['producto_id'];

// (Opcional) Lógica para eliminar imágenes asociadas de la galería y la biblioteca

// Eliminar el producto de la base de datos
$stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
$stmt->execute([$producto_id]);

$_SESSION['mensaje_carrito'] = 'Producto eliminado exitosamente.';
header('Location: ' . BASE_URL . 'panel/gestionar_productos');
exit();
?>