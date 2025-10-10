<?php
// includes/db_connection.php

$host = 'localhost';
$db_name = 'u680460687_tiendaweb_0';
$db_user = 'u680460687_master';
$db_password = 'l?87Rlo[U4/l';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>