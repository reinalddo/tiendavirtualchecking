document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const resultsContainer = document.getElementById('search-results-container');
    let debounceTimer;

    searchInput.addEventListener('keyup', function() {
        clearTimeout(debounceTimer);
        const searchTerm = this.value;

        if (searchTerm.length < 3) {
            resultsContainer.innerHTML = '';
            resultsContainer.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(BASE_URL + `ajax_buscar_productos.php?term=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            html += `
                                <a href="${item.url_producto}" class="search-result-item">
                                    <img src="${item.imagen_url}" alt="${item.nombre}">
                                    <div class="info">
                                        <h6>${item.nombre}</h6>
                                    </div>
                                </a>
                            `;
                        });
                    } else {
                        html = '<div class="p-3 text-muted">No se encontraron productos.</div>';
                    }
                    resultsContainer.innerHTML = html;
                    resultsContainer.style.display = 'block';
                });
        }, 300); // Espera 300ms después de que el usuario deja de escribir
    });

    // Ocultar resultados si se hace clic fuera
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
});