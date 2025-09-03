<?php
// respuesta_payu.php
require_once 'includes/header.php';

$estado_transaccion = $_REQUEST['transactionState'] ?? '';
$mensaje = '';

switch($estado_transaccion) {
    case '4':
        $mensaje = "¡Tu pago ha sido aprobado! Gracias por tu compra.";
        break;
    case '6':
        $mensaje = "Tu pago ha sido rechazado. Por favor, intenta de nuevo o contacta a tu banco.";
        break;
    case '5':
        $mensaje = "Tu transacción ha expirado.";
        break;
    default:
        $mensaje = "El estado de tu pago está pendiente. Te notificaremos cuando sea confirmado.";
        break;
}
?>
<div class="container py-5 text-center">
    <h1>Respuesta de la Transacción</h1>
    <p class="fs-4"><?php echo htmlspecialchars($mensaje); ?></p>
    <a href="perfil.php" class="btn btn-primary mt-3">Ir a Mis Pedidos</a>
</div>
<?php require_once 'includes/footer.php'; ?>