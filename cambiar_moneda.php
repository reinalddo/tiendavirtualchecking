<?php
// cambiar_moneda.php
session_start();
require_once 'includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['moneda_id'])) {
    $moneda_id = $_POST['moneda_id'];
    $stmt = $pdo->prepare("SELECT * FROM monedas WHERE id = ? AND es_activa = 1");
    $stmt->execute([$moneda_id]);
    $moneda_seleccionada = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($moneda_seleccionada) {
        $_SESSION['moneda'] = $moneda_seleccionada;
    }
}

// Redirigir a la página anterior
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
?>