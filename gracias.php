<?php
// gracias.php
require_once 'includes/header.php';

$pedido_id = isset($_GET['pedido_id']) ? htmlspecialchars($_GET['pedido_id']) : 'desconocido';
?>
<div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
<div class="thank-you-container" style="text-align: center; padding: 50px;">
    <h1>🎉 ¡Gracias por tu compra! 🎉</h1>
    <p>Hemos recibido tu pedido y lo estamos pºrocesando.</p>
    <p>Tu número de pedido es: <strong><?php echo $pedido_id; ?></strong></p>
    <p>Recibirás una confirmación por correo electrónico pronto.</p>
    <a href="index.php" class="button">Volver a la Tienda</a>
</div>
</div>
</div>
</div>
<?php
require_once 'includes/footer.php';
?>