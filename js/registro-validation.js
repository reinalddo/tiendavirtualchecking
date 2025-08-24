document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registro-form');
    // Si el formulario de registro no existe en esta página, no hacer nada.
    if (!form) {
        return;
    }

    // Obtener todos los elementos del formulario
    const nombreInput = document.getElementById('nombre_pila');
    const apellidoInput = document.getElementById('apellido');
    const emailInput = document.getElementById('email');
    const telefonoInput = document.getElementById('telefono');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm');
    const registerBtn = document.getElementById('register-btn');
    
    const passwordFeedback = document.getElementById('password-feedback');
    const passwordConfirmFeedback = document.getElementById('password-confirm-feedback');

    // Función principal para validar todo el formulario
    function validateForm() {
        let isNombreValid = nombreInput.value.trim() !== '';
        let isApellidoValid = apellidoInput.value.trim() !== '';
        let isEmailValid = emailInput.value.trim() !== '' && emailInput.checkValidity();

        // Validación de contraseña
        const password = passwordInput.value;
        const confirmPassword = confirmInput.value;
        let isPasswordStrong = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/.test(password);
        let doPasswordsMatch = password === confirmPassword;

        // Mostrar mensajes de ayuda para la contraseña
        if (password.length > 0 && !isPasswordStrong) {
            passwordFeedback.textContent = 'Debe tener al menos 8 caracteres, con letras y números.';
            passwordFeedback.style.color = 'red';
        } else {
            passwordFeedback.textContent = '';
        }

        // Mostrar mensaje si las contraseñas no coinciden
        if (confirmPassword.length > 0 && !doPasswordsMatch) {
            passwordConfirmFeedback.textContent = 'Las contraseñas no coinciden.';
            passwordConfirmFeedback.style.color = 'red';
        } else {
            passwordConfirmFeedback.textContent = '';
        }

        // El botón de registro se activa si todo es válido
        registerBtn.disabled = !(isNombreValid && isApellidoValid && isEmailValid && isPasswordStrong && doPasswordsMatch);
    }

    // Lógica para mostrar/ocultar contraseña con el ícono del ojo
    document.querySelectorAll('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.remove('bi-eye-slash-fill');
                this.classList.add('bi-eye-fill');
            } else {
                input.type = 'password';
                this.classList.remove('bi-eye-fill');
                this.classList.add('bi-eye-slash-fill');
            }
        });
    });

    // Lógica para permitir solo números en el teléfono
    telefonoInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Añadimos un listener a cada campo para validar en tiempo real
    form.querySelectorAll('input').forEach(input => {
        input.addEventListener('keyup', validateForm);
        input.addEventListener('change', validateForm);
    });
});