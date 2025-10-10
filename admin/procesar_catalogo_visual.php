<?php
// admin/procesar_catalogo_visual.php
ini_set('memory_limit', '512M'); // Aumentamos la memoria para procesar imágenes
set_time_limit(300); // Aumentamos el tiempo máximo de ejecución a 5 minutos
require_once '../includes/config.php';
verificar_sesion_admin();

// Obtener filtros del formulario (igual que en el otro script)
$categoria_id = $_POST['categoria_id'] ?? 'todas';
$stock_filter = $_POST['stock'] ?? 'todos';

// --- Consulta SQL Modificada ---
// Ahora obtenemos hasta 2 imágenes por producto usando GROUP_CONCAT
$sql = "SELECT p.nombre, p.precio_usd, p.precio_descuento, 
               GROUP_CONCAT(pg.url ORDER BY pg.orden ASC, pg.id ASC SEPARATOR '||') as imagenes
        FROM productos p
        LEFT JOIN producto_categorias pc ON p.id = pc.producto_id
        LEFT JOIN producto_galeria pg ON p.id = pg.producto_id AND pg.tipo = 'imagen'
        WHERE p.es_activo = 1";
$params = [];

if ($categoria_id !== 'todas') { /* ... (lógica de filtros igual que antes) ... */ }
if ($stock_filter === 'con_stock') { /* ... */ }
// ...

$sql .= " GROUP BY p.id ORDER BY p.nombre ASC";
$stmt_productos = $pdo->prepare($sql);
$stmt_productos->execute($params);
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// --- INICIO DE LA NUEVA FUNCIÓN DE OPTIMIZACIÓN ---
/**
 * Redimensiona una imagen a un ancho máximo para optimizarla para el PDF.
 * @param string $ruta_original La ruta del archivo de la imagen original.
 * @param int $ancho_maximo El ancho máximo que tendrá la imagen redimensionada.
 * @return string La ruta a la nueva imagen temporal redimensionada.
 */
function redimensionar_imagen_para_pdf($ruta_original, $ancho_maximo = 800) {
    if (!file_exists($ruta_original)) {
        return $ruta_original; // Devuelve la ruta original si no existe para que muestre el error de imagen no encontrada
    }

    list($ancho, $alto) = getimagesize($ruta_original);
    // Si la imagen ya es pequeña, no la procesamos
    if ($ancho <= $ancho_maximo) {
        return $ruta_original;
    }

    $proporcion = $alto / $ancho;
    $nuevo_ancho = $ancho_maximo;
    $nuevo_alto = $ancho_maximo * $proporcion;

    $imagen_p = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
    $tipo = exif_imagetype($ruta_original);

    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imagen = imagecreatefromjpeg($ruta_original);
            break;
        case IMAGETYPE_PNG:
            $imagen = imagecreatefrompng($ruta_original);
            imagealphablending($imagen_p, false);
            imagesavealpha($imagen_p, true);
            break;
        case IMAGETYPE_GIF:
            $imagen = imagecreatefromgif($ruta_original);
            break;
        default:
            return $ruta_original; // Si es un formato no soportado, usamos la original
    }

    imagecopyresampled($imagen_p, $imagen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
    
    // Guardamos la imagen redimensionada en una carpeta temporal
    $temp_dir = __DIR__ . '/temp_pdf_images/';
    if (!is_dir($temp_dir)) { mkdir($temp_dir, 0755, true); }
    $ruta_temporal = $temp_dir . basename($ruta_original);

    imagejpeg($imagen_p, $ruta_temporal, 85); // Guardamos como JPG con calidad 85
    imagedestroy($imagen);
    imagedestroy($imagen_p);

    return $ruta_temporal;
}
// --- FIN DE LA NUEVA FUNCIÓN ---

// --- CREACIÓN DEL PDF ---
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
// ... (Configuración del PDF: SetCreator, SetAuthor, etc.) ...
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);

$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

$pdf->writeHTML('<h1>Catálogo de Productos</h1><br><br>', true, false, true, false, '');


$contador_productos_pagina = 0;
$total_productos = count($productos);

$contador = 0;
foreach ($productos as $index => $producto) {
    // Si la página está llena (4 productos) y no es el último producto, añadimos una nueva página
    if ($contador_productos_pagina > 0 && $contador_productos_pagina % 1 == 0 && ($index) < $total_productos) {
        $pdf->AddPage();
        $pdf->writeHTML('<h1>Catálogo de Productos</h1><br><br>', true, false, true, false, '');
        $contador_productos_pagina = 0; // Reiniciamos el contador para la nueva página
    }

    //if ($contador % 2 == 0) $html .= '<tr>';

    $imagenes_urls = !empty($producto['imagenes']) ? explode('||', $producto['imagenes']) : [];
    $ruta_base_servidor = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . 'uploads/';
    
    // Optimizamos la imagen principal
    $imagen_principal_original = !empty($imagenes_urls[0]) ? $ruta_base_servidor . $imagenes_urls[0] : null;
    $imagen_principal_optimizada = redimensionar_imagen_para_pdf($imagen_principal_original);
    if ($imagen_principal_optimizada !== $imagen_principal_original) $imagenes_temporales[] = $imagen_principal_optimizada;
    
    $precio_html = format_price($producto['precio_usd'], $producto['precio_descuento']);
    
    $html_producto = '
    <table cellpadding="5" cellspacing="0" style="border: 1px solid #eee; width: 98%; display:inline-block; margin:1%; vertical-align: top;">
        <tr>
            <td style="text-align:center; height: 250px;">
                <img src="' . $imagen_principal_optimizada . '" style="max-width:100%; max-height:250px; height:auto; width:auto;">
            </td>
        </tr>
        <tr>
            <td style="text-align:center;">
                <h3 style="font-size: 20pt;">' . htmlspecialchars($producto['nombre']) . '</h3>
                <p style="font-size: 18pt; font-weight: bold;">' . $precio_html . '</p>
            </td>
        </tr>
    </table>';

    // Escribimos el HTML del producto en el PDF
    $pdf->writeHTML($html_producto, true, false, true, false, '');
    
    $contador_productos_pagina++;
}

//$html .= '</table>';
//$pdf->writeHTML($html, true, false, true, false, '');

// --- LIMPIEZA DE IMÁGENES TEMPORALES ---
foreach ($imagenes_temporales as $ruta_temp) {
    if (file_exists($ruta_temp)) {
        unlink($ruta_temp);
    }
}
if (is_dir(__DIR__ . '/temp_pdf_images/')) { rmdir(__DIR__ . '/temp_pdf_images/'); }
// --- FIN DE LA LIMPIEZA ---

$pdf->Output('catalogo_visual.pdf', 'I');
exit();
?>