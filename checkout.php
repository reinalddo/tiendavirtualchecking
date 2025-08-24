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
$stmt_config = $pdo->query("SELECT valor_setting FROM configuraciones WHERE nombre_setting = 'metodos_pago_activos'");
$metodos_pago_activos = $stmt_config->fetchColumn();

$carrito = $_SESSION['carrito'] ?? [];
$productos_en_carrito = [];
$total_carrito_usd = 0;

if (!empty($carrito)) {
    $ids = array_keys($carrito);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Obtenemos los precios de la base de datos
    $stmt = $pdo->prepare("SELECT id, nombre, precio_usd, precio_descuento FROM productos WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $productos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos_db as $producto) {
        $cantidad = $carrito[$producto['id']];
        
        // --- LÓGICA DE DESCUENTO ---
        // Usamos el precio de descuento si está disponible y es mayor a cero
        $precio_a_usar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0) 
                         ? $producto['precio_descuento'] 
                         : $producto['precio_usd'];
        
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
                <form action="procesar_pedido.php" method="POST" id="payment-form">
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
                        <?php if ($metodos_pago_activos == 'stripe' || $metodos_pago_activos == 'ambos'): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="metodo_pago" id="pago_stripe" value="stripe" checked>
                            <label class="form-check-label" for="pago_stripe">Pagar con Tarjeta</label>
                        </div>
                        <?php endif; ?>

                        <?php if ($metodos_pago_activos == 'manual' || $metodos_pago_activos == 'ambos'): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="metodo_pago" id="pago_manual" value="manual" <?php if ($metodos_pago_activos == 'manual') echo 'checked'; ?>>
                            <label class="form-check-label" for="pago_manual">Pago Manual (Transferencia)</label>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3" id="stripe-payment-form">
                        <label for="card-element" class="form-label">Tarjeta de Crédito o Débito</label>
                        <div id="card-element">
                          </div>
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



<script src="https://js.stripe.com/v3/"></script>
<script>
    // Creamos una instancia de Stripe con tu clave publicable
    const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
    const elements = stripe.elements();
    
    // Estilos para el elemento de la tarjeta
    const style = {
        base: { fontSize: '16px', color: '#32325d' }
    };
    
    // Creamos y montamos el elemento de la tarjeta
    const card = elements.create('card', {style: style});
    card.mount('#card-element');

    // Manejar errores de validación en tiempo real
    card.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Manejar el envío del formulario
    const form = document.getElementById('payment-form');
    form.addEventListener('submit', function(event) {
        // Verificamos qué método de pago está seleccionado
        const paymentMethod = document.querySelector('input[name="metodo_pago"]:checked').value;

        // Si el método es 'stripe', validamos la tarjeta.
        // Si es 'manual', el formulario se enviará normalmente.
        if (paymentMethod === 'stripe') {
            event.preventDefault(); // Prevenimos el envío para que Stripe procese la tarjeta

            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    // Informar al usuario si hubo un error en la tarjeta
                    const errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    // Si todo va bien, insertamos el token y enviamos el formulario
                    document.getElementById('stripeToken').value = result.token.id;
                    form.submit();
                }
            });
        }
        // Si el método de pago es 'manual', no hacemos nada aquí y permitimos
        // que el formulario se envíe de la forma tradicional.
    });

    function toggleStripeForm() {
        const paymentMethod = document.querySelector('input[name="metodo_pago"]:checked').value;
        document.getElementById('stripe-payment-form').style.display = paymentMethod === 'stripe' ? 'block' : 'none';
    }

    // Ocultar/mostrar al cambiar la selección
    document.querySelectorAll('input[name="metodo_pago"]').forEach(radio => {
        radio.addEventListener('change', toggleStripeForm);
    });

    // Ocultar/mostrar al cargar la página
    document.addEventListener('DOMContentLoaded', toggleStripeForm);


</script>

<?php require_once 'includes/footer.php'; ?>
