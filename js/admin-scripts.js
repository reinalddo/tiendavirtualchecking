document.addEventListener('DOMContentLoaded', function() {
    const descripcionModal = document.getElementById('descripcionModal');
    if (descripcionModal) {
        let editorInstance = null;
        const modalDescripcionHTML = document.getElementById('modalDescripcionHTML');
        const modalProductoId = document.getElementById('modalProductoId');
        const guardarDescripcionBtn = document.getElementById('guardarDescripcionBtn');

        descripcionModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const productoId = button.dataset.productoId;

            //const descripcion = button.dataset.descripcion;
            // Obtenemos la descripción del preview, que sí se actualiza
            const descripcionPreview = document.querySelector(`.descripcion-preview[data-producto-id="${productoId}"]`);
            //const descripcion = descripcionPreview.textContent;
            const descripcion = button.dataset.descripcion;

            //modalDescripcionHTML.value = descripcion;
            modalProductoId.value = productoId;


            // Si ya hay un editor, simplemente le ponemos el nuevo contenido
            if (editorInstance) {
                editorInstance.setContent(descripcion);
            } else {
                // Inicializar el editor
                tinymce.init({
                    selector: '#modalDescripcionHTML',
                    plugins: 'lists link image media table code help wordcount visualblocks',
                    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | help',
                    toolbar_mode: 'wrap',
                    extended_valid_elements: '+*[*]', // Permite cualquier elemento con cualquier atributo
                    valid_children: '+body[style]', // Permite la etiqueta style en el body
                    verify_html: false, // Desactiva la verificación estricta de HTML
                    allow_html_in_named_anchor: true,
                    cleanup: false,
                    valid_elements: '*[*]', // Permite cualquier etiqueta con cualquier atributo
                    setup: function(editor) {
                        editorInstance = editor;
                    }
                }).then(editors => {
                    editorInstance = editors[0];
                    editorInstance.setContent(descripcion);
                });
            }


        });

        guardarDescripcionBtn.addEventListener('click', function() {
            const productoId = document.getElementById('modalProductoId').value;
            // Obtenemos el contenido del editor
            const nuevaDescripcion = editorInstance ? editorInstance.getContent() : '';

            fetch(BASE_URL + 'panel/ajax/guardar-descripcion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `producto_id=${productoId}&descripcion_html=${encodeURIComponent(nuevaDescripcion)}`,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const descripcionPreview = document.querySelector(`.btn-edit-descripcion.btn-edit-descripcion` + `\[data-producto-id="${productoId}"]`).nextElementSibling;
                    if (descripcionPreview) {
                        descripcionPreview.textContent = nuevaDescripcion.substring(0, 100) + '...';
                    }
                    bootstrap.Modal.getInstance(descripcionModal).hide();
                    //alert('Descripción guardada correctamente.');
                } else {
                    alert('Error al guardar la descripción.');
                }
            });
        });
        descripcionModal.addEventListener('hidden.bs.modal', function() {
            // Importante: Destruir la instancia del editor al cerrar el modal
            if (editorInstance) {
                tinymce.remove(editorInstance);
                editorInstance = null;
            }
        });

    }
});