<?php
// subir_comprobante.php (Versión Mejorada)
require_once 'includes/config.php'; // Incluye $pdo y helpers

// --- Función para redirigir con mensaje ---
function redirect_with_message($message, $is_error = true) {
    $_SESSION['mensaje_carrito'] = ($is_error ? 'Error: ' : '') . $message;
    header('Location: ' . BASE_URL . 'perfil'); // Siempre redirigir a perfil
    exit();
}

// --- Verificaciones Iniciales ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id']) || empty($_POST['pedido_id'])) {
    redirect_with_message('Acceso inválido.');
}

$pedido_id = $_POST['pedido_id'];
$usuario_id = $_SESSION['usuario_id'];

// Verificar que el archivo fue subido y no hubo errores iniciales
if (!isset($_FILES['comprobante']) || !is_uploaded_file($_FILES['comprobante']['tmp_name'])) {
     redirect_with_message('No se recibió ningún archivo o hubo un error al subirlo.');
}

$comprobante = $_FILES['comprobante'];

// Verificar errores específicos de la subida
if ($comprobante['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'Error desconocido al subir el archivo.';
    switch ($comprobante['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $error_message = 'El archivo es demasiado grande.';
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_message = 'El archivo se subió solo parcialmente.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_message = 'No se seleccionó ningún archivo.';
            break;
    }
     redirect_with_message($error_message);
}

// --- Validación del Pedido ---
try {
    $stmt_check = $pdo->prepare("SELECT id FROM pedidos WHERE id = ? AND usuario_id = ?");
    $stmt_check->execute([$pedido_id, $usuario_id]);
    if ($stmt_check->fetchColumn() === false) {
        redirect_with_message('No tienes permiso para subir un comprobante para este pedido.');
    }
} catch (PDOException $e) {
     error_log("Error DB verificando pedido para comprobante: " . $e->getMessage());
     redirect_with_message('Error al verificar el pedido.');
}

// --- Procesar y Mover Archivo ---
$upload_dir = 'comprobantes/'; // Relativo a la raíz del proyecto
$nombre_original = basename($comprobante['name']);
$extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
$nombre_archivo_seguro = 'pedido_' . $pedido_id . '_' . uniqid() . '.' . $extension;
$ruta_destino = $upload_dir . $nombre_archivo_seguro;

// Crear directorio si no existe
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        error_log("Error: No se pudo crear el directorio de comprobantes: " . $upload_dir);
         redirect_with_message('Error interno del servidor al preparar la subida.');
    }
}

// Mover el archivo subido
if (move_uploaded_file($comprobante['tmp_name'], $ruta_destino)) {
    // --- Guardar en Base de Datos ---
    try {
        // Opcional: Eliminar comprobantes anteriores para este pedido si solo quieres guardar el último
        // $stmt_delete_old = $pdo->prepare("DELETE FROM comprobantes_pago WHERE pedido_id = ?");
        // $stmt_delete_old->execute([$pedido_id]);

        // Insertar nuevo comprobante (estado 'pendiente' por defecto)
        $stmt_insert = $pdo->prepare("INSERT INTO comprobantes_pago (pedido_id, url_comprobante, estado) VALUES (?, ?, 'pendiente')");
        $stmt_insert->execute([$pedido_id, $nombre_archivo_seguro]);

        // --- Notificar a Admins ---
        $admins_ids = obtener_admins($pdo);
        $mensaje_admin = "El cliente ha subido un comprobante para el pedido #" . $pedido_id;
        $url_admin = BASE_URL . "panel/pedido/" . $pedido_id;
        foreach ($admins_ids as $admin_id) {
            crear_notificacion($pdo, $admin_id, $mensaje_admin, $url_admin);
        }

        // --- Éxito ---
        redirect_with_message('¡Comprobante subido exitosamente! Será revisado pronto.', false); // false = no es error

    } catch (PDOException $e) {
         error_log("Error DB guardando comprobante: " . $e->getMessage());
         // Intentar borrar el archivo físico si falla el guardado en BD
         if (file_exists($ruta_destino)) { unlink($ruta_destino); }
         redirect_with_message('Error al guardar la información del comprobante.');
    }
} else {
    // Error al mover el archivo
    error_log("Error: move_uploaded_file falló para " . $ruta_destino . ". Verifica permisos.");
    redirect_with_message('No se pudo guardar el archivo subido en el servidor.');
}

?>