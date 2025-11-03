<?php
// paypal_capture_order.php
session_start();
require_once 'includes/config.php'; // Incluye $pdo, $config, helpers.php

// --- Importar Clases del SDK de PayPal ---
use PayPalHttp\HttpException;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest; // Para obtener detalles si es necesario

// --- Configuración de Respuesta JSON y Función de Error ---
header('Content-Type: application/json');
function returnJsonError($message, $statusCode = 500) {
    http_response_code($statusCode);
    echo json_encode(['error' => $message]);
    exit();
}

// --- Verificaciones ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnJsonError('Método no permitido.', 405);
}
if (!isset($_SESSION['usuario_id'])) { // Verificar usuario logueado
    returnJsonError('No autorizado.', 403);
}
if (empty($config['paypal_activo']) || empty($config['paypal_client_id']) || empty($config['paypal_client_secret'])) {
    returnJsonError('PayPal no está configurado o activo.', 503);
}

// --- Obtener Order ID de PayPal enviado por Fetch ---
$json_payload = file_get_contents('php://input');
$request_data = json_decode($json_payload, true);
$paypal_order_id = $request_data['orderID'] ?? null;

if (empty($paypal_order_id)) {
    returnJsonError('ID de orden de PayPal no recibido.', 400);
}

// --- Configurar Entorno PayPal ---
$environment = ($config['paypal_entorno'] ?? 'sandbox') == 'live'
    ? new ProductionEnvironment($config['paypal_client_id'], $config['paypal_client_secret'])
    : new SandboxEnvironment($config['paypal_client_id'], $config['paypal_client_secret']);
$client = new PayPalHttpClient($environment);

// --- Preparar Solicitud de Captura ---
$request = new OrdersCaptureRequest($paypal_order_id);
$request->prefer('return=representation');

try {
    // --- Ejecutar Captura en PayPal ---
    $response = $client->execute($request);
    $paypal_result = $response->result;

    // --- Verificar Estado de la Captura ---
    if ($paypal_result->status == 'COMPLETED') {
        // ¡Pago Completado Exitosamente en PayPal!

        // Buscar nuestro pedido asociado usando el ID de PayPal
        $stmt_find_pedido = $pdo->prepare("SELECT * FROM pedidos WHERE id_transaccion_gw = ? AND usuario_id = ? AND estado = 'Pendiente de Pago'");
        $stmt_find_pedido->execute([$paypal_order_id, $_SESSION['usuario_id']]);
        $pedido = $stmt_find_pedido->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            // Esto no debería pasar si create_order funcionó bien, pero es una salvaguarda
             error_log("Error Captura PayPal: No se encontró pedido local pendiente para PayPal Order ID: " . $paypal_order_id . " y Usuario ID: " . $_SESSION['usuario_id']);
             // Devolvemos éxito a JS para que no muestre error al usuario, pero registramos el problema
             echo json_encode(['status' => 'COMPLETED', 'message' => 'Pago confirmado, error interno al actualizar pedido.', 'pedido_id' => null]);
             exit();
        }

        $pedido_id = $pedido['id'];

        // --- ACTUALIZAR NUESTRA BASE DE DATOS ---
        $pdo->beginTransaction();

        // 1. Marcar pedido como 'Pagado'
        $stmt_update_status = $pdo->prepare("UPDATE pedidos SET estado = 'Pagado' WHERE id = ?");
        $stmt_update_status->execute([$pedido_id]);

        // 2. Actualizar Stock (solo físicos) y obtener items digitales
        $stmt_detalles = $pdo->prepare("SELECT pd.producto_id, pd.cantidad, prod.tipo_producto, pd.id as pedido_detalle_id FROM pedido_detalles pd JOIN productos prod ON pd.producto_id = prod.id WHERE pd.pedido_id = ?");
        $stmt_detalles->execute([$pedido_id]);
        $detalles_pedido = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

        $sql_stock = "UPDATE productos SET stock = stock - ? WHERE id = ? AND tipo_producto = 'fisico'";
        $stmt_stock = $pdo->prepare($sql_stock);
        $items_digitales_para_descarga = [];

        foreach($detalles_pedido as $detalle) {
            if ($detalle['tipo_producto'] == 'fisico') {
                $stmt_stock->execute([$detalle['cantidad'], $detalle['producto_id']]);
            } else {
                 $items_digitales_para_descarga[] = [
                     'pedido_detalle_id' => $detalle['pedido_detalle_id'],
                     'usuario_id' => $pedido['usuario_id'],
                     'producto_id' => $detalle['producto_id']
                 ];
            }
        }

        // 3. Actualizar Uso de Cupón (si aplica)
        if (!empty($pedido['cupon_usado'])) {
             $stmt_cupon = $pdo->prepare("UPDATE cupones SET usos_actuales = usos_actuales + 1 WHERE codigo = ?");
             $stmt_cupon->execute([$pedido['cupon_usado']]);
        }
        
        // 4. Generar Accesos de Descarga (SI HAY DIGITALES)
        if (!empty($items_digitales_para_descarga)) {
             generar_accesos_descarga($pdo, $pedido_id, $config); // Llamamos a la función helper
        }
        
        // 5. Enviar Notificaciones y Correos
        try {
            // Notificar Admins
            $admins_ids = obtener_admins($pdo);
            $mensaje_admin = "Pedido #" . $pedido_id . " pagado con PayPal.";
            $url_admin = BASE_URL . "panel/pedido/" . $pedido_id;
            foreach ($admins_ids as $admin_id) {
                crear_notificacion($pdo, $admin_id, $mensaje_admin, $url_admin);
            }
            // Enviar correo Admin (opcional, podrías tenerlo ya en create_order)

            // Enviar correo Cliente
            $stmt_cliente = $pdo->prepare("SELECT nombre_pila, email FROM usuarios WHERE id = ?");
            $stmt_cliente->execute([$pedido['usuario_id']]);
            $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
            if ($cliente) {
                $asunto_cliente = "¡Pago Confirmado! Pedido #" . $pedido_id . " en " . ($config['tienda_nombre'] ?? 'Mi Tienda');
                $mensaje_cliente_email = "<p>Hola " . htmlspecialchars($cliente['nombre_pila']) . ",</p><p>Hemos confirmado tu pago con PayPal para el pedido <strong>#" . $pedido_id . "</strong>.</p>";
                if (!empty($items_digitales_para_descarga)) {
                    $mensaje_cliente_email .= "<p>Puedes acceder a tus productos digitales desde la sección 'Mis Descargas' en tu perfil.</p>";
                } else {
                     $mensaje_cliente_email .= "<p>Prepararemos tu envío pronto.</p>";
                }
                $url_cliente_email = ABSOLUTE_URL . "perfil";
                $cuerpo_email_cliente = generar_plantilla_email("Pago Confirmado", $mensaje_cliente_email, "Ver Mi Cuenta", $url_cliente_email, $config);
                enviar_email($pdo, $cliente['email'], $cliente['nombre_pila'], $asunto_cliente, $cuerpo_email_cliente, $config);
            }
        } catch(Exception $e) {
             error_log("Error enviando notificaciones/correos para pedido PayPal {$pedido_id}: " . $e->getMessage());
             // No revertimos la transacción por esto, pero lo registramos
        }


        // 6. Confirmar Transacción en BD
        $pdo->commit();

        // 7. Limpiar Carrito
        unset($_SESSION['carrito'], $_SESSION['cupon']);

        // 8. Devolver Respuesta Exitosa a JavaScript
        http_response_code(200);
        echo json_encode([
            'status' => 'COMPLETED',
            'pedido_id' => $pedido_id, // Nuestro ID de pedido
            'paypal_order_id' => $paypal_order_id,
            'redirectUrl' => BASE_URL . 'gracias/' . $pedido_id // URL a la página de gracias
        ]);
        exit();

    } else {
        // El pago no se completó (ej. PENDING, FAILED, etc.)
        // No actualizamos nuestro pedido a 'Pagado'. Podríamos marcarlo como 'Cancelado' si falla.
        error_log("Captura PayPal NO COMPLETADA para PayPal Order ID: " . $paypal_order_id . ". Estado: " . $paypal_result->status);
        if ($paypal_result->status == 'VOIDED' || $paypal_result->status == 'FAILED') {
             // Opcional: Marcar pedido como cancelado en nuestra BD
             $stmt_cancel = $pdo->prepare("UPDATE pedidos SET estado = 'Cancelado' WHERE id_transaccion_gw = ? AND estado = 'Pendiente de Pago'");
             $stmt_cancel->execute([$paypal_order_id]);
        }
        returnJsonError('El pago con PayPal no pudo ser completado. Estado: ' . $paypal_result->status, 400);
    }

} catch (HttpException $e) {
    // Error de la API de PayPal al capturar
    error_log("Error API PayPal al capturar orden {$paypal_order_id}: " . $e->getMessage() . " - Body: " . $e->getMessage());
    // Intentar obtener nuestro pedido_id si es posible para loguear
    $stmt_find_pid = $pdo->prepare("SELECT id FROM pedidos WHERE id_transaccion_gw = ?");
    $stmt_find_pid->execute([$paypal_order_id]);
    $pid_local = $stmt_find_pid->fetchColumn();
    error_log("^^^ Corresponde a Pedido Local ID: " . ($pid_local ?? 'No encontrado'));
    
    // Devolver error genérico al usuario
    returnJsonError('Error al finalizar el pago con PayPal. '. $e->getMessage(), 500);
} catch (Exception $e) {
     // Otro error (ej. BD)
     if ($pdo->inTransaction()) $pdo->rollBack(); // Asegurar rollback
     error_log("Error general capturando orden PayPal {$paypal_order_id}: " . $e->getMessage());
     returnJsonError('Ocurrió un error inesperado al finalizar tu pago.', 500);
}

?>