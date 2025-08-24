document.addEventListener('DOMContentLoaded', function() {

    // Identificamos en qué formulario estamos.
    const productForm = document.getElementById('product-form');
    const heroGalleryForm = document.getElementById('hero-gallery-form');
    const currentForm = productForm || heroGalleryForm;

    // Si no estamos en una página con uno de estos formularios, no hacemos nada.
    if (!currentForm) {
        return;
    }

    const mediaModalElement = document.getElementById('mediaLibraryModal');
    if (mediaModalElement) {
        const mediaGrid = document.getElementById('media-library-grid');
        const addSelectedBtn = document.getElementById('add-selected-media-btn');
        const mediaModal = bootstrap.Modal.getOrCreateInstance(mediaModalElement);
        
        // Instancias de los modales de alerta y confirmación
        const alertModalElement = document.getElementById('alertModal');
        const alertModal = bootstrap.Modal.getOrCreateInstance(alertModalElement);
        const alertModalMessage = document.getElementById('alertModalMessage');

        // Cargar imágenes cuando se abre el modal
        mediaModalElement.addEventListener('show.bs.modal', function() {
            mediaGrid.innerHTML = '<p class="text-center">Cargando imágenes...</p>';
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
        });

        // Manejar la selección de imágenes (añadir/quitar la clase 'selected')
        mediaGrid.addEventListener('click', function(e) {
            const mediaItem = e.target.closest('.media-item');
            if (!mediaItem) return;

            // Lógica para seleccionar solo una imagen en la galería de inicio
            if (currentForm.id === 'hero-gallery-form') {
                const selectedImage = selectedImages[0];
                const fileName = selectedImage.dataset.filename;
                document.getElementById('imagen_from_library').value = fileName;

                // Reemplazar el alert() con el modal
                const alertModalMessage = document.getElementById('alertModalMessage');
                const alertModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('alertModal'));
                alertModalMessage.textContent = `Imagen "${fileName}" seleccionada. Haz clic en "Guardar Slide" para confirmar.`;
                alertModal.show();
            }
            mediaItem.classList.toggle('selected');
        });

        // Añadir las imágenes seleccionadas al formulario correspondiente
        addSelectedBtn.addEventListener('click', function() {
            const selectedImages = mediaGrid.querySelectorAll('.media-item.selected img');
            if (selectedImages.length === 0) {
                alertModalMessage.textContent = 'No has seleccionado ninguna imagen.';
                alertModal.show();
                return;
            }

            // Si estamos en el formulario de productos (permite selección múltiple)
            if (currentForm.id === 'product-form') {
                const currentGallery = document.querySelector('.current-gallery');
                selectedImages.forEach(img => {
                    const fileName = img.dataset.filename;
                    
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'gallery_from_library[]';
                    hiddenInput.value = fileName;
                    currentForm.appendChild(hiddenInput);

                    const alertHtml = `<div class="alert alert-info alert-dismissible fade show my-1" role="alert">
                                          ✔️ ${fileName} (se añadirá al guardar)
                                          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                       </div>`;
                    currentGallery.insertAdjacentHTML('beforeend', alertHtml);
                });
            } 
            // Si estamos en el formulario de la galería de inicio (solo una selección)
            else if (currentForm.id === 'hero-gallery-form') {
                const selectedImage = selectedImages[0];
                const fileName = selectedImage.dataset.filename;
                document.getElementById('imagen_from_library').value = fileName;
                
                alertModalMessage.textContent = `Imagen "${fileName}" seleccionada. Haz clic en "Guardar Slide" para confirmar.`;
                alertModal.show();
            }
            
            mediaModal.hide();
        });
    }
});