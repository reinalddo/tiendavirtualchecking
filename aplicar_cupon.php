<?php
// aplicar_cupon.php
//session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['codigo_cupon'])) {
    header('Location: ' . BASE_URL . 'ver_carrito.php');
    exit();
}

$codigo = trim($_POST['codigo_cupon']);
unset($_SESSION['cupon']);

$stmt = $pdo->prepare("SELECT * FROM cupones WHERE codigo = ?");
$stmt->execute([$codigo]);
$cupon = $stmt->fetch(PDO::FETCH_ASSOC);

$error_mensaje = '';

if (!$cupon) {
    $error_mensaje = 'El código del cupón no existe.';
} elseif (!$cupon['es_activo']) {
    $error_mensaje = 'Este cupón ya no está activo.';
} elseif ($cupon['fecha_expiracion'] && new DateTime() > new DateTime($cupon['fecha_expiracion'])) {
    $error_mensaje = 'Este cupón ha expirado por fecha.';
} 
// --- LÓGICA DE USOS CORREGIDA ---
elseif (!is_null($cupon['usos_maximos']) && $cupon['usos_maximos'] > 0 && $cupon['usos_actuales'] >= $cupon['usos_maximos']) {
    $error_mensaje = 'Este cupón ha alcanzado su límite de usos.';
}
// --- FIN DE LA CORRECCIÓN ---

if ($error_mensaje) {
    $_SESSION['mensaje_carrito'] = 'Error: ' . $error_mensaje;
} else {
    $_SESSION['cupon'] = [
        'id' => $cupon['id'],
        'codigo' => $cupon['codigo'],
        'tipo_descuento' => $cupon['tipo_descuento'],
        'valor' => $cupon['valor']
    ];
    $_SESSION['mensaje_carrito'] = '¡Cupón aplicado exitosamente!';
}

header('Location: ' . BASE_URL . 'ver_carrito.php');
exit();
?>