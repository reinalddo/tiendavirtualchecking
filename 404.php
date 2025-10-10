<?php
// 404.php
require_once 'includes/config.php';
// Enviamos el encabezado HTTP 404 Not Found
header("HTTP/1.0 404 Not Found");
require_once 'includes/header.php';
?>

<div class="container text-center py-5">
    <h1 class="display-1">404</h1>
    <h2 class="mb-4">Página no encontrada</h2>
    <p class="lead mb-4">Lo sentimos, no hemos podido encontrar la página que buscas. Es posible que el enlace esté roto o que la página haya sido eliminada.</p>
    <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">Volver a la Página de Inicio</a>
</div>

<?php
require_once 'includes/footer.php';
?>