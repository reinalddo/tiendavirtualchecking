<?php
// admin/procesar_catalogo.php
session_start();
require_once '../includes/config.php'; // Para BASE_URL y autoloader de Composer
require_once '../includes/db_connection.php';
require_once '../includes/helpers.php'; // Para la función format_price()

// Verificación de seguridad de admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    die('Acceso denegado.');
}

// Obtener datos del administrador logueado
$admin_id = $_SESSION['usuario_id'];
$stmt_admin = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt_admin->execute([$admin_id]);
$admin_data = $stmt_admin->fetch(PDO::FETCH_ASSOC);

// Obtener filtros del formulario
$categoria_id = $_POST['categoria_id'] ?? 'todas';
$stock_filter = $_POST['stock'] ?? 'todos';

// Construir la consulta SQL basada en los filtros
$sql = "SELECT p.*, c.nombre as nombre_categoria,
            (SELECT gal.url FROM producto_galeria gal WHERE gal.producto_id = p.id AND gal.tipo = 'imagen' ORDER BY gal.orden ASC, gal.id ASC LIMIT 1) as imagen_principal
        FROM productos p
        LEFT JOIN producto_categorias pc ON p.id = pc.producto_id
        LEFT JOIN categorias c ON pc.categoria_id = c.id
        WHERE p.es_activo = 1";
$params = [];

if ($categoria_id !== 'todas') {
    $sql .= " AND pc.categoria_id = ?";
    $params[] = (int)$categoria_id;
}
if ($stock_filter === 'con_stock') {
    $sql .= " AND p.stock > 0";
} elseif ($stock_filter === 'sin_stock') {
    $sql .= " AND p.stock = 0";
}
$sql .= " GROUP BY p.id ORDER BY p.nombre ASC";

$stmt_productos = $pdo->prepare($sql);
$stmt_productos->execute($params);
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// --- CREACIÓN DEL PDF CON TCPDF ---

// 1. Crear instancia del PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// 2. Establecer información del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($admin_data['nombre_pila']);
$pdf->SetTitle('Catálogo de Productos - Mi Tienda Web');
$pdf->SetSubject('Catálogo de Productos');

// 3. Configurar cabecera y pie de página (opcional pero recomendado)
$pdf->setPrintHeader(false); // Desactivamos la cabecera por defecto para usar la nuestra
$pdf->setPrintFooter(true); // Usamos el pie de página por defecto con el número de página

// 4. Añadir una página
$pdf->AddPage();

// 5. Construir el HTML para el contenido

// Cabecera con logo y datos del admin
$avatar_path = $_SERVER['DOCUMENT_ROOT'] . '/avatar/avatar-default.png'; // Por defecto
if (!empty($admin_data['avatar_manual']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/' . $admin_data['avatar_manual'])) {
    $avatar_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/' . $admin_data['avatar_manual'];
}
// Lógica para determinar el avatar correcto
if (!empty($admin_data['avatar_url'])) { // Google
    // TCPDF a veces tiene problemas con URLs externas, sería mejor descargarla temporalmente
    $avatar_url = $admin_data['avatar_url'];
} elseif (!empty($admin_data['avatar_manual'])) { // Manual
    $avatar_url = '../uploads/avatars/' . $admin_data['avatar_manual'];
}

$header_html = '
<table cellpadding="5">
    <tr>
        <td width="20%"><img src="'.$avatar_path.'" width="80"></td>
        <td width="80%">
            <h1>Catálogo de Productos</h1>
            <p><strong>Generado por:</strong> ' . htmlspecialchars($admin_data['nombre_pila'] ?? '' . ' ' . $admin_data['apellido'] ?? '') . '<br>
               <strong>Email:</strong> ' . htmlspecialchars($admin_data['email'] ?? '') . '<br>
               <strong>Teléfono:</strong> ' . htmlspecialchars($admin_data['telefono'] ?? '') . '</p>
        </td>
    </tr>
</table><hr>';
$pdf->writeHTML($header_html, true, false, true, false, '');


// Tabla de productos
$table_html = '
<table border="1" cellpadding="4" cellspacing="0">
    <thead>
        <tr style="background-color:#f2f2f2; font-weight:bold;">
            <th width="15%">Imagen</th>
            <th width="10%">SKU</th>
            <th width="30%">Producto</th> 
            <th width="10%">Stock</th>
            <th width="15%">Precio (USD)</th>
            <th width="20%">Categoría</th>
        </tr>
    </thead>
    <tbody>';

foreach ($productos as $producto) {
    // Definimos la ruta de la imagen
    $imagen_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $producto['imagen_principal'];
    if (empty($producto['imagen_principal']) || !file_exists($imagen_path)) {
        $imagen_path = $_SERVER['DOCUMENT_ROOT'] . '/images/placeholder.png'; // Un placeholder por si acaso
    }

    // Formateamos el precio con descuento
    $precio_html = '$' . number_format($producto['precio_usd'], 2);
    if (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0) {
        $precio_html = '<span style="color:red;">$' . number_format($producto['precio_descuento'], 2) . '</span> <del>$' . number_format($producto['precio_usd'], 2) . '</del>';
    }

    $table_html .= '
    <tr>
        <td width="15%" style="text-align:center;"><img src="' . $imagen_path . '" height="50"></td>
        <td width="10%">' . htmlspecialchars($producto['sku'] ?? '') . '</td>
        <td width="30%"><strong>' . htmlspecialchars($producto['nombre'] ?? '') . '</strong></td> 
        <td width="10%" style="text-align:center;">' . $producto['stock'] . '</td>
        <td width="15%">' . $precio_html . '</td>
        <td width="20%">' . htmlspecialchars($producto['nombre_categoria'] ?? '') . '</td>
    </tr>';
}

$table_html .= '</tbody></table>';

// 6. Escribir el HTML en el PDF
$pdf->writeHTML($table_html, true, false, true, false, '');


// 7. Cerrar y generar el PDF
// 'I' lo muestra en el navegador, 'D' fuerza la descarga.
$pdf->Output('catalogo_productos.pdf', 'I');
exit();
?>