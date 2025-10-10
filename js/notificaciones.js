// js/notificaciones.js (Versión con clic para marcar como leído)
document.addEventListener('DOMContentLoaded', function() {
    const countBadge = document.getElementById('notification-count');
    const notificationList = document.getElementById('notification-list');

    function fetchNotifications() {
        fetch(`${BASE_URL}ajax/get-notificaciones`)
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                countBadge.textContent = data.count;
                countBadge.style.display = 'inline-block';
            } else {
                countBadge.style.display = 'none';
            }

            let html = '';
            if (data.items.length > 0) {
                data.items.forEach(item => {
                    const esLeida = item.leida == 1;
                    const disabledClass = esLeida ? 'disabled' : '';

                    // --- INICIO DE LA MODIFICACIÓN ---
                    // Ahora, el enlace principal tiene los datos y una clase para identificarlo
                    html += `
                    <div class="dropdown-item-wrapper p-2 border-bottom">
                        <a href="${item.url}" class="text-decoration-none text-dark notification-link ${!esLeida ? 'fw-bold' : ''}" data-id="${item.id}" data-url="${item.url}">
                            ${item.mensaje}
                        </a>
                        <div class="mt-2 text-end">
                            <button class="btn btn-sm btn-outline-secondary mark-as-read-btn" data-id="${item.id}" ${disabledClass}>
                                Marcar como leído
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-notification-btn" data-id="${item.id}">
                                Eliminar
                            </button>
                        </div>
                    </div>`;
                    // --- FIN DE LA MODIFICACIÓN ---
                });
            } else {
                html = '<div class="text-center text-muted p-3">No hay notificaciones.</div>';
            }
            notificationList.innerHTML = html;
        });
    }

    // Manejador de clics unificado
    notificationList.addEventListener('click', function(e) {
        const target = e.target;
        
        // --- INICIO DE LA NUEVA LÓGICA ---
        // Si se hace clic en el enlace de la notificación
        if (target.classList.contains('notification-link')) {
            e.preventDefault(); // Detenemos la redirección inmediata
            const notificacionId = target.dataset.id;
            const url = target.dataset.url;

            // Primero, marcamos la notificación como leída
            fetch(`${BASE_URL}notificaciones/marcar-leida`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${notificacionId}`
            }).then(() => {
                // Después de que se complete la petición, redirigimos
                window.location.href = url;
            });
            return; // Detenemos la ejecución aquí
        }
        // --- FIN DE LA NUEVA LÓGICA ---

        const notificacionId = target.dataset.id;
        if (target.classList.contains('mark-as-read-btn')) {
            e.preventDefault();
            fetch(`${BASE_URL}notificaciones/marcar-leida`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${notificacionId}`
            }).then(() => fetchNotifications());
        }

        if (target.classList.contains('delete-notification-btn')) {
            e.preventDefault();
            fetch(`${BASE_URL}notificaciones/eliminar`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${notificacionId}`
            }).then(() => fetchNotifications());
        }
    });

    fetchNotifications();
    setInterval(fetchNotifications, 10000);
});