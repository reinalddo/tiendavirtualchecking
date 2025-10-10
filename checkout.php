<?php
require_once 'includes/config.php'; // config.php inicia la sesión
require_once 'includes/db_connection.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['redirect_url'] = 'checkout.php';
    $_SESSION['mensaje_carrito'] = "Error: Debes iniciar sesión para proceder al pago.";
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}
if (empty($_SESSION['carrito'])) {
    header('Location: ' . BASE_URL . 'ver_carrito.php');
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
if (!empty($carrito)) {
    $ids = array_keys($carrito);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, nombre, precio_usd, precio_descuento FROM productos WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $productos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($productos_db as $producto) {
        $cantidad = $carrito[$producto['id']];
        $precio_a_usar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0) ? $producto['precio_descuento'] : $producto['precio_usd'];
        $subtotal_usd = $precio_a_usar * $cantidad;
        $total_carrito_usd += $subtotal_usd;
        $producto['cantidad'] = $cantidad;
        $producto['subtotal_usd'] = $subtotal_usd;
        $productos_en_carrito[] = $producto;
    }
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
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección de Envío:</label>
                        <textarea id="direccion" name="direccion" class="form-control" rows="4" required></textarea>
                    </div>
                    
                    <h4 class="mt-4">Método de Pago</h4>


                    <div class="payment-methods">
                        <?php $primer_metodo_activo = ''; ?>

                        <?php if (!empty($config['pago_manual_activo'])): ?>
                            <?php if (empty($primer_metodo_activo)) $primer_metodo_activo = 'manual'; ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="metodo_pago" id="pago_manual" value="manual" <?php if ($primer_metodo_activo == 'manual') echo 'checked'; ?>>
                                <label class="form-check-label" for="pago_manual">Pago Manual (Transferencia / Pago Móvil)</label>
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
                    </div>

                    <div class="mb-3 mt-3" id="stripe-payment-form" style="display:none;">
                        <label for="card-element" class="form-label">Tarjeta de Crédito o Débito</label>
                        <div id="card-element" class="form-control"></div>
                        <div id="card-errors" role="alert" class="text-danger mt-2"></div>
                    </div>

                    <input type="hidden" name="stripeToken" id="stripeToken">
                    
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Realizar Pedido</button>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lógica para mostrar/ocultar el formulario de Stripe
    const stripeForm = document.getElementById('stripe-payment-form');
    const paymentRadios = document.querySelectorAll('input[name="metodo_pago"]');

    function togglePaymentForms() {
        const selectedMethod = document.querySelector('input[name="metodo_pago"]:checked').value;
        if (stripeForm) {
            stripeForm.style.display = (selectedMethod === 'stripe') ? 'block' : 'none';
        }
    }

    paymentRadios.forEach(radio => radio.addEventListener('change', togglePaymentForms));
    togglePaymentForms(); // Ejecutar al cargar

    // --- LÓGICA DE STRIPE ---
    <?php if (!empty($config['stripe_activo']) && !empty($config['stripe_public_key']) && !empty($config['stripe_secret_key'])): ?>
    const stripe = Stripe('<?php echo $config['stripe_public_key']; ?>');
    const elements = stripe.elements();
    const cardElement = elements.create('card');
    cardElement.mount('#card-element');

    const form = document.getElementById('payment-form');
    form.addEventListener('submit', function(event) {
        const selectedMethod = document.querySelector('input[name="metodo_pago"]:checked').value;
        if (selectedMethod === 'stripe') {
            event.preventDefault();
            stripe.createToken(cardElement).then(function(result) {
                if (result.error) {
                    document.getElementById('card-errors').textContent = result.error.message;
                } else {
                    document.getElementById('stripeToken').value = result.token.id;
                    form.submit();
                }
            });
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>
