document.addEventListener('DOMContentLoaded', function() {
    // --- LÓGICA PARA MODALES DE ALERTA Y CONFIRMACIÓN (REUTILIZABLE) ---
    const mediaModalElement = document.getElementById('mediaLibraryModal');
    let currentTargetRow = null; 
    if (mediaModalElement) 
    {

    const mediaGrid = document.getElementById('media-library-grid');
    const uploadForm = document.getElementById('media-upload-form');
    const selectTabButton = document.getElementById('select-tab');
    const mediaModal = bootstrap.Modal.getOrCreateInstance(mediaModalElement);
    const addSelectedBtn = document.getElementById('add-selected-media-btn');

    // Función para cargar/refrescar las imágenes en la biblioteca
    const loadMediaImages = () => {
        mediaGrid.innerHTML = '<p class="text-center">Cargando...</p>';
        fetch(BASE_URL + 'admin/ajax_get_media.php')
            .then(response => response.json())
            .then(data => {
                let html = '';
                if (data.length === 0) {
                    html = '<p class="text-center">No hay imágenes en la biblioteca.</p>';
                } else {
                    data.forEach(item => {
                        html += `
                            <div class="col-6 col-md-4 col-lg-3 mb-3 media-item">
                                <div class="media-item-wrapper">
                                    <img src="${BASE_URL}uploads/${item.nombre_archivo}" class="img-fluid rounded border" alt="${item.alt_text}" data-filename="${item.nombre_archivo}">
                                </div>
                            </div>
                        `;
                    });
                }
                mediaGrid.innerHTML = html;
            });
    };

    // Cargar las imágenes cuando se abre el modal
    mediaModalElement.addEventListener('show.bs.modal', loadMediaImages);

    // Lógica para la subida de nuevos archivos desde el modal
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch(BASE_URL + 'admin/ajax_upload_media.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.uploaded_count > 0) {
                    // Si la subida fue exitosa, refrescamos la galería y cambiamos de pestaña
                    loadMediaImages();
                    const tab = new bootstrap.Tab(selectTabButton);
                    tab.show();
                    uploadForm.reset(); // Resetea el formulario y limpia el campo de archivos
                } else {
                    alert('Error: No se pudo subir el archivo.');
                }
            })
            .catch(error => console.error('Error al subir:', error));
        });
    }

    // Manejar la selección de imágenes (añadir/quitar la clase 'selected')
    mediaGrid.addEventListener('click', function(e) {
        const mediaItem = e.target.closest('.media-item');
        if (!mediaItem) return;

        // Identificamos si estamos en el formulario de la galería de inicio
        const heroGalleryForm = document.getElementById('hero-gallery-form');

        // Si estamos en la galería de inicio, solo permitimos una selección
        if (heroGalleryForm) {
            // Deseleccionamos todas las demás antes de seleccionar la nueva
            mediaGrid.querySelectorAll('.media-item.selected').forEach(el => {
                if (el !== mediaItem) {
                    el.classList.remove('selected');
                }
            });
        }
        mediaItem.classList.toggle('selected');
    });

    // Añadir las imágenes seleccionadas al formulario correspondiente
    addSelectedBtn.addEventListener('click', function() {
        const selectedImages = mediaGrid.querySelectorAll('.media-item.selected img');
        if (selectedImages.length === 0) {
            alert('No has seleccionado ninguna imagen.');
            return;
        }

        // Identificamos el formulario activo en la página principal
        const productForm = document.getElementById('product-form');
        const heroGalleryForm = document.getElementById('hero-gallery-form');
        const massProductForm = document.querySelector('.table-bordered');

        // Si estamos en el formulario de productos
        if (productForm) {
            const currentGallery = document.querySelector('.current-gallery');
            selectedImages.forEach(img => {
                const fileName = img.dataset.filename;
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'gallery_from_library[]';
                hiddenInput.value = fileName;
                productForm.appendChild(hiddenInput);

                const alertHtml = `<div class="alert alert-info alert-dismissible fade show my-1" role="alert">
                                    ✔️ ${fileName} (se añadirá al guardar)
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>`;
                currentGallery.insertAdjacentHTML('beforeend', alertHtml);
            });
        } 
        // Si estamos en el formulario de la galería de inicio
        else if (heroGalleryForm) {
            const selectedImage = selectedImages[0];
            const fileName = selectedImage.dataset.filename;
            document.getElementById('imagen_from_library').value = fileName;
        }
        // Si estamos en el editor masivo
        else if (massProductForm && currentTargetRow) {
            // Lógica para el editor masivo
            const productoId = currentTargetRow.dataset.productoId;
            const previewContainer = currentTargetRow.querySelector('.new-images-preview');
            const fileNames = Array.from(selectedImages).map(img => img.dataset.filename);
            
            // (Aquí va la lógica fetch para asociar las imágenes que ya teníamos)
        }
        
        mediaModal.hide();
    });

    //const addSelectedBtn = document.getElementById('add-selected-media-btn');
    const selectTab = document.getElementById('select-tab');
    const uploadTab = document.getElementById('upload-tab');

    if (addSelectedBtn && selectTab && uploadTab) {
        // Ocultar el botón si la pestaña de subida está activa
        uploadTab.addEventListener('show.bs.tab', function() {
            addSelectedBtn.style.display = 'none';
        });

        // Mostrar el botón si la pestaña de selección está activa
        selectTab.addEventListener('show.bs.tab', function() {
            addSelectedBtn.style.display = 'block';
        });
    }

        $("#select-tab").click(function(){
            $("#add-selected-media-btn").show();
        });
        $("#upload-tab").click(function(){
            $("#add-selected-media-btn").hide();
        });
    }

});