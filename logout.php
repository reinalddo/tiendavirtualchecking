<?php
// logout.php

require_once 'includes/config.php';

// 1. Iniciar la sesión ANTES de cualquier otra cosa
//session_start();

// 2. Borrar el token de la base de datos si la cookie existe
if (isset($_COOKIE['remember_me'])) {
    require_once 'includes/db_connection.php';
    // Dividimos la cookie para obtener el "selector"
    list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);
    if (!empty($selector)) {
        $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE selector = ?");
        $stmt->execute([$selector]);
    }
}

// 3. Limpiar la cookie del navegador
setcookie('remember_me', '', time() - 3600, '/');

// 4. Limpiar todas las variables de la sesión.
$_SESSION = array();

// 5. Destruir la sesión por completo.
session_destroy();

// 6. Redirigir al usuario a la página de inicio.
header('Location: ' . BASE_URL);
exit();
?>