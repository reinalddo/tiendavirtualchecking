<?php
// logout.php
require_once 'includes/config.php';

// 1. Iniciar la sesión para poder acceder a ella.
session_start();

// 2. Limpiar todas las variables de la sesión.
$_SESSION = array();

// 3. Destruir la sesión por completo.
session_destroy();

// 4. Redirigir al usuario a la página de inicio.
header('Location: ' . BASE_URL . 'index.php');
exit();
?>