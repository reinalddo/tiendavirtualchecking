<?php
// restablecer_contrasena.php
require_once 'includes/config.php';

// Obtener tokens de la URL
$selector = $_GET['selector'] ?? '';
$validator = $_GET['validator'] ?? '';

// Validar que los tokens tengan el formato esperado (hexadecimal)
if (empty($selector) || empty($validator) || !ctype_xdigit($selector) || !ctype_xdigit($validator)) {
    // Si los tokens no son válidos o faltan, redirigir o mostrar error
    $_SESSION['mensaje_restablecimiento'] = 'Error: Enlace de restablecimiento inválido o caducado.';
    header('Location: ' . BASE_URL . 'solicitar-restablecimiento');
    exit();
}

require_once 'includes/header.php';
?>

<main>
    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Establecer Nueva Contraseña</h2>

                        <?php
                        // Mostrar mensajes de error si los hay desde el procesamiento
                        if (isset($_SESSION['mensaje_error_reset'])) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['mensaje_error_reset']) . '</div>';
                            unset($_SESSION['mensaje_error_reset']);
                        }
                        ?>

                        <form action="procesar-restablecimiento" method="POST" id="reset-form">
                            <input type="hidden" name="selector" value="<?php echo htmlspecialchars($selector); ?>">
                            <input type="hidden" name="validator" value="<?php echo htmlspecialchars($validator); ?>">

                            <div class="mb-3 position-relative">
                                <label for="password_nueva" class="form-label">Nueva Contraseña:</label>
                                <input type="password" id="password_nueva" name="password_nueva" class="form-control" required>
                                <i class="bi bi-eye-slash-fill toggle-password" style="cursor: pointer; position: absolute; right: 10px; top: 38px;"></i>
                            </div>
                             <div id="password-feedback" class="form-text mb-2"></div>


                            <div class="mb-3 position-relative">
                                <label for="password_confirm" class="form-label">Confirmar Nueva Contraseña:</label>
                                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                                <i class="bi bi-eye-slash-fill toggle-password" style="cursor: pointer; position: absolute; right: 10px; top: 38px;"></i>
                            </div>
                            <div id="password-confirm-feedback" class="form-text mb-2"></div>


                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Guardar Contraseña</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="<?php echo BASE_URL; ?>js/registro-validation.js"></script> 
<?php require_once 'includes/footer.php'; ?>