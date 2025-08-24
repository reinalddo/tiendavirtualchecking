document.addEventListener('DOMContentLoaded', function() {
    const miniCartDropdown = document.getElementById('mini-cart-dropdown');
    const cartDropdownToggle = document.getElementById('cartDropdown');
    const cartItemCount = document.getElementById('cart-item-count');

    if (!cartDropdownToggle) return;

    // Función para cargar y actualizar el contenido del mini-carrito
    const updateMiniCart = () => {
        fetch(BASE_URL + 'ajax_get_cart_details.php')
            .then(response => response.json())
            .then(data => {
                let html = '';
                if (data.total_items > 0) {
                    data.productos.forEach(p => {
                        const imageUrl = p.imagen_url ? `${BASE_URL}uploads/${p.imagen_url}` : `${BASE_URL}images/placeholder.png`;

                        html += `
                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2 product-item">
                                <img src="${imageUrl}" alt="${p.nombre}" style="width: 50px; height: 50px; object-fit: cover;" class="me-2 rounded">
                                <div class="flex-grow-1">
                                    <strong>${p.nombre}</strong><br>
                                    <small>${p.cantidad} x ${p.precio_formateado}</small>
                                </div>
                                <button class="btn btn-sm btn-outline-danger remove-from-cart-btn" data-producto-id="${p.id}">&times;</button>
                            </div>`;
                    });
                    html += `<div class="mt-3">
                                <p class="text-end fw-bold">Total: ${data.total_formateado}</p>
                                <div class="d-grid gap-2">
                                    <a href="${BASE_URL}ver_carrito.php" class="btn btn-primary btn-sm">Ver Carrito Completo</a>
                                 </div>
                             </div>`;
                } else {
                    html = '<p class="text-center text-muted">Tu carrito está vacío.</p>';
                }
                miniCartDropdown.innerHTML = html;
                cartItemCount.textContent = data.total_items;
            });
    };
    
    // Cargar el carrito cuando el usuario abre el desplegable
    cartDropdownToggle.addEventListener('mouseover', updateMiniCart);

    // Lógica para eliminar ítems directamente desde el mini-carrito
    miniCartDropdown.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-from-cart-btn')) {
            const productoId = e.target.dataset.productoId;

            const formData = new FormData();
            formData.append('producto_id', productoId);
            formData.append('eliminar_del_carrito', '1');

            fetch(BASE_URL + 'carrito_acciones.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => {
                // Después de eliminar, volvemos a cargar el contenido del carrito
                updateMiniCart();
            });
        }
    });
});