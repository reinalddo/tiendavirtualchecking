<?php
// admin/configuracion_sitio.php
// 1. TODA LA LÓGICA PHP PRIMERO
require_once '../includes/config.php';
verificar_sesion_admin();

// Lógica para guardar los cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Normalizamos los checkboxes
    $_POST['pago_manual_activo'] = isset($_POST['pago_manual_activo']) ? '1' : '0';
    $_POST['stripe_activo'] = isset($_POST['stripe_activo']) ? '1' : '0';
    $_POST['payu_activo'] = isset($_POST['payu_activo']) ? '1' : '0';
    $_POST['payu_test_mode'] = isset($_POST['payu_test_mode']) ? '1' : '0';
    $_POST['mostrar_mapa_en_productos'] = isset($_POST['mostrar_mapa_en_productos']) ? '1' : '0';

    // Normalizamos los checkboxes de redes sociales
    $_POST['facebook_activo'] = isset($_POST['facebook_activo']) ? '1' : '0';
    $_POST['instagram_activo'] = isset($_POST['instagram_activo']) ? '1' : '0';
    $_POST['twitter_activo'] = isset($_POST['twitter_activo']) ? '1' : '0';
    $_POST['tiktok_activo'] = isset($_POST['tiktok_activo']) ? '1' : '0';
    $_POST['youtube_activo'] = isset($_POST['youtube_activo']) ? '1' : '0';

    $_POST['mapa_principal_activo'] = isset($_POST['mapa_principal_activo']) ? '1' : '0';
    $_POST['tienda_email_footer_activo'] = isset($_POST['tienda_email_footer_activo']) ? '1' : '0';
    $_POST['whatsapp_activo'] = isset($_POST['whatsapp_activo']) ? '1' : '0';

    // Guardamos todos los campos enviados
    foreach ($_POST as $nombre_setting => $valor_setting) {
        $stmt = $pdo->prepare("UPDATE configuraciones SET valor_setting = ? WHERE nombre_setting = ?");
        $stmt->execute([trim($valor_setting), $nombre_setting]);
    }

    $_SESSION['mensaje_carrito'] = '¡Configuración guardada!';
    header('Location: ' . BASE_URL . 'panel/configuracion-sitio');
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
        <form action="panel/configuracion-sitio" method="POST">
            <div class="row">
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-lg-12">
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

                        <div class="col-lg-12">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header"><h5 class="my-0 fw-normal">Redes Sociales</h5></div>
                                <div class="card-body">
                                    <p class="text-muted">Introduce los enlaces a tus perfiles y activa los que deseas mostrar en la página de contacto y en el pie de página.</p>

                                    <?php
                                    $redes_sociales = [
                                        'facebook' => ['nombre' => 'Facebook', 'icono' => 'bi-facebook'],
                                        'instagram' => ['nombre' => 'Instagram', 'icono' => 'bi-instagram'],
                                        'twitter' => ['nombre' => 'Twitter / X', 'icono' => 'bi-twitter-x'],
                                        'tiktok' => ['nombre' => 'TikTok', 'icono' => 'bi-tiktok'],
                                        'youtube' => ['nombre' => 'YouTube', 'icono' => 'bi-youtube']
                                    ];

                                    foreach ($redes_sociales as $key => $red):
                                    ?>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text"><i class="bi <?php echo $red['icono']; ?>"></i></span>
                                        <input type="url" name="<?php echo $key; ?>_url" class="form-control" placeholder="URL de <?php echo $red['nombre']; ?>" value="<?php echo htmlspecialchars($config[$key.'_url'] ?? ''); ?>">
                                        <div class="input-group-text">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="<?php echo $key; ?>_activo" value="1" <?php echo !empty($config[$key.'_activo']) ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header"><h5 class="my-0 fw-normal">Identidad del Sitio (Header y Footer)</h5></div>
                                <div class="card-body">

                                    <div class="mb-3">
                                        <label for="tienda_nombre" class="form-label">Nombre de la Tienda:</label>
                                        <input type="text" id="tienda_nombre" name="tienda_nombre" class="form-control" value="<?php echo htmlspecialchars($config['tienda_nombre'] ?? ''); ?>">
                                        <small class="form-text text-muted">Aparecerá en el encabezado y pie de página.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tienda_descripcion_corta" class="form-label">Descripción Corta (Footer):</label>
                                        <textarea name="tienda_descripcion_corta" id="tienda_descripcion_corta" class="form-control" rows="3"><?php echo htmlspecialchars($config['tienda_descripcion_corta'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tienda_email_footer" class="form-label">Email de Contacto (Footer):</label>
                                        <input type="email" id="tienda_email_footer" name="tienda_email_footer" class="form-control" value="<?php echo htmlspecialchars($config['tienda_email_footer'] ?? ''); ?>">
                                    </div>

                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="tienda_email_footer_activo" value="1" <?php echo !empty($config['tienda_email_footer_activo']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="tienda_email_footer_activo">Mostrar email en el pie de página</label>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-lg-12">
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
                                    <div style="display:none">
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

                        <div class="col-lg-12">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header"><h5 class="my-0 fw-normal">Configuración de Correo (SMTP)</h5></div>
                                <div class="card-body">
                                    <p class="text-muted">Configura los datos de tu cuenta de correo para que la tienda pueda enviar notificaciones de forma segura y evitar la carpeta de Spam.</p>
                                    
                                    <div class="mb-3">
                                        <label for="smtp_host" class="form-label">Servidor SMTP (Host):</label>
                                        <input type="text" id="smtp_host" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($config['smtp_host'] ?? ''); ?>">
                                        <small class="form-text text-muted">Ej: smtp.hostinger.com, smtp.gmail.com</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="smtp_email" class="form-label">Usuario (Email):</label>
                                        <input type="email" id="smtp_email" name="smtp_email" class="form-control" value="<?php echo htmlspecialchars($config['smtp_email'] ?? ''); ?>">
                                        <small class="form-text text-muted">La dirección de correo completa que enviará los emails.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="smtp_password" class="form-label">Contraseña:</label>
                                        <input type="password" id="smtp_password" name="smtp_password" class="form-control" value="<?php echo htmlspecialchars($config['smtp_password'] ?? ''); ?>">
                                        <small class="form-text text-muted">La contraseña de la cuenta de correo. Si usas Gmail, debe ser una <a href="https://support.google.com/accounts/answer/185833?hl=es" target="_blank">contraseña de aplicación</a>.</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="smtp_puerto" class="form-label">Puerto:</label>
                                            <input type="number" id="smtp_puerto" name="smtp_puerto" class="form-control" value="<?php echo htmlspecialchars($config['smtp_puerto'] ?? '465'); ?>">
                                            <small class="form-text text-muted">Comúnmente 465 (SSL) o 587 (TLS).</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="smtp_seguridad" class="form-label">Seguridad:</label>
                                            <select id="smtp_seguridad" name="smtp_seguridad" class="form-select">
                                                <option value="ssl" <?php if (($config['smtp_seguridad'] ?? '') == 'ssl') echo 'selected'; ?>>SSL</option>
                                                <option value="tls" <?php if (($config['smtp_seguridad'] ?? '') == 'tls') echo 'selected'; ?>>TLS</option>
                                            </select>
                                        </div>
                                    </div>

                                    <hr>
                                    <h6 class="mt-4">Correo de Contacto</h6>
                                    <div class="mb-3">
                                        <label for="email_contacto" class="form-label">Email para Recibir Mensajes de Contacto:</label>
                                        <input type="email" id="email_contacto" name="email_contacto" class="form-control" value="<?php echo htmlspecialchars($config['email_contacto'] ?? ''); ?>">
                                        <small class="form-text text-muted">El correo donde llegarán las consultas del formulario de contacto.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header"><h5 class="my-0 fw-normal">Ubicación (Google Maps)</h5></div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="mapa_principal" class="form-label">Código iFrame de Google Maps:</label>
                                        <textarea name="mapa_principal" id="mapa_principal" class="form-control" rows="5"><?php echo htmlspecialchars($config['mapa_principal'] ?? ''); ?></textarea>
                                        <small class="form-text text-muted">Ve a Google Maps, busca tu negocio, haz clic en "Compartir", luego en "Insertar un mapa" y copia el código HTML aquí.</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="mapa_principal_activo" value="1" <?php echo !empty($config['mapa_principal_activo']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="mapa_principal_activo">Mostrar mapa en la página de contacto</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <div class="col-lg-12">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header"><h5 class="my-0 fw-normal">Botón Flotante de WhatsApp</h5></div>
                            <div class="card-body">
                                <p class="text-muted">Activa un botón en la esquina inferior derecha de tu sitio para que los clientes puedan contactarte directamente por WhatsApp.</p>

                                <div class="mb-3">
                                    <label for="whatsapp_numero" class="form-label">Número de Teléfono de WhatsApp:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
                                        <input type="text" id="whatsapp_numero" name="whatsapp_numero" class="form-control" value="<?php echo htmlspecialchars($config['whatsapp_numero'] ?? ''); ?>" placeholder="Ej: 584121234567">
                                    </div>
                                    <small class="form-text text-muted">Incluye el código de país sin el símbolo "+" ni ceros al inicio.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="whatsapp_mensaje" class="form-label">Mensaje Predeterminado:</label>
                                    <textarea id="whatsapp_mensaje" name="whatsapp_mensaje" class="form-control" rows="3"><?php echo htmlspecialchars($config['whatsapp_mensaje'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted">Este será el mensaje que aparecerá escrito cuando el cliente haga clic en el botón.</small>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="whatsapp_activo" value="1" <?php echo !empty($config['whatsapp_activo']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="whatsapp_activo">Mostrar botón de WhatsApp en la tienda</label>
                                </div>
                            </div>
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