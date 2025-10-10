document.addEventListener('DOMContentLoaded', function() {
    const passNueva = document.getElementById('password_nueva');
    const passConfirm = document.getElementById('password_confirm');
    const passFeedback = document.getElementById('password-feedback');
    const passConfirmFeedback = document.getElementById('password-confirm-feedback');
    const saveButton = document.querySelector('button[type="submit"]');

    if (!passNueva) return; // Si no hay campos de contraseña, no hacer nada

    function validatePassword() {
        const nueva = passNueva.value;
        const confirm = passConfirm.value;
        let esValido = true;

        // 1. Validar complejidad
        if (nueva.length > 0) {
            if (nueva.length < 8 || !/[A-Z]/.test(nueva) || !/[0-9]/.test(nueva)) {
                passFeedback.textContent = 'Mín. 8 caracteres, 1 mayúscula, 1 número.';
                passFeedback.style.color = 'red';
                esValido = false;
            } else {
                passFeedback.textContent = 'Contraseña segura.';
                passFeedback.style.color = 'green';
            }
        } else {
            passFeedback.textContent = '';
        }

        // 2. Validar coincidencia
        if (confirm.length > 0) {
            if (nueva !== confirm) {
                passConfirmFeedback.textContent = 'Las contraseñas no coinciden.';
                passConfirmFeedback.style.color = 'red';
                esValido = false;
            } else {
                passConfirmFeedback.textContent = 'Las contraseñas coinciden.';
                passConfirmFeedback.style.color = 'green';
            }
        } else {
            passConfirmFeedback.textContent = '';
        }
    }

    passNueva.addEventListener('keyup', validatePassword);
    passConfirm.addEventListener('keyup', validatePassword);
});