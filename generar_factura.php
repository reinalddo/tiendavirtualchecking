<?php
// generar_factura.php (Versión Completa y Corregida)
session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

if (!isset($_SESSION['usuario_id']) || empty($_GET['pedido_id'])) {
    die('Acceso denegado.');
}

$pedido_id = (int)$_GET['pedido_id'];
$usuario_id = $_SESSION['usuario_id'];

// 1. OBTENER DATOS DE LA TIENDA
$stmt_config = $pdo->query("SELECT * FROM configuraciones");
$configuraciones_raw = $stmt_config->fetchAll(PDO::FETCH_ASSOC);
$tienda_info = [];
foreach ($configuraciones_raw as $setting) {
    $tienda_info[$setting['nombre_setting']] = $setting['valor_setting'];
}

// 2. OBTENER DATOS DEL PEDIDO
$stmt_pedido = $pdo->prepare("SELECT p.*, u.nombre_pila, u.apellido, u.email as email_cliente, 
                                     u.rif_cedula as rif_cedula_cliente, u.direccion as direccion_cliente 
                             FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id
                             WHERE p.id = ? AND (p.usuario_id = ? OR 'admin' = ?)");
$stmt_pedido->execute([$pedido_id, $usuario_id, $_SESSION['usuario_rol'] ?? '']);
$pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die('Pedido no encontrado o no tienes permiso para verlo.');
}

// 3. OBTENER DETALLES DEL PEDIDO
$stmt_detalles = $pdo->prepare("SELECT d.*, p.nombre as nombre_producto, p.sku as sku_producto 
                               FROM pedido_detalles d JOIN productos p ON d.producto_id = p.id
                               WHERE d.pedido_id = ?");
$stmt_detalles->execute([$pedido_id]);
$detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);


// --- CÁLCULOS CORREGIDOS PARA LA FACTURA ---
$total_final = $pedido['total'];
$iva_porcentaje = $tienda_info['iva_porcentaje'];

// 1. Calculamos el subtotal bruto (suma de productos ANTES de descuentos)
$subtotal_bruto = 0;
foreach($detalles as $item) {
    $subtotal_bruto += ($item['cantidad'] * $item['precio_unitario']) * $pedido['tasa_conversion_pedido'];
}

// 2. Obtenemos el valor REAL del cupón desde la base de datos
$monto_descuento = 0;
if (!empty($pedido['cupon_usado'])) {
    $stmt_cupon = $pdo->prepare("SELECT valor, tipo_descuento FROM cupones WHERE codigo = ?");
    $stmt_cupon->execute([$pedido['cupon_usado']]);
    $cupon = $stmt_cupon->fetch(PDO::FETCH_ASSOC);

    if ($cupon) {
        if ($cupon['tipo_descuento'] == 'fijo') {
            $monto_descuento = $cupon['valor'];
        } else { // Si es porcentaje
            $monto_descuento = $subtotal_bruto * ($cupon['valor'] / 100);
        }
    }
}

// 3. Recalculamos los otros valores para que cuadren en la factura
// Esto funciona para la lógica antigua de IVA exclusivo que se usó en el pedido #27
$base_imponible = $subtotal_bruto - $monto_descuento;
$monto_iva = $base_imponible * ($iva_porcentaje / 100);
$base_imponible -= $monto_iva;

// 5. CREACIÓN DEL PDF CON TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($tienda_info['tienda_razon_social']);
$pdf->SetTitle('Factura Pedido #' . $pedido_id);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->AddPage();


// 6. CONSTRUCCIÓN DEL HTML PARA EL PDF
$logo_html = '';
if (!empty($tienda_info['tienda_logo']) && file_exists(__DIR__ . '/uploads/' . $tienda_info['tienda_logo'])) {
    $logo_path = __DIR__ . '/uploads/' . $tienda_info['tienda_logo'];
    $logo_html = '<img src="' . $logo_path . '" height="50">';
}

$html = '
<table cellpadding="5" cellspacing="0">
    <tr>
        <td width="50%" style="border-bottom: 1px solid #dee2e6;">
            ' . $logo_html . '
            <h2>' . htmlspecialchars($tienda_info['tienda_razon_social']) . '</h2>
            <p>
                RIF: ' . htmlspecialchars($tienda_info['tienda_rif']) . '<br>
                ' . nl2br(htmlspecialchars($tienda_info['tienda_domicilio_fiscal'])) . '<br>
                Teléfono: ' . htmlspecialchars($tienda_info['tienda_telefono']) . '
            </p>
        </td>
        <td width="50%" align="right" style="border-bottom: 1px solid #dee2e6;">
            <h1>FACTURA</h1>
            <p>
                <strong>Nº de Factura:</strong> ' . str_pad($pedido_id, 8, '0', STR_PAD_LEFT) . '<br>
                <strong>Fecha de Emisión:</strong> ' . date("d/m/Y", strtotime($pedido['fecha_pedido'])) . '<br>
                <strong>Moneda:</strong> ' . htmlspecialchars($pedido['moneda_pedido']) . '
            </p>
        </td>
    </tr>
    <tr><td width="100%" height="20"></td></tr>
    <tr>
        <td width="50%">
            <strong>Facturar a:</strong><br>
            ' . htmlspecialchars($pedido['nombre_pila'] . ' ' . ($pedido['apellido'] ?? '')) . '<br>
            RIF/C.I: ' . htmlspecialchars($pedido['rif_cedula_cliente']) . '<br>
            ' . nl2br(htmlspecialchars($pedido['direccion_cliente'])) . '<br>
            Email: ' . htmlspecialchars($pedido['email_cliente']) . '
        </td>
        <td width="50%">
            <strong>Enviar a:</strong><br>
            ' . nl2br(htmlspecialchars($pedido['direccion_envio'])) . '
        </td>
    </tr>
</table>
<br><br>
<table border="1" cellpadding="4" cellspacing="0">
    <thead style="background-color:#f2f2f2; font-weight:bold;">
        <tr>
            <th width="15%">SKU</th>
            <th width="40%">Producto</th>
            <th width="15%" align="center">Cantidad</th>
            <th width="15%" align="right">Precio Unit.</th>
            <th width="15%" align="right">Subtotal</th>
        </tr>
    </thead>
    <tbody>';

foreach ($detalles as $item) {
    $precio_unit_convertido = $item['precio_unitario'] * $pedido['tasa_conversion_pedido'];
    $subtotal_item = $item['cantidad'] * $precio_unit_convertido;
    $html .= '<tr>
                <td width="15%">' . htmlspecialchars($item['sku_producto']) . '</td>
                <td width="40%">' . htmlspecialchars($item['nombre_producto']) . '</td>
                <td width="15%" align="center">' . $item['cantidad'] . '</td>
                <td width="15%" align="right">' . number_format($precio_unit_convertido, 2) . '</td>
                <td width="15%" align="right">' . number_format($subtotal_item, 2) . '</td>
              </tr>';
}

$html .= '</tbody></table><br><br>
<table cellpadding="4">
    <tr>
        <td width="55%"></td>
        <td width="45%">
            <table border="1" cellpadding="4" cellspacing="0">
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td align="right">' . number_format($subtotal_bruto, 2) . '</td>
                </tr>';

if (!empty($pedido['cupon_usado'])) {
    $html .= '<tr>
                <td><strong>Descuento (' . htmlspecialchars($pedido['cupon_usado']) . '):</strong></td>
                <td align="right">-' . number_format(abs($monto_descuento), 2) . '</td>
            </tr>';
}

$html .= '  <tr>
                <td><strong>Base Imponible:</strong></td>
                <td align="right">' . number_format($base_imponible, 2) . '</td>
            </tr>
            <tr>
                <td><strong>IVA (' . htmlspecialchars($tienda_info['iva_porcentaje']) . '%):</strong></td>
                <td align="right">' . number_format($monto_iva, 2) . '</td>
            </tr>
            <tr style="background-color:#f2f2f2; font-weight:bold; font-size:1.1em;">
                <td><strong>TOTAL (' . htmlspecialchars($pedido['moneda_pedido']) . '):</strong></td>
                <td align="right">' . number_format($total_final, 2) . '</td>
            </tr>
            </table>
        </td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('factura_pedido_' . $pedido_id . '.pdf', 'I');
exit();
?>