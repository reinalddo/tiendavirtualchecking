<?php
// admin/detalle_pedido.php (Versión Corregida)
require_once '../includes/config.php';
verificar_sesion_admin();

$pedido_id = (int)$_GET['id'];

$cliente_id_filtro = $_GET['cliente_id'] ?? null;
$url_volver = 'panel/ver_pedidos'; // URL por defecto
if ($cliente_id_filtro) {
    $url_volver = 'panel/pedidos/cliente/' . $cliente_id_filtro;
}

// --- CORRECCIÓN: OBTENEMOS LOS DATOS DEL PEDIDO ANTES DE CUALQUIER OTRA ACCIÓN ---
$stmt_pedido = $pdo->prepare("SELECT p.*, u.nombre_pila as nombre_cliente, u.email as email_cliente 
                             FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
$stmt_pedido->execute([$pedido_id]);
$pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

// Si el pedido no existe, detenemos la ejecución.
if (!$pedido) {
    die('Pedido no encontrado.');
}

// AHORA PROCESAMOS EL FORMULARIO, YA CON LOS DATOS DEL PEDIDO DISPONIBLES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $nuevo_estado = $_POST['estado'];
    $stmt_update = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    $stmt_update->execute([$nuevo_estado, $pedido_id]);

    // Ahora $pedido['usuario_id'] sí existe y la notificación se crea correctamente
    $mensaje_cliente = "El estado de tu pedido #" . $pedido_id . " ha cambiado a: " . $nuevo_estado;
    $url_cliente = BASE_URL . "perfil.php";
    crear_notificacion($pdo, $pedido['usuario_id'], $mensaje_cliente, $url_cliente);

    header("Location: ". BASE_URL ."panel/pedido/" . $pedido_id);
    exit();
}

// Obtener detalles del pedido (productos)
$stmt_detalles = $pdo->prepare("SELECT d.*, p.nombre as nombre_producto, p.sku as sku_producto
                               FROM pedido_detalles d JOIN productos p ON d.producto_id = p.id WHERE d.pedido_id = ?");
$stmt_detalles->execute([$pedido_id]);
$detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

// Obtener el último comprobante
$stmt_comprobante = $pdo->prepare("SELECT * FROM comprobantes_pago WHERE pedido_id = ? ORDER BY fecha_subida DESC LIMIT 1");
$stmt_comprobante->execute([$pedido_id]);
$comprobante = $stmt_comprobante->fetch(PDO::FETCH_ASSOC);

// Lógica para el color del badge de estado
$status_class = '';
switch (trim(strtolower($pedido['estado']))) {
    case 'pagado': $status_class = 'bg-success'; break;
    case 'enviado': $status_class = 'bg-primary'; break;
    case 'pendiente de pago': $status_class = 'bg-warning text-dark'; break;
    case 'cancelado': $status_class = 'bg-danger'; break;
    default: $status_class = 'bg-secondary';
}

require_once '../includes/header.php';
?>

<main>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h2">Detalles del Pedido #<?php echo htmlspecialchars($pedido_id); ?></h1>
            <a href="../generar_factura.php?pedido_id=<?php echo $pedido_id; ?>" target="_blank" class="btn btn-primary">
                <i class="bi bi-download me-2"></i>Descargar Factura
            </a>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h5 class="my-0 fw-normal">Datos del Cliente</h5></div>
                    <div class="card-body">
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($pedido['nombre_cliente']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($pedido['email_cliente']); ?></p>
                        <p class="mb-0"><strong>Dirección de Envío:</strong><br><?php echo nl2br(htmlspecialchars($pedido['direccion_envio'])); ?></p>
                    </div>
                </div>

                <?php if ($comprobante): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h5 class="my-0 fw-normal">Comprobante de Pago</h5></div>
                    <div class="card-body">
                        <p><strong>Estado:</strong> <span class="badge bg-secondary"><?php echo htmlspecialchars($comprobante['estado'] ?? ''); ?></span></p>
                        <a href="<?php echo BASE_URL . 'comprobantes/' . htmlspecialchars($comprobante['url_comprobante'] ?? ''); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Ver Comprobante</a>
                        
                        <?php if ($comprobante['estado'] !== 'aprobado'): ?>
                            <a href="panel/comprobante/actualizar/<?php echo $comprobante['id']; ?>/aprobar/<?php echo $pedido_id; ?>" class="btn btn-sm btn-success">Aprobar</a>
                        <?php endif; ?>
                        
                        <?php if ($comprobante['estado'] !== 'rechazado'): ?>
                            <a href="panel/comprobante/actualizar/<?php echo $comprobante['id']; ?>/rechazar/<?php echo $pedido_id; ?>" class="btn btn-sm btn-danger">Rechazar</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header"><h5 class="my-0 fw-normal">Estado del Pedido</h5></div>
                    <div class="card-body">
                        <p><strong>Estado Actual:</strong> <span class="badge <?php echo $status_class; ?> fs-6"><?php echo htmlspecialchars($pedido['estado']); ?></span></p>
                        <form action="<?php echo BASE_URL; ?>panel/pedido/<?php echo $pedido_id; ?>" method="POST" class="d-flex">
                            <select name="estado" class="form-select me-2">
                                <option value="Pendiente de Pago" <?php if($pedido['estado'] == 'Pendiente de Pago') echo 'selected'; ?>>Pendiente de Pago</option>
                                <option value="Pagado" <?php if($pedido['estado'] == 'Pagado') echo 'selected'; ?>>Pagado</option>
                                <option value="Enviado" <?php if($pedido['estado'] == 'Enviado') echo 'selected'; ?>>Enviado</option>
                                <option value="Cancelado" <?php if($pedido['estado'] == 'Cancelado') echo 'selected'; ?>>Cancelado</option>
                            </select>
                            <button type="submit" name="actualizar_estado" class="btn btn-success">Actualizar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header"><h5 class="my-0 fw-normal">Productos en este Pedido</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>SKU / Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unitario (USD)</th>
                                        <th class="text-end">Subtotal (USD)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $subtotal_usd = 0; // Variable para calcular el subtotal en USD ?>
                                    <?php foreach ($detalles as $item): ?>
                                            <?php 
                                                $item_subtotal_usd = $item['cantidad'] * $item['precio_unitario'];
                                                $subtotal_usd += $item_subtotal_usd;
                                            ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($item['sku_producto']); ?></strong><br><?php echo htmlspecialchars($item['nombre_producto']); ?></td>
                                                <td><?php echo htmlspecialchars($item['cantidad']); ?></td>
                                                <td><?php echo format_historical_price($item['precio_unitario'], $pedido, $pdo); ?></td>
                                                <td class="text-end"><?php echo format_historical_price($item_subtotal_usd, $pedido, $pdo); ?></td>
                                            </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <?php
                                        // Calculamos el subtotal sumando los productos
                                        $subtotal = 0;
                                        foreach ($detalles as $item) {
                                            $subtotal += $item['cantidad'] * $item['precio_unitario'];
                                        }
                                        // Convertimos a la moneda del pedido
                                        $subtotal_convertido = $subtotal * $pedido['tasa_conversion_pedido'];
                                    ?>
                                    <tr>
                                        <td colspan="3" class="text-end">Subtotal:</td>
                                        <td class="text-end"><?php echo format_historical_price($subtotal_usd, $pedido, $pdo); ?></td>
                                    </tr>
                                    <?php if (!empty($pedido['cupon_usado'])):
                                        // El total del pedido ya tiene el descuento aplicado
                                        $subtotal_con_descuento_usd = $pedido['total'] - $pedido['iva_total'];
                                        $monto_descuento_usd = $subtotal_usd - $subtotal_con_descuento_usd;
                                    ?>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Descuento (<?php echo htmlspecialchars($pedido['cupon_usado']); ?>):</strong></td>
                                        <td class="text-end text-success">- <?php echo format_historical_price($monto_descuento_usd, $pedido, $pdo); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr class="fw-bold fs-5">
                                        <td colspan="3" class="text-end">Total del Pedido:</td>
                                        <td class="text-end"><?php echo format_historical_price($pedido['total'], $pedido, $pdo); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <a href="<?php echo $url_volver; ?>" class="btn btn-secondary mt-4">← Volver al listado de pedidos</a>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>