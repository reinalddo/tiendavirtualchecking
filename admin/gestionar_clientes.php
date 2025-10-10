<?php
// admin/gestionar_clientes.php
require_once '../includes/config.php';
verificar_sesion_admin();

// NUEVA CONSULTA: Obtenemos el conteo de pedidos pendientes y pagados para cada cliente
$clientes = $pdo->query("
    SELECT 
        u.id, 
        u.nombre_pila, 
        u.apellido, 
        u.email, 
        u.fecha_registro,
        (SELECT COUNT(*) FROM pedidos WHERE usuario_id = u.id AND estado = 'Pendiente de Pago') as pedidos_pendientes,
        (SELECT COUNT(*) FROM pedidos WHERE usuario_id = u.id AND estado = 'Pagado') as pedidos_por_enviar
    FROM usuarios u 
    WHERE u.rol = 'cliente' 
    ORDER BY u.fecha_registro DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<main>
<div class="container-fluid py-4">
    <h1 class="h2 mb-4">Gestionar Clientes</h1>
    <div class="card shadow-sm">
        <div class="card-header"><h5 class="my-0 fw-normal">Lista de Clientes</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Fecha de Registro</th>
                            <th>Estado de Pedidos</th> <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?php echo $cliente['id']; ?></td>
                            <td><?php echo htmlspecialchars($cliente['nombre_pila'] . ' ' . $cliente['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td><?php echo $cliente['fecha_registro']; ?></td>
                            <td>
                                <?php
                                    $tiene_pendientes = $cliente['pedidos_pendientes'] > 0;
                                    $tiene_por_enviar = $cliente['pedidos_por_enviar'] > 0;
                                ?>

                                <?php if ($tiene_pendientes): ?>
                                    <span class="badge bg-warning text-dark d-block mb-1">Tiene Pagos Pendientes</span>
                                <?php endif; ?>

                                <?php if ($tiene_por_enviar): ?>
                                    <span class="badge bg-success d-block">Tiene Pedidos por Enviar</span>
                                <?php endif; ?>
                                
                                <?php if (!$tiene_pendientes && !$tiene_por_enviar): ?>
                                    <span class="badge bg-secondary">Sin Pedidos Pendientes</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="panel/pedidos/cliente/<?php echo $cliente['id']; ?>" class="btn btn-sm btn-secondary">Ver Pedidos</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</main>
<?php require_once '../includes/footer.php'; ?>