<?php
// includes/config.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/helpers.php'; 

// Define la ruta base de la aplicación.
define('BASE_URL', '/');

// Detecta automáticamente el protocolo (http o https) y el dominio para crear la URL absoluta
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
define('ABSOLUTE_URL', $scheme . '://' . $host . BASE_URL);
define('ENTORNO', ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == 'localhost.tiendaweb.com') ? 'desarrollo' : 'produccion');


// --- INICIO DEL NUEVO BLOQUE DE AUTO-LOGIN ---
if (!isset($_SESSION['usuario_id']) && isset($_COOKIE['remember_me'])) {
    // Verificamos que la cookie tenga el formato correcto
    if (strpos($_COOKIE['remember_me'], ':') !== false) {
        list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);

        if ($selector && $validator) {
            // Incluimos la conexión a la BD solo si es necesario
            if (!isset($pdo)) { require_once __DIR__ . '/db_connection.php'; }

            $stmt = $pdo->prepare("SELECT * FROM auth_tokens WHERE selector = ? AND expires_at >= NOW()");
            $stmt->execute([$selector]);
            $token = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($token) {
                $hashed_validator_from_cookie = hash('sha256', $validator);
                if (hash_equals($token['hashed_validator'], $hashed_validator_from_cookie)) {
                    // ¡Token válido! Reconstruimos la sesión del usuario
                    $stmt_user = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
                    $stmt_user->execute([$token['user_id']]);
                    $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);
                    
                    if ($usuario) {
                        session_regenerate_id(true);
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['usuario_nombre'] = $usuario['nombre_pila'];
                        $_SESSION['usuario_rol'] = $usuario['rol']; // <-- Se añade el rol

                        // Lógica para reconstruir el avatar_final (igual que en login.php)
                        $avatar_final = '';
                        if (!empty($usuario['avatar_url'])) {
                            $avatar_final = $usuario['avatar_url'];
                        } elseif (!empty($usuario['avatar_manual'])) {
                            $avatar_final = BASE_URL . 'uploads/avatars/' . $usuario['avatar_manual'];
                        } else {
                            $avatar_final = BASE_URL . 'avatar/avatar-default.png';
                        }
                        $_SESSION['usuario_avatar'] = $avatar_final; // <-- Se añade el avatar
                    }
                }
            }
        }
    }
}
// --- FIN DEL NUEVO BLOQUE ---



if (ENTORNO == 'desarrollo') {
    define('GOOGLE_REDIRECT_URL', 'http://localhost.tiendaweb.com/google-callback.php');
}else{
    define('GOOGLE_REDIRECT_URL', 'https://tienda.primerpasodigital.com/google-callback.php');
}
require_once __DIR__ . '/db_connection.php'; // Ahora $pdo está disponible
    
// Establecer moneda por defecto en la primera visita
if (!isset($_SESSION['moneda']) && isset($pdo)) {
    try {
        require_once __DIR__ . '/db_connection.php';
        if (isset($pdo)) {
            $stmt_moneda = $pdo->prepare("SELECT * FROM monedas WHERE codigo = 'USD'");
            $stmt_moneda->execute();
            $_SESSION['moneda'] = $stmt_moneda->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // Manejar el error si la base de datos no está disponible
        // No hacer nada dejará la sesión de moneda sin establecer
    }
}

// Cargar toda la configuración del sitio en una variable global
// para que esté disponible en todas las páginas.
if (isset($pdo)) {
    try {
        $stmt_global_config = $pdo->query("SELECT nombre_setting, valor_setting FROM configuraciones");
        // Usamos una variable global para que sea accesible en cualquier archivo
        $config = $stmt_global_config->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        $config = []; // Si hay un error, creamos un array vacío
    }
}


?>