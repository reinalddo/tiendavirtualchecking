<?php
// admin/configuracion_sitio.php
// 1. TODA LA LÓGICA PHP PRIMERO
require_once '../includes/config.php';
verificar_sesion_admin();

// Lógica para guardar los cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Normalizamos los checkboxes (sin cambios)
    $_POST['pago_manual_activo'] = isset($_POST['pago_manual_activo']) ? '1' : '0';
    $_POST['stripe_activo'] = isset($_POST['stripe_activo']) ? '1' : '0';
    $_POST['payu_activo'] = isset($_POST['payu_activo']) ? '1' : '0';
    $_POST['payu_test_mode'] = isset($_POST['payu_test_mode']) ? '1' : '0';
    $_POST['mostrar_mapa_en_productos'] = isset($_POST['mostrar_mapa_en_productos']) ? '1' : '0';
    $_POST['facebook_activo'] = isset($_POST['facebook_activo']) ? '1' : '0';
    $_POST['instagram_activo'] = isset($_POST['instagram_activo']) ? '1' : '0';
    $_POST['twitter_activo'] = isset($_POST['twitter_activo']) ? '1' : '0';
    $_POST['tiktok_activo'] = isset($_POST['tiktok_activo']) ? '1' : '0';
    $_POST['youtube_activo'] = isset($_POST['youtube_activo']) ? '1' : '0';
    $_POST['mapa_principal_activo'] = isset($_POST['mapa_principal_activo']) ? '1' : '0';
    $_POST['tienda_email_footer_activo'] = isset($_POST['tienda_email_footer_activo']) ? '1' : '0';
    $_POST['whatsapp_activo'] = isset($_POST['whatsapp_activo']) ? '1' : '0';
    $_POST['pagoflash_activo'] = isset($_POST['pagoflash_activo']) ? '1' : '0';     
    $_POST['paypal_activo'] = isset($_POST['paypal_activo']) ? '1' : '0';

    // Guardamos todos los campos enviados (sin cambios)
    foreach ($_POST as $nombre_setting => $valor_setting) {
        // Asegurarse de que $pdo está disponible (debería estarlo por config.php)
        if (isset($pdo)) {
            $stmt = $pdo->prepare("INSERT INTO configuraciones (nombre_setting, valor_setting) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor_setting = ?");
            $stmt->execute([$nombre_setting, trim($valor_setting), trim($valor_setting)]);
        }
    }

    $_SESSION['mensaje_carrito'] = '¡Configuración guardada!';
    header('Location: ' . BASE_URL . 'panel/configuracion-sitio');
    exit();
}

// Obtener la configuración actual (sin cambios)
$config = [];
if (isset($pdo)) {
    $stmt_config = $pdo->query("SELECT nombre_setting, valor_setting FROM configuraciones");
    $config = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
}

// 2. UNA VEZ TERMINADA LA LÓGICA, INCLUIMOS EL HEADER
require_once '../includes/header.php';

// ===== INICIO: Definición de la función selected() =====
function selected($currentValue, $optionValue) {
    // Comprobación más robusta para evitar errores si $currentValue no existe
    if (isset($currentValue) && trim($currentValue) === trim($optionValue)) {
        echo 'selected';
    }
}
// ===== FIN: Definición de la función selected() =====

?>

<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Configuración del Sitio</h1>

        <form action="panel/configuracion-sitio" method="POST">

            <ul class="nav nav-tabs" id="configTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tienda-tab" data-bs-toggle="tab" data-bs-target="#tienda-pane" type="button" role="tab" aria-controls="tienda-pane" aria-selected="true">Datos de la Tienda</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pagos-tab" data-bs-toggle="tab" data-bs-target="#pagos-pane" type="button" role="tab" aria-controls="pagos-pane" aria-selected="false">Métodos de Pago</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="correo-tab" data-bs-toggle="tab" data-bs-target="#correo-pane" type="button" role="tab" aria-controls="correo-pane" aria-selected="false">Correo (SMTP)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="redes-tab" data-bs-toggle="tab" data-bs-target="#redes-pane" type="button" role="tab" aria-controls="redes-pane" aria-selected="false">Redes y Contacto</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="apariencia-tab" data-bs-toggle="tab" data-bs-target="#apariencia-pane" type="button" role="tab" aria-controls="apariencia-pane" aria-selected="false">Apariencia y Módulos</button>
                </li>
            </ul>

            <div class="tab-content card shadow-sm border-top-0" id="configTabsContent">

                <div class="tab-pane fade show active" id="tienda-pane" role="tabpanel" aria-labelledby="tienda-tab">
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

                <div class="tab-pane fade" id="pagos-pane" role="tabpanel" aria-labelledby="pagos-tab">
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
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="pagoflash_activo" name="pagoflash_activo" value="1" <?php echo !empty($config['pagoflash_activo']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="pagoflash_activo"><h5><i class="bi bi-credit-card-fill"></i> Pagoflash (Bolívares)</h5></label>
                        </div>
                        <div class="mb-3">
                            <label for="pagoflash_commerce_token" class="form-label">Commerce Token (Llave Secreta):</label>
                            <input type="password" id="pagoflash_commerce_token" name="pagoflash_commerce_token" class="form-control" value="<?php echo htmlspecialchars($config['pagoflash_commerce_token'] ?? ''); ?>">
                        </div>
                        <?php /* ?>
                        <div class="mb-3">
                            <label for="pagoflash_entorno" class="form-label">Entorno:</label>
                            <select id="pagoflash_entorno" name="pagoflash_entorno" class="form-select">
                                <option value="calidad" <?php if (($config['pagoflash_entorno'] ?? '') == 'calidad') echo 'selected'; ?>>Calidad (Pruebas)</option>
                                <option value="produccion" <?php if (($config['pagoflash_entorno'] ?? '') == 'produccion') echo 'selected'; ?>>Producción (Real)</option>
                            </select>
                            <small class="form-text text-muted">Asegúrate de que el Token coincida con el entorno seleccionado.</small>
                        </div>
                        <?php */ ?>
                        <hr>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="paypal_activo" name="paypal_activo" value="1" <?php echo !empty($config['paypal_activo']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="paypal_activo"><h5><i class="bi bi-paypal"></i> PayPal</h5></label>
                        </div>
                        <div class="mb-3">
                            <label for="paypal_client_id" class="form-label">PayPal Client ID:</label>
                            <input type="text" id="paypal_client_id" name="paypal_client_id" class="form-control" value="<?php echo htmlspecialchars($config['paypal_client_id'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="paypal_client_secret" class="form-label">PayPal Client Secret:</label>
                            <input type="password" id="paypal_client_secret" name="paypal_client_secret" class="form-control" value="<?php echo htmlspecialchars($config['paypal_client_secret'] ?? ''); ?>">
                        </div>
                        <?php /* ?>
                        <div class="mb-3">
                            <label for="paypal_entorno" class="form-label">Entorno:</label>
                            <select id="paypal_entorno" name="paypal_entorno" class="form-select">
                                <option value="sandbox" <?php if (($config['paypal_entorno'] ?? '') == 'sandbox') echo 'selected'; ?>>Sandbox (Pruebas)</option>
                                <option value="live" <?php if (($config['paypal_entorno'] ?? '') == 'live') echo 'selected'; ?>>Live (Producción)</option>
                            </select>
                            <small class="form-text text-muted">Asegúrate de que el Client ID y Secret coincidan con el entorno seleccionado.</small>
                        </div>
                        <?php */ ?>
                        <div class="mb-3">
                            <label for="paypal_webhook_id" class="form-label">Webhook ID:</label>
                            <input type="text" id="paypal_webhook_id" name="paypal_webhook_id" class="form-control" value="<?php echo htmlspecialchars($config['paypal_webhook_id'] ?? ''); ?>">
                             <small class="form-text text-muted">Obtenido desde el panel de desarrollador de PayPal al configurar el Webhook.</small>
                       </div>

                    </div>
                </div>

                <div class="tab-pane fade" id="correo-pane" role="tabpanel" aria-labelledby="correo-tab">
                    <div class="card-body">
                         <p class="text-muted">Configura los datos de tu cuenta de correo...</p>
                        <div class="mb-3">
                            <label for="smtp_host" class="form-label">Servidor SMTP (Host):</label>
                            <input type="text" id="smtp_host" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($config['smtp_host'] ?? ''); ?>">
                            <small class="form-text text-muted">Ej: smtp.hostinger.com, smtp.gmail.com</small>
                        </div>
                        <div class="mb-3">
                            <label for="smtp_email" class="form-label">Usuario (Email):</label>
                            <input type="email" id="smtp_email" name="smtp_email" class="form-control" value="<?php echo htmlspecialchars($config['smtp_email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="smtp_password" class="form-label">Contraseña:</label>
                            <input type="password" id="smtp_password" name="smtp_password" class="form-control" value="<?php echo htmlspecialchars($config['smtp_password'] ?? ''); ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="smtp_puerto" class="form-label">Puerto:</label>
                                <input type="number" id="smtp_puerto" name="smtp_puerto" class="form-control" value="<?php echo htmlspecialchars($config['smtp_puerto'] ?? '465'); ?>">
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
                        </div>
                   </div>
                </div>

                <div class="tab-pane fade" id="redes-pane" role="tabpanel" aria-labelledby="redes-tab">

                    <div class="card-body">
                        
                        <h6>Redes Sociales</h6>
                        <p class="text-muted">Introduce los enlaces a tus perfiles y activa los que deseas mostrar.</p>
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
                        <hr>
                        
                        <h6>Botón Flotante de WhatsApp</h6>
                        <div class="mb-3">
                            <label for="whatsapp_numero" class="form-label">Número de WhatsApp:</label>
                            <input type="text" id="whatsapp_numero" name="whatsapp_numero" class="form-control" value="<?php echo htmlspecialchars($config['whatsapp_numero'] ?? ''); ?>" placeholder="Ej: 584121234567">
                        </div>
                        <div class="mb-3">
                            <label for="whatsapp_mensaje" class="form-label">Mensaje Predeterminado:</label>
                            <textarea id="whatsapp_mensaje" name="whatsapp_mensaje" class="form-control" rows="2"><?php echo htmlspecialchars($config['whatsapp_mensaje'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="whatsapp_activo" name="whatsapp_activo" value="1" <?php echo !empty($config['whatsapp_activo']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="whatsapp_activo">Mostrar botón de WhatsApp</label>
                        </div>
                    </div>

                </div>

                <div class="tab-pane fade" id="apariencia-pane" role="tabpanel" aria-labelledby="apariencia-tab">
                    <div class="card-body">
                        <h6>Identidad del Sitio</h6>
                        <div class="mb-3">
                            <label for="tienda_nombre_apariencia" class="form-label">Nombre de la Tienda:</label> <input type="text" id="tienda_nombre_apariencia" name="tienda_nombre" class="form-control" value="<?php echo htmlspecialchars($config['tienda_nombre'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo de la Tienda:</label>
                            <div id="logo-preview-container" class="mb-2">
                                <img src="<?php echo !empty($config['tienda_logo']) ? BASE_URL . 'uploads/' . htmlspecialchars($config['tienda_logo']) : BASE_URL . 'admin/placeholder.png'; ?>" id="logo-preview" class="img-thumbnail" style="max-height: 100px;">
                            </div>
                            <input type="hidden" name="tienda_logo" id="tienda_logo_input" value="<?php echo htmlspecialchars($config['tienda_logo'] ?? ''); ?>">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#mediaLibraryModal">Seleccionar Logo</button>
                        </div>
                        <hr>
                        <h6>Productos Digitales</h6>
                        <div class="mb-3">
                            <div class="row align-items-center">
                                <label for="digital_download_limit" class="col-auto col-form-label">Límite de Descargas por Compra:</label>
                                <div class="col-auto">
                                     <input type="number" id="digital_download_limit" name="digital_download_limit" class="form-control" style="max-width: 80px;" value="<?php echo htmlspecialchars($config['digital_download_limit'] ?? '5'); ?>" min="0">
                                </div>
                            </div>                            <small class="form-text text-muted">
                                Establece cuántas veces un cliente puede descargar un producto digital comprado. Ingresa <strong>0</strong> para descargas ilimitadas.
                                <br><strong>Ventajas del límite:</strong> Previene el abuso y la compartición masiva de enlaces.
                                <br><strong>Desventajas del límite:</strong> Puede requerir soporte si un cliente legítimo necesita más descargas.
                            </small>
                        </div>
                        <hr>
                        <h6>Personalización Header</h6>
                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label for="header_bg_color" class="form-label">Fondo Header:</label>
                                <div class="input-group">
                                    <input type="text" id="header_bg_color" name="header_bg_color" class="form-control" value="<?php echo htmlspecialchars($config['header_bg_color'] ?? '#343a40'); ?>" placeholder="Color (#rrggbb) o Gradiente CSS">
                                    <input type="color" id="header_bg_color_picker" class="form-control form-control-color" value="<?php echo (strpos($config['header_bg_color'] ?? '#343a40', 'gradient') === false) ? htmlspecialchars($config['header_bg_color'] ?? '#343a40') : '#343a40'; ?>" title="Elige un color sólido">
                                </div>
                                <small class="form-text text-muted">Usa el selector para color sólido o escribe un gradiente CSS.</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                    <label for="header_font_color" class="form-label">Color Fuente Header:</label>
                                    <div class="input-group">
                                        <input type="text" id="header_font_color" name="header_font_color" class="form-control" value="<?php echo htmlspecialchars($config['header_font_color'] ?? '#ffffff'); ?>" placeholder="#ffffff">
                                        <input type="color" id="header_font_color_picker" class="form-control form-control-color" value="<?php echo htmlspecialchars($config['header_font_color'] ?? '#ffffff'); ?>" title="Elige color">
                                    </div>                            </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="header_font_size" class="form-label">Tamaño Fuente Header (px):</label>
                                    <input type="number" id="header_font_size" name="header_font_size" class="form-control" value="<?php echo htmlspecialchars($config['header_font_size'] ?? '16'); ?>" min="10" max="30">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="header_font_family" class="form-label">Fuente Header:</label>
                                    <select name="header_font_family" id="header_font_family" class="form-select">
                                        <option value='system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"' <?php selected($config['header_font_family'] ?? '', 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"'); ?>>Por Defecto (Sistema)</option>
                                        <option value='Arial, Helvetica, sans-serif' <?php selected($config['header_font_family'] ?? '', 'Arial, Helvetica, sans-serif'); ?>>Arial</option>
                                        <option value='"Times New Roman", Times, serif' <?php selected($config['header_font_family'] ?? '', '"Times New Roman", Times, serif'); ?>>Times New Roman</option>
                                        <option value='"Courier New", Courier, monospace' <?php selected($config['header_font_family'] ?? '', '"Courier New", Courier, monospace'); ?>>Courier New</option>
                                        <option value='Verdana, Geneva, sans-serif' <?php selected($config['header_font_family'] ?? '', 'Verdana, Geneva, sans-serif'); ?>>Verdana</option>
                                        <option value='Georgia, serif' <?php selected($config['header_font_family'] ?? '', 'Georgia, serif'); ?>>Georgia</option>
                                        <option value='"Palatino Linotype", "Book Antiqua", Palatino, serif' <?php selected($config['header_font_family'] ?? '', '"Palatino Linotype", "Book Antiqua", Palatino, serif'); ?>>Palatino</option>
                                        <option value='"Lucida Sans Unicode", "Lucida Grande", sans-serif' <?php selected($config['header_font_family'] ?? '', '"Lucida Sans Unicode", "Lucida Grande", sans-serif'); ?>>Lucida Sans</option>
                                        <option value='Tahoma, Geneva, sans-serif' <?php selected($config['header_font_family'] ?? '', 'Tahoma, Geneva, sans-serif'); ?>>Tahoma</option>
                                        <option value='"Trebuchet MS", Helvetica, sans-serif' <?php selected($config['header_font_family'] ?? '', '"Trebuchet MS", Helvetica, sans-serif'); ?>>Trebuchet MS</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="dropdown_bg_color_picker" class="form-label">Color Fondo Menú Desplegable:</label>
                                    <div class="input-group"> <input type="text" id="dropdown_bg_color" name="dropdown_bg_color" class="form-control" value="<?php echo htmlspecialchars($config['dropdown_bg_color'] ?? '#ffffff'); ?>" placeholder="#ffffff">
                                        <input type="color" id="dropdown_bg_color_picker" class="form-control form-control-color" value="<?php echo htmlspecialchars($config['dropdown_bg_color'] ?? '#ffffff'); ?>" title="Elige color">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="dropdown_font_color" class="form-label">Color Fuente Menú Desplegable:</label>
                                    <div class="input-group">
                                        <input type="text" id="dropdown_font_color" name="dropdown_font_color" class="form-control" value="<?php echo htmlspecialchars($config['dropdown_font_color'] ?? '#212529'); ?>" placeholder="#212529">
                                        <input type="color" id="dropdown_font_color_picker" class="form-control form-control-color" value="<?php echo htmlspecialchars($config['dropdown_font_color'] ?? '#212529'); ?>" title="Elige color">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="search_button_bg_color" class="form-label">Fondo Botón Buscar:</label>
                                <div class="input-group">
                                    <input type="text" id="search_button_bg_color" name="search_button_bg_color" class="form-control" value="<?php echo htmlspecialchars($config['search_button_bg_color'] ?? '#198754'); ?>" placeholder="#198754">
                                    <input type="color" id="search_button_bg_color_picker" class="form-control form-control-color color-picker-input" value="<?php echo htmlspecialchars($config['search_button_bg_color'] ?? '#198754'); ?>" title="Elige color">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="search_button_font_color" class="form-label">Fuente Botón Buscar:</label>
                                <div class="input-group">
                                    <input type="text" id="search_button_font_color" name="search_button_font_color" class="form-control" value="<?php echo htmlspecialchars($config['search_button_font_color'] ?? '#ffffff'); ?>" placeholder="#ffffff">
                                    <input type="color" id="search_button_font_color_picker" class="form-control form-control-color color-picker-input" value="<?php echo htmlspecialchars($config['search_button_font_color'] ?? '#ffffff'); ?>" title="Elige color">
                                </div>
                            </div>
                        </div>
                        <hr>

                        <h6>Personalización Filtro Categorías (Inicio)</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cat_filter_bg_color" class="form-label">Fondo Botón Normal:</label>
                                <div class="input-group">
                                    <input type="text" id="cat_filter_bg_color" name="cat_filter_bg_color" class="form-control" value="<?php echo htmlspecialchars($config['cat_filter_bg_color'] ?? '#f8f9fa'); ?>" placeholder="#f8f9fa">
                                    <input type="color" id="cat_filter_bg_color_picker" class="form-control form-control-color color-picker-input" value="<?php echo htmlspecialchars($config['cat_filter_bg_color'] ?? '#f8f9fa'); ?>" title="Elige color">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cat_filter_font_color" class="form-label">Fuente Botón Normal:</label>
                                <div class="input-group">
                                    <input type="text" id="cat_filter_font_color" name="cat_filter_font_color" class="form-control" value="<?php echo htmlspecialchars($config['cat_filter_font_color'] ?? '#6c757d'); ?>" placeholder="#6c757d">
                                    <input type="color" id="cat_filter_font_color_picker" class="form-control form-control-color color-picker-input" value="<?php echo htmlspecialchars($config['cat_filter_font_color'] ?? '#6c757d'); ?>" title="Elige color">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cat_filter_active_bg_color" class="form-label">Fondo Botón Activo:</label>
                                <div class="input-group">
                                    <input type="text" id="cat_filter_active_bg_color" name="cat_filter_active_bg_color" class="form-control" value="<?php echo htmlspecialchars($config['cat_filter_active_bg_color'] ?? '#343a40'); ?>" placeholder="#343a40">
                                    <input type="color" id="cat_filter_active_bg_color_picker" class="form-control form-control-color color-picker-input" value="<?php echo htmlspecialchars($config['cat_filter_active_bg_color'] ?? '#343a40'); ?>" title="Elige color">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cat_filter_active_font_color" class="form-label">Fuente Botón Activo:</label>
                                <div class="input-group">
                                    <input type="text" id="cat_filter_active_font_color" name="cat_filter_active_font_color" class="form-control" value="<?php echo htmlspecialchars($config['cat_filter_active_font_color'] ?? '#ffffff'); ?>" placeholder="#ffffff">
                                    <input type="color" id="cat_filter_active_font_color_picker" class="form-control form-control-color color-picker-input" value="<?php echo htmlspecialchars($config['cat_filter_active_font_color'] ?? '#ffffff'); ?>" title="Elige color">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6>Personalización Botón Principal</h6>
                        <p class="text-muted small">Define el estilo de los botones de acción principales (Añadir carrito, Pagar, etc.).</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="primary_button_bg_color" class="form-label">Fondo Botón:</label>
                                <div class="input-group">
                                    <input type="text" id="primary_button_bg_color" name="primary_button_bg_color" class="form-control" value="<?php echo htmlspecialchars($config['primary_button_bg_color'] ?? '#0d6efd'); ?>" placeholder="Color (#rrggbb) o Gradiente CSS">
                                    <input type="color" id="primary_button_bg_color_picker" class="form-control form-control-color color-picker-input" value="<?php echo (strpos($config['primary_button_bg_color'] ?? '#0d6efd', 'gradient') === false) ? htmlspecialchars($config['primary_button_bg_color'] ?? '#0d6efd') : '#0d6efd'; ?>" title="Elige un color sólido">
                                </div>
                                <small class="form-text text-muted">Usa el selector o escribe un gradiente CSS.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="primary_button_font_color" class="form-label">Fuente Botón:</label>
                                <div class="input-group">
                                    <input type="text" id="primary_button_font_color" name="primary_button_font_color" class="form-control" value="<?php echo htmlspecialchars($config['primary_button_font_color'] ?? '#ffffff'); ?>" placeholder="#ffffff">
                                    <input type="color" id="primary_button_font_color_picker" class="form-control form-control-color color-picker-input" value="<?php echo htmlspecialchars($config['primary_button_font_color'] ?? '#ffffff'); ?>" title="Elige color">
                                </div>
                            </div>
                        </div>
                         <hr>
                        
                        <h6>Personalización Footer</h6>
                        <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="footer_bg_color" class="form-label">Fondo Footer:</label>
                            <div class="input-group">
                                <input type="text" id="footer_bg_color" name="footer_bg_color" class="form-control" value="<?php echo htmlspecialchars($config['footer_bg_color'] ?? '#212529'); ?>" placeholder="Color (#rrggbb) o Gradiente CSS">
                                <input type="color" id="footer_bg_color_picker" class="form-control form-control-color" value="<?php echo (strpos($config['footer_bg_color'] ?? '#212529', 'gradient') === false) ? htmlspecialchars($config['footer_bg_color'] ?? '#212529') : '#212529'; ?>" title="Elige un color sólido">
                            </div>
                            <small class="form-text text-muted">Usa el selector para color sólido o escribe un gradiente CSS.</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="footer_font_color" class="form-label">Color Fuente Footer:</label>
                            <div class="input-group">
                                <input type="text" id="footer_font_color" name="footer_font_color" class="form-control" value="<?php echo htmlspecialchars($config['footer_font_color'] ?? '#ffffff'); ?>" placeholder="#ffffff">
                                <input type="color" id="footer_font_color_picker" class="form-control form-control-color" value="<?php echo htmlspecialchars($config['footer_font_color'] ?? '#ffffff'); ?>" title="Elige color">
                            </div>
                        </div>
                        </div>
                        <div class="row">
                           <div class="col-md-6 mb-3">
                                <label for="footer_font_size" class="form-label">Tamaño Fuente Footer (px):</label>
                                <input type="number" id="footer_font_size" name="footer_font_size" class="form-control" value="<?php echo htmlspecialchars($config['footer_font_size'] ?? '14'); ?>" min="10" max="24">
                            </div>
                        </div>
                        <hr>
                        <h6>Footer (Pie de página)</h6>
                         <div class="mb-3">
                            <label for="tienda_descripcion_corta" class="form-label">Descripción Corta:</label>
                            <textarea name="tienda_descripcion_corta" id="tienda_descripcion_corta" class="form-control" rows="3"><?php echo htmlspecialchars($config['tienda_descripcion_corta'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="tienda_email_footer" class="form-label">Email de Contacto:</label>
                            <input type="email" id="tienda_email_footer" name="tienda_email_footer" class="form-control" value="<?php echo htmlspecialchars($config['tienda_email_footer'] ?? ''); ?>">
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="tienda_email_footer_activo" name="tienda_email_footer_activo" value="1" <?php echo !empty($config['tienda_email_footer_activo']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="tienda_email_footer_activo">Mostrar email en el pie de página</label>
                        </div>
                        <div class="mt-3">
                             <button type="button" id="reset-styles-btn" class="btn btn-outline-secondary btn-sm">Restaurar Colores/Fuentes por Defecto</button>
                         </div>
                        <hr>
                        <h6>Ubicación (Google Maps)</h6>
                        <div class="mb-3">
                            <label for="mapa_principal" class="form-label">Código iFrame de Google Maps:</label>
                            <textarea name="mapa_principal" id="mapa_principal" class="form-control" rows="4"><?php echo htmlspecialchars($config['mapa_principal'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="mapa_principal_activo" name="mapa_principal_activo" value="1" <?php echo !empty($config['mapa_principal_activo']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="mapa_principal_activo">Mostrar mapa en la página de contacto</label>
                        </div>
                        <hr>
                        <h6>Integración con Google (Login)</h6>
                        <p class="text-muted">Credenciales obtenidas desde la Consola de APIs de Google para permitir el inicio de sesión con Google.</p>
                        <div class="mb-3">
                            <label for="google_client_id" class="form-label">Google Client ID:</label>
                            <input type="text" id="google_client_id" name="google_client_id" class="form-control" value="<?php echo htmlspecialchars($config['google_client_id'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="google_client_secret" class="form-label">Google Client Secret:</label>
                            <input type="password" id="google_client_secret" name="google_client_secret" class="form-control" value="<?php echo htmlspecialchars($config['google_client_secret'] ?? ''); ?>">
                        </div>

                    </div>
                </div>

            </div> <div class="card shadow-sm mt-4">
                <div class="card-body text-end">
                    <button type="submit" class="btn btn-primary">Guardar Toda la Configuración</button>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Default style values
    const defaultStyles = {
        header_bg_color: '#343a40',
        header_font_color: '#ffffff',
        header_font_size: '16',
        header_font_family: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
        footer_bg_color: '#212529',
        footer_font_color: '#ffffff',
        footer_font_size: '14',
        dropdown_bg_color: '#ffffff',
        dropdown_font_color: '#212529',
        search_button_bg_color: '#198754', // Default Bootstrap success
        search_button_font_color: '#ffffff',  // Default white text
        cat_filter_bg_color: '#f8f9fa',
        cat_filter_font_color: '#6c757d',
        cat_filter_active_bg_color: '#343a40',
        cat_filter_active_font_color: '#ffffff',
        primary_button_bg_color: '#0d6efd', // Bootstrap primary
        primary_button_font_color: '#ffffff'
    };

    // ===== MOVED HERE: Define compoundPickers in the outer scope =====
const compoundPickers = [
        { pickerId: 'header_bg_color_picker', inputId: 'header_bg_color', allowGradient: true },
        { pickerId: 'header_font_color_picker', inputId: 'header_font_color', allowGradient: false },
        { pickerId: 'dropdown_bg_color_picker', inputId: 'dropdown_bg_color', allowGradient: false },
        { pickerId: 'dropdown_font_color_picker', inputId: 'dropdown_font_color', allowGradient: false },
        { pickerId: 'footer_bg_color_picker', inputId: 'footer_bg_color', allowGradient: true },
        { pickerId: 'footer_font_color_picker', inputId: 'footer_font_color', allowGradient: false },
        { pickerId: 'search_button_bg_color_picker', inputId: 'search_button_bg_color', allowGradient: false }, 
        { pickerId: 'search_button_font_color_picker', inputId: 'search_button_font_color', allowGradient: false }, 
        { pickerId: 'cat_filter_bg_color_picker', inputId: 'cat_filter_bg_color', allowGradient: false },
        { pickerId: 'cat_filter_font_color_picker', inputId: 'cat_filter_font_color', allowGradient: false },
        { pickerId: 'cat_filter_active_bg_color_picker', inputId: 'cat_filter_active_bg_color', allowGradient: false },
        { pickerId: 'cat_filter_active_font_color_picker', inputId: 'cat_filter_active_font_color', allowGradient: false },
        { pickerId: 'primary_button_bg_color_picker', inputId: 'primary_button_bg_color', allowGradient: true }, // Permite gradiente
        { pickerId: 'primary_button_font_color_picker', inputId: 'primary_button_font_color', allowGradient: false }
    ];    // =================================================================

    // --- Function to update pickers/inputs ---
    function syncPickersAndInputs() {
        // Now it uses the compoundPickers defined outside
        compoundPickers.forEach(p => {
            const picker = document.getElementById(p.pickerId);
            const input = document.getElementById(p.inputId);
            if (picker && input) {
                // Remove previous listeners to prevent duplicates if called multiple times
                picker.removeEventListener('input', updateTextInput);
                input.removeEventListener('input', updateColorPicker);

                // Add listeners
                picker.addEventListener('input', updateTextInput);
                input.addEventListener('input', updateColorPicker);

                 // Initial sync
                 syncInitial(picker, input, p.allowGradient);
            }
        });
    }

    // Helper functions for sync logic (no changes needed inside these)
    function updateTextInput(event) {
        const inputId = event.target.id.replace('_picker', '');
        const input = document.getElementById(inputId);
        if(input) input.value = event.target.value;
    }
    function updateColorPicker(event) {
         const pickerId = event.target.id + '_picker';
         const picker = document.getElementById(pickerId);
         if(picker && event.target.value.startsWith('#') && (event.target.value.length === 7 || event.target.value.length === 4)) {
              picker.value = event.target.value;
         }
    }
     function syncInitial(picker, input, allowGradient) {
         const initialValue = input.value;
            if (initialValue) {
                if (!initialValue.includes('gradient') || !allowGradient) {
                    picker.value = initialValue;
                    // Check if browser corrected the hex and update input if needed
                    if(picker.value.toLowerCase() !== initialValue.toLowerCase() && (initialValue.startsWith('#') && (initialValue.length === 7 || initialValue.length === 4))) {
                         input.value = picker.value;
                    }
                }
            } else {
                 input.value = picker.value;
            }
     }

    // --- Reset Button Logic ---
    const resetButton = document.getElementById('reset-styles-btn');
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            if (confirm('¿Restaurar los estilos de color y fuente a los valores por defecto? Los cambios no guardados se perderán.')) {
                for (const key in defaultStyles) {
                    const inputElement = document.getElementById(key) || document.querySelector(`[name="${key}"]`);
                    if (inputElement) {
                        inputElement.value = defaultStyles[key];
                        // Trigger change only for selects to update their display
                        if (inputElement.tagName === 'SELECT') {
                             inputElement.dispatchEvent(new Event('change'));
                        }
                        // For text/hidden/number inputs, syncPickersAndInputs will handle update
                    }
                }
                 // Re-sync all pickers/inputs visually after setting default values in the form fields
                 syncPickersAndInputs();
                alert('Estilos restaurados a los valores por defecto. Haz clic en "Guardar" para aplicarlos.');
            }
        });
    }

    // Initial sync on page load
    syncPickersAndInputs();
});
</script>

<script src="<?php echo BASE_URL; ?>js/media-library-modal.js"></script>
<?php require_once '../includes/footer.php'; ?>