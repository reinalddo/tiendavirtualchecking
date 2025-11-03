<?php
// admin/eliminar_imagen_galeria.php
require_once '../includes/config.php';
verificar_sesion_admin();

$item_id = $_GET['id'];
$producto_id = $_GET['producto_id']; // Para redirigir de vuelta

// Opcional pero recomendado: eliminar el archivo físico si es una imagen
$stmt = $pdo->prepare("SELECT * FROM producto_galeria WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if ($item) {
    if ($item['tipo'] == 'imagen') {
        $ruta_archivo = '../uploads/' . $item['url'];
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
    } elseif ($item['tipo'] == 'video_archivo') {
        $ruta_archivo = '../uploads/videos/' . $item['url'];
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
    }
}

// Eliminar el registro de la base de datos
$stmt_delete = $pdo->prepare("DELETE FROM producto_galeria WHERE id = ?");
$stmt_delete->execute([$item_id]);

$_SESSION['mensaje_carrito'] = 'Ítem de la galería eliminado.';
header("Location: " . BASE_URL . "panel/producto/editar/" . $producto_id);
exit();
?>