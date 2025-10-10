<?php
// guardar_resena.php

// 1. Cargamos la configuración (esto ya inicia la sesión y conecta a la BD)
require_once 'includes/config.php';

// 2. Verificaciones de seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST' 
    || !isset($_SESSION['usuario_id']) 
    || empty($_POST['producto_id'])
    || empty($_POST['calificacion'])) {
    
    header('Location: ' . BASE_URL);
    exit();
}

$producto_id = $_POST['producto_id'];
$usuario_id = $_SESSION['usuario_id'];
$calificacion = (int)$_POST['calificacion'];
$comentario = trim($_POST['comentario'] ?? '');

// 3. Insertar la reseña
$stmt = $pdo->prepare("INSERT INTO resenas (producto_id, usuario_id, calificacion, comentario) VALUES (?, ?, ?, ?)");
$stmt->execute([$producto_id, $usuario_id, $calificacion, $comentario]);

// 4. Obtenemos el slug del producto para la redirección
$stmt_slug = $pdo->prepare("SELECT slug FROM productos WHERE id = ?");
$stmt_slug->execute([$producto_id]);
$producto_slug = $stmt_slug->fetchColumn();

// 5. Construimos la URL amigable y redirigimos
if ($producto_slug) {
    $_SESSION['mensaje_carrito'] = '¡Gracias por tu reseña!';
    header('Location: ' . BASE_URL . 'producto/' . $producto_slug);
} else {
    // Si por alguna razón no se encuentra el slug, redirigimos al perfil
    header('Location: ' . BASE_URL . 'perfil');
}
exit();
?>