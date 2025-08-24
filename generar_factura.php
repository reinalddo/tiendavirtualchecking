<?php
// generar_factura.php
session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

if (!isset($_SESSION['usuario_id']) || empty($_GET['pedido_id'])) {
    die('Acceso denegado.');
}

$pedido_id = (int)$_GET['pedido_id'];
$usuario_id = $_SESSION['usuario_id'];

// Obtener datos del pedido
$stmt_pedido = $pdo->prepare("SELECT p.*, u.nombre_pila as nombre_cliente, u.email as email_cliente, 
                                     u.rif_cedula as rif_cedula_cliente, u.direccion as direccion_cliente 
                             FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id
                             WHERE p.id = ? AND (p.usuario_id = ? OR 'admin' = ?)");
$stmt_pedido->execute([$pedido_id, $usuario_id, $_SESSION['usuario_rol']]);
$pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die('Pedido no encontrado o no tienes permiso para verlo.');
}

// Obtener detalles del pedido
$stmt_detalles = $pdo->prepare("SELECT d.*, p.nombre as nombre_producto, p.sku as sku_producto 
                               FROM pedido_detalles d JOIN productos p ON d.producto_id = p.id
                               WHERE d.pedido_id = ?");
$stmt_detalles->execute([$pedido_id]);
$detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

// --- CREACIÓN DEL PDF CON TCPDF ---
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Mi Tienda Web');
$pdf->SetTitle('Factura Pedido #' . $pedido_id);
$pdf->SetSubject('Factura');

// Añadir una página
$pdf->AddPage();

// Contenido del PDF (HTML)
$html = '
<h1>Factura Pedido #' . $pedido_id . '</h1>
<p><strong>Fecha:</strong> ' . $pedido['fecha_pedido'] . '</p>
<p><strong>Cliente:</strong> ' . htmlspecialchars($pedido['nombre_cliente'] ?? '') . '</p>
<p><strong>RIF/Cédula:</strong> ' . htmlspecialchars($pedido['rif_cedula_cliente'] ?? '') . '</p>
<p><strong>Dirección:</strong> ' . nl2br(htmlspecialchars($pedido['direccion_cliente'] ?? '')) . '</p>
<p><strong>Email:</strong> ' . htmlspecialchars($pedido['email_cliente'] ?? '') . '</p>
<hr>
<h3>Detalles de la Compra</h3>
<table border="1" cellpadding="4">
    <thead>
        <tr>
            <th>SKU</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>';

foreach ($detalles as $item) {
    $subtotal = $item['cantidad'] * $item['precio_unitario'];
    $html .= '<tr>
                <td>' . htmlspecialchars($item['sku_producto']) . '</td>
                <td>' . htmlspecialchars($item['nombre_producto']) . '</td>
                <td>' . $item['cantidad'] . '</td>
                <td>' . $pedido['moneda_pedido'] . ' ' . number_format($item['precio_unitario'], 2) . '</td>
                <td>' . $pedido['moneda_pedido'] . ' ' . number_format($subtotal, 2) . '</td>
              </tr>';
}

$html .= '</tbody>
</table>
<h3 align="right">Total del Pedido: ' . $pedido['moneda_pedido'] . ' ' . number_format($pedido['total'], 2) . '</h3>';

// Escribir el HTML en el PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Cerrar y generar el PDF, forzando la descarga
$pdf->Output('factura_pedido_' . $pedido_id . '.pdf', 'D');
exit();
?>