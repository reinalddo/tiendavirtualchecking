<?php
// procesar_solicitud_restablecimiento.php
require_once 'includes/config.php'; // Incluye $pdo y helpers.php

use PHPMailer\PHPMailer\PHPMailer; // Necesario si no está ya en helpers

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['email'])) {
    header('Location: ' . BASE_URL . 'solicitar-restablecimiento');
    exit();
}

$email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);

if (!$email) {
    $_SESSION['mensaje_restablecimiento'] = 'Error: Por favor, introduce un correo electrónico válido.';
    header('Location: ' . BASE_URL . 'solicitar-restablecimiento');
    exit();
}

try {
    // 1. Verificar si el email existe
    $stmt_user = $pdo->prepare("SELECT id, nombre_pila FROM usuarios WHERE email = ?");
    $stmt_user->execute([$email]);
    $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        // Por seguridad, no revelamos si el email existe o no
        $_SESSION['mensaje_restablecimiento'] = 'Si tu correo está registrado, recibirás un enlace para restablecer tu contraseña.';
        header('Location: ' . BASE_URL . 'solicitar-restablecimiento');
        exit();
    }

    // 2. Generar tokens seguros
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32)); // Token secreto que irá en el email
    $hashed_token = hash('sha256', $validator); // Token hasheado para la BD
    $expires_at = new DateTime('+1 hour'); // El token expira en 1 hora

    // 3. Eliminar tokens antiguos para este email (opcional pero recomendado)
    $stmt_delete = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt_delete->execute([$email]);

    // 4. Guardar el nuevo token en la BD
    $stmt_insert = $pdo->prepare("INSERT INTO password_resets (email, selector, hashed_token, expires_at) VALUES (?, ?, ?, ?)");
    $stmt_insert->execute([$email, $selector, $hashed_token, $expires_at->format('Y-m-d H:i:s')]);

    // 5. Construir el enlace de restablecimiento
    // Usamos http_build_query para construir la URL de forma segura
    $reset_url = ABSOLUTE_URL . 'restablecer-contrasena?' . http_build_query([
        'selector' => $selector,
        'validator' => $validator // Enviamos el token secreto en la URL
    ]);

    // 6. Enviar el correo
    $asunto = "Restablece tu contraseña en " . ($config['tienda_nombre'] ?? 'Mi Tienda Web');
    $mensaje_html = "<p>Hola " . htmlspecialchars($usuario['nombre_pila']) . ",</p>";
    $mensaje_html .= "<p>Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el siguiente botón para continuar:</p>";
    $mensaje_html .= "<p>Si no solicitaste esto, puedes ignorar este correo.</p>";

    // Usamos la función de plantilla de correo
    $cuerpo_email = generar_plantilla_email(
        "Restablecer Contraseña",
        $mensaje_html,
        "Restablecer Mi Contraseña", // Texto del botón
        $reset_url,                   // URL del botón
        $config                       // Configuración global
    );

    // Intentamos enviar el correo usando la función helper
    if (enviar_email($pdo, $email, $usuario['nombre_pila'], $asunto, $cuerpo_email, $config)) {
        $_SESSION['mensaje_restablecimiento'] = 'Si tu correo está registrado, recibirás un enlace para restablecer tu contraseña.';
    } else {
        $_SESSION['mensaje_restablecimiento'] = 'Error: No se pudo enviar el correo de restablecimiento. Intenta más tarde.';
    }

} catch (Exception $e) {
    error_log("Error en solicitud restablecimiento: " . $e->getMessage());
    $_SESSION['mensaje_restablecimiento'] = 'Error: Ocurrió un problema inesperado. Intenta más tarde.';
}

header('Location: ' . BASE_URL . 'solicitar-restablecimiento');
exit();

?>