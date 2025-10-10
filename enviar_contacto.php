<?php
//session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email_cliente = trim($_POST['email']);
    $asunto = trim($_POST['asunto']);
    $mensaje = trim($_POST['mensaje']);

    // Validación
    if (empty($nombre) || !filter_var($email_cliente, FILTER_VALIDATE_EMAIL) || empty($asunto) || empty($mensaje)) {
        $_SESSION['mensaje_carrito'] = 'Error: Por favor, completa todos los campos correctamente.';
        header('Location: ' . BASE_URL . 'contacto');
        exit();
    }

    // Obtenemos el email de contacto desde la configuración de la BD
    $stmt_config = $pdo->query("SELECT valor_setting FROM configuraciones WHERE nombre_setting = 'email_contacto'");
    $destinatario = $stmt_config->fetchColumn();

    // Creamos el cuerpo del mensaje que recibirá el administrador
    $cuerpo_email_admin = "
        <h3>Nuevo Mensaje desde el Formulario de Contacto</h3>
        <p><strong>Nombre:</strong> " . htmlspecialchars($nombre) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($email_cliente) . "</p>
        <hr>
        <h4>Mensaje:</h4>
        <p>" . nl2br(htmlspecialchars($mensaje)) . "</p>
    ";

    // Usamos nuestra nueva función para enviar el correo
    if (enviar_email($pdo, $destinatario, 'Administrador', 'Nuevo Mensaje de Contacto: ' . $asunto, $cuerpo_email_admin)) {
        $_SESSION['mensaje_carrito'] = '¡Gracias! Tu mensaje ha sido enviado exitosamente.';
    } else {
        $_SESSION['mensaje_carrito'] = 'Error: No se pudo enviar el mensaje. Intenta más tarde.';
    }

    header('Location: ' . BASE_URL . 'contacto');
    exit();
}