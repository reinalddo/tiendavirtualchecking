<?php
require_once 'includes/config.php'; // config.php inicia la sesión
require_once 'includes/db_connection.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['redirect_url'] = 'checkout.php';
    $_SESSION['mensaje_carrito'] = "Error: Debes iniciar sesión para proceder al pago.";
    header('Location: ' . BASE_URL . 'login');
    exit();
}
if (empty($_SESSION['carrito'])) {
    header('Location: ' . BASE_URL . 'ver_carrito');
    exit();
}
// 1. OBTENER LA CONFIGURACIÓN DE PAGOS
$stmt_config = $pdo->query("SELECT nombre_setting, valor_setting FROM configuraciones");
$config_list = $stmt_config->fetchAll(PDO::FETCH_ASSOC);
$config = [];
foreach ($config_list as $setting) {
    $config[$setting['nombre_setting']] = $setting['valor_setting'];
}

$carrito = $_SESSION['carrito'] ?? [];
$productos_en_carrito = [];
$total_carrito_usd = 0;
$contiene_solo_digitales = true; // Asumimos true hasta encontrar un físico

if (!empty($carrito)) {
    $ids = array_keys($carrito);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, nombre, precio_usd, precio_descuento, tipo_producto FROM productos WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $productos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($productos_db as $producto) 
    {
        // --- INICIO: Verificación Segura ---
        // Si la clave 'tipo_producto' existe Y su valor es 'fisico', cambiamos la bandera
        if (isset($producto['tipo_producto']) && $producto['tipo_producto'] == 'fisico') {
            $contiene_solo_digitales = false;
        } 
        // Si la clave no existe, asumimos que NO es solo digital por seguridad (requerirá dirección)
        elseif (!isset($producto['tipo_producto'])) {
             $contiene_solo_digitales = false;
             // Opcional: Registrar un error para investigar por qué falta el dato
             error_log("Advertencia: tipo_producto no encontrado para producto ID: " . ($producto['id'] ?? 'desconocido') . " en checkout.");
        }
        // --- FIN: Verificación Segura ---        

        $cantidad = $carrito[$producto['id']];
        $precio_a_usar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0) ? $producto['precio_descuento'] : $producto['precio_usd'];
        $subtotal_usd = $precio_a_usar * $cantidad;
        $total_carrito_usd += $subtotal_usd;
        $producto['cantidad'] = $cantidad;
        $producto['subtotal_usd'] = $subtotal_usd;
        $productos_en_carrito[] = $producto;
    }
} else {
    $contiene_solo_digitales = false; // Si el carrito está vacío, no aplica
}
$descuento_usd = 0;
$total_final_usd = $total_carrito_usd;
$cupon_aplicado = $_SESSION['cupon'] ?? null;
if ($cupon_aplicado) {
    if ($cupon_aplicado['tipo_descuento'] == 'porcentaje') {
        $descuento_usd = ($total_carrito_usd * $cupon_aplicado['valor']) / 100;
    } else {
        $descuento_usd = $cupon_aplicado['valor'];
    }
    $total_final_usd = $total_carrito_usd - $descuento_usd;
}
require_once 'includes/header.php';

?>

    <div class="container-fluid py-9">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-9">

                <h1>Finalizar Compra</h1>
    <div class="checkout-layout">
        <div class="customer-info">
            <h2>Tus Datos y Pago</h2>
                <form action="procesar_pedido" method="POST" id="payment-form">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo:</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>" required>
                    </div>
                    <div class="mb-3" id="campo-direccion-envio" style="<?php echo $contiene_solo_digitales ? 'display: none;' : ''; ?>">
                        <label for="direccion" class="form-label">Dirección de Envío:</label>
                        <textarea id="direccion" name="direccion" class="form-control" rows="4" <?php echo !$contiene_solo_digitales ? 'required' : ''; ?>></textarea>
                    </div>

                    <h4 class="mt-4">Método de Pago</h4>


                    <div class="payment-methods">
                        <?php $primer_metodo_activo = ''; ?>

                        <?php if (!empty($config['pago_manual_activo'])): ?>
                            <?php if (empty($primer_metodo_activo)) $primer_metodo_activo = 'manual'; ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="metodo_pago" id="pago_manual" value="manual" <?php if ($primer_metodo_activo == 'manual') echo 'checked'; ?>>
                                <label class="form-check-label" for="pago_manual">Pago Manual (Transferencia<?php if(!empty($config['venezuela'])){ ?> / Pago Móvil<?php } ?>)</label>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($config['stripe_activo']) && !empty($config['stripe_public_key']) && !empty($config['stripe_secret_key'])): ?>
                            <?php if (empty($primer_metodo_activo)) $primer_metodo_activo = 'stripe'; ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="metodo_pago" id="pago_stripe" value="stripe" <?php if ($primer_metodo_activo == 'stripe') echo 'checked'; ?>>
                                <label class="form-check-label" for="pago_stripe">Pagar con Tarjeta (Internacional)</label>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($config['payu_activo']) && !empty($config['payu_merchant_id']) && !empty($config['payu_api_key']) && !empty($config['payu_account_id'])):
                            if (empty($primer_metodo_activo)) $primer_metodo_activo = 'payu'; ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="metodo_pago" id="pago_payu" value="payu" <?php if ($primer_metodo_activo == 'payu') echo 'checked'; ?>>
                                <label class="form-check-label" for="pago_payu">Pagar con Tarjeta (PayU)</label>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($config['pagoflash_activo']) && !empty($config['pagoflash_commerce_token']) && !empty($config['venezuela'])): ?>

                            <?php if (empty($primer_metodo_activo)) $primer_metodo_activo = 'pagoflash'; ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="metodo_pago" id="pago_pagoflash" value="pagoflash" <?php if ($primer_metodo_activo == 'pagoflash') echo 'checked'; ?>>
                                <label class="form-check-label" for="pago_pagoflash">Pagoflash (Bolívares)</label>
                            </div>
                            <?php if (($config['pagoflash_entorno'] ?? '0') == '0'): // 0 = Calidad ?>
                            <div class="alert alert-warning small p-2 ms-4">
                                <i class="bi bi-cone-striped"></i> <strong>Modo Pruebas:</strong> PagoFlash está en modo de pruebas. No se procesarán pagos reales.
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php 
                        // Verifica si PayPal está activo y si tiene Client ID y Secret configurados
                        if (!empty($config['paypal_activo']) && !empty($config['paypal_client_id']) && !empty($config['paypal_client_secret'])): 
                        ?>
                            <?php if (empty($primer_metodo_activo)) $primer_metodo_activo = 'paypal'; ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="metodo_pago" id="pago_paypal" value="paypal" <?php if ($primer_metodo_activo == 'paypal') echo 'checked'; ?>>
                                <label class="form-check-label" for="pago_paypal">
                                    <i class="bi bi-paypal" style="color: #00457C; vertical-align: middle; font-size: 1.2rem;"></i> PayPal
                                </label>
                            </div>
                            <?php if (($config['paypal_entorno'] ?? '0') == '0'): // 0 = Sandbox ?>
                                <div class="alert alert-warning small p-2 ms-4">
                                    <i class="bi bi-cone-striped"></i> <strong>Modo Pruebas:</strong> Paypal está en modo de pruebas. Usarás dinero ficticio de Sandbox.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 mt-3" id="stripe-payment-form" style="display:none;">
                        <label for="card-element" class="form-label">Tarjeta de Crédito o Débito</label>
                        <div id="card-element" class="form-control"></div>
                        <div id="card-errors" role="alert" class="text-danger mt-2"></div>
                    </div>

                    <div id="paypal-button-container" class="mt-3" style="display: none;">
                    </div>

                    <input type="hidden" name="stripeToken" id="stripeToken">
                    
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg" id="realizar-pedido-btn-container">Realizar Pedido</button>
                    </div>
                </form>
        </div>

        <div class="order-summary">
            <h2>Resumen del Pedido</h2>
            <?php foreach ($productos_en_carrito as $item): ?>
                <div class="summary-item">
                    <span><?php echo htmlspecialchars($item['nombre']); ?> (x<?php echo $item['cantidad']; ?>)</span>
                    <strong><?php echo format_price($item['subtotal_usd']); ?></strong>
                </div>
            <?php endforeach; ?>
            <hr>
            <?php if ($descuento_usd > 0): ?>
            <div class="summary-item discount">
                <span>Descuento (<?php echo htmlspecialchars($cupon_aplicado['codigo']); ?>):</span>
                <strong>- <?php echo format_price($descuento_usd); ?></strong>
            </div>
            <?php endif; ?>
            <div class="summary-total">
                <strong>Total a Pagar:</strong>
                <strong><?php echo format_price($total_final_usd); ?></strong>
            </div>
        </div>
    </div>
</div>
</div>
</div>


<?php if (!empty($config['stripe_activo']) && !empty($config['stripe_public_key']) && !empty($config['stripe_secret_key'])): ?>
<script src="https://js.stripe.com/v3/"></script>
<?php endif; ?>
<?php
// Solo incluir el SDK si PayPal está activo y configurado
if (!empty($config['paypal_activo']) && !empty($config['paypal_client_id']) && !empty($config['paypal_client_secret'])): 
    // Obtener el Client ID desde la configuración
    $paypal_client_id = htmlspecialchars($config['paypal_client_id']); 
?>
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypal_client_id; ?>&currency=USD&disable-funding=venmo,card" data-sdk-integration-source="integrationbuilder_sc"></script> 
<?php endif; // Fin del if para incluir SDK ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos
    const paypalButtonContainer = document.getElementById('paypal-button-container');
    const standardCheckoutButtonContainer = document.getElementById('realizar-pedido-btn-container');
    const form = document.getElementById('payment-form');
    const stripeForm = document.getElementById('stripe-payment-form');
    const paymentRadios = document.querySelectorAll('input[name="metodo_pago"]');


// --- NUEVA FUNCIÓN HELPER PARA MOSTRAR MODAL DE ERROR ---
    const alertModalEl = document.getElementById('alertModal');
    const alertModalMessage = document.getElementById('alertModalMessage');
    let alertModalInstance = null;
    if (alertModalEl) {
        alertModalInstance = new bootstrap.Modal(alertModalEl);
    }

    function mostrarErrorModal(titulo, mensajeHTML) {
        if (alertModalInstance && alertModalMessage) {
            // (Opcional) Si tu modal tiene un título, así lo cambiarías:
            // const alertModalTitle = document.getElementById('alertModalTitle'); 
            // if(alertModalTitle) alertModalTitle.innerHTML = titulo;
            
            alertModalMessage.innerHTML = mensajeHTML; // Usamos innerHTML para permitir <br>
            alertModalInstance.show();
        } else {
            // Fallback si el modal no está disponible
            alert(titulo + "\n\n" + mensajeHTML.replace(/<br\s*\/?>/gi, "\n"));
        }
    }
    // --- FIN FUNCIÓN HELPER ---

    // --- FUNCIÓN UNIFICADA para mostrar/ocultar elementos de pago ---
    function togglePaymentDisplay() {
        const selectedRadio = document.querySelector('input[name="metodo_pago"]:checked');
        if (!selectedRadio) return; // Salir si no hay nada seleccionado
        
        const selectedMethod = selectedRadio.value;

        // Ocultar todo primero
        if (paypalButtonContainer) paypalButtonContainer.style.display = 'none';
        if (standardCheckoutButtonContainer) standardCheckoutButtonContainer.style.display = 'none';
        if (stripeForm) stripeForm.style.display = 'none';

        // Mostrar solo lo necesario
        console.log("selectedMethod es:", selectedMethod);
        if (selectedMethod === 'paypal') {
            console.log("paypalButtonContainer:", paypalButtonContainer);
            if (paypalButtonContainer) paypalButtonContainer.style.display = 'block';
            //standardCheckoutButtonContainer.style.display = 'none';
            // El botón "Realizar Pedido" (standardCheckoutButtonContainer) permanece oculto
            
        } else if (selectedMethod === 'stripe') {
            if (standardCheckoutButtonContainer) standardCheckoutButtonContainer.style.display = 'block';
            if (stripeForm) stripeForm.style.display = 'block';
            //standardCheckoutButtonContainer.style.display = 'block';
            
        } else { // Manual, Pagoflash, etc.
            if (standardCheckoutButtonContainer) standardCheckoutButtonContainer.style.display = 'block';
        }
    }

    // Asignar el evento a todos los radios
    paymentRadios.forEach(radio => radio.addEventListener('change', togglePaymentDisplay));
    
    // Ejecutar una vez al cargar la página para establecer el estado inicial
    togglePaymentDisplay(); 

    // --- Prevenir envío de formulario si PayPal está seleccionado ---
    form.addEventListener('submit', function(event) {
        const selectedMethod = document.querySelector('input[name="metodo_pago"]:checked').value;
        if (selectedMethod === 'paypal') {
            event.preventDefault(); // Detiene el envío
            console.log('Envío de formulario bloqueado, esperando acción de PayPal.');
        }
        // Para Stripe, el listener de Stripe (más abajo) manejará el event.preventDefault()
    });

    // --- Renderizar Botón PayPal ---
if (paypalButtonContainer && typeof paypal !== 'undefined') {
        paypal.Buttons({
            style: {
                layout: 'vertical',
                color:  'gold',
                shape:  'rect',
                label:  'pay',
                height: 40
            },

            // 1. Llamada al backend para CREAR la orden en PayPal
            createOrder: function(data, actions) {
                console.log('Creando orden PayPal...');
                const direccionManual = document.getElementById('direccion')?.value || '';
                const nombreCliente = document.getElementById('nombre')?.value || 'Cliente';

                // Usamos la URL amigable que definimos
                return fetch('<?php echo BASE_URL; ?>paypal/create-order', {
                    method: 'POST',
                     headers: {
                        'Content-Type': 'application/json'
                    },
                     body: JSON.stringify({
                         direccion: direccionManual,
                         nombre: nombreCliente
                     })
                })
                .then(res => {
                    if (!res.ok) {
                         // Si el backend da error 500 o 400, muestra el error
                         console.error('Error del backend al crear orden:', res.status, res.statusText);
                         return res.json().then(errData => { 
                             mostrarErrorModal('Error al Crear Orden', 'No se pudo iniciar el pago.<br>Detalles: ' + (errData.error || 'Error desconocido del servidor.'));
                             //alert('Error al crear orden: ' + (errData.error || 'Error desconocido'));
                             throw new Error(errData.error || 'Error desconocido'); 
                         });
                    }
                    return res.json();
                })
                .then(orderData => {
                    console.log('Orden PayPal creada:', orderData);
                    if (orderData && orderData.id) {
                        return orderData.id; // Devuelve el ID de la orden de PayPal al SDK
                    } else {
                         alert('No se pudo obtener el ID de la orden de PayPal.');
                         throw new Error('ID de orden no recibido del backend.');
                    }
                })
                .catch(error => {
                    console.error('Error en createOrder fetch:', error);
                    // Aquí se captura el error si el fetch falla (ej. 404 o 500)
                    // --- AQUÍ CAPTURAMOS EL ERROR "Expected an order id..." ---
                    // (O cualquier error del fetch o del .then anterior)
                    
                    // Verificamos si el error es el que esperamos
                    if (error.message.includes('Expected an order id')) {
                         mostrarErrorModal(
                            'Error de Configuración de PayPal', 
                            'El script del backend (<code>paypal_create_order.php</code>) falló.<br><br><strong>Pasos a seguir:</strong><br>1. Verifica las URLs amigables en <code>.htaccess</code>.<br>2. Revisa que <code>paypal_create_order.php</code> no tenga errores de PHP (revisa el log de errores del servidor).'
                         );
                    } else if (!error.message.includes('Error desconocido')) {
                         // No mostramos alerta duplicada si ya la mostramos en res.json()
                         mostrarErrorModal('Error Inesperado (createOrder)', error.message);
                    }
                    // Relanzamos el error para que PayPal lo maneje (cierre la ventana)
                    throw error;
                });
            },

            // 2. Llamada al backend para CAPTURAR el pago
            onApprove: function(data, actions) {
                console.log('Pago aprobado por el usuario:', data);
                // data contiene orderID, payerID, etc.
                
                // Usamos la URL amigable
                return fetch('<?php echo BASE_URL; ?>paypal/capture-order', {
                    method: 'POST',
                     headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ orderID: data.orderID }) // Envía el ID de la orden de PayPal
                })
                .then(res => {
                     if (!res.ok) {
                         console.error('Error del backend al capturar orden:', res.statusText);
                         return res.json().then(errData => { 
                             alert('Hubo un problema al finalizar tu pago: ' + (errData.error || 'Contacta a soporte'));
                             throw new Error(errData.error || 'Error desconocido'); 
                         });
                    }
                    return res.json();
                 })
                .then(details => {
                    console.log('Pago capturado:', details);
                    // El backend (paypal_capture_order.php) devuelve la URL de "gracias"
                    if (details.redirectUrl) {
                        window.location.href = details.redirectUrl;
                    } else {
                         alert('¡Pago completado!');
                         window.location.href = '<?php echo BASE_URL; ?>perfil'; // Fallback al perfil
                    }
                })
                 .catch(error => {
                     console.error('Error en onApprove fetch:', error);
                     alert('Ocurrió un error después de aprobar el pago. Revisa tu perfil o contacta a soporte.');
                 });
            },

            // 3. Manejar cancelación
            onCancel: function (data) {
                console.log('Pago cancelado por el usuario:', data);
                alert('Has cancelado el pago.');
            },

            // 4. Manejar errores del SDK
            onError: function (err) {
                console.error('Error de PayPal SDK:', err);
                //alert('Ocurrió un error inesperado con PayPal. Intenta de nuevo o elige otro método.');
                mostrarErrorModal('Error del SDK de PayPal', 'Ocurrió un error inesperado con PayPal. Intenta de nuevo o elige otro método.');
            }
        }).render('#paypal-button-container'); // Renderiza el botón
    }    
    // --- LÓGICA DE STRIPE ---
    <?php if (!empty($config['stripe_activo']) && !empty($config['stripe_public_key']) && !empty($config['stripe_secret_key'])): ?>
    const stripe = Stripe('<?php echo $config['stripe_public_key']; ?>');
    const elements = stripe.elements();
    const cardElement = elements.create('card');
    cardElement.mount('#card-element');

    // El listener de submit para Stripe ya está dentro del listener del formulario principal
    // (Añadido en la Petición #A018.3, pero tu archivo checkout.php subido no lo tenía,
    // así que lo baso en el archivo original de la tienda)
    
    // Asegurarse que el listener de Stripe esté separado del de PayPal
    form.addEventListener('submit', function(event) {
        const selectedMethod = document.querySelector('input[name="metodo_pago"]:checked').value;
        if (selectedMethod === 'stripe') {
            event.preventDefault(); // Prevenir envío normal para Stripe
            stripe.createToken(cardElement).then(function(result) {
                if (result.error) {
                    document.getElementById('card-errors').textContent = result.error.message;
                } else {
                    document.getElementById('stripeToken').value = result.token.id;
                    form.submit(); // Enviar formulario AHORA SÍ con el token de Stripe
                }
            });
        }
    });
    <?php endif; ?>
});
</script>
<?php require_once 'includes/footer.php'; ?>
