document.addEventListener('DOMContentLoaded', function() {
    const massEditorTable = document.querySelector('.table-bordered');
    if (massEditorTable) 
    {
    // Lógica para guardar cambios en los campos de texto
    document.querySelectorAll('.update-producto').forEach(input => {
        input.addEventListener('change', function() {
            const productoId = this.closest('tr').dataset.productoId;
            const field = this.name;
            const value = this.value;

            fetch(BASE_URL + 'admin/ajax_update_producto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${productoId}&field=${field}&value=${encodeURIComponent(value)}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error al actualizar el campo.');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Lógica para el modal de la biblioteca
    const mediaModalElement = document.getElementById('mediaLibraryModal');
    if (mediaModalElement) {
        const mediaModal = bootstrap.Modal.getOrCreateInstance(mediaModalElement);
        const addSelectedBtn = document.getElementById('add-selected-media-btn');
        let currentTargetRow = null; // Guardará la fila del producto que estamos editando

        // Guardar la fila del producto cuando se hace clic en "Añadir Imágenes"
        document.querySelectorAll('.select-media-btn').forEach(button => {
            button.addEventListener('click', function() {
                currentTargetRow = this.closest('tr');
            });
        });

        // Lógica para el botón "Añadir Seleccionadas"
        addSelectedBtn.addEventListener('click', function() {
            const selectedImages = document.querySelectorAll('#media-library-grid .media-item.selected img');
            if (selectedImages.length === 0 || !currentTargetRow) {
                alert('No has seleccionado ninguna imagen o no se ha definido un producto.');
                return;
            }

            const productoId = currentTargetRow.dataset.productoId;
            const previewContainer = currentTargetRow.querySelector('.new-images-preview');
            const fileNames = [];

            selectedImages.forEach(img => {
                const fileName = img.dataset.filename;
                fileNames.push(fileName);

                // Añadir vista previa de la imagen con botón de eliminar
            const previewHtml = `
                <div class="preview-item position-relative" data-filename="${fileName}">
                    <img src="${BASE_URL}uploads/${fileName}" class="img-fluid rounded" style="width: 50px; height: 50px; object-fit: cover;">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-preview-item" style="padding: 0.1rem 0.3rem; line-height: 1;">&times;</button>
                </div>`;
                previewContainer.insertAdjacentHTML('beforeend', previewHtml);
            });
            
            // Enviamos los nombres de archivo al servidor para asociarlos
            fetch(BASE_URL + 'admin/ajax_associate_media.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `producto_id=${productoId}&filenames[]=${fileNames.join('&filenames[]=')}`
            });

            mediaModal.hide();
        });
    }

    // Listener para eliminar una imagen de la vista previa (no de la BD)
    massEditorTable.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-preview-item')) {
            e.target.closest('.preview-item').remove();
        }
    });

    // --- LÓGICA PARA ELIMINAR IMÁGENES EXISTENTES ---
    if (massEditorTable) {
        massEditorTable.addEventListener('click', function(e) {

            if (e.target.classList.contains('remove-existing-image-btn')) {
            const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            const modalTitle = document.getElementById('confirmationModalTitle');
            const modalBody = document.getElementById('confirmationModalBody');
            const confirmButton = document.getElementById('confirmActionButton');
            const previewItem = e.target.closest('.preview-item');
            const galleryId = previewItem.dataset.galleryId;

            modalTitle.textContent = 'Confirmar Eliminación';
            modalBody.textContent = '¿Estás seguro de que quieres eliminar esta imagen permanentemente?';

            // Remove any existing event listeners to avoid duplicates
            confirmButton.removeEventListener('click', handleDeleteImage);

            function handleDeleteImage() {
                fetch(BASE_URL + 'admin/ajax_delete_gallery_item.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'gallery_id=' + galleryId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        previewItem.remove(); // Elimina la miniatura de la vista
                    } else {
                        const errorModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
                        document.getElementById('confirmationModalTitle').textContent = 'Error';
                        document.getElementById('confirmationModalBody').textContent = 'Error: No se pudo eliminar la imagen.';
                        document.getElementById('confirmActionButton').style.display = 'none'; // Hide confirm button
                        setTimeout(() => errorModal.show(), 100); // Show error message
                        setTimeout(() => {
                            errorModal.hide();
                            document.getElementById('confirmActionButton').style.display = 'inline-block'; // Restore button
                        }, 2000);
                    }
                    confirmationModal.hide();
                });
            }

            confirmButton.addEventListener('click', handleDeleteImage);
            confirmationModal.show();
        }

        });
    }

    document.querySelectorAll('.update-producto-categoria').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const productoId = this.dataset.productoId;
            const categoriaId = this.value;
            const isChecked = this.checked;

            fetch(BASE_URL + 'admin/ajax_update_product_category.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `producto_id=${productoId}&categoria_id=${categoriaId}&checked=${isChecked}`
            });
        });
    });
    }

});
