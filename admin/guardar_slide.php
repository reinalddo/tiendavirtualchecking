<?php
// admin/guardar_slide.php
//session_start();
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
// ... (Verificación de seguridad de admin) ...
$titulo = $_POST['titulo'] ?? '';
$enlace_final = '';

// Priorizamos el producto seleccionado
if (!empty($_POST['producto_id_enlace'])) {
    $producto_id = (int)$_POST['producto_id_enlace'];
    $enlace_final = 'producto_detalle.php?id=' . $producto_id;
} 
// Si no hay producto, usamos la URL manual
elseif (!empty($_POST['enlace_url'])) {
    $enlace_final = trim($_POST['enlace_url']);
}

// Procesar la subida
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    // Crear carpeta si no existe
    $upload_dir = '../uploads/hero/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $nombre_archivo = uniqid() . '-' . basename($_FILES['imagen']['name']);
    $ruta_destino = $upload_dir . $nombre_archivo;
    
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
        // Guardar en la BD
        $titulo = $_POST['titulo'] ?? '';
        $enlace_url = $_POST['enlace_url'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO hero_gallery (imagen_url, titulo, enlace_url) VALUES (?, ?, ?)");
        $stmt->execute([$nombre_archivo, $titulo, $enlace_final]);
        $_SESSION['mensaje_carrito'] = '¡Slide añadido exitosamente!';
    } else {
        $_SESSION['mensaje_carrito'] = 'Error: No se pudo subir la imagen.';
    }
} else {
    $_SESSION['mensaje_carrito'] = 'Error: Hubo un problema con la subida del archivo.';
}
header('Location: ' . BASE_URL . 'admin/gestionar_galeria_inicio.php');
exit();
?>