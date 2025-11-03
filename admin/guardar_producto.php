<?php
// admin/guardar_producto.php
require_once '../includes/config.php';
verificar_sesion_admin();

// Recoger datos del formulario (ya no se incluye moneda_id)
$id = $_POST['id'] ?? null;
$nombre = trim($_POST['nombre'] ?? '');
$slug = generar_slug($nombre); // Generamos el slug a partir del nombre
$descripcion_html = trim($_POST['descripcion_html'] ?? '');
$precio_usd = $_POST['precio_usd'] ?? 0;
$stock = max(0, (int)($_POST['stock'] ?? 0));
$es_activo = isset($_POST['es_activo']) ? 1 : 0;
$es_fisico = isset($_POST['es_fisico']) ? 1 : 0;
$categorias = $_POST['categorias'] ?? [];
$sku = trim($_POST['sku'] ?? '');
$precio_descuento = !empty($_POST['precio_descuento'] ?? '') ? $_POST['precio_descuento'] ?? '' : null;

// === INICIO: Recoger y procesar nuevos campos ===
$tipo_producto = $_POST['tipo_producto'] ?? 'fisico';
$stock = ($tipo_producto == 'fisico') ? max(0, (int)($_POST['stock'] ?? 0)) : null; // Stock solo para físicos
$archivo_digital_nombre = null; // Nombre original
$archivo_digital_ruta = null;   // Nombre seguro en servidor

// Procesar subida de archivo digital SI se seleccionó "Digital" Y se subió un archivo
if ($tipo_producto == 'digital' && isset($_FILES['archivo_digital']) && $_FILES['archivo_digital']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['archivo_digital'];
    $max_size = 500 * 1024 * 1024; // Límite 500MB (ajustar si es necesario)

    if ($archivo['size'] > $max_size) {
        throw new Exception("Error: El archivo digital no puede superar los 50MB.");
    }

    $upload_dir = '../uploads/digital_products/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $archivo_digital_nombre = basename($archivo['name']); // Nombre original
    $extension = strtolower(pathinfo($archivo_digital_nombre, PATHINFO_EXTENSION));
    // Nombre seguro: tipo-idproducto(si existe)-timestamp.extension
    $nombre_seguro = 'digital-' . ($id ?? 'new') . '-' . time() . '.' . $extension;
    $archivo_digital_ruta = $nombre_seguro; // Guardamos solo el nombre seguro en la BD
    $ruta_destino = $upload_dir . $nombre_seguro;

    // Antes de mover, si estamos editando, borramos el archivo anterior si existe
    if ($id) {
        $stmt_old = $pdo->prepare("SELECT archivo_digital_ruta FROM productos WHERE id = ?");
        $stmt_old->execute([$id]);
        $ruta_antigua = $stmt_old->fetchColumn();
        if ($ruta_antigua && file_exists($upload_dir . $ruta_antigua)) {
            unlink($upload_dir . $ruta_antigua);
        }
    }

    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        throw new Exception("Error al mover el archivo digital subido. Verifica permisos.");
    }

} elseif ($id && $tipo_producto == 'digital') {
    // Si estamos editando un producto digital pero NO se subió un archivo nuevo,
    // mantenemos los datos del archivo existente en la BD.
    $stmt_keep = $pdo->prepare("SELECT archivo_digital_nombre, archivo_digital_ruta FROM productos WHERE id = ?");
    $stmt_keep->execute([$id]);
    $archivos_existentes = $stmt_keep->fetch(PDO::FETCH_ASSOC);
    if ($archivos_existentes) {
        $archivo_digital_nombre = $archivos_existentes['archivo_digital_nombre'];
        $archivo_digital_ruta = $archivos_existentes['archivo_digital_ruta'];
    }
}
// === FIN: Recoger y procesar nuevos campos ===

$producto_id = $id;

try {
    if (empty($id)) {
        $sql = "INSERT INTO productos (nombre, slug, sku, descripcion_html, precio_usd, stock, es_activo, tipo_producto, archivo_digital_nombre, archivo_digital_ruta, precio_descuento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nombre, $slug, $sku, $descripcion_html, $precio_usd, $stock, $es_activo,
            $tipo_producto, $archivo_digital_nombre, $archivo_digital_ruta, $precio_descuento
        ]);
        $producto_id = $pdo->lastInsertId();

        if ($tipo_producto == 'digital' && $archivo_digital_ruta) {
             $stmt_update_digital = $pdo->prepare("UPDATE productos SET archivo_digital_nombre = ?, archivo_digital_ruta = ? WHERE id = ?");
             $stmt_update_digital->execute([$archivo_digital_nombre, $archivo_digital_ruta, $producto_id]);
        }
    } else {
        $sql = "UPDATE productos SET 
                    nombre = ?, slug = ?, sku = ?, descripcion_html = ?, precio_usd = ?, 
                    stock = ?, es_activo = ?, tipo_producto = ?, 
                    archivo_digital_nombre = ?, archivo_digital_ruta = ?, precio_descuento = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nombre, $slug, $sku, $descripcion_html, $precio_usd, $stock, $es_activo,
            $tipo_producto, $archivo_digital_nombre, $archivo_digital_ruta, $precio_descuento,
            $id
        ]);        
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

    // Procesar subida de archivo de video
    if (isset($_FILES['video_archivo']) && $_FILES['video_archivo']['error'] === UPLOAD_ERR_OK) {
        $video_file = $_FILES['video_archivo'];
        
        $allowed_types = ['video/mp4'];
        $max_size = 20 * 1024 * 1024; // 20MB

        if (!in_array($video_file['type'], $allowed_types)) {
            throw new Exception("Error: El formato del video debe ser MP4.");
        }
        if ($video_file['size'] > $max_size) {
            throw new Exception("Error: El video no puede superar los 20MB.");
        }

        $upload_dir = '../uploads/videos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $nombre_archivo_video = uniqid() . '-' . basename(str_replace(' ', '-', $video_file['name']));
        $ruta_destino = $upload_dir . $nombre_archivo_video;
        
        if (move_uploaded_file($video_file['tmp_name'], $ruta_destino)) {
            $sql_video_archivo = "INSERT INTO producto_galeria (producto_id, tipo, url) VALUES (?, 'video_archivo', ?)";
            $stmt_video_archivo = $pdo->prepare($sql_video_archivo);
            $stmt_video_archivo->execute([$producto_id, $nombre_archivo_video]);
        } else {
            throw new Exception("Error al mover el archivo de video subido.");
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
    header("Location: " . BASE_URL . "panel/gestionar_productos");
    exit();

} catch (Exception $e) {
    // Guardar datos del formulario en sesión ANTES de redirigir
    $_SESSION['form_data'] = $_POST; 

    // Verificamos si el error es por una entrada duplicada (código 23000)
    if ($e instanceof PDOException && $e->getCode() == 23000) {
        // Verificar si es el SKU o el SLUG
        if (strpos($e->getMessage(), 'productos.sku') !== false) {
             $_SESSION['mensaje_carrito'] = 'Error: El SKU "' . htmlspecialchars($sku) . '" ya existe. Introduce un código único.';
        } elseif (strpos($e->getMessage(), 'productos.slug') !== false) {
             $_SESSION['mensaje_carrito'] = 'Error: Ya existe un producto con un nombre muy similar (slug duplicado). Cambia ligeramente el nombre.';
        } else {
             $_SESSION['mensaje_carrito'] = 'Error: Se ha producido un conflicto de datos únicos al guardar.';
        }
    } else {
        // Error genérico para otros problemas
        $_SESSION['mensaje_carrito'] = "Error al guardar el producto: " . $e->getMessage();
        // Log detallado para el administrador (opcional)
        error_log("Error guardando producto (ID: $id): " . $e->getMessage()); 
    }

    // Redirigimos de vuelta al formulario
    $redirect_url = empty($id) ? 'panel/producto/nuevo' : 'panel/producto/editar/' . $id;
    header("Location: " . BASE_URL . $redirect_url);
    exit();}
?>