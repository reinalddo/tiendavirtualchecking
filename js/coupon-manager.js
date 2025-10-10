document.addEventListener('DOMContentLoaded', function() {
    // Esta lógica solo se ejecutará si encuentra un elemento con la clase '.update-cupon'
    document.querySelectorAll('.update-cupon').forEach(input => {
        input.addEventListener('change', function() {
            const id = this.closest('tr').dataset.cuponId;
            const field = this.name;
            let value = this.type === 'checkbox' ? this.checked : this.value;

            // Validación para la fecha
            if (field === 'fecha_expiracion' && value) {
                const selectedDate = new Date(value);
                const today = new Date();
                today.setHours(0, 0, 0, 0); // Ignorar la hora para la comparación
                if (selectedDate < today) {
                    alert('Error: La fecha de expiración no puede ser una fecha pasada.');
                    this.value = this.dataset.originalValue; // Revertir al valor original
                    return;
                }
            }

            fetch(BASE_URL + 'panel/ajax/update-cupon', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}&field=${field}&value=${encodeURIComponent(value)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                    location.reload(); 
                }
            })
            .catch(error => {
                console.error('Error al actualizar el cupón:', error);
                alert('Ocurrió un error de conexión.');
            });
        });
        // Guardar el valor original de la fecha para poder revertirlo si es inválido
        if (input.type === 'date') {
            input.dataset.originalValue = input.value;
        }
    });

    document.querySelectorAll('.clear-date-btn').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const dateInput = row.querySelector('input[name="fecha_expiracion"]');
            
            // Limpiamos el valor del campo
            dateInput.value = ''; 
            
            // Creamos y disparamos un evento 'change' para que se guarde el cambio
            const changeEvent = new Event('change');
            dateInput.dispatchEvent(changeEvent);
        });
    });

});