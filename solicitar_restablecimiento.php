<?php
// solicitar_restablecimiento.php
require_once 'includes/header.php'; // Incluye el layout general
?>

<main>
    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Restablecer Contraseña</h2>
                        <p class="text-center text-muted">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>

                        <?php
                        // Mostrar mensajes de éxito o error si existen
                        if (isset($_SESSION['mensaje_restablecimiento'])) {
                            $message_type = strpos(strtolower($_SESSION['mensaje_restablecimiento']), 'error') === false ? 'alert-success' : 'alert-danger';
                            echo '<div class="alert ' . $message_type . '">' . htmlspecialchars($_SESSION['mensaje_restablecimiento']) . '</div>';
                            unset($_SESSION['mensaje_restablecimiento']);
                        }
                        ?>

                        <form action="procesar-solicitud-restablecimiento" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico:</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Enviar Enlace</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <a href="login">Volver a Iniciar Sesión</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>