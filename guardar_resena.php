<?php
// guardar_resena.php
session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

// Verificaciones de seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST' 
    || !isset($_SESSION['usuario_id']) 
    || empty($_POST['producto_id'])
    || empty($_POST['calificacion'])) {
    
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$producto_id = $_POST['producto_id'];
$usuario_id = $_SESSION['usuario_id'];
$calificacion = (int)$_POST['calificacion'];
$comentario = trim($_POST['comentario'] ?? '');

// (Aquí podrías repetir la lógica para asegurar que el usuario ha comprado el producto)

// Insertar la reseña
$stmt = $pdo->prepare("INSERT INTO resenas (producto_id, usuario_id, calificacion, comentario) VALUES (?, ?, ?, ?)");
$stmt->execute([$producto_id, $usuario_id, $calificacion, $comentario]);

$_SESSION['mensaje_carrito'] = '¡Gracias por tu reseña!';
header('Location: ' . BASE_URL . 'producto_detalle.php?id=' . $producto_id);
exit();
?>