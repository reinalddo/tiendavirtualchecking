<?php
// login.php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password'])) {
            session_start();

            $avatar_final = '';
            if (!empty($usuario['avatar_url'])) {
                // Prioridad 1: Avatar de Google
                $avatar_final = $usuario['avatar_url'];
            } elseif (!empty($usuario['avatar_manual'])) {
                // Prioridad 2: Avatar subido manualmente
                $avatar_final = BASE_URL . 'uploads/avatars/' . $usuario['avatar_manual'];
            } else {
                // Prioridad 3: Avatar por defecto
                $avatar_final = BASE_URL . 'avatar/avatar-default.png';
            }

            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre_pila'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            $_SESSION['usuario_avatar'] = $avatar_final; 

            if ($usuario['rol'] === 'admin') {
                header("Location: admin/panel_admin.php");
            } else {
                header("Location: perfil.php");
            }
            exit();
        } else {
            $error = 'Email o contraseña incorrectos.';
        }
    }
}


require_once 'includes/header.php';
?>
<main>
    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Iniciar Sesión</h2>

                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email:</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña:</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                            </div>
                        </form>
                        
                        <hr class="my-4">

                        <a href="google-login.php" id="googleLoginBtn" class="btn btn-danger w-100 mb-3">
                            Iniciar Sesión con Google
                        </a>
                        
                        <div class="text-center">
                            <p>¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a>.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>
<script>
document.getElementById('googleLoginBtn').addEventListener('click', function(e) {
    e.preventDefault(); // Prevenimos la redirección normal
    const href = this.href;
    const width = 600, height = 600;
    const left = (screen.width / 2) - (width / 2);
    const top = (screen.height / 2) - (height / 2);
    
    // Abrimos la URL de login de Google en una ventana popup centrada
    window.open(href, 'googleLogin', `width=${width},height=${height},top=${top},left=${left}`);
});
</script>

<?php require_once 'includes/footer.php'; ?>