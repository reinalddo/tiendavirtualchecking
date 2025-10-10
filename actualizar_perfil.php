<?php
// actualizar_perfil.php
session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$nombre_pila = trim($_POST['nombre_pila']);
$apellido = trim($_POST['apellido']);
$telefono = trim($_POST['telefono']);
$rif_cedula = trim($_POST['rif_cedula']);
$direccion = trim($_POST['direccion']);
$avatar_path = null;

try {
    // --- LÓGICA PARA CAMBIAR CONTRASEÑA (VERSIÓN SEGURA Y COMPLETA) ---
    $password_nueva = $_POST['password_nueva'];

    // Solo intentamos cambiar la contraseña si el campo "Contraseña Nueva" no está vacío
    if (!empty($password_nueva)) {
        $password_actual = $_POST['password_actual'];
        $password_confirm = $_POST['password_confirm'];

        // 1. Validar que todos los campos necesarios estén llenos
        if (empty($password_actual) || empty($password_confirm)) {
            throw new Exception("Debes llenar todos los campos de contraseña para cambiarla.");
        }
        
        // 2. Validar que la contraseña actual sea correcta
        $stmt_user = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt_user->execute([$usuario_id]);
        $hash_actual = $stmt_user->fetchColumn();

        if (!$hash_actual || !password_verify($password_actual, $hash_actual)) {
            throw new Exception("La contraseña actual es incorrecta.");
        }

        // 3. Validar que la nueva contraseña y su confirmación coincidan
        if ($password_nueva !== $password_confirm) {
            throw new Exception("Las contraseñas nuevas no coinciden.");
        }

        // 4. Validar la complejidad de la nueva contraseña
        if (strlen($password_nueva) < 8 || !preg_match('/[A-Z]/', $password_nueva) || !preg_match('/[0-9]/', $password_nueva)) {
            throw new Exception("La nueva contraseña debe tener al menos 8 caracteres, una mayúscula y un número.");
        }
        
        // Si todo es correcto, hasheamos y guardamos la nueva contraseña
        $hashed_password = password_hash($password_nueva, PASSWORD_DEFAULT);
        $stmt_update_pass = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt_update_pass->execute([$hashed_password, $usuario_id]);
    }
    // --- FIN LÓGICA DE CONTRASEÑA ---

// Subir nuevo avatar si se proporciona
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/avatars/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $filename = uniqid() . '-' . basename($_FILES['avatar']['name']);
    $destination = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
        $avatar_path = $filename;

        // Eliminar el avatar manual anterior si existe
        $stmt_select_old_avatar = $pdo->prepare("SELECT avatar_manual FROM usuarios WHERE id = ?");
        $stmt_select_old_avatar->execute([$usuario_id]);
        $old_avatar = $stmt_select_old_avatar->fetchColumn();
        if (!empty($old_avatar) && file_exists($upload_dir . $old_avatar)) {
            unlink($upload_dir . $old_avatar);
        }
    } else {
        $_SESSION['mensaje_carrito'] = 'Error al subir el avatar.';
        header('Location: ' . BASE_URL . 'editar_perfil.php');
        exit();
    }
}

// Actualizar los datos en la base de datos
$sql = "UPDATE usuarios SET nombre_pila = ?, apellido = ?, telefono = ?, rif_cedula = ?, direccion = ? ";
$params = [$nombre_pila, $apellido, $telefono, $rif_cedula, $direccion];

if ($avatar_path !== null) {
    $sql .= ", avatar_manual = ?, avatar_url = NULL"; // Si se sube un avatar manual, anulamos el de Google
    $params[] = $avatar_path;
}

$sql .= " WHERE id = ?";
$params[] = $usuario_id;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Actualizamos el nombre en la sesión
$_SESSION['usuario_nombre'] = $nombre_pila;

if ($avatar_path !== null) {
    $_SESSION['usuario_avatar'] = BASE_URL . 'uploads/avatars/' . $avatar_path;
}

$_SESSION['mensaje_carrito'] = '¡Tu perfil ha sido actualizado!';
} catch (Exception $e) {
    $_SESSION['mensaje_carrito'] = 'Error: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . 'editar-perfil');
exit();
?>