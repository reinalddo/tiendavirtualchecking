<?php
// descargar_producto.php
require_once 'includes/config.php'; // Incluye $pdo y session_start()

// 1. Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    // Si no está logueado, redirigir al login o mostrar un error simple
    $_SESSION['mensaje_carrito'] = 'Error: Debes iniciar sesión para descargar tus productos.';
    header('Location: ' . BASE_URL . 'login');
    exit();
}
$usuario_id = $_SESSION['usuario_id'];

// 2. Obtener el token de la URL (el .htaccess nos lo pasará como un parámetro GET 'token')
$token_descarga = $_GET['token'] ?? '';

if (empty($token_descarga) || !ctype_alnum($token_descarga)) { // Validar formato básico
    die('Enlace de descarga inválido o corrupto.');
}

try {
    // 3. Buscar el registro de descarga usando el token
    $stmt_descarga = $pdo->prepare("
        SELECT 
            pdg.id as descarga_id, 
            pdg.descargas_restantes, 
            pdg.fecha_expiracion,
            p.archivo_digital_ruta, 
            p.archivo_digital_nombre
        FROM pedidos_descargas pdg
        JOIN productos p ON pdg.producto_id = p.id
        WHERE pdg.token_descarga = ? AND pdg.usuario_id = ?
    ");
    $stmt_descarga->execute([$token_descarga, $usuario_id]);
    $descarga_info = $stmt_descarga->fetch(PDO::FETCH_ASSOC);

    // 4. Validar el enlace
    if (!$descarga_info) {
        die('Enlace de descarga inválido o no te pertenece.');
    }
    if ($descarga_info['fecha_expiracion'] && new DateTime() > new DateTime($descarga_info['fecha_expiracion'])) {
        die('Este enlace de descarga ha expirado.');
    }
    if (!is_null($descarga_info['descargas_restantes']) && $descarga_info['descargas_restantes'] <= 0) {
        die('Has alcanzado el límite de descargas para este producto.');
    }
    if (empty($descarga_info['archivo_digital_ruta'])) {
         die('Error: El archivo asociado a esta descarga no se encuentra.');
    }

    // 5. Construir la ruta completa al archivo
    $ruta_archivo = __DIR__ . '/uploads/digital_products/' . $descarga_info['archivo_digital_ruta'];

    if (!file_exists($ruta_archivo) || !is_readable($ruta_archivo)) {
        error_log("Error descarga: Archivo no encontrado o sin permisos en ruta: " . $ruta_archivo);
        die('Error: No se pudo acceder al archivo en el servidor. Contacta a soporte.');
    }

    // 6. Actualizar contador de descargas (si aplica)
    if (!is_null($descarga_info['descargas_restantes'])) {
        $stmt_update = $pdo->prepare("UPDATE pedidos_descargas SET descargas_restantes = descargas_restantes - 1 WHERE id = ?");
        $stmt_update->execute([$descarga_info['descarga_id']]);
    }

    // 7. Preparar y enviar cabeceras HTTP para la descarga
    header('Content-Description: File Transfer');
    // Detectar tipo MIME (requiere extensión fileinfo habilitada)
    if (function_exists('mime_content_type')) {
         header('Content-Type: ' . mime_content_type($ruta_archivo));
    } else {
         header('Content-Type: application/octet-stream'); // Genérico si fileinfo no está
    }
    header('Content-Disposition: attachment; filename="' . basename($descarga_info['archivo_digital_nombre']) . '"'); // Usa el nombre original
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($ruta_archivo));
    
    // Limpiar buffer de salida por si acaso
    ob_clean();
    flush(); 
    
    // 8. Leer y enviar el archivo al navegador
    readfile($ruta_archivo);
    exit; // Terminar ejecución después de enviar el archivo

} catch (PDOException $e) {
    error_log("Error de BD en descarga: " . $e->getMessage());
    die('Error al procesar tu solicitud de descarga. Intenta más tarde.');
} catch (Exception $e) { // Captura otras excepciones generales
    error_log("Error general en descarga: " . $e->getMessage());
     die('Ocurrió un error inesperado durante la descarga.');
}

?>