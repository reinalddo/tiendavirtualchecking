<?php
// admin/configuracion_sitio.php
// 1. TODA LA LÓGICA PHP PRIMERO
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/helpers.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Lógica para guardar los cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Normalizamos los checkboxes
    $_POST['pago_manual_activo'] = isset($_POST['pago_manual_activo']) ? '1' : '0';
    $_POST['stripe_activo'] = isset($_POST['stripe_activo']) ? '1' : '0';
    $_POST['payu_activo'] = isset($_POST['payu_activo']) ? '1' : '0';
    $_POST['payu_test_mode'] = isset($_POST['payu_test_mode']) ? '1' : '0';
    $_POST['mostrar_mapa_en_productos'] = isset($_POST['mostrar_mapa_en_productos']) ? '1' : '0';

    // Guardamos todos los campos enviados
    foreach ($_POST as $nombre_setting => $valor_setting) {
        $stmt = $pdo->prepare("UPDATE configuraciones SET valor_setting = ? WHERE nombre_setting = ?");
        $stmt->execute([trim($valor_setting), $nombre_setting]);
    }

    $_SESSION['mensaje_carrito'] = '¡Configuración guardada!';
    header('Location: configuracion_sitio.php');
    exit();
}

// Obtener la configuración actual
$stmt_config = $pdo->query("SELECT * FROM configuraciones");
$configuraciones_raw = $stmt_config->fetchAll(PDO::FETCH_ASSOC);
$config = [];
foreach ($configuraciones_raw as $setting) {
    $config[$setting['nombre_setting']] = $setting['valor_setting'];
}

// 2. UNA VEZ TERMINADA LA LÓGICA, INCLUIMOS EL HEADER
require_once '../includes/header.php';

?>

<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Configuración del Sitio</h1>
        <form action="configuracion_sitio.php" method="POST">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header"><h5 class="my-0 fw-normal">Datos Fiscales y de la Tienda</h5></div>
                        <div class="card-body">

                            <div class="mb-3">
                                <label for="tienda_razon_social" class="form-label">Razón Social:</label>
                                <input type="text" id="tienda_razon_social" name="tienda_razon_social" class="form-control" value="<?php echo htmlspecialchars($config['tienda_razon_social'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="tienda_rif" class="form-label">RIF:</label>
                                <input type="text" id="tienda_rif" name="tienda_rif" class="form-control" value="<?php echo htmlspecialchars($config['tienda_rif'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Logo de la Tienda:</label>
                                <div id="logo-preview-container" class="mb-2">
                                    <?php if (!empty($config['tienda_logo'])): ?>
                                        <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($config['tienda_logo']); ?>" id="logo-preview" class="img-thumbnail" style="max-height: 100px;">
                                    <?php else: ?>
                                        <img src="<?php echo BASE_URL; ?>admin/placeholder.png" id="logo-preview" class="img-thumbnail" style="max-height: 100px;">
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="tienda_logo" id="tienda_logo_input" value="<?php echo htmlspecialchars($config['tienda_logo'] ?? ''); ?>">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#mediaLibraryModal">
                                    Seleccionar Logo de la Biblioteca
                                </button>
                            </div>
                            <div class="mb-3">
                                <label for="tienda_domicilio_fiscal" class="form-label">Domicilio Fiscal:</label>
                                <textarea id="tienda_domicilio_fiscal" name="tienda_domicilio_fiscal" class="form-control" rows="3"><?php echo htmlspecialchars($config['tienda_domicilio_fiscal'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="tienda_telefono" class="form-label">Teléfono de Contacto:</label>
                                <input type="text" id="tienda_telefono" name="tienda_telefono" class="form-control" value="<?php echo htmlspecialchars($config['tienda_telefono'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="iva_porcentaje" class="form-label">Porcentaje de IVA (%):</label>
                                <input type="number" id="iva_porcentaje" name="iva_porcentaje" class="form-control" step="0.01" value="<?php echo htmlspecialchars($config['iva_porcentaje'] ?? '16.00'); ?>">
                            </div>
                        
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header"><h5 class="my-0 fw-normal">Métodos de Pago</h5></div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="pago_manual_activo" name="pago_manual_activo" value="1" <?php echo !empty($config['pago_manual_activo']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pago_manual_activo"><h5><i class="bi bi-cash-coin"></i> Ventas Manuales</h5></label>
                            </div>
                            <hr>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="stripe_activo" name="stripe_activo" value="1" <?php echo !empty($config['stripe_activo']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="stripe_activo"><h5><i class="bi bi-stripe"></i> Stripe (Tarjetas Internacionales)</h5></label>
                            </div>
                            <div class="mb-3">
                                <label for="stripe_public_key" class="form-label">Stripe Publishable Key:</label>
                                <input type="text" id="stripe_public_key" name="stripe_public_key" class="form-control" value="<?php echo htmlspecialchars($config['stripe_public_key'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="stripe_secret_key" class="form-label">Stripe Secret Key:</label>
                                <input type="password" id="stripe_secret_key" name="stripe_secret_key" class="form-control" value="<?php echo htmlspecialchars($config['stripe_secret_key'] ?? ''); ?>">
                            </div>
                            <hr>

                            <div class="d-flex justify-content-between align-items-center">
                                <h5><i class="bi bi-credit-card-2-back-fill"></i> PayU Latam</h5>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="payu_activo" value="1" <?php echo !empty($config['payu_activo']) ? 'checked' : ''; ?>>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Merchant ID:</label>
                                <input type="text" name="payu_merchant_id" class="form-control" value="<?php echo htmlspecialchars($config['payu_merchant_id'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">API Key:</label>
                                <input type="password" name="payu_api_key" class="form-control" value="<?php echo htmlspecialchars($config['payu_api_key'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account ID:</label>
                                <input type="text" name="payu_account_id" class="form-control" value="<?php echo htmlspecialchars($config['payu_account_id'] ?? ''); ?>">
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="payu_test_mode" value="1" <?php echo !empty($config['payu_test_mode']) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Modo de Pruebas (Test)</label>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body text-end">
                    <button type="submit" class="btn btn-primary">Guardar Toda la Configuración</button>
                </div>
            </div>
        </form>
    </div>
</main>
<script src="<?php echo BASE_URL; ?>js/media-library-modal.js"></script>
<?php require_once '../includes/footer.php'; ?>