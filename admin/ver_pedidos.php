<?php
// admin/ver_pedidos.php
require_once '../includes/config.php';
verificar_sesion_admin();


// Obtener todos los pedidos, uniendo con la tabla de usuarios para obtener el nombre del cliente
$sql = "SELECT p.*, u.nombre_pila as nombre_cliente, cp.url_comprobante,
        (SELECT COUNT(*) FROM comprobantes_pago WHERE pedido_id = p.id) as total_comprobantes
        FROM pedidos p 
        JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN comprobantes_pago cp ON cp.pedido_id = p.id
        ";
$params = [];
$titulo = "Ver Todos los Pedidos";
$url_ver_detalles1 = "panel/";
if (!empty($_GET['cliente_id'])) {
    $url_ver_detalles1 = "panel/pedidos/cliente/".$_GET['cliente_id']."/";
    $sql .= " WHERE p.usuario_id = ?";
    $params[] = (int)$_GET['cliente_id'];
    $stmt_cliente = $pdo->prepare("SELECT nombre_pila, apellido FROM usuarios WHERE id = ?");
    $stmt_cliente->execute([(int)$_GET['cliente_id']]);
    $cliente = $stmt_cliente->fetch();
    $titulo = "Pedidos de: " . htmlspecialchars($cliente['nombre_pila'] . ' ' . $cliente['apellido']);
}

$sql .= " ORDER BY p.fecha_pedido DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once '../includes/header.php';

?>

<div class="admin-panel">
    <h1><?php echo $titulo; ?></h1>

    <div class="table-container">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h1 class="my-0 fw-normal fs-4">Ver Pedidos</h1>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID Pedido</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos as $pedido): ?>
                                    <?php
                                        $status_class = '';
                                        switch (trim(strtolower($pedido['estado']))) {
                                            case 'pagado': $status_class = 'bg-success'; break;
                                            case 'enviado': $status_class = 'bg-primary'; break;
                                            case 'pendiente de pago': $status_class = 'bg-warning text-dark'; break;
                                            case 'cancelado': $status_class = 'bg-danger'; break;
                                            default: $status_class = 'bg-secondary';
                                        }
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo htmlspecialchars($pedido['id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></td>
                                        <td><?php echo htmlspecialchars($pedido['fecha_pedido']); ?></td>
                                        <td><?php echo htmlspecialchars($pedido['moneda_pedido'] . ' ' . number_format($pedido['total'] * $pedido['tasa_conversion_pedido'], 2)); ?></td>
                                        <td>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($pedido['estado'] ?? ''); ?>
                                            </span>
                                            <?php if ($pedido['metodo_pago'] == 'manual' && $pedido['total_comprobantes'] > 0): ?>
                                                <a href="<?php echo BASE_URL . 'comprobantes/' . htmlspecialchars($pedido['url_comprobante']); ?>" target="_blank" class="btn btn-outline-primary">
                                                <i class="bi bi-paperclip text-success" title="Comprobante subido"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?php echo $url_ver_detalles1; ?>pedido/<?php echo $pedido['id']; ?>" class="btn btn-sm btn-secondary">Ver Detalles</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>