<?php
// webhook_paypal.php (Versión Completa y Verificada)
require_once 'includes/config.php'; // Incluye $pdo, $config, helpers.php

// --- Importar Clases del SDK de PayPal ---
use PayPalHttp\HttpException;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersGetRequest; // Para verificar el estado si es necesario
use PayPalCheckoutSdk\Webhooks\WebhooksVerifySignatureRequest; // Para verificar firma

// --- Configuración Inicial ---
$webhook_log_file = __DIR__ . '/paypal_webhook.log'; // Archivo para registrar actividad
ini_set('log_errors', 1);
ini_set('error_log', $webhook_log_file);

// --- Función de Log ---
function logWebhook($message) {
    // Asegura que la variable $webhook_log_file esté disponible
    $log_file = $GLOBALS['webhook_log_file'] ?? __DIR__ . '/paypal_webhook.log';
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, $log_file);
}

logWebhook("Webhook recibido.");

// --- Validaciones Preliminares de Configuración ---
// Verifica que PayPal esté activo y todas las credenciales necesarias estén presentes en $config
if (empty($config['paypal_activo']) || empty($config['paypal_client_id']) || empty($config['paypal_client_secret']) || empty($config['paypal_webhook_id'])) {
    logWebhook("Error Crítico: Configuración de PayPal incompleta o inactiva en la BD.");
    http_response_code(500); // Error interno del servidor
    exit('Configuracion PayPal Incompleta');
}

// --- Leer Datos de PayPal (Payload y Cabeceras) ---
$payload = file_get_contents('php://input');
$event = json_decode($payload, true); // Decodifica el JSON a un array asociativo
// Usar getallheaders() si está disponible (Apache), o construir manualmente
if (!function_exists('getallheaders')) {
    function getallheaders() {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
    return $headers;
    }
}
$headers = getallheaders();

logWebhook("Headers recibidos: " . json_encode($headers));
logWebhook("Payload recibido: " . $payload);

// --- Configurar Cliente PayPal SDK (Necesario para verificación) ---
try {
    $environment = ($config['paypal_entorno'] ?? 'sandbox') == 'live'
        ? new ProductionEnvironment($config['paypal_client_id'], $config['paypal_client_secret'])
        : new SandboxEnvironment($config['paypal_client_id'], $config['paypal_client_secret']);
    $client = new PayPalHttpClient($environment);
} catch (Exception $e) {
    logWebhook("Error configurando cliente PayPal SDK: " . $e->getMessage());
    http_response_code(500);
    exit('Error SDK Setup');
}

// --- Verificar Firma del Webhook (Implementación Crucial) ---
// Extraer cabeceras necesarias (sensible a mayúsculas/minúsculas en algunos servidores)
$paypalTransmissionId = $headers['Paypal-Transmission-Id'] ?? $headers['paypal-transmission-id'] ?? null;
$paypalTransmissionTime = $headers['Paypal-Transmission-Time'] ?? $headers['paypal-transmission-time'] ?? null;
$paypalCertUrl = $headers['Paypal-Cert-Url'] ?? $headers['paypal-cert-url'] ?? null;
$paypalAuthAlgo = $headers['Paypal-Auth-Algo'] ?? $headers['paypal-auth-algo'] ?? null;
$paypalTransmissionSig = $headers['Paypal-Transmission-Sig'] ?? $headers['paypal-transmission-sig'] ?? null;
$webhookId = $config['paypal_webhook_id']; // El ID obtenido de PayPal y guardado en config

// Validar presencia de todas las cabeceras y el ID
if (!$paypalTransmissionId || !$paypalTransmissionTime || !$paypalCertUrl || !$paypalAuthAlgo || !$paypalTransmissionSig || !$webhookId) {
    logWebhook("Error Crítico: Faltan cabeceras HTTP de verificación de PayPal o Webhook ID en config.");
    http_response_code(400); // Bad Request
    exit('Missing Verification Headers');
}

try {
    $request = new WebhooksVerifySignatureRequest();
    $request->body = [
        'transmission_id' => $paypalTransmissionId,
        'transmission_time' => $paypalTransmissionTime,
        'cert_url' => $paypalCertUrl,
        'auth_algo' => $paypalAuthAlgo,
        'transmission_sig' => $paypalTransmissionSig,
        'webhook_id' => $webhookId,
        'webhook_event' => $event // El cuerpo JSON completo del evento recibido (como array asociativo)
    ];

    // Ejecutar la verificación contra la API de PayPal
    $response = $client->execute($request);

    // Verificar el resultado de la verificación
    if ($response->statusCode !== 200 || !isset($response->result->verification_status) || $response->result->verification_status !== 'SUCCESS') {
         logWebhook("Error Crítico: Verificación de firma de Webhook fallida. Status devuelto: " . ($response->result->verification_status ?? 'DESCONOCIDO'));
         http_response_code(403); // Forbidden
         exit('Signature Verification Failed');
    }
     logWebhook("Verificación de firma exitosa.");

} catch (HttpException $e) {
     // Error de comunicación con la API de PayPal durante la verificación
     $bodyContent = $e->getMessage(); // HttpException suele tener el cuerpo del error en el mensaje
     logWebhook("HttpException durante verificación de firma: Status Code: " . $e->statusCode . " Body: " . $bodyContent);
     http_response_code(500); // Error del servidor (no se pudo verificar)
     exit('Signature Verification HTTP Error');
} catch (Exception $e) {
     // Otro error durante la verificación
     logWebhook("Excepción general durante verificación de firma: " . $e->getMessage());
     http_response_code(500);
     exit('Signature Verification General Error');
}
// --- FIN VERIFICACIÓN DE FIRMA ---


// --- Procesar el Evento (Ahora sabemos que es legítimo) ---
$event_type = $event['event_type'] ?? null;
$resource = $event['resource'] ?? null; // Contiene los datos del objeto (captura, orden, etc.)

logWebhook("Tipo de evento verificado: " . ($event_type ?? 'N/A'));

// Evento principal que confirma el pago completado exitosamente
if ($event_type === 'PAYMENT.CAPTURE.COMPLETED' && isset($resource['status']) && $resource['status'] == 'COMPLETED') {

    $capture_id = $resource['id']; // ID de la captura de PayPal
    $paypal_order_id = null; // ID de la orden original de PayPal

    // Intentar obtener el Order ID de PayPal desde diferentes lugares posibles en el recurso
     if (isset($resource['supplementary_data']['related_ids']['order_id'])) { // SDK v1/v2 style (puede no estar presente)
         $paypal_order_id = $resource['supplementary_data']['related_ids']['order_id'];
     } elseif (isset($resource['invoice_id']) && is_numeric($resource['invoice_id'])) {
         // Si usamos el ID de nuestro pedido como invoice_id, podemos buscarlo
         $potential_pedido_id = $resource['invoice_id'];
         $stmt_check_invoice = $pdo->prepare("SELECT id_transaccion_gw FROM pedidos WHERE id = ?");
         $stmt_check_invoice->execute([$potential_pedido_id]);
         $paypal_order_id = $stmt_check_invoice->fetchColumn(); // Obtiene el order_id si el invoice_id coincide con nuestro pedido_id
     } elseif (isset($resource['links'])) { // Intentar extraer de los links HATEOAS
        foreach ($resource['links'] as $link) {
             if (isset($link['rel']) && $link['rel'] === 'up' && isset($link['href'])) {
                 // Extraer el order ID de la URL /v2/checkout/orders/ORDER_ID
                 if (preg_match('/\/v2\/checkout\/orders\/([a-zA-Z0-9_-]+)/', $link['href'], $matches)) { // Regex más permisivo para ID
                     $paypal_order_id = $matches[1];
                     break;
                 }
             }
         }
     }

    logWebhook("Captura COMPLETADA detectada: CaptureID {$capture_id}, OrderID {$paypal_order_id}");

    if ($paypal_order_id) {
        // Buscar nuestro pedido local asociado al ID de la orden de PayPal
        $stmt_find_pedido = $pdo->prepare("SELECT * FROM pedidos WHERE id_transaccion_gw = ?");
        $stmt_find_pedido->execute([$paypal_order_id]);
        $pedido = $stmt_find_pedido->fetch(PDO::FETCH_ASSOC);

        // SOLO actualizar si encontramos el pedido y AÚN está 'Pendiente de Pago'
        if ($pedido && $pedido['estado'] == 'Pendiente de Pago') {
            $pedido_id = $pedido['id'];
            logWebhook("Encontrado pedido local ID {$pedido_id} pendiente. Actualizando a Pagado...");

            try {
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
                         // Guardar info para generar descarga
                         $items_digitales_para_descarga[] = [
                             'pedido_detalle_id' => $detalle['pedido_detalle_id'],
                             'usuario_id' => $pedido['usuario_id'],
                             'producto_id' => $detalle['producto_id']
                         ];
                    }
                }

                // 3. Actualizar Uso de Cupón (si aplica)
                if (!empty($pedido['cupon_usado'])) {
                     $stmt_cupon = $pdo->prepare("UPDATE cupones SET usos_actuales = usos_actuales + 1 WHERE codigo = ? AND (usos_maximos IS NULL OR usos_maximos=0 OR usos_actuales < usos_maximos)");
                     $stmt_cupon->execute([$pedido['cupon_usado']]);
                     logWebhook("Uso de cupón {$pedido['cupon_usado']} incrementado para pedido {$pedido_id}.");
                }

                // 4. Generar Accesos de Descarga (SI HAY DIGITALES)
                if (!empty($items_digitales_para_descarga)) {
                     // Pasar $config a la función es crucial aquí
                     generar_accesos_descarga($pdo, $pedido_id, $config);
                     logWebhook("Accesos de descarga generados para pedido {$pedido_id}.");
                }

                // 5. Notificaciones / Correos (Opcional, pero recomendado aquí como respaldo)
                try {
                     // Notificar Admins
                     $admins_ids = obtener_admins($pdo);
                     $mensaje_admin = "Pedido #" . $pedido_id . " pagado con PayPal (Confirmado por Webhook).";
                     $url_admin = BASE_URL . "panel/pedido/" . $pedido_id;
                     foreach ($admins_ids as $admin_id) {
                         crear_notificacion($pdo, $admin_id, $mensaje_admin, $url_admin);
                     }
                      logWebhook("Notificaciones de admin enviadas para pedido {$pedido_id}.");

                     // Enviar correo Cliente (importante si capture_order falla o el usuario cierra antes)
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
                        logWebhook("Correo de confirmación enviado al cliente para pedido {$pedido_id}.");
                    }
                } catch(Exception $e) {
                     logWebhook("Error enviando notificaciones/correos (Webhook) para pedido {$pedido_id}: " . $e->getMessage());
                }

                // Confirmar todos los cambios en la BD
                $pdo->commit();
                logWebhook("Pedido local ID {$pedido_id} actualizado a Pagado vía Webhook.");

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                 logWebhook("Error CRÍTICO al actualizar BD local para pedido ID {$pedido_id} vía Webhook: " . $e->getMessage());
                 http_response_code(500); // Indicar error a PayPal para que reintente
                 exit('DB Update Error');
            }

        } elseif ($pedido && $pedido['estado'] != 'Pendiente de Pago') {
             // Si el pedido ya estaba pagado (probablemente por capture_order.php), simplemente lo registramos y respondemos OK
             logWebhook("Pedido local ID {$pedido['id']} ya estaba procesado ({$pedido['estado']}). Webhook PAYMENT.CAPTURE.COMPLETED ignorado para evitar duplicados.");
        } else {
             // Si no encontramos el pedido local asociado al ID de PayPal
             logWebhook("ADVERTENCIA: No se encontró pedido local pendiente asociado a PayPal Order ID: " . $paypal_order_id . " para evento PAYMENT.CAPTURE.COMPLETED.");
             // Respondemos 200 OK a PayPal para que no reintente, pero registramos el problema.
        }
    } else {
         // Si recibimos PAYMENT.CAPTURE.COMPLETED pero no pudimos extraer el Order ID
         logWebhook("Evento PAYMENT.CAPTURE.COMPLETED recibido pero no se pudo extraer un Order ID válido. Recurso: " . json_encode($resource));
    }

// Manejo Opcional de Eventos Negativos (Reembolsos, Rechazos, etc.)
} elseif (($event_type === 'PAYMENT.CAPTURE.DENIED' || $event_type === 'PAYMENT.CAPTURE.REVERSED' || $event_type === 'PAYMENT.CAPTURE.REFUNDED') && isset($resource['id'])) {

     $paypal_order_id = null; // Inicializar

     // Intentar obtener el Order ID de PayPal (COPIADO DEL BLOQUE ANTERIOR)
     if (isset($resource['supplementary_data']['related_ids']['order_id'])) {
         $paypal_order_id = $resource['supplementary_data']['related_ids']['order_id'];
     } elseif (isset($resource['invoice_id']) && is_numeric($resource['invoice_id'])) {
         $potential_pedido_id = $resource['invoice_id'];
         $stmt_check_invoice = $pdo->prepare("SELECT id_transaccion_gw FROM pedidos WHERE id = ?");
         $stmt_check_invoice->execute([$potential_pedido_id]);
         $paypal_order_id = $stmt_check_invoice->fetchColumn();
     } elseif (isset($resource['links'])) {
        foreach ($resource['links'] as $link) {
             if (isset($link['rel']) && $link['rel'] === 'up' && isset($link['href'])) {
                 if (preg_match('/\/v2\/checkout\/orders\/([a-zA-Z0-9_-]+)/', $link['href'], $matches)) {
                     $paypal_order_id = $matches[1];
                     break;
                 }
             }
         }
     }

     if ($paypal_order_id) {
         logWebhook("Evento negativo/reembolso {$event_type} recibido para PayPal Order ID {$paypal_order_id}.");
         // Aquí podrías añadir lógica para marcar el pedido como 'Cancelado' o 'Reembolsado'
         // Ejemplo:
         // $nuevoEstado = ($event_type === 'PAYMENT.CAPTURE.DENIED') ? 'Cancelado' : 'Reembolsado';
         // $stmt_update_status = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id_transaccion_gw = ? AND estado = 'Pagado'"); // Solo si estaba pagado
         // $stmt_update_status->execute([$nuevoEstado, $paypal_order_id]);
         // También podrías necesitar revocar acceso a descargas digitales aquí
     } else {
          logWebhook("Evento negativo/reembolso {$event_type} recibido sin Order ID identificable. Recurso: " . json_encode($resource));
     }

} else {
    // Si el evento no es PAYMENT.CAPTURE.COMPLETED ni uno negativo manejado
    logWebhook("Evento no relevante o no manejado recibido: " . ($event_type ?? 'Tipo desconocido'));
}

// Responder siempre 200 OK a PayPal si llegamos aquí sin errores fatales que requieran reintento
http_response_code(200);
echo json_encode(['status' => 'received']);
exit(); // Terminar script explícitamente
?>