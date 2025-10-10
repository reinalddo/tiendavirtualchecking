<?php
// subir_comprobante.php
//session_start();
require_once 'includes/config.php';
//require_once 'includes/db_connection.php';
// Verificaciones
if ($_SERVER['REQUEST_METHOD'] !== 'POST' 
    || !isset($_SESSION['usuario_id']) 
    || empty($_POST['pedido_id'])) {
    
    // Si hay un acceso inválido, redirigimos al inicio
    header('Location: ' . BASE_URL);
    exit();
}

$pedido_id = $_POST['pedido_id'];
$usuario_id = $_SESSION['usuario_id'];
$comprobante = $_FILES['comprobante'];

// Verificar que el pedido pertenezca al usuario
$stmt_check = $pdo->prepare("SELECT id FROM pedidos WHERE id = ? AND usuario_id = ?");
$stmt_check->execute([$pedido_id, $usuario_id]);
if ($stmt_check->fetchColumn() === false) {
    die('Acceso denegado.');
}

// Procesar la subida del archivo
if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
    // Crear carpeta si no existe
    if (!is_dir('comprobantes')) {
        mkdir('comprobantes', 0755, true);
    }

    $nombre_archivo = 'pedido_' . $pedido_id . '_' . uniqid() . '-' . basename($comprobante['name']);
    $ruta_destino = 'comprobantes/' . $nombre_archivo;
    
    if (move_uploaded_file($comprobante['tmp_name'], $ruta_destino)) {
        // Guardar en la base de datos
        $stmt_insert = $pdo->prepare("INSERT INTO comprobantes_pago (pedido_id, url_comprobante) VALUES (?, ?)");
        $stmt_insert->execute([$pedido_id, $nombre_archivo]);

        // --- GENERAR NOTIFICACIONES ---
        $admins_ids = obtener_admins($pdo);
        $mensaje_admin = "El cliente ha subido un comprobante para el pedido #" . $pedido_id;
        $url_admin = BASE_URL . "panel/pedido/" . $pedido_id;

        foreach ($admins_ids as $admin_id) {
            crear_notificacion($pdo, $admin_id, $mensaje_admin, $url_admin);
        }
        // --- FIN NOTIFICACIONES ---

        $_SESSION['mensaje_carrito'] = '¡Comprobante subido exitosamente!';
    } else {
        $_SESSION['mensaje_carrito'] = 'Error: No se pudo subir el archivo.';
    }
} else {
    $_SESSION['mensaje_carrito'] = 'Error en la subida del archivo.';
}

header('Location: ' . BASE_URL . 'perfil');
exit();
?>