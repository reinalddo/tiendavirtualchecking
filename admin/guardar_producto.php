<?php
// admin/guardar_producto.php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/config.php';

// 1. Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Recoger datos del formulario (ya no se incluye moneda_id)
$id = $_POST['id'] ?? null;
$nombre = trim($_POST['nombre'] ?? '');
$descripcion_html = trim($_POST['descripcion_html'] ?? '');
$precio_usd = $_POST['precio_usd'] ?? 0;
$stock = max(0, (int)($_POST['stock'] ?? 0));
$es_activo = isset($_POST['es_activo']) ? 1 : 0;
$es_fisico = isset($_POST['es_fisico']) ? 1 : 0;
$categorias = $_POST['categorias'] ?? [];
$sku = trim($_POST['sku'] ?? '');
$precio_descuento = !empty($_POST['precio_descuento'] ?? '') ? $_POST['precio_descuento'] ?? '' : null;

$producto_id = $id;

try {
    if (empty($id)) {
        $sql = "INSERT INTO productos (nombre, sku, descripcion_html, precio_usd, stock, es_activo, es_fisico, precio_descuento) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $sku, $descripcion_html, $precio_usd, $stock, $es_activo, $es_fisico, $precio_descuento]);
        $producto_id = $pdo->lastInsertId();
    } else {
        $sql = "UPDATE productos SET nombre = ?, sku = ?, descripcion_html = ?, precio_usd = ?, stock = ?, es_activo = ?, es_fisico = ?, precio_descuento = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $sku, $descripcion_html, $precio_usd, $stock, $es_activo, $es_fisico, $precio_descuento, $id]);
        
        $stmt_delete_cat = $pdo->prepare("DELETE FROM producto_categorias WHERE producto_id = ?");
        $stmt_delete_cat->execute([$producto_id]);
    }

    // Si todo va bien, limpiamos los datos del formulario de la sesión
    unset($_SESSION['form_data']);

    // 4. Asignar las categorías
    if (!empty($categorias)) {
        $sql_cat = "INSERT INTO producto_categorias (producto_id, categoria_id) VALUES (?, ?)";
        $stmt_cat = $pdo->prepare($sql_cat);
        foreach ($categorias as $categoria_id) {
            $stmt_cat->execute([$producto_id, $categoria_id]);
        }
    }

    // 5. Lógica para la Galería
    if (isset($_FILES['imagenes']) && $_FILES['imagenes']['error'][0] != UPLOAD_ERR_NO_FILE) {
        $sql_galeria = "INSERT INTO producto_galeria (producto_id, tipo, url) VALUES (?, 'imagen', ?)";
        $stmt_galeria = $pdo->prepare($sql_galeria);

        foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['imagenes']['error'][$key] === UPLOAD_ERR_OK) {
                $nombre_archivo = uniqid() . '-' . basename($_FILES['imagenes']['name'][$key]);
                $ruta_destino = '../uploads/' . $nombre_archivo;
                
                if (move_uploaded_file($tmp_name, $ruta_destino)) {
                    $stmt_galeria->execute([$producto_id, $nombre_archivo]);
                } else {
                    throw new Exception("Error al mover el archivo subido. Verifica los permisos de la carpeta 'uploads'.");
                }
            }
        }
    }

    // Procesar URL de video de YouTube
    if (isset($_POST['video_youtube']) && !empty($_POST['video_youtube'])) {
        $video_url = trim($_POST['video_youtube']);
        parse_str(parse_url($video_url, PHP_URL_QUERY), $vars);
        $video_id = $vars['v'] ?? '';
        if (!empty($video_id)) {
            $sql_video = "INSERT INTO producto_galeria (producto_id, tipo, url) VALUES (?, 'youtube', ?)";
            $stmt_video = $pdo->prepare($sql_video);
            $stmt_video->execute([$producto_id, $video_id]);
        }
    }

    // Procesar imágenes seleccionadas desde la Biblioteca de Medios
    if (isset($_POST['gallery_from_library']) && is_array($_POST['gallery_from_library'])) {
        $sql_galeria_bib = "INSERT INTO producto_galeria (producto_id, tipo, url) VALUES (?, 'imagen', ?)";
        $stmt_galeria_bib = $pdo->prepare($sql_galeria_bib);

        foreach ($_POST['gallery_from_library'] as $nombre_archivo) {
            // Nos aseguramos de que el archivo no sea vacío
            if (!empty($nombre_archivo)) {
                $stmt_galeria_bib->execute([$producto_id, $nombre_archivo]);
            }
        }
    }

    
    $_SESSION['mensaje_carrito'] = '¡Producto guardado exitosamente!';
    header("Location: gestionar_productos.php");
    exit();

} catch (Exception $e) {
    // Verificamos si el error es por una entrada duplicada (código 23000)
    if ($e->getCode() == 23000) {
        $_SESSION['mensaje_carrito'] = 'Error: El SKU "' . htmlspecialchars($sku) . '" ya existe. Por favor, introduce un código único.';
    } else {
        $_SESSION['mensaje_carrito'] = "Error al guardar el producto: " . $e->getMessage();
    }

    // Redirigimos de vuelta al formulario
    $redirect_url = empty($id) ? 'formulario_producto.php' : 'formulario_producto.php?id=' . $id;
    header("Location: " . $redirect_url);
    exit();
}
?>