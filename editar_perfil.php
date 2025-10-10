<?php
// editar_perfil.php
require_once 'includes/config.php'; // config.php inicia la sesión
require_once 'includes/db_connection.php';

// Seguridad: Solo usuarios logueados
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'login');
    exit();
}
$usuario_id = $_SESSION['usuario_id'];

// Obtener los datos actuales del usuario
$stmt_user = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt_user->execute([$usuario_id]);
$usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);

require_once 'includes/header.php';

?>

<main>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h2 class="my-0 fw-normal fs-4">Editar Mi Perfil</h2>
                    </div>
                    <div class="card-body">
                        <form action="datos/actualizar" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre_pila" class="form-label">Nombre:</label>
                                    <input type="text" name="nombre_pila" class="form-control" value="<?php echo htmlspecialchars($usuario['nombre_pila'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apellido" class="form-label">Apellido:</label>
                                    <input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($usuario['apellido'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email:</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" disabled>
                                <small class="form-text text-muted">El email no se puede cambiar.</small>
                            </div>

                            <hr>
                            <h5 class="mt-4">Cambiar Contraseña</h5>

                            <?php 
                            // Verificamos si el usuario tiene una contraseña local. 
                            // Los usuarios de Google tienen el campo 'password' vacío.
                            if (!empty($usuario['password'])): 
                            ?>
                                <p class="text-muted"><small>Deja estos campos en blanco si no deseas cambiar tu contraseña.</small></p>

                                <div class="mb-3 position-relative">
                                    <label for="password_actual" class="form-label">Contraseña Actual:</label>
                                    <input type="password" name="password_actual" id="password_actual" class="form-control">
                                    <i class="bi bi-eye-slash-fill toggle-password" style="cursor: pointer; position: absolute; right: 10px; top: 38px; transform: translateY(0);"></i>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3 position-relative">
                                        <label for="password_nueva" class="form-label">Contraseña Nueva:</label>
                                        <input type="password" name="password_nueva" id="password_nueva" class="form-control">
                                        <i class="bi bi-eye-slash-fill toggle-password" style="cursor: pointer; position: absolute; right: 18px; top: 38px; transform: translateY(0);"></i>
                                        <div id="password-feedback" class="form-text"></div>
                                    </div>
                                    <div class="col-md-6 mb-3 position-relative">
                                        <label for="password_confirm" class="form-label">Confirmar Contraseña Nueva:</label>
                                        <input type="password" name="password_confirm" id="password_confirm" class="form-control">
                                        <i class="bi bi-eye-slash-fill toggle-password" style="cursor: pointer; position: absolute; right: 18px; top: 38px; transform: translateY(0);"></i>
                                        <div id="password-confirm-feedback" class="form-text"></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    Iniciaste sesión con Google, por lo que no necesitas gestionar una contraseña aquí.
                                </div>
                            <?php endif; ?>
                            <hr>

                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono:</label>
                                <input type="tel" name="telefono" class="form-control" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="rif_cedula" class="form-label">RIF o Cédula:</label>
                                <input type="text" name="rif_cedula" class="form-control" value="<?php echo htmlspecialchars($usuario['rif_cedula'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección Fiscal:</label>
                                <textarea name="direccion" class="form-control" rows="3"><?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="avatar" class="form-label">Avatar (opcional):</label>
                                <input class="form-control" type="file" id="avatar" name="avatar" accept="image/*">
                                <?php if (!empty($usuario['avatar_manual'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo BASE_URL . 'uploads/avatars/' . htmlspecialchars($usuario['avatar_manual']); ?>" alt="Avatar Actual" class="img-thumbnail" style="max-width: 100px;">
                                        <small class="form-text text-muted">Avatar actual.</small>
                                    </div>
                                <?php elseif (empty($usuario['avatar_url'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo BASE_URL . 'avatar/avatar-default.png'; ?>" alt="Avatar Predeterminado" class="img-thumbnail" style="max-width: 100px;">
                                        <small class="form-text text-muted">Avatar predeterminado.</small>
                                    </div>
                                <?php elseif (!empty($usuario['avatar_url'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($usuario['avatar_url']); ?>" alt="Avatar de Google" class="img-thumbnail" style="max-width: 100px;">
                                        <small class="form-text text-muted">Avatar de Google.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="js/main.js"></script>
<script src="js/perfil-validation.js"></script>

<?php require_once 'includes/footer.php'; ?>