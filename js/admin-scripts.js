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
                    plugins: 'lists link image media table code help wordcount',
                    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | help'
                }).then(editors => {
                    editorInstance = editors[0];
                    editorInstance.setContent(descripcion); // Ponemos el contenido
                });
            }


        });

        guardarDescripcionBtn.addEventListener('click', function() {
            const productoId = document.getElementById('modalProductoId').value;
            // Obtenemos el contenido del editor
            const nuevaDescripcion = editorInstance ? editorInstance.getContent() : '';

            fetch(BASE_URL + 'admin/ajax_guardar_descripcion.php', {
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