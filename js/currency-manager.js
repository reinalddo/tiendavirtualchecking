document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.update-moneda').forEach(input => {
        input.addEventListener('change', function() {
            const id = this.closest('tr').dataset.monedaId;
            const field = this.name;
            const value = this.type === 'checkbox' ? this.checked : this.value;

            fetch(BASE_URL + 'panel/ajax/update-moneda', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${id}&field=${field}&value=${encodeURIComponent(value)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && field === 'es_activa') {
                    location.reload();
                } else if (data.error) {
                    alert('Error: ' + data.error);
                }
            });
        });
    });
});