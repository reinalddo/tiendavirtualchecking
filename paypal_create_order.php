<?php
// paypal_create_order.php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once 'includes/config.php'; // Incluye $pdo, $config, helpers.php

// --- Importar Clases del SDK de PayPal ---
use PayPalHttp\HttpException;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment; // O ProductionEnvironment
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
// Podríamos necesitar más clases para detalles del pedido, pero empezamos simple

// --- Configuración de Respuesta JSON ---
header('Content-Type: application/json');

// --- Función para devolver errores en JSON ---
function returnJsonError($message, $statusCode = 500) {
    http_response_code($statusCode);
    echo json_encode(['error' => $message]);
    exit();
}

// --- Verificaciones de Seguridad ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnJsonError('Método no permitido.', 405);
}
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['carrito'])) {
    returnJsonError('No autorizado o carrito vacío.', 403);
}
if (empty($config['paypal_activo']) || empty($config['paypal_client_id']) || empty($config['paypal_client_secret'])) {
    returnJsonError('PayPal no está configurado o activo.', 503); // Service Unavailable
}

// --- Obtener datos del POST (JSON enviado por fetch) ---
$json_payload = file_get_contents('php://input');
$request_data = json_decode($json_payload, true);
$direccion_envio_js = trim($request_data['direccion'] ?? ''); // Dirección opcional enviada por JS
$nombre_cliente_js = trim($request_data['nombre'] ?? $_SESSION['usuario_nombre']); // Nombre enviado por JS o de sesión

// --- Recalcular Total del Carrito (¡MUY IMPORTANTE - Seguridad!) ---
$carrito = $_SESSION['carrito'];
$total_carrito_usd = 0;
$items_paypal = []; // Array para los items de la orden PayPal
$productos_db = []; // Para guardar datos de productos para el pedido local

try {
    $ids = array_keys($carrito);
    if (empty($ids)) throw new Exception("El carrito está vacío.");
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Obtenemos los datos necesarios del producto (incluyendo tipo)
    $stmt_productos = $pdo->prepare("SELECT id, nombre, precio_usd, precio_descuento, tipo_producto, stock FROM productos WHERE id IN ($placeholders) AND es_activo = 1");
    $stmt_productos->execute($ids);
    $productos_db_raw = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    if (count($productos_db_raw) != count($ids)) {
         throw new Exception("Algunos productos del carrito no están disponibles.");
    }

    $contiene_solo_digitales = true;
    foreach($productos_db_raw as $p) {
        $productos_db[$p['id']] = $p; // Mapear por ID
        if ($p['tipo_producto'] == 'fisico') $contiene_solo_digitales = false;
    }


    foreach ($carrito as $id => $cantidad) {
        $producto = $productos_db[$id];

        // Validar stock solo para físicos
        if ($producto['tipo_producto'] == 'fisico' && $producto['stock'] < $cantidad) {
             throw new Exception("Stock insuficiente para: " . htmlspecialchars($producto['nombre']));
        }

        $precio_a_usar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0)
                         ? (float)$producto['precio_descuento']
                         : (float)$producto['precio_usd'];
        $subtotal_linea = $precio_a_usar * $cantidad;
        $total_carrito_usd += $subtotal_linea;

        // Añadir item formateado para PayPal API
        $items_paypal[] = [
            'name' => mb_substr($producto['nombre'], 0, 127), // PayPal tiene límite de caracteres
            'quantity' => (string)$cantidad, // Debe ser string
            'unit_amount' => [
                'currency_code' => 'USD',
                'value' => number_format($precio_a_usar, 2, '.', '') // Formato '123.45'
            ]
        ];
    }

    // Aplicar descuento si existe (NECESARIO recalcularlo aquí también)
    $descuento_usd = 0;
    $cupon_aplicado = $_SESSION['cupon'] ?? null;
    $codigo_cupon_usado = null;
    if ($cupon_aplicado) {
        // Re-validar cupón aquí por seguridad (activo, fecha, usos, monto mínimo)
        $stmt_val_cupon = $pdo->prepare("SELECT * FROM cupones WHERE id = ? AND codigo = ? AND es_activo = 1 AND (fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE()) AND (usos_maximos IS NULL OR usos_maximos = 0 OR usos_actuales < usos_maximos) AND (monto_minimo_compra IS NULL OR monto_minimo_compra <= ?)");
        $stmt_val_cupon->execute([$cupon_aplicado['id'], $cupon_aplicado['codigo'], $total_carrito_usd]);
        $cupon_valido = $stmt_val_cupon->fetch(PDO::FETCH_ASSOC);

        if ($cupon_valido) {
            $codigo_cupon_usado = $cupon_valido['codigo'];
            if ($cupon_valido['tipo_descuento'] == 'porcentaje') {
                $descuento_usd = ($total_carrito_usd * (float)$cupon_valido['valor']) / 100;
            } else {
                $descuento_usd = (float)$cupon_valido['valor'];
            }
             // Asegurarse que el descuento no sea mayor que el total
             $descuento_usd = min($descuento_usd, $total_carrito_usd);
        } else {
            unset($_SESSION['cupon']); // Eliminar cupón inválido de la sesión
        }
    }
    $total_final_usd = round($total_carrito_usd - $descuento_usd, 2);
    if ($total_final_usd < 0) $total_final_usd = 0; // Evitar total negativo

     // Validar dirección si es necesario
     $direccion_envio_final = $contiene_solo_digitales ? '' : $direccion_envio_js;
     if (!$contiene_solo_digitales && empty($direccion_envio_final)) {
         throw new Exception("La dirección de envío es requerida para productos físicos.");
     }

} catch (Exception $e) {
    returnJsonError("Error al calcular el total o validar el carrito: " . $e->getMessage(), 400);
}

// --- Configurar Entorno PayPal ---
    $environment = (($config['paypal_entorno'] ?? '0') == '1')
    ? new ProductionEnvironment($config['paypal_client_id'], $config['paypal_client_secret'])
    : new SandboxEnvironment($config['paypal_client_id'], $config['paypal_client_secret']);
$client = new PayPalHttpClient($environment);

// --- Crear Pedido PRELIMINAR en TU Base de Datos ---
$pedido_id = null;
try {
    $pdo->beginTransaction();

    // Usamos la moneda y tasa de la sesión actual
    $moneda_info = $_SESSION['moneda'] ?? ['codigo' => 'USD', 'tasa_conversion' => 1.0];
    $moneda_pedido = $moneda_info['codigo'];
    $tasa_conversion_pedido = (float)($moneda_info['tasa_conversion'] ?? 1.0);

    $sql_pedido = "INSERT INTO pedidos (usuario_id, direccion_envio, total, estado, metodo_pago, cupon_usado, moneda_pedido, tasa_conversion_pedido) VALUES (?, ?, ?, 'Pendiente de Pago', 'paypal', ?, ?, ?)";
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute([$_SESSION['usuario_id'], $direccion_envio_final, $total_final_usd, $codigo_cupon_usado, $moneda_pedido, $tasa_conversion_pedido]);
    $pedido_id = $pdo->lastInsertId(); // Guardamos el ID de nuestro pedido

    // Guardar detalles (sin actualizar stock aún)
    $sql_detalle = "INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $stmt_detalle = $pdo->prepare($sql_detalle);
     foreach ($carrito as $id => $cantidad) {
        $producto = $productos_db[$id];
        $precio_unitario_guardar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0)
                                   ? $producto['precio_descuento']
                                   : $producto['precio_usd'];
        $stmt_detalle->execute([$pedido_id, $id, $cantidad, $precio_unitario_guardar]);
    }
    
    // Crear conversación (sin cambios)
    $sql_conversacion = "INSERT INTO conversaciones (pedido_id, cliente_id) VALUES (?, ?)";
    $stmt_conversacion = $pdo->prepare($sql_conversacion);
    $stmt_conversacion->execute([$pedido_id, $_SESSION['usuario_id']]);

    // ¡NO hacemos commit todavía! Esperamos la confirmación de PayPal.

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error BD al crear pedido preliminar PayPal: " . $e->getMessage());
    returnJsonError('Error al preparar tu pedido antes de ir a PayPal.', 500);
}

// --- Crear la Solicitud de Orden en PayPal ---
$request = new OrdersCreateRequest();
$request->prefer('return=representation');
$request->body = [
    "intent" => "CAPTURE", // Intención de capturar el pago inmediatamente
    "purchase_units" => [[
        "reference_id" => "PEDIDO_" . $pedido_id, // Referencia interna (TU pedido ID)
        "description" => mb_substr("Compra en " . ($config['tienda_nombre'] ?? 'Mi Tienda'), 0, 127),
        "invoice_id" => (string)$pedido_id, // ID de factura (TU pedido ID)
        "amount" => [
            "currency_code" => "USD",
            "value" => number_format($total_final_usd, 2, '.', ''), // Total final formateado '123.45'
            // Desglose (breakdown) es opcional pero recomendado si tienes impuestos/envío
             "breakdown" => [
                 "item_total" => [ // Suma de todos los items
                     "currency_code" => "USD",
                     "value" => number_format($total_carrito_usd, 2, '.', '')
                 ],
                 // Podrías añadir descuento aquí si lo calculaste
                  "discount" => [
                      "currency_code" => "USD",
                      "value" => number_format($descuento_usd, 2, '.', '')
                  ]
                 // "shipping" => [...], "tax_total" => [...] // Si aplicas envío o impuestos
             ]
        ],
        "items" => $items_paypal // Array de items que preparamos antes
    ]],
    // Información de la aplicación y URLs de retorno (opcional pero recomendado)
    "application_context" => [
        "brand_name" => $config['tienda_nombre'] ?? 'Mi Tienda Web',
        "landing_page" => "LOGIN", // O GUEST_CHECKOUT
        "user_action" => "PAY_NOW", // Texto en el botón final de PayPal
        "return_url" => ABSOLUTE_URL . "paypal_capture_order.php?action=return&pid=" . $pedido_id, // URL si se aprueba
        "cancel_url" => ABSOLUTE_URL . "checkout?paypal_cancel=1" // URL si el usuario cancela
    ]
];

// --- Ejecutar la Solicitud a PayPal ---
try {
    $response = $client->execute($request);
    $paypal_order_id = $response->result->id;

    // Guardar el ID de la orden de PayPal en nuestro pedido (¡IMPORTANTE!)
    $stmt_update_paypal_id = $pdo->prepare("UPDATE pedidos SET id_transaccion_gw = ? WHERE id = ?");
    // Necesitas añadir la columna `id_transaccion_gw` VARCHAR(100) NULL a `pedidos` si no lo hiciste
    $stmt_update_paypal_id->execute([$paypal_order_id, $pedido_id]);

    // Confirmamos la transacción SÓLO para guardar el ID de PayPal en nuestro pedido pendiente
    $pdo->commit();

    // Devolver el ID de la orden de PayPal al JavaScript
    http_response_code(200);
    echo json_encode(['id' => $paypal_order_id]);
    exit();

} catch (HttpException $e) {
    // Error de la API de PayPal
    $pdo->rollBack(); // Revertir la creación del pedido en nuestra BD
    error_log("Error API PayPal al crear orden: " . $e->getMessage() . " - Body: " . $e->getMessage()); // Log detallado
    returnJsonError('No se pudo crear la orden de pago en PayPal. Detalles: ' . $e->getMessage(), 500);
} catch (Exception $e) {
     // Otro error (ej. BD al guardar ID PayPal)
     if ($pdo->inTransaction()) $pdo->rollBack();
     error_log("Error general creando orden PayPal: " . $e->getMessage());
     returnJsonError('Ocurrió un error inesperado al procesar con PayPal.', 500);
}

?>