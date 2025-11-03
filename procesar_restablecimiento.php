<?php
// procesar_restablecimiento.php
require_once 'includes/config.php'; // Incluye $pdo

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['selector']) || empty($_POST['validator']) || empty($_POST['password_nueva']) || empty($_POST['password_confirm'])) {
    // Redirigir si faltan datos
    header('Location: ' . BASE_URL . 'login');
    exit();
}

$selector = $_POST['selector'];
$validator = $_POST['validator']; // Token secreto de la URL
$password_nueva = $_POST['password_nueva'];
$password_confirm = $_POST['password_confirm'];

// 1. Validar formato de tokens (básico)
if (!ctype_xdigit($selector) || !ctype_xdigit($validator)) {
    $_SESSION['mensaje_carrito'] = 'Error: Enlace inválido.';
    header('Location: ' . BASE_URL . 'login');
    exit();
}

// 2. Validar contraseñas
if ($password_nueva !== $password_confirm) {
    $_SESSION['mensaje_error_reset'] = 'Las contraseñas no coinciden.';
    // Redirigir de vuelta al formulario de restablecimiento con los tokens
    header('Location: ' . BASE_URL . 'restablecer-contrasena?' . http_build_query(['selector' => $selector, 'validator' => $validator]));
    exit();
}
// Validar complejidad (reutilizando la lógica de registro/perfil)
if (strlen($password_nueva) < 8 || !preg_match('/[A-Z]/', $password_nueva) || !preg_match('/[0-9]/', $password_nueva)) {
     $_SESSION['mensaje_error_reset'] = "La contraseña debe tener al menos 8 caracteres, una mayúscula y un número.";
     header('Location: ' . BASE_URL . 'restablecer-contrasena?' . http_build_query(['selector' => $selector, 'validator' => $validator]));
     exit();
}


try {
    $pdo->beginTransaction();

    // 3. Buscar el token en la BD usando el SELECTOR y verificar si ha expirado
    $stmt_token = $pdo->prepare("SELECT * FROM password_resets WHERE selector = ? AND expires_at >= NOW()");
    $stmt_token->execute([$selector]);
    $token_data = $stmt_token->fetch(PDO::FETCH_ASSOC);

    if (!$token_data) {
        throw new Exception('Token no encontrado o expirado.');
    }

    // 4. Verificar el VALIDATOR (token secreto)
    $hashed_validator_from_url = hash('sha256', $validator);
    if (!hash_equals($token_data['hashed_token'], $hashed_validator_from_url)) {
        throw new Exception('Token inválido.');
    }

    // 5. Token válido - Actualizar contraseña del usuario
    $email = $token_data['email'];
    $hashed_password_nuevo = password_hash($password_nueva, PASSWORD_DEFAULT);

    $stmt_update_pass = $pdo->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
    $stmt_update_pass->execute([$hashed_password_nuevo, $email]);

    // 6. Eliminar el token usado de la BD
    $stmt_delete = $pdo->prepare("DELETE FROM password_resets WHERE email = ?"); // Eliminar todos los tokens para ese email
    $stmt_delete->execute([$email]);

    $pdo->commit();

    $_SESSION['mensaje_carrito'] = '¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.';
    header('Location: ' . BASE_URL . 'login');
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error procesando restablecimiento: " . $e->getMessage());
    // Mensaje genérico para el usuario por seguridad
    $_SESSION['mensaje_carrito'] = 'Error: No se pudo restablecer la contraseña. El enlace puede haber expirado o ser inválido.';
    header('Location: ' . BASE_URL . 'login');
    exit();
}
?>