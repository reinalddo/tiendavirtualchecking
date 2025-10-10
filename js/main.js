
document.addEventListener('DOMContentLoaded', function() {
    // --- LÓGICA DE LA GALERÍA DE PRODUCTOS ---
    const galleryContainer = document.querySelector('.product-gallery');
    if (galleryContainer) {
        const modalElement = document.getElementById('gallery-modal');
        const galleryModal = new bootstrap.Modal(modalElement);
        const modalContentHost = document.getElementById('modal-content-host');
        const mainGalleryDiv = document.getElementById('gallery-main');
        const galleryItems = Array.from(document.querySelectorAll('.thumbnail'));
        let currentImageIndex = 0;

        const openModal = (index) => {
            currentImageIndex = index;
            const item = galleryItems[index];
            if (!item) return;
            const tipo = item.dataset.tipo;
            const url = item.dataset.url;
            let modalHTML = '';
            if (tipo === 'imagen') {
                modalHTML = `<img src="${BASE_URL}uploads/${url}">`;
            } else if (tipo === 'youtube') {
                modalHTML = `<iframe src="https://www.youtube.com/embed/${url}?autoplay=1&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
            }
            modalContentHost.innerHTML = modalHTML;
            galleryModal.show();
        };

        const updateMainView = (index) => {
            const item = galleryItems[index];
            if (!item) return;
            currentImageIndex = index;
            const tipo = item.dataset.tipo;
            const url = item.dataset.url;
            let newContent = '';
            if (tipo === 'imagen') {
                newContent = `<img src="${BASE_URL}uploads/${url}" alt="Vista principal" class="clickable-gallery-image" data-index="${index}">`;
            } else if (tipo === 'youtube') {
                newContent = `<iframe src="https://www.youtube.com/embed/${url}" frameborder="0" allowfullscreen class="clickable-gallery-image" data-index="${index}"></iframe>`;
            }
            mainGalleryDiv.innerHTML = newContent;
            mainGalleryDiv.querySelector('.clickable-gallery-image').addEventListener('click', () => openModal(currentImageIndex));
        };

        // Asignación de evento a cada miniatura
        galleryItems.forEach((thumb, index) => {
            thumb.addEventListener('click', function() {
                updateMainView(index);
            });
        });
        
        // El resto del código para abrir/cerrar el modal y demás funciones
        const mainClickableElement = mainGalleryDiv.querySelector('.clickable-gallery-image');
        if (mainClickableElement) {
            mainClickableElement.addEventListener('click', function() {
                openModal(parseInt(this.dataset.index));
            });
        }

        function changeImage(direction) {
            let newIndex = currentImageIndex + direction;
            if (newIndex >= galleryItems.length) newIndex = 0;
            else if (newIndex < 0) newIndex = galleryItems.length - 1;
            openModal(newIndex);
        }

        // --- AÑADE ESTE BLOQUE DE CÓDIGO ---
        // Usamos el evento 'hidden.bs.modal' de Bootstrap.
        // Este evento se dispara automáticamente cuando el modal ha terminado de ocultarse.
        modalElement.addEventListener('hidden.bs.modal', function () {
            // Vaciamos el contenido para detener cualquier video de YouTube.
            modalContentHost.innerHTML = '';
        });

        modalElement.querySelector('.modal-prev').addEventListener('click', () => changeImage(-1));
        modalElement.querySelector('.modal-next').addEventListener('click', () => changeImage(1));

    }

    // --- LÓGICA PARA MOSTRAR/OCULTAR CONTRASEÑA CON EL ÍCONO DEL OJO ---
    document.querySelectorAll('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.remove('bi-eye-slash-fill');
                this.classList.add('bi-eye-fill');
            } else {
                input.type = 'password';
                this.classList.remove('bi-eye-fill');
                this.classList.add('bi-eye-slash-fill');
            }
        });
    });


    // --- LÓGICA PARA AÑADIR CATEGORÍAS (AJAX) ---
    const addCategoryBtn = document.getElementById('add-category-btn');
    if (addCategoryBtn) {
        addCategoryBtn.addEventListener('click', function() {
            const newCategoryNameInput = document.getElementById('new_category_name');
            const categoryName = newCategoryNameInput.value.trim();
            const newCategoryCodeInput = document.getElementById('new_category_code');
            const categoryCode = newCategoryCodeInput.value.trim();

            if (categoryName === '' || categoryCode === '') {
                alert('Tanto el nombre como el código de la categoría son obligatorios.');
                return;
            }

            fetch(BASE_URL + 'panel/ajax/add-category', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `nombre=${encodeURIComponent(categoryName)}&codigo=${encodeURIComponent(categoryCode)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error de red o del servidor.');
                }
                return response.json();
            })
            .then(data => {
                //console.log("data.id = ", data.id ,"data.nombre = ", data.nombre);
                if (data.id && data.nombre) {
                    const categoryListDiv = document.getElementById('category-list');
                    console.log("categoryListDiv = ", categoryListDiv);
                    const newLabel = document.createElement('label');
                    const newId = `cat-${data.id}`;
                    newLabel.innerHTML = `<input type="checkbox" name="categorias[]" value="${data.id}" id="${newId}" checked> <label for="${newId}">${data.nombre}</label>`;
                    categoryListDiv.appendChild(newLabel);
                    categoryListDiv.appendChild(document.createElement('br'));
                    newCategoryNameInput.value = '';
                    newCategoryCodeInput.value = '';
                } else {
                    alert('Error: ' + (data.error || 'No se pudo añadir la categoría.'));
                }
            })
            .catch(error => {
                console.error('Error en la petición:', error);
                alert('La clave de la categoría ya está usada.');
            });
        });
    }

    const slider = document.querySelector('.slider');
    if (slider) {
        const slides = document.querySelectorAll('.slide');
        let currentSlide = 0;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.remove('active');
                if (i === index) {
                    slide.style.backgroundImage = `url(${slide.querySelector('img').src})`;
                    slide.classList.add('active');
                }
            });
        }

        if (slides.length > 0) {
            showSlide(currentSlide);
            setInterval(() => {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            }, 5000); // Cambia cada 5 segundos
        }
    }

     document.querySelectorAll('.update-field').forEach(input => {
            input.addEventListener('change', function() {
                const slideId = this.closest('tr').dataset.slideId;
                const field = this.name;
                const value = this.value;

                fetch(BASE_URL + 'panel/ajax/update-slide', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${slideId}&field=${field}&value=${encodeURIComponent(value)}`
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

// Lógica para el Modal de Detalles del Pedido (Versión Bootstrap)
const orderModalElement = document.getElementById('order-details-modal');
if (orderModalElement) {
    const orderModal = new bootstrap.Modal(orderModalElement);
    const modalContent = document.getElementById('modal-order-content');

    document.querySelectorAll('.view-order-details-btn').forEach(button => {
        button.addEventListener('click', function() {
            const pedidoId = this.dataset.pedidoId;
            modalContent.innerHTML = '<p>Cargando...</p>';
            orderModal.show(); // Usamos el método de Bootstrap

            fetch(BASE_URL + 'ajax_get_order_details.php?pedido_id=' + pedidoId)
                .then(response => response.text())
                .then(html => {
                    modalContent.innerHTML = html;
                });
        });
    });
}

document.querySelectorAll('.update-moneda').forEach(input => {
    input.addEventListener('change', function() {
        const monedaId = this.closest('tr').dataset.monedaId;
        const field = this.name;
        const value = this.type === 'checkbox' ? this.checked : this.value;

        fetch(BASE_URL + 'admin/ajax_update_moneda.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${monedaId}&field=${field}&value=${encodeURIComponent(value)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Guardado');
                // Si el campo que cambió fue el checkbox de 'activa', recargamos la página
                if (field === 'es_activa') {
                    location.reload();
                }
            } else {
                alert('Error al actualizar.');
            }
        })
        .catch(error => console.error('Error:', error));
    });
});



document.addEventListener('click', function(event) {
    if (event.target.classList.contains('wishlist-btn')) {
        const button = event.target;
        const productoId = button.dataset.productoId;

        fetch(BASE_URL + 'toggle-wishlist', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'producto_id=' + productoId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cambiamos el estilo del botón
                button.classList.toggle('active', data.in_wishlist);
            } else {
                alert(data.error || 'Ocurrió un error.');
            }
        })
        .catch(error => console.error('Error:', error));
    }
});


// --- LÓGICA PARA URL AMIGABLE EN BÚSQUEDA ---
    const searchForm = document.getElementById('search-form');
        //console.log("searchForm = ", searchForm);

    if (searchForm) {
        //console.log("searchForm IN 1= ", searchForm);
        searchForm.addEventListener('submit', function(event) {
            event.preventDefault();// 1. Prevenimos que el formulario se envíe de la forma tradicional
            //console.log("searchForm IN 2= ", searchForm);
            // 2. Obtenemos el valor del campo de búsqueda
            const searchInput = document.getElementById('search-input');
            const searchTerm = searchInput.value.trim();
            //console.log("searchTerm = ", searchTerm);
            // 3. Si hay un término de búsqueda, construimos la nueva URL y redirigimos
            if (searchTerm) {
                // 1. Reemplazamos uno o más espacios en blanco (\s+) con un guion (-)
                const slugTerm = searchTerm.replace(/\s+/g, '-');
                // 2. Codificamos el resultado para seguridad en la URL
                const friendlySearchTerm = encodeURIComponent(slugTerm);
                //console.log("friendlySearchTerm", friendlySearchTerm);
                window.location.href = BASE_URL + 'buscar/' + friendlySearchTerm;
                //window.location.href = BASE_URL + 'buscar.php?q=' + friendlySearchTerm;
            }
        });
    }


});

document.querySelectorAll('.update-alt-text').forEach(input => {
    input.addEventListener('change', function() {
        const mediaId = this.dataset.mediaId;
        const altText = this.value;

        fetch(BASE_URL + 'panel/ajax/update-media', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${mediaId}&alt_text=${encodeURIComponent(altText)}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Error al actualizar el nombre.');
            }
        })
        .catch(error => console.error('Error:', error));
    });



});

// --- LÓGICA PARA EL MODAL DE RENOMBRAR ---
const renameModalElement = document.getElementById('renameModal');
if (renameModalElement) {
    const renameModal = new bootstrap.Modal(renameModalElement);
    const renameModalButton = document.getElementById('renameModalButton');
    
    // Instancia del modal de alerta para mostrar mensajes
    const alertModalElement = document.getElementById('alertModal');
    const alertModal = new bootstrap.Modal(alertModalElement);
    const alertModalMessage = document.getElementById('alertModalMessage');
    
    let renameAction = null; // Guardará la función a ejecutar al confirmar

    // Listener para los botones "Renombrar" en la biblioteca
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('rename-media-btn')) {
            e.preventDefault();
            const button = e.target;
            const altTextInput = button.closest('.card').querySelector('.update-alt-text');
            const newAltText = altTextInput.value.trim();

            // Si el campo de texto está vacío, muestra el modal de alerta
            if (newAltText === '') {
                alertModalMessage.textContent = 'Por favor, introduce un Nombre/Alt text primero.';
                alertModal.show();
                return;
            }
            
            // Si hay texto, guarda la función de renombrado y muestra el modal de confirmación
            renameAction = () => {
                const mediaId = button.dataset.mediaId;
                const oldFilename = button.dataset.oldFilename;
                
                fetch(BASE_URL + 'panel/ajax/rename-media', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `id=${mediaId}&alt_text=${encodeURIComponent(newAltText)}&old_filename=${encodeURIComponent(oldFilename)}`
                })
                .then(response => response.json())
                .then(data => {
                    renameModal.hide(); // Oculta el modal de confirmación
                    if (data.success) {
                        // Muestra un mensaje de éxito en el modal de alerta
                        alertModalMessage.textContent = 'Archivo renombrado con éxito.';
                        alertModal.show();
                        // Opcional: recargar la página para ver el cambio reflejado
                        // location.reload(); 
                    } else {
                        // Muestra un mensaje de error en el modal de alerta
                        alertModalMessage.textContent = 'Error: ' + (data.error || 'No se pudo renombrar.');
                        alertModal.show();
                    }
                });
            };

            renameModal.show(); // Muestra el modal de confirmación
        }
    });

    // Listener para el botón "Renombrar" DENTRO del modal de confirmación
    renameModalButton.addEventListener('click', function() {
        if (typeof renameAction === 'function') {
            renameAction();
        }
    });
}

// --- LÓGICA PARA EL MODAL DE CONFIRMACIÓN PERSONALIZADO ---
const customConfirmModal = document.getElementById('customConfirmModal');
if (customConfirmModal) {
    const confirmBtn = document.getElementById('customConfirmButton');
    const cancelBtn = document.getElementById('customConfirmCancel');
    let formToSubmit = null; // Se declara aquí

    const showModal = (form) => {
        formToSubmit = form;
        customConfirmModal.style.display = 'flex';
    };

    const closeModal = () => {
        customConfirmModal.style.display = 'none';
        formToSubmit = null; // Se limpia la variable al cerrar
    };

    document.body.addEventListener('click', function(e) {
        const targetButton = e.target.closest('.confirm-delete');
        if (targetButton) {
            e.preventDefault();
            showModal(targetButton.closest('form'));
        }
    });

    confirmBtn.addEventListener('click', function() {
        if (formToSubmit) {
            formToSubmit.submit();
        }
    });

    cancelBtn.addEventListener('click', closeModal);
}


//-----------------------------------------------------------------------------------------------------------
// --- LÓGICA PARA LA BIBLIOTECA DE MEDIOS EN EL FORMULARIO DE PRODUCTOS ---
const mediaModalElement = document.getElementById('mediaLibraryModal');
if (mediaModalElement) {
    const mediaGrid = document.getElementById('media-library-grid');
    const addSelectedBtn = document.getElementById('add-selected-media-btn');
    // Usamos el método más robusto para obtener la instancia del modal
    const mediaModal = bootstrap.Modal.getOrCreateInstance(mediaModalElement);

    // Cargar imágenes cuando se abre el modal
    mediaModalElement.addEventListener('show.bs.modal', function() {
        mediaGrid.innerHTML = '<p class="text-center">Cargando imágenes...</p>';
        fetch(BASE_URL + 'panel/ajax/get-media')
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
        if (mediaItem) {
            mediaItem.classList.toggle('selected');
        }
    });

    // Añadir las imágenes seleccionadas al formulario del producto
    addSelectedBtn.addEventListener('click', function() {
        const selectedImages = mediaGrid.querySelectorAll('.media-item.selected img');
        if (selectedImages.length === 0) {
            // Usaremos el modal de alerta que ya creamos
            const alertModalMessage = document.getElementById('alertModalMessage');
            if (alertModalMessage) {
                alertModalMessage.textContent = 'No has seleccionado ninguna imagen.';
                const alertModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('alertModal'));
                alertModal.show();
            } else {
                alert('No has seleccionado ninguna imagen.'); // Fallback por si acaso
            }
            return;
        }

        const currentGallery = document.querySelector('.current-gallery');
        const form = document.querySelector('#product-form');

        selectedImages.forEach(img => {
            const fileName = img.dataset.filename;
            
            // Añadimos un campo oculto al formulario por cada imagen seleccionada
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'gallery_from_library[]';
            hiddenInput.value = fileName;
            form.appendChild(hiddenInput);

            // Actualizamos la vista previa para que el usuario sepa que se añadió
            const alertHtml = `<div class="alert alert-info alert-dismissible fade show my-1" role="alert">
                                  ✔️ ${fileName} (se añadirá al guardar)
                                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                               </div>`;
            currentGallery.insertAdjacentHTML('beforeend', alertHtml);
        });
        
        // Ocultamos el modal de la biblioteca
        mediaModal.hide();
    });
}
//-----------------------------------------------------------------------------------------------------------
//-----------------------------------------------------------------------------------------------------------
//-----------------------------------------------------------------------------------------------------------

// Lógica para la validación de SKU en tiempo real
const skuInput = document.getElementById('sku');
if (skuInput) {
    // Buscamos el botón de guardar UNA SOLA VEZ para eficiencia
    const submitButton = skuInput.closest('form').querySelector('button[type="submit"]');

    skuInput.addEventListener('blur', function() { // Usamos 'blur' que se activa al salir del campo
        const sku = this.value.trim();
        const feedbackDiv = document.getElementById('sku-feedback');
        const productIdInput = document.querySelector('input[name="id"]');
        const currentId = productIdInput ? productIdInput.value : 0;

        if (sku === '') {
            feedbackDiv.textContent = '';
            submitButton.disabled = false; // Si está vacío, el 'required' del HTML se encargará
            return;
        }

        // Mostramos un mensaje de "verificando"
        feedbackDiv.textContent = 'Verificando...';
        feedbackDiv.style.color = 'gray';

        fetch(`${BASE_URL}panel/ajax/check-sku?sku=${encodeURIComponent(sku)}&current_id=${currentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    feedbackDiv.textContent = 'Este SKU ya está en uso. Por favor, elige otro.';
                    feedbackDiv.style.color = 'red';
                    submitButton.disabled = true; // Desactivamos el botón
                } else {
                    feedbackDiv.textContent = 'SKU disponible.';
                    feedbackDiv.style.color = 'green';
                    submitButton.disabled = false; // Lo activamos porque el SKU es válido
                }
            })
            .catch(error => {
                console.error('Error de validación:', error);
                feedbackDiv.textContent = 'No se pudo verificar el SKU.';
                feedbackDiv.style.color = 'orange';
                submitButton.disabled = false; // Lo dejamos activo si hay un error de red
            });
    });
}

