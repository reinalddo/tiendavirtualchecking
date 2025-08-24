<?php
// ajax_get_cart_details.php
session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php'; // Necesario para la función format_price()

$carrito = $_SESSION['carrito'] ?? [];
$response = [
    'total_items' => count($carrito),
    'productos' => [],
    'total_formateado' => ''
];
$total_carrito_usd = 0;

if (!empty($carrito)) {
    $ids = array_keys($carrito);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT id, nombre, precio_usd, precio_descuento,
               (SELECT gal.url FROM producto_galeria gal WHERE gal.producto_id = p.id AND gal.tipo = 'imagen' ORDER BY gal.orden ASC, gal.id ASC LIMIT 1) as imagen_principal
        FROM productos p
        WHERE p.id IN ($placeholders)
    ");
    $stmt->execute($ids);
    $productos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos_db as $producto) {
        $cantidad = $carrito[$producto['id']];
        
        // Usamos el precio de descuento si está disponible
        $precio_a_usar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0) 
                         ? $producto['precio_descuento'] 
                         : $producto['precio_usd'];
        
        $subtotal_usd = $precio_a_usar * $cantidad;
        $total_carrito_usd += $subtotal_usd;
        
        $response['productos'][] = [
            'id' => $producto['id'],
            'nombre' => $producto['nombre'],
            'cantidad' => $carrito[$producto['id']],
            'precio_formateado' => format_price($precio_a_usar),
            'imagen_url' => $producto['imagen_principal'] // Añadimos la URL de la imagen
        ];
    }
    
    // Aquí podrías añadir la lógica de cupones si quieres que se refleje en el total del mini-carrito
    $response['total_formateado'] = format_price($total_carrito_usd);
}

header('Content-Type: application/json');
echo json_encode($response);
?>