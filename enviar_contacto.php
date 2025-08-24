<?php
session_start();
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $asunto = trim($_POST['asunto']);
    $mensaje = trim($_POST['mensaje']);

    // Validación básica
    if (empty($nombre) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($asunto) || empty($mensaje)) {
        $_SESSION['mensaje_carrito'] = 'Error: Por favor, completa todos los campos correctamente.';
        header('Location: ' . BASE_URL . 'contacto.php');
        exit();
    }

    $destinatario = "tu-correo@ejemplo.com"; // <-- CAMBIA ESTO A TU CORREO REAL
    $asunto_email = "Nuevo Mensaje de Contacto: " . $asunto;
    $cuerpo_email = "Has recibido un nuevo mensaje desde tu tienda web.\n\n";
    $cuerpo_email .= "Nombre: " . $nombre . "\n";
    $cuerpo_email .= "Email: " . $email . "\n";
    $cuerpo_email .= "Mensaje:\n" . $mensaje . "\n";
    $cabeceras = "From: " . $email;

    // Usamos la función mail() de PHP
    if (mail($destinatario, $asunto_email, $cuerpo_email, $cabeceras)) {
        $_SESSION['mensaje_carrito'] = '¡Gracias! Tu mensaje ha sido enviado exitosamente.';
    } else {
        $_SESSION['mensaje_carrito'] = 'Error: No se pudo enviar el mensaje. Intenta más tarde.';
    }

    header('Location: ' . BASE_URL . 'contacto.php');
    exit();

} else {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}
?>