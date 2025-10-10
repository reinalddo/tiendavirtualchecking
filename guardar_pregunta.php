<?php
// guardar_pregunta.php (Versión con Notificaciones)
require_once 'includes/config.php';

// Verificaciones de seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST' 
    || !isset($_SESSION['usuario_id']) 
    || empty($_POST['pregunta']) 
    || empty($_POST['producto_id'])) {
    
    header('Location: ' . BASE_URL);
    exit();
}

$pregunta_texto = trim($_POST['pregunta']);
$producto_id = $_POST['producto_id'];
$usuario_id = $_SESSION['usuario_id'];

try {
    // Insertar la pregunta en la base de datos
    $stmt = $pdo->prepare("INSERT INTO preguntas_respuestas (producto_id, usuario_id, pregunta) VALUES (?, ?, ?)");
    $stmt->execute([$producto_id, $usuario_id, $pregunta_texto]);
    $pregunta_id = $pdo->lastInsertId();

    // --- INICIO DE LA NUEVA LÓGICA DE NOTIFICACIÓN ---
    
    // 1. Obtenemos el nombre del producto y del cliente para el mensaje
    $stmt_info = $pdo->prepare("SELECT p.nombre as nombre_producto, u.nombre_pila as nombre_cliente 
                               FROM productos p, usuarios u 
                               WHERE p.id = ? AND u.id = ?");
    $stmt_info->execute([$producto_id, $usuario_id]);
    $info = $stmt_info->fetch();

    // 2. Obtenemos los IDs de todos los administradores
    $admins_ids = obtener_admins($pdo);
    
    // 3. Creamos el mensaje y la URL de la notificación
    $mensaje_admin = "Nueva pregunta de " . ($info['nombre_cliente'] ?? 'un cliente') . " en el producto '" . ($info['nombre_producto'] ?? 'N/A') . "'.";
    $url_notificacion = BASE_URL . "panel/pregunta/responder/" . $pregunta_id;

    // 4. Enviamos la notificación a cada administrador
    foreach ($admins_ids as $admin_id) {
        crear_notificacion($pdo, $admin_id, $mensaje_admin, $url_notificacion);
    }
    // --- FIN DE LA LÓGICA DE NOTIFICACIÓN ---

    // Obtenemos el slug para la redirección (ya lo teníamos)
    $stmt_slug = $pdo->prepare("SELECT slug FROM productos WHERE id = ?");
    $stmt_slug->execute([$producto_id]);
    $producto_slug = $stmt_slug->fetchColumn();
    $url_redireccion = BASE_URL . 'producto/' . $producto_slug . '#qa-tab'; // Apunta a la pestaña de preguntas

    $_SESSION['mensaje_carrito'] = '¡Tu pregunta ha sido enviada!';
    header('Location: ' . $url_redireccion);
    exit();

} catch (PDOException $e) {
    // Manejo de errores de base de datos
    $_SESSION['mensaje_carrito'] = 'Error: No se pudo enviar tu pregunta.';
    // Redirigir a una página anterior o mostrar un error
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}
?>