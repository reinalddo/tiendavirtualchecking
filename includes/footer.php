</main> 

<div id="customConfirmModal" class="custom-modal-overlay">
    <div class="custom-modal-box">
        <h4>Confirmar Acción</h4>
        <p>¿Estás seguro de que quieres eliminar este elemento?</p>
        <div class="modal-buttons">
            <button id="customConfirmCancel" class="btn btn-secondary">Cancelar</button>
            <button id="customConfirmButton" class="btn btn-danger">Confirmar y Eliminar</button>
        </div>
    </div>
</div>

<div class="modal fade" id="alertModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <p id="alertModalMessage" class="fs-5 my-3"></p>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="renameModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Renombrar Archivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>El nuevo nombre del archivo se basará en el "Nombre/Alt text" que hayas escrito.</p>
                <p><strong>¿Estás seguro de que quieres continuar?</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="renameModalButton">Renombrar</button>
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-white pt-5 pb-4">
    <div class="container text-center text-md-start">
        <div class="row">
            <div class="col-md-4 mx-auto mb-4">

                <h6 class="text-uppercase fw-bold footer-dw d-flex align-items-center">
                    <?php
                    $logo_footer_path = !empty($config['tienda_logo']) ? 'uploads/' . $config['tienda_logo'] : null;
                    if ($logo_footer_path && file_exists($_SERVER['DOCUMENT_ROOT'] . BASE_URL . $logo_footer_path)):
                    ?>
                        <img src="<?php echo BASE_URL . $logo_footer_path; ?>" alt="<?php echo htmlspecialchars($config['tienda_nombre'] ?? 'Mi Tienda'); ?> Logo" style="height: 40px; margin-right: 10px;">
                    <?php endif; ?>
                    <?php echo htmlspecialchars($config['tienda_nombre'] ?? 'Mi Tienda'); ?>
                </h6>

                <hr class="mb-4 mt-0 d-inline-block mx-auto" style="width: 60px; background-color: #7c4dff; height: 2px"/>
                <p><?php echo nl2br(htmlspecialchars($config['tienda_descripcion_corta'] ?? '')); ?></p>
            </div>

            <div class="col-md-2 mx-auto mb-4">
                <h6 class="text-uppercase fw-bold footer-dw">Enlaces</h6>
                <hr class="mb-4 mt-0 d-inline-block mx-auto" style="width: 60px; background-color: #7c4dff; height: 2px"/>
                <p><a href="/" class="text-white">Inicio</a></p>
                <p><a href="productos" class="text-white">Productos</a></p>
                <p><a href="contacto" class="text-white">Contacto</a></p>
            </div>

            <div class="col-md-4 mx-auto mb-4">
                <h6 class="text-uppercase fw-bold footer-dw">Contacto</h6>
                <hr class="mb-4 mt-0 d-inline-block mx-auto" style="width: 60px; background-color: #7c4dff; height: 2px"/>
                <p><i class="bi bi-geo-alt-fill me-3"></i> <?php echo htmlspecialchars($config['tienda_domicilio_fiscal'] ?? ''); ?></p>
                
                <?php if (!empty($config['tienda_email_footer_activo']) && !empty($config['tienda_email_footer'])): ?>
                    <p><i class="bi bi-envelope-fill me-3"></i> <?php echo htmlspecialchars($config['tienda_email_footer']); ?></p>
                <?php endif; ?>

                <div class="mt-3">
                    <?php
                    $redes_sociales_footer = [
                        'facebook' => ['icono' => 'bi-facebook'],
                        'instagram' => ['icono' => 'bi-instagram'],
                        'twitter' => ['icono' => 'bi-twitter-x'],
                        'tiktok' => ['icono' => 'bi-tiktok'],
                        'youtube' => ['icono' => 'bi-youtube']
                    ];
                    foreach ($redes_sociales_footer as $key => $red) {
                        if (!empty($config[$key.'_activo']) && !empty($config[$key.'_url'])) {
                            echo '<a href="' . htmlspecialchars($config[$key.'_url']) . '" target="_blank" class="text-white me-4"><i class="bi ' . $red['icono'] . ' fs-4"></i></a>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2)">
            © <?php echo date('Y'); ?> <?php echo htmlspecialchars($config['tienda_razon_social'] ?? 'Mi Tienda Web'); ?>. Todos los derechos reservados.
    </div>

    <?php
    // Mostramos el botón solo si es cliente, si está activo y si se ha introducido un número
    if (!empty($config['whatsapp_activo']) && !empty($config['whatsapp_numero']) && $_SESSION['usuario_rol'] != 'admin'):
        // Limpiamos el número para asegurar que solo contenga dígitos
        $numero_whatsapp_limpio = preg_replace('/\D/', '', $config['whatsapp_numero']);
        // Preparamos el mensaje para la URL, codificando los caracteres especiales
        $mensaje_whatsapp_codificado = urlencode($config['whatsapp_mensaje'] ?? '¡Hola! Tengo una pregunta.');
    ?>
        <a href="https://wa.me/<?php echo $numero_whatsapp_limpio; ?>?text=<?php echo $mensaje_whatsapp_codificado; ?>" class="whatsapp-float" target="_blank">
            <i class="bi bi-whatsapp"></i>
        </a>
    <?php endif; ?>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/cart-dropdown.js"></script>
<script src="js/main.js"></script>
<script src="js/notificaciones.js"></script>
<script src="js/live-search.js"></script>

<div class="modal fade" id="mediaLibraryModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Biblioteca de Medios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="mediaTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="select-tab" data-bs-toggle="tab" data-bs-target="#select-pane" type="button">Seleccionar Archivo</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload-pane" type="button">Subir Archivo Nuevo</button>
                    </li>
                </ul>
                <div class="tab-content" id="mediaTabsContent">
                    <div class="tab-pane fade show active p-3" id="select-pane">
                        <div class="row" id="media-library-grid">
                            </div>
                    </div>
                    <div class="tab-pane fade p-3" id="upload-pane">
                        <form id="media-upload-form">
                            <div class="mb-3">
                                <label for="modal_media_files" class="form-label">Seleccionar archivo(s) para subir:</label>
                                <input class="form-control" type="file" name="media_files[]" id="modal_media_files" multiple>
                            </div>
                            <button type="submit" class="btn btn-primary">Subir a la Biblioteca</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="add-selected-media-btn">Añadir Seleccionadas</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>