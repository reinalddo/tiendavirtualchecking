<?php
// respuesta_paypal.php
require_once 'includes/config.php'; // Incluye $pdo, $config, helpers.php
require_once 'includes/header.php'; // Incluye el layout

$status = $_GET['status'] ?? null;
$pedido_id = $_GET['pid'] ?? null;
$paypal_cancel = isset($_GET['paypal_cancel']);
$paypal_token = $_GET['token'] ?? null; // PayPal a veces añade 'token' y 'PayerID' en la URL de retorno

$titulo_mensaje = "Procesando tu pago...";
$cuerpo_mensaje = "Estamos confirmando tu pago con PayPal. Serás redirigido pronto.";
$alert_class = "alert-info";
$enlace_destino = BASE_URL . 'perfil'; // Por defecto, va al perfil

if ($paypal_cancel) {
    $titulo_mensaje = "Pago Cancelado";
    $cuerpo_mensaje = "Has cancelado el proceso de pago con PayPal. Tu pedido no ha sido completado.";
    $alert_class = "alert-warning";
    $enlace_destino = BASE_URL . 'checkout'; // Volver al checkout
} elseif ($status === 'success' && $pedido_id && $paypal_token) {
    // Éxito preliminar (onApprove debería haber redirigido ya a 'gracias')
    // Esto es más un respaldo o si el flujo JS fallara después de aprobar.
    $titulo_mensaje = "¡Pago Aprobado!";
    $cuerpo_mensaje = "Gracias por tu compra. Hemos recibido la confirmación de PayPal para tu pedido #" . htmlspecialchars($pedido_id) . ".";
    $alert_class = "alert-success";
    $enlace_destino = BASE_URL . 'gracias/' . $pedido_id; // Ir a la página de gracias específica
     // Podríamos intentar verificar el estado aquí de nuevo si fuera necesario, pero confiamos en onApprove/Webhook
} elseif ($status === 'failure') {
    $titulo_mensaje = "Pago Fallido";
    $cuerpo_mensaje = "Hubo un problema al procesar tu pago con PayPal. Por favor, inténtalo de nuevo o elige otro método.";
    $alert_class = "alert-danger";
    $enlace_destino = BASE_URL . 'checkout';
}
// Podríamos añadir manejo para 'pending' si fuera relevante

?>

<main>
    <div class="container py-5 text-center">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="alert <?php echo $alert_class; ?>" role="alert">
                    <h4 class="alert-heading"><?php echo $titulo_mensaje; ?></h4>
                    <p><?php echo $cuerpo_mensaje; ?></p>
                </div>
                <a href="<?php echo $enlace_destino; ?>" class="btn btn-primary mt-3">Continuar</a>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>