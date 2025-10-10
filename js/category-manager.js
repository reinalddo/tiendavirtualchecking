document.addEventListener('DOMContentLoaded', function() {
    // Esta lógica solo se ejecutará si encuentra la clase '.update-categoria'
    document.querySelectorAll('.update-categoria').forEach(input => {
        input.addEventListener('change', function() {
            const id = this.closest('tr').dataset.categoriaId;
            const field = this.name;
            const value = this.type === 'checkbox' ? this.checked : this.value;

            fetch(BASE_URL + 'panel/ajax/update-category', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${id}&field=${field}&value=${encodeURIComponent(value)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                    // Opcional: recargar para revertir el cambio visual
                    location.reload(); 
                }
            })
            .catch(error => {
                console.error('Error al actualizar la categoría:', error);
                alert('Ocurrió un error de conexión.');
            });
        });
    });
});