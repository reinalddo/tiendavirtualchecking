<?php
// registro.php

// Primero, procesamos la lógica del formulario
//require_once 'includes/db_connection.php';
require_once 'includes/config.php';

// Initialize variables
$nombre = '';
$email = '';
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_pila']);
    $email = trim($_POST['email']);
    $rif_cedula = trim($_POST['rif_cedula']);
    $direccion = trim($_POST['direccion']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $error = '';
    $avatar_path = null;

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/avatars/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $filename = uniqid() . '-' . basename($_FILES['avatar']['name']);
        $destination = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
            $avatar_path = $filename;
        }
    }
    
    if (empty($nombre) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Este correo electrónico ya está registrado.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = $pdo->prepare("INSERT INTO usuarios (nombre_pila, email, password, rol, avatar_manual, rif_cedula, direccion) VALUES (?, ?, ?, 'cliente', ?, ?, ?)");
            if ($stmt_insert->execute([$nombre, $email, $hashed_password, $avatar_path, $rif_cedula, $direccion])) {
                $_SESSION['mensaje_carrito'] = '¡Registro exitoso! Ahora puedes iniciar sesión.';
                $exito = true;
            } else {
                $error = 'Hubo un error al crear tu cuenta.';
            }
        }
    }

    if ($error) {
        $_SESSION['mensaje_carrito'] = 'Error: ' . $error;
    }
}

// AHORA, después de procesar todo, incluimos el header.
// El header podrá "ver" el mensaje que acabamos de guardar en la sesión.
require_once 'includes/header.php'; 
?>
    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
<h2>Registro de Nuevo Cliente</h2>

<?php if (!$exito): ?>
<form action="registro.php" method="POST" id="registro-form" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nombre_pila" class="form-label">Nombre:</label>
            <input type="text" id="nombre_pila" name="nombre_pila" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="apellido" class="form-label">Apellido:</label>
            <input type="text" id="apellido" name="apellido" class="form-control" required>
        </div>
    </div>
    
    <div class="mb-3">
        <label for="email" class="form-label">Email:</label>
        <input type="email" id="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="telefono" class="form-label">Teléfono:</label>
        <input type="tel" id="telefono" name="telefono" class="form-control" placeholder="Ej: 04121234567">
    </div>

    <div class="mb-3">
        <label for="rif_cedula" class="form-label">RIF o Cédula:</label>
        <input type="text" id="rif_cedula" name="rif_cedula" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="direccion" class="form-label">Dirección Fiscal:</label>
        <textarea id="direccion" name="direccion" class="form-control" rows="3" required></textarea>
    </div>

    <div class="mb-3 position-relative">
        <label for="password" class="form-label">Contraseña:</label>
        <input type="password" id="password" name="password" class="form-control" required>
        <i class="bi bi-eye-slash-fill toggle-password"></i>
    </div>
    <div id="password-feedback" class="form-text mb-2"></div>

    <div class="mb-3 position-relative">
        <label for="password_confirm" class="form-label">Repetir Contraseña:</label>
        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
        <i class="bi bi-eye-slash-fill toggle-password"></i>
    </div>
    <div id="password-confirm-feedback" class="form-text mb-2"></div>

    <div class="mb-3">
        <label for="avatar" class="form-label">Avatar (opcional):</label>
        <input class="form-control" type="file" id="avatar" name="avatar" accept="image/*">
    </div>

    <div class="mb-3" id="recaptcha-container">
        </div>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="acepta_marketing" value="1" id="acepta_marketing">
        <label class="form-check-label" for="acepta_marketing">Deseo recibir promociones</label>
    </div>

    <div class="d-grid">
        <button type="submit" id="register-btn" class="btn btn-primary" disabled>Registrarse</button>
    </div>
</form>
    <p>¿Ya tienes una cuenta? <a href="login.php">Inicia Sesión aquí</a>.</p>
<?php else: ?>
    <p><a href="login.php" class="button">Ir a Iniciar Sesión</a></p>
<?php endif; ?>
</div>
</div>
</div>
<script src="<?php echo BASE_URL; ?>js/registro-validation.js"></script>
<?php require_once 'includes/footer.php'; ?>