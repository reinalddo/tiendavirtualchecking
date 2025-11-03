<?php
// procesar_pedido.php (Versión Completa y Actualizada con Mercado Pago)
session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

use GuzzleHttp\Client; // Importar Guzzle
use GuzzleHttp\Exception\RequestException; // Para manejo de errores

// 1. OBTENER LA CONFIGURACIÓN COMPLETA DESDE LA BASE DE DATOS
$stmt_config = $pdo->query("SELECT nombre_setting, valor_setting FROM configuraciones");
$config_list = $stmt_config->fetchAll(PDO::FETCH_ASSOC);
$config = [];
foreach ($config_list as $setting) {
    $config[$setting['nombre_setting']] = $setting['valor_setting'];
}

// 2. VERIFICACIONES DE SEGURIDAD
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id']) || empty($_SESSION['carrito'])) {
    header('Location: ' . BASE_URL );
    exit();
}

// 3. DATOS INICIALES DEL PEDIDO
$usuario_id = $_SESSION['usuario_id'];
$carrito = $_SESSION['carrito'];
$metodo_pago = $_POST['metodo_pago'] ?? '';
$direccion_envio = trim($_POST['direccion'] ?? '');
$estado_pedido = 'Pendiente de Pago'; // Todos los pedidos empiezan como pendientes

try {
    // --- 4. CÁLCULO DE TOTALES ---
    $subtotal_usd = 0;
    $ids = array_keys($carrito);
    if (empty($ids)) {
        throw new Exception("El carrito está vacío.");
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt_productos = $pdo->prepare("SELECT * FROM productos WHERE id IN ($placeholders)");
    $stmt_productos->execute($ids);
    $productos_db_raw = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    $productos_db = [];
    foreach($productos_db_raw as $p) { $productos_db[$p['id']] = $p; }
    $contiene_solo_digitales = true; // Re-verificamos aquí por seguridad

    foreach ($carrito as $id => $cantidad) {
        $producto = $productos_db[$id];
        if ($producto['tipo_producto'] == 'fisico') $contiene_solo_digitales = false;

        // Validar stock solo para productos físicos
        if ($producto['tipo_producto'] == 'fisico' && $producto['stock'] < $cantidad) {
             throw new Exception("No hay suficiente stock para el producto: " . htmlspecialchars($producto['nombre']));
        }

        $precio_a_usar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0) 
                         ? $producto['precio_descuento'] 
                         : $producto['precio_usd'];
        $subtotal_usd += $precio_a_usar * $cantidad;
    }

    $descuento_usd = 0;
    $cupon_aplicado = $_SESSION['cupon'] ?? null;
    $codigo_cupon_usado = $cupon_aplicado ? $cupon_aplicado['codigo'] : null;
    if ($cupon_aplicado) {
        if ($cupon_aplicado['tipo_descuento'] == 'porcentaje') {
            // CORRECCIÓN 1: Usar la variable correcta $subtotal_usd
            $descuento_usd = ($subtotal_usd * $cupon_aplicado['valor']) / 100;
        } else {
            $descuento_usd = $cupon_aplicado['valor'];
        }
    }
    // CORRECCIÓN 2: Restar el descuento calculado al total final
    $total_final_usd = $subtotal_usd - $descuento_usd;
    // --- 5. MANEJAR LA PASARELA DE PAGO ---

    // --- Validar dirección si hay productos físicos ---
    if (!$contiene_solo_digitales && empty($direccion_envio)) {
        throw new Exception("La dirección de envío es obligatoria para productos físicos.");
    }

    // --- OBTENER DATOS DE MONEDA (Importante para Pagoflash) ---
    $moneda_info = $_SESSION['moneda'] ?? ['codigo' => 'USD', 'tasa_conversion' => 1.0];
    $moneda_pedido = $moneda_info['codigo'];
    $tasa_conversion_pedido = (float)($moneda_info['tasa_conversion'] ?? 1.0); // Asegurar que sea float


    // --- 6. GUARDAR PEDIDO (SOLO PARA STRIPE Y MANUAL) ---
    $pdo->beginTransaction();
    $sql_pedido = "INSERT INTO pedidos (usuario_id, direccion_envio, total, estado, metodo_pago, cupon_usado, moneda_pedido, tasa_conversion_pedido) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_pedido = $pdo->prepare($sql_pedido);
    
    // AÑADIMOS LAS NUEVAS VARIABLES A LA EJECUCIÓN
    $stmt_pedido->execute([$usuario_id, $direccion_envio, $total_final_usd, $estado_pedido, $metodo_pago, $codigo_cupon_usado, $moneda_pedido, $tasa_conversion_pedido]);
    
    $pedido_id = $pdo->lastInsertId();
    
    $sql_conversacion = "INSERT INTO conversaciones (pedido_id, cliente_id) VALUES (?, ?)";
    $stmt_conversacion = $pdo->prepare($sql_conversacion);
    $stmt_conversacion->execute([$pedido_id, $usuario_id]);
    $sql_detalle = "INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $stmt_detalle = $pdo->prepare($sql_detalle);
    $sql_stock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
    $stmt_stock = $pdo->prepare($sql_stock);

    $items_digitales_comprados = []; // Para generar descargas después

    foreach ($carrito as $id => $cantidad) {
        $producto = $productos_db[$id]; // Usamos el array que ya cargamos
        $precio_unitario_guardar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0)
                                   ? $producto['precio_descuento']
                                   : $producto['precio_usd'];
                                   
        $stmt_detalle->execute([$pedido_id, $id, $cantidad, $precio_unitario_guardar]);
        $pedido_detalle_id = $pdo->lastInsertId(); // Guardamos el ID del detalle

        // === MODIFICACIÓN: Actualizar stock SOLO si es físico ===
        if ($producto['tipo_producto'] == 'fisico') {
            $stmt_stock->execute([$cantidad, $id]);
        } else {
             // Si es digital, guardamos la info para generar el enlace después
             $items_digitales_comprados[] = [
                 'pedido_detalle_id' => $pedido_detalle_id,
                 'usuario_id' => $usuario_id,
                 'producto_id' => $id
             ];
        }
    }

    if ($cupon_aplicado) {
        $stmt_cupon = $pdo->prepare("UPDATE cupones SET usos_actuales = usos_actuales + 1 WHERE id = ?");
        $stmt_cupon->execute([$cupon_aplicado['id']]);
    }

    switch ($metodo_pago) {
        case 'manual':
            if (empty($config['pago_manual_activo'])) throw new Exception("Método de pago no disponible.");
            // La lógica para guardar el pedido se hará más abajo
            break;

        case 'stripe':
            if (empty($config['stripe_activo'])) throw new Exception("Método de pago no disponible.");
            
            \Stripe\Stripe::setApiKey($config['stripe_secret_key']);
            $token = $_POST['stripeToken'] ?? '';
            if (empty($token)) throw new Exception("Token de pago no válido.");

            $charge = \Stripe\Charge::create([
                'amount' => round($total_final_usd * 100),
                'currency' => 'usd',
                'description' => 'Pedido para el usuario ' . $usuario_id,
                'source' => $token,
            ]);

            if ($charge->status == 'succeeded') {
                $estado_pedido = 'Pagado'; // El estado cambia a Pagado
            } else {
                throw new Exception("El pago con Stripe no fue exitoso.");
            }
            break;

        case 'payu':
            if (empty($config['payu_activo'])) throw new Exception("Método de pago no disponible.");
            
            // Configurar SDK de PayU
            OpenPayU_Configuration::setApiKey($config['payu_api_key']);
            OpenPayU_Configuration::setApiLogin("pRRXKOl8ikMmt9u");
            OpenPayU_Configuration::setMerchantId($config['payu_merchant_id']);
            OpenPayU_Configuration::setIsTest(!empty($config['payu_test_mode']));
            OpenPayU_Configuration::setLanguage('es');

            $referenceCode = "PEDIDO_" . $pedido_id . "_" . time();
            $signature = md5($config['payu_api_key'] . "~" . $config['payu_merchant_id'] . "~" . $referenceCode . "~" . $total_final_usd . "~USD");
            $payu_url = OpenPayU_Configuration::getWebCheckoutUrl();

            echo '<body onload="document.forms[0].submit()">
                    <p>Redirigiendo a PayU para completar el pago de forma segura...</p>
                    <form action="' . $payu_url . '" method="post">
                        <input name="merchantId" type="hidden" value="' . htmlspecialchars($config['payu_merchant_id']) . '">
                        <input name="accountId" type="hidden" value="' . htmlspecialchars($config['payu_account_id']) . '">
                        <input name="description" type="hidden" value="Compra en Mi Tienda - Pedido #' . $pedido_id . '">
                        <input name="referenceCode" type="hidden" value="' . htmlspecialchars($referenceCode) . '">
                        <input name="amount" type="hidden" value="' . htmlspecialchars($total_final_usd) . '">
                        <input name="currency" type="hidden" value="USD">
                        <input name="signature" type="hidden" value="' . htmlspecialchars($signature) . '">
                        <input name="test" type="hidden" value="' . ($config['payu_test_mode'] ? '1' : '0') . '">
                        <input name="buyerEmail" type="hidden" value="' . htmlspecialchars($email_cliente) . '">
                        <input name="responseUrl" type="hidden" value="' . BASE_URL . 'respuesta_payu.php">
                        <input name="confirmationUrl" type="hidden" value="' . BASE_URL . 'webhook_payu.php">
                    </form>
                  </body>';
            exit();
            break;
        // ===== INICIO: NUEVO CASE PAGOFLASH =====
        case 'pagoflash':
            if (empty($config['pagoflash_activo']) || empty($config['pagoflash_commerce_token'])) {
                $pdo->rollBack(); // Revertir pedido si Pagoflash no está configurado
                throw new Exception("Método de pago Pagoflash no disponible o no configurado.");
            }

            // Determinar URL de API según entorno
            $pagoflash_api_url = (($config['pagoflash_entorno'] ?? '0') == '1') 
                ? 'https://pagoflash.com/payment-gateway-commerce' // URL Producción
                : 'https://qa.pagoflash.com/payment-gateway-commerce'; // URL Calidad
            $endpoint = '/api/v1/orders'; // Endpoint para crear orden

            // Calcular monto en Bolívares (VES)
            // IMPORTANTE: Asume que la tasa guardada ($tasa_conversion_pedido) es USD -> VES
            // Si $moneda_pedido NO es VES, necesitas obtener la tasa VES desde la tabla monedas
            $monto_ves = $total_final_usd * $tasa_conversion_pedido;
            // Pagoflash espera el monto sin decimales (céntimos)
            $monto_pagoflash = round($monto_ves * 100);

            // Datos para la API
            $commerce_token = $config['pagoflash_commerce_token'];
            $id_pedido_tienda = (string)$pedido_id; // Pagoflash requiere string
            $descripcion = "Pedido #" . $pedido_id . " en " . ($config['tienda_nombre'] ?? 'Mi Tienda');
            // URLs de retorno y notificación (debes crearlas)
            $url_retorno_exito = ABSOLUTE_URL . 'respuesta_pagoflash?status=success&pid=' . $pedido_id;
            $url_retorno_fallo = ABSOLUTE_URL . 'respuesta_pagoflash?status=failure&pid=' . $pedido_id;
            $url_notificacion = ABSOLUTE_URL . 'webhook_pagoflash'; // Webhook para confirmación

            // Crear cliente Guzzle
            $client = new Client(['base_uri' => $pagoflash_api_url]);

            try {
                $response = $client->post($endpoint, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $commerce_token,
                        'Accept'        => 'application/json',
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'pc_order_number' => $id_pedido_tienda,
                        'pc_amount'       => $monto_pagoflash, // Monto en céntimos
                        'pc_description'  => $descripcion,
                        'pc_reference'    => 'Pedido ' . $id_pedido_tienda, // Referencia interna opcional
                        'pc_url_succes'   => $url_retorno_exito, // Corregido: success con 2 s
                        'pc_url_failure'  => $url_retorno_fallo,
                        'pc_url_notify'   => $url_notificacion,
                        // Añadir datos del comprador si la API los requiere/permite
                        // 'pf_payer_name' => $_POST['nombre'] ?? '',
                        // 'pf_payer_email' => $cliente['email'] ?? '', // Necesitarías obtener el email del cliente
                    ],
                    'http_errors' => false, // Para manejar errores 4xx/5xx manualmente
                ]);

                $statusCode = $response->getStatusCode();
                $body = json_decode($response->getBody()->getContents(), true);

                if ($statusCode >= 200 && $statusCode < 300 && isset($body['data']['pf_url_payment'])) {
                    // ¡Éxito! Orden creada en Pagoflash
                    $url_pago_pagoflash = $body['data']['pf_url_payment'];
                    
                    // Guardar ID de transacción de Pagoflash si se devuelve (útil para consultas futuras)
                    if(isset($body['data']['id_transaccion'])) {
                        $id_transaccion_pf = $body['data']['id_transaccion'];
                        $stmt_update_trans = $pdo->prepare("UPDATE pedidos SET id_transaccion_gw = ? WHERE id = ?");
                        // Necesitarías añadir la columna `id_transaccion_gw` VARCHAR(100) NULL a la tabla `pedidos`
                         // $stmt_update_trans->execute([$id_transaccion_pf, $pedido_id]); 
                    }

                    $pdo->commit(); // Confirmar la creación del pedido en TU BD

                    // Redirigir al cliente a la URL de pago de Pagoflash
                    header('Location: ' . $url_pago_pagoflash);
                    exit();

                } else {
                    // Error al crear la orden en Pagoflash
                    $error_message = 'Pagoflash: ';
                    if (isset($body['message'])) {
                        $error_message .= $body['message'];
                    } elseif ($response->getReasonPhrase()) {
                        $error_message .= $response->getReasonPhrase();
                    } else {
                        $error_message .= 'Error desconocido al crear orden.';
                    }
                     error_log("Error API Pagoflash (Pedido {$pedido_id}): Status {$statusCode} - " . json_encode($body));
                     $pdo->rollBack(); // Revertir pedido
                     throw new Exception($error_message);
                }

            } catch (RequestException $e) {
                 error_log("Error Guzzle Pagoflash (Pedido {$pedido_id}): " . $e->getMessage());
                 $pdo->rollBack(); // Revertir pedido
                 throw new Exception('Error de conexión al procesar el pago con Pagoflash.');
            }
            break;
        // ===== FIN: NUEVO CASE PAGOFLASH =====
            
        default:
            $pdo->rollBack(); // Revertir si el método no es válido
            throw new Exception("Por favor, selecciona un método de pago válido.");
    }

    // OBTENEMOS LOS DATOS DE LA MONEDA DE LA SESIÓN
    //$moneda_info = $_SESSION['moneda'] ?? ['codigo' => 'USD', 'tasa_conversion' => 1.0];
    //$moneda_pedido = $moneda_info['codigo'];
    //$tasa_conversion_pedido = $moneda_info['tasa_conversion'];
    
    // === INICIO: Generar descargas SI el pago ya está confirmado (ej. Stripe) ===
    if ($estado_pedido == 'Pagado' && !empty($items_digitales_comprados)) {
        generar_accesos_descarga($pdo, $pedido_id, $config);
    /*
        $sql_descarga = "INSERT IGNORE INTO pedidos_descargas (pedido_detalle_id, usuario_id, producto_id, token_descarga, descargas_restantes) VALUES (?, ?, ?, ?, ?)";
        $stmt_descarga = $pdo->prepare($sql_descarga);
        $limite_descargas = 5; // O leerlo de config si lo haces configurable
        foreach ($items_digitales_comprados as $item) {
            $token = bin2hex(random_bytes(32)); // Generar token seguro
            $stmt_descarga->execute([
                $item['pedido_detalle_id'],
                $item['usuario_id'],
                $item['producto_id'],
                $token,
                $limite_descargas 
            ]);
        }
        */
    }
    // === FIN: Generar descargas ===

    // --- GENERAR NOTIFICACIONES ---
    $admins_ids = obtener_admins($pdo);
    $mensaje_admin = "Nuevo pedido #" . $pedido_id . " recibido.";
    $url_admin = BASE_URL . "panel/pedido/" . $pedido_id;

    foreach ($admins_ids as $admin_id) {
        crear_notificacion($pdo, $admin_id, $mensaje_admin, $url_admin);
    }
    // --- FIN NOTIFICACIONES ---

    // --- NUEVO: ENVIAR NOTIFICACIONES POR CORREO ELECTRÓNICO ---

    // 1. Correo para el Administrador
    if (!empty($config['email_contacto'])) {
        $asunto_admin = "¡Nuevo Pedido Recibido! - #" . $pedido_id;
        $mensaje_admin_email = "<p>Has recibido un nuevo pedido en tu tienda con el número <strong>#" . $pedido_id . "</strong>.</p><p>Por favor, ingresa al panel de administración para ver los detalles y procesarlo.</p>";
        $url_admin_email = ABSOLUTE_URL . "panel/pedido/" . $pedido_id;
        
        // Pasamos el array $config a la función
        $cuerpo_email_admin = generar_plantilla_email("Nuevo Pedido Recibido", $mensaje_admin_email, "Ver Pedido", $url_admin_email, $config);
        enviar_email($pdo, $config['email_contacto'], 'Administrador', $asunto_admin, $cuerpo_email_admin, $config);
    }

    // 2. Correo para el Cliente
    $stmt_cliente = $pdo->prepare("SELECT nombre_pila, email FROM usuarios WHERE id = ?");
    $stmt_cliente->execute([$usuario_id]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        $asunto_cliente = "Confirmación de tu Pedido en Mi Tienda Web - #" . $pedido_id;
        $mensaje_cliente_email = "<p>¡Gracias por tu compra! Hemos recibido tu pedido con el número <strong>#" . $pedido_id . "</strong> y ya lo estamos procesando.</p>";
        
        if ($metodo_pago == 'manual') {
            $mensaje_cliente_email .= "<p><strong>Siguientes pasos:</strong> Has elegido el método de pago manual. Por favor, realiza la transferencia o pago móvil y sube el comprobante desde la sección 'Mis Pedidos' en tu perfil para que podamos validar tu pago.</p>";
        }
        
        // --- CAMBIO 4: URL directa al pedido en el perfil ---
        $url_cliente_email = ABSOLUTE_URL . "perfil.php#pedido-" . $pedido_id;
        
        // Pasamos el array $config a la función
        $cuerpo_email_cliente = generar_plantilla_email("¡Hemos recibido tu pedido!", $mensaje_cliente_email, "Ver Mis Pedidos", $url_cliente_email, $config);
        enviar_email($pdo, $cliente['email'], $cliente['nombre_pila'], $asunto_cliente, $cuerpo_email_cliente, $config);
    }
    // --- FIN DE ENVÍO DE CORREOS ---

    $pdo->commit();

    unset($_SESSION['carrito'], $_SESSION['cupon']);
    header("Location: " . BASE_URL . "gracias/" . $pedido_id);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['mensaje_carrito'] = "Error al procesar tu pedido: " . $e->getMessage();
    header('Location: ' . BASE_URL . 'checkout');
    exit();
}
?>