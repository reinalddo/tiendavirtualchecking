<?php
// admin/desactivar_producto.php
require_once '../includes/config.php';
verificar_sesion_admin();

$producto_id = $_POST['producto_id'];

// Actualizamos el estado del producto a inactivo (es_activo = 0)
$stmt = $pdo->prepare("UPDATE productos SET es_activo = 0 WHERE id = ?");
$stmt->execute([$producto_id]);

$_SESSION['mensaje_carrito'] = 'Producto desactivado exitosamente. Ya no será visible para los clientes.';
header('Location: ' . BASE_URL . 'panel/gestionar_productos');
exit();
?>