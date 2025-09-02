<?php
// perfil.php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

// Seguridad: Solo usuarios logueados
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}
$usuario_id = $_SESSION['usuario_id'];

// Obtener los pedidos del usuario
$stmt = $pdo->prepare("SELECT p.*,
           (SELECT estado FROM comprobantes_pago WHERE pedido_id = p.id ORDER BY fecha_subida DESC LIMIT 1) as estado_comprobante,
           (SELECT COUNT(*) FROM comprobantes_pago WHERE pedido_id = p.id) as tiene_comprobante
    FROM pedidos p 
    WHERE p.usuario_id = ? 
    ORDER BY p.fecha_pedido DESC");
$stmt->execute([$usuario_id]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once 'includes/header.php';
?>

<main>
    <div class="container-fluid py-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h1 class="card-title">Pedidos</h1>
                        <p class="card-text fs-5">Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong>.</p>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h2 class="my-0 fw-normal fs-4">Mis Pedidos</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>ID Pedido</th>
                                        <th>Fecha</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pedidos)): ?>
                                        <tr>
                                            <td colspan="5">Aún no has realizado ningún pedido.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($pedidos as $pedido): ?>
                                            <?php
                                                // Lógica para seleccionar el color del badge
                                                $status_class = '';
                                                switch (trim(strtolower($pedido['estado']))) {
                                                    case 'pagado':
                                                        $status_class = 'bg-success';
                                                        break;
                                                    case 'enviado':
                                                        $status_class = 'bg-primary';
                                                        break;
                                                    case 'pendiente de pago':
                                                        $status_class = 'bg-warning text-dark';
                                                        break;
                                                    case 'cancelado':
                                                        $status_class = 'bg-danger';
                                                        break;
                                                    default:
                                                        $status_class = 'bg-secondary';
                                                }
                                            ?>
                                        <tr>
                                            <td><strong>#<?php echo htmlspecialchars($pedido['id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($pedido['fecha_pedido']); ?></td>
                                            <td><?php echo format_price($pedido['total']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($pedido['estado']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <?php if (trim(strtolower($pedido['estado'])) == 'pendiente de pago'): ?>
                                                    
                                                    <?php if ($pedido['tiene_comprobante'] && $pedido['estado_comprobante'] != 'rechazado'): ?>
                                                        <span class="badge bg-info text-dark">Comprobante en revisión</span>
                                                    <?php else: ?>
                                                        <?php if ($pedido['estado_comprobante'] == 'rechazado'): ?>
                                                            <p class="text-danger small">Tu comprobante anterior fue rechazado. Por favor, sube uno nuevo.</p>
                                                        <?php endif; ?>
                                                        <form action="subir_comprobante.php" method="POST" enctype="multipart/form-data" class="d-inline-flex">
                                                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                                            <input type="file" name="comprobante" class="form-control form-control-sm" required>
                                                            <button type="submit" class="btn btn-sm btn-success ms-2">Subir</button>
                                                        </form>
                                                    <?php endif; ?>

                                                <?php else: ?>
                                                    <a href="generar_factura.php?pedido_id=<?php echo $pedido['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">Factura</a>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-secondary view-order-details-btn ms-2" data-pedido-id="<?php echo $pedido['id']; ?>">Ver Detalles</button>
                                                <a href="mensajes_pedido.php?pedido_id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-info text-white ms-2">Mensajes</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<div id="order-details-modal" class="modal fade">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modal-order-content">Cargando...</div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>