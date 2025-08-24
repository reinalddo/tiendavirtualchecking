<?php
// admin/ver_preguntas.php
require_once '../includes/header.php';
require_once '../includes/db_connection.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

// Obtener todas las preguntas
$stmt = $pdo->query("SELECT pr.*, p.nombre as nombre_producto, u.nombre_pila as nombre_usuario, p.sku
                     FROM preguntas_respuestas pr
                     JOIN productos p ON pr.producto_id = p.id
                     JOIN usuarios u ON pr.usuario_id = u.id
                     ORDER BY pr.respuesta IS NULL DESC, pr.fecha_pregunta DESC");
$preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Gestionar Preguntas y Respuestas</h1>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="my-0 fw-normal">Preguntas de Clientes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th>Usuario</th>
                                <th>Pregunta</th>
                                <th>Fecha</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preguntas as $pregunta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pregunta['sku']); ?></td>
                                <td><?php echo htmlspecialchars($pregunta['nombre_producto']); ?></td>
                                <td><?php echo htmlspecialchars($pregunta['nombre_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($pregunta['pregunta']); ?></td>
                                <td><?php echo htmlspecialchars($pregunta['fecha_pregunta']); ?></td>
                                <td class="text-end">
                                    <?php if (empty($pregunta['respuesta'])): ?>
                                        <a href="responder_pregunta.php?id=<?php echo $pregunta['id']; ?>" class="btn btn-sm btn-primary">Responder</a>
                                    <?php else: ?>
                                        <span class="badge bg-success">Respondida</span>
                                    <?php endif; ?>
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