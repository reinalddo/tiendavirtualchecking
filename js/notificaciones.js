// js/notificaciones.js (Versión Mejorada)
document.addEventListener('DOMContentLoaded', function() {
    const countBadge = document.getElementById('notification-count');
    const notificationList = document.getElementById('notification-list');

    function fetchNotifications() {
        fetch(`${BASE_URL}ajax_get_notificaciones.php`)
        .then(response => response.json())
        .then(data => {
            // Actualizar contador
            if (data.count > 0) {
                countBadge.textContent = data.count;
                countBadge.style.display = 'inline-block';
            } else {
                countBadge.style.display = 'none';
            }

            // Actualizar lista con los nuevos botones
            let html = '';
            if (data.items.length > 0) {
                data.items.forEach(item => {
                    const esLeida = item.leida == 1;
                    const disabledClass = !esLeida ? 'disabled' : '';

                    html += `
                    <div class="dropdown-item-wrapper p-2 border-bottom">
                        <a href="${item.url}" class="text-decoration-none text-dark ${!esLeida ? 'fw-bold' : ''}">
                            ${item.mensaje}
                        </a>
                        <div class="mt-2 text-end">
                            <button class="btn btn-sm btn-outline-secondary mark-as-read-btn" data-id="${item.id}" ${esLeida ? 'disabled' : ''}>
                                Marcar como leído
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-notification-btn" data-id="${item.id}" ${disabledClass}>
                                Eliminar
                            </button>
                        </div>
                    </div>`;
                });
            } else {
                html = '<div class="text-center text-muted p-3">No hay notificaciones.</div>';
            }
            notificationList.innerHTML = html;
        });
    }

    // Manejador de clics para los nuevos botones
    notificationList.addEventListener('click', function(e) {
        const target = e.target;
        const notificacionId = target.dataset.id;

        if (target.classList.contains('mark-as-read-btn')) {
            e.preventDefault();
            fetch(`${BASE_URL}ajax_marcar_notificacion_leida.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${notificacionId}`
            }).then(() => fetchNotifications()); // Refrescar la lista
        }

        if (target.classList.contains('delete-notification-btn')) {
            e.preventDefault();
            fetch(`${BASE_URL}ajax_eliminar_notificacion.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${notificacionId}`
            }).then(() => fetchNotifications()); // Refrescar la lista
        }
    });

    fetchNotifications();
    setInterval(fetchNotifications, 10000);
});