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


if (!isset($_SESSION['moneda'])) {
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

?>