<?php
// admin/activar_producto.php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connection.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || empty($_POST['producto_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$producto_id = $_POST['producto_id'];

// Actualizamos el estado del producto a activo (es_activo = 1)
$stmt = $pdo->prepare("UPDATE productos SET es_activo = 1 WHERE id = ?");
$stmt->execute([$producto_id]);

$_SESSION['mensaje_carrito'] = 'Producto activado exitosamente. Vuelve a ser visible para los clientes.';
header('Location: ' . BASE_URL . 'admin/gestionar_productos.php');
exit();
?>