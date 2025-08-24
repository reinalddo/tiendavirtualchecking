document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('gallery-sortable-container');
    if (!container) return;

    const sortable = new Sortable(container, {
        animation: 150, // Animación suave al arrastrar
        onEnd: function(evt) { // Se ejecuta cuando terminas de arrastrar
            const order = [];
            // Recorremos los items en su nuevo orden y guardamos sus IDs
            container.querySelectorAll('.gallery-item').forEach(item => {
                order.push(item.dataset.id);
            });

            // Creamos un objeto FormData para enviar los datos
            const formData = new FormData();
            order.forEach(id => {
                formData.append('order[]', id);
            });

            // Enviamos el nuevo orden al servidor
            fetch(BASE_URL + 'admin/ajax_update_gallery_order.php', {
                method: 'POST',
                body: formData // Usar FormData es más robusto
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('¡Orden guardado!');
                    // Opcional: Mostrar una pequeña notificación de éxito
                    const successToast = document.createElement('div');
                    successToast.className = 'toast-notification success';
                    successToast.textContent = 'Orden actualizado';
                    document.body.appendChild(successToast);
                    setTimeout(() => successToast.remove(), 2000);
                }
            })
            .catch(error => console.error('Error al guardar el orden:', error));
        }
    });
});