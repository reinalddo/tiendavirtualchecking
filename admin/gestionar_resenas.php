<?php
// admin/gestionar_resenas.php
require_once '../includes/header.php';
require_once '../includes/db_connection.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

// Obtener todas las reseñas
$resenas = $pdo->query("SELECT r.*, p.nombre as nombre_producto, u.nombre_pila as nombre_usuario 
                        FROM resenas r
                        JOIN productos p ON r.producto_id = p.id
                        JOIN usuarios u ON r.usuario_id = u.id
                        ORDER BY r.es_aprobada ASC, r.fecha_creacion DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Gestionar Reseñas</h1>
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="my-0 fw-normal">Reseñas de Clientes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Usuario</th>
                                <th>Calificación</th>
                                <th>Comentario</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resenas as $resena): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($resena['nombre_producto']); ?></td>
                                    <td><?php echo htmlspecialchars($resena['nombre_usuario']); ?></td>
                                    <td><?php echo $resena['calificacion']; ?>/5</td>
                                    <td><?php echo htmlspecialchars($resena['comentario']); ?></td>
                                    <td>
                                        <?php if ($resena['es_aprobada']): ?>
                                            <span class="badge bg-success">Aprobada</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if (!$resena['es_aprobada']): ?>
                                            <a href="actualizar_resena.php?id=<?php echo $resena['id']; ?>&accion=aprobar" class="btn btn-sm btn-success">Aprobar</a>
                                        <?php else: ?>
                                            <a href="actualizar_resena.php?id=<?php echo $resena['id']; ?>&accion=rechazar" class="btn btn-sm btn-warning">Rechazar</a>
                                        <?php endif; ?>
                                        <a href="actualizar_resena.php?id=<?php echo $resena['id']; ?>&accion=eliminar" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro?');">Eliminar</a>
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