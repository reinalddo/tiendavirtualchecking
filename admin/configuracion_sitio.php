<?php
// admin/configuracion_sitio.php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

// Lógica para guardar los cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Usamos un bucle para guardar todas las configuraciones enviadas
    foreach ($_POST as $nombre_setting => $valor_setting) {
        if ($nombre_setting === 'mostrar_mapa_en_productos') continue; // Caso especial para el checkbox
        
        $stmt = $pdo->prepare("UPDATE configuraciones SET valor_setting = ? WHERE nombre_setting = ?");
        $stmt->execute([trim($valor_setting), $nombre_setting]);
    }

    // Manejo especial para el checkbox
    $mostrar_mapa = isset($_POST['mostrar_mapa_en_productos']) ? '1' : '0';
    $stmt_mapa = $pdo->prepare("UPDATE configuraciones SET valor_setting = ? WHERE nombre_setting = 'mostrar_mapa_en_productos'");
    $stmt_mapa->execute([$mostrar_mapa]);

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
require_once '../includes/header.php';

?>

<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Configuración del Sitio</h1>
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="my-0 fw-normal">Ajustes Generales y Fiscales</h5>
            </div>
            <div class="card-body">
                <form action="configuracion_sitio.php" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Datos Fiscales de la Tienda</h5>
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

                        <div class="col-md-6">
                            <h5>Otros Ajustes</h5>
                            <div class="mb-3">
                                <label for="metodos_pago_activos" class="form-label">Métodos de Pago Activos:</label>
                                <select name="metodos_pago_activos" id="metodos_pago_activos" class="form-select">
                                    <option value="ambos" <?php if (($config['metodos_pago_activos'] ?? '') == 'ambos') echo 'selected'; ?>>Ambos (Tarjeta y Manual)</option>
                                    <option value="stripe" <?php if (($config['metodos_pago_activos'] ?? '') == 'stripe') echo 'selected'; ?>>Solo Ventas con Tarjeta</option>
                                    <option value="manual" <?php if (($config['metodos_pago_activos'] ?? '') == 'manual') echo 'selected'; ?>>Solo Ventas Manuales</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="mapa_principal" class="form-label">Código del Mapa Principal (Google Maps Embed):</label>
                                <textarea id="mapa_principal" name="mapa_principal" class="form-control" rows="6"><?php echo htmlspecialchars($config['mapa_principal'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="mostrar_mapa_en_productos" value="1" id="mostrar_mapa" <?php echo !empty($config['mostrar_mapa_en_productos']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="mostrar_mapa">
                                    Mostrar mapa en todas las páginas de productos
                                </label>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                </form>
            </div>
        </div>
    </div>
</main>
<script src="<?php echo BASE_URL; ?>js/media-library-modal.js"></script>
<?php require_once '../includes/footer.php'; ?>