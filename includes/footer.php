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
                <h6 class="text-uppercase fw-bold">Mi Tienda</h6>
                <hr class="mb-4 mt-0 d-inline-block mx-auto" style="width: 60px; background-color: #7c4dff; height: 2px"/>
                <p>
                    Aquí puedes escribir una breve descripción de tu tienda, tu misión o los productos que ofreces.
                </p>
            </div>

            <div class="col-md-2 mx-auto mb-4">
                <h6 class="text-uppercase fw-bold">Enlaces</h6>
                <hr class="mb-4 mt-0 d-inline-block mx-auto" style="width: 60px; background-color: #7c4dff; height: 2px"/>
                <p><a href="<?php echo BASE_URL; ?>index.php" class="text-white">Inicio</a></p>
                <p><a href="<?php echo BASE_URL; ?>productos.php" class="text-white">Productos</a></p>
                <p><a href="<?php echo BASE_URL; ?>contacto.php" class="text-white">Contacto</a></p>
            </div>

            <div class="col-md-4 mx-auto mb-4">
                <h6 class="text-uppercase fw-bold">Contacto</h6>
                <hr class="mb-4 mt-0 d-inline-block mx-auto" style="width: 60px; background-color: #7c4dff; height: 2px"/>
                <p><i class="bi bi-geo-alt-fill me-3"></i> San Cristóbal, Táchira</p>
                <p><i class="bi bi-envelope-fill me-3"></i> info@mitienda.com</p>
                <div class="mt-3">
                    <a href="#" class="text-white me-4"><i class="bi bi-facebook fs-4"></i></a>
                    <a href="#" class="text-white me-4"><i class="bi bi-instagram fs-4"></i></a>
                    <a href="#" class="text-white me-4"><i class="bi bi-twitter fs-4"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2)">
        © <?php echo date('Y'); ?> Mi Tienda Web. Todos los derechos reservados.
    </div>
</footer>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>js/cart-dropdown.js"></script>
<script src="<?php echo BASE_URL; ?>js/main.js"></script>

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