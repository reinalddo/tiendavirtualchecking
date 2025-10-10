<?php
// aplicar_cupon.php
//session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['codigo_cupon'])) {
    header('Location: ' . BASE_URL . 'carrito');
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
else {
    // --- NUEVA VALIDACIÓN DE MONTO MÍNIMO ---
    if ($cupon['monto_minimo_compra'] > 0) {
        // Calculamos el subtotal actual del carrito en USD
        $carrito = $_SESSION['carrito'] ?? [];
        $total_carrito_usd = 0;
        if (!empty($carrito)) {
            $ids = array_keys($carrito);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt_productos = $pdo->prepare("SELECT id, precio_usd, precio_descuento FROM productos WHERE id IN ($placeholders)");
            $stmt_productos->execute($ids);
            $productos_db = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

            foreach ($productos_db as $producto) {
                $precio_a_usar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0) ? $producto['precio_descuento'] : $producto['precio_usd'];
                $total_carrito_usd += $precio_a_usar * $carrito[$producto['id']];
            }
        }
        // Comparamos el total del carrito con el mínimo requerido
        if ($total_carrito_usd < $cupon['monto_minimo_compra']) {
            $error_mensaje = 'Necesitas un mínimo de compra de $' . number_format($cupon['monto_minimo_compra'], 2) . ' para usar este cupón.';
        }
    }
    // --- FIN DE LA NUEVA VALIDACIÓN ---
}

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

header('Location: ' . BASE_URL . 'carrito');
exit();
?>