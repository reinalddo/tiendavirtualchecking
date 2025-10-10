<?php
// ajax_get_order_details.php
//session_start();
require_once 'includes/config.php';
//require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

if (!isset($_SESSION['usuario_id']) || empty($_GET['pedido_id'])) {
    http_response_code(403);
    exit();
}

$pedido_id = $_GET['pedido_id'];
$usuario_id = $_SESSION['usuario_id'];

// Obtener la moneda del pedido principal
$stmt_pedido = $pdo->prepare("SELECT moneda_pedido, tasa_conversion_pedido, total FROM pedidos WHERE id = ? AND usuario_id = ?");
$stmt_pedido->execute([$pedido_id, $usuario_id]);
$pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);
$moneda_del_pedido = $pedido['moneda_pedido'] ?? 'USD';

if (!$pedido) {
    echo "<p>No se encontraron detalles para este pedido.</p>";
    exit();
}

// Obtener los detalles y la imagen principal de cada producto
$stmt_detalles = $pdo->prepare("SELECT pd.*, p.nombre as nombre_producto, 
                                   (SELECT gal.url FROM producto_galeria gal WHERE gal.producto_id = pd.producto_id AND gal.tipo = 'imagen' ORDER BY gal.id ASC LIMIT 1) as imagen_principal
                               FROM pedido_detalles pd
                               JOIN productos p ON pd.producto_id = p.id
                               WHERE pd.pedido_id = ?");
$stmt_detalles->execute([$pedido_id]);
$detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

if (!$detalles) {
    echo "<p>No se encontraron detalles para este pedido.</p>";
    exit();
}

// Generar el HTML
$total_calculado = 0;
$html = '<table><thead><tr><th></th><th>Producto</th><th>Cantidad</th><th>Precio Unitario</th><th>Subtotal</th></tr></thead><tbody>';
foreach ($detalles as $item) {
    $subtotal = $item['cantidad'] * $item['precio_unitario'];
    $total_calculado += $subtotal; // Acumulamos el total
    $imagen_html = !empty($item['imagen_principal']) 
                   ? '<img src="' . BASE_URL . 'uploads/' . htmlspecialchars($item['imagen_principal']) . '" width="50" alt="">' 
                   : '';

    $html .= '<tr>';
    $html .= '<td>' . $imagen_html . '</td>';
    $html .= '<td>' . htmlspecialchars($item['nombre_producto']) . '</td>';
    $html .= '<td>' . $item['cantidad'] . '</td>';
    $html .= '<td>' . format_historical_price($item['precio_unitario'], $pedido, $pdo) . '</td>';
    $html .= '<td>' . format_historical_price($subtotal, $pedido, $pdo) . '</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table>';
// Mostramos el total recién calculado
//$html .= '<h4 style="text-align:right; margin-top:15px;">Total del Pedido: ' . $moneda_del_pedido . ' ' . number_format($total_calculado, 2) . '</h4>';
$html .= '<h4 style="text-align:right; margin-top:15px;">Total del Pedido: ' . format_historical_price($pedido['total'], $pedido, $pdo) . '</h4>';

echo $html;
?>