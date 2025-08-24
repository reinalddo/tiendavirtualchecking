<?php
// guardar_pregunta.php
session_start();
require_once 'includes/db_connection.php';

// Verificaciones de seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST' 
    || !isset($_SESSION['usuario_id']) 
    || empty($_POST['pregunta']) 
    || empty($_POST['producto_id'])) {
    
    header('Location: index.php');
    exit();
}

$pregunta = trim($_POST['pregunta']);
$producto_id = $_POST['producto_id'];
$usuario_id = $_SESSION['usuario_id'];

// Insertar la pregunta en la base de datos
$stmt = $pdo->prepare("INSERT INTO preguntas_respuestas (producto_id, usuario_id, pregunta) VALUES (?, ?, ?)");
$stmt->execute([$producto_id, $usuario_id, $pregunta]);

// Guardar un mensaje de éxito y redirigir de vuelta al producto
$_SESSION['mensaje_carrito'] = '¡Tu pregunta ha sido enviada!';
header('Location: producto_detalle.php?id=' . $producto_id);
exit();
?>