<?php
// pago_confirmado.php
require_once 'includes/header.php';
$pedido_id = $_GET['pedido_id'] ?? 'desconocido';
// Aquí podrías añadir lógica para verificar el estado real del pago si es necesario.
?>
<div class="container py-5 text-center">
    <h1>🎉 ¡Gracias por tu compra! 🎉</h1>
    <p>Estamos esperando la confirmación final de Mercado Pago.</p>
    <p>Tu número de pedido es: <strong><?php echo htmlspecialchars($pedido_id); ?></strong></p>
    <p>Recibirás una notificación y un correo cuando tu pago sea aprobado.</p>
    <a href="perfil.php" class="btn btn-primary mt-3">Ver Mis Pedidos</a>
</div>
<?php require_once 'includes/footer.php'; ?>