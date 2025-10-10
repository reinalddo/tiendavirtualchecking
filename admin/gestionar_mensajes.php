<?php
// admin/gestionar_mensajes.php (Versión Corregida)
require_once '../includes/config.php';
verificar_sesion_admin();

// Consulta corregida para manejar conversaciones vacías
$sql = "SELECT 
            c.id as conversacion_id,
            c.pedido_id,
            c.fecha_creacion, -- Añadimos la fecha de creación como fallback
            u.nombre_pila as nombre_cliente,
            (SELECT m.mensaje FROM mensajes m WHERE m.conversacion_id = c.id ORDER BY m.fecha_envio DESC LIMIT 1) as ultimo_mensaje,
            (SELECT m.fecha_envio FROM mensajes m WHERE m.conversacion_id = c.id ORDER BY m.fecha_envio DESC LIMIT 1) as fecha_ultimo_mensaje,
            (SELECT COUNT(*) FROM mensajes m WHERE m.conversacion_id = c.id AND m.leido = 0 AND m.remitente_id = c.cliente_id) as no_leidos
        FROM conversaciones c
        JOIN usuarios u ON c.cliente_id = u.id
        ORDER BY COALESCE(fecha_ultimo_mensaje, c.fecha_creacion) DESC"; // Ordenamos por la fecha más reciente disponible
        
$conversaciones = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
require_once '../includes/header.php';

?>

<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Centro de Mensajes</h1>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="my-0 fw-normal">Conversaciones con Clientes</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($conversaciones)): ?>
                        <div class="list-group-item">No hay conversaciones activas.</div>
                    <?php else: ?>
                        <?php foreach ($conversaciones as $conv): ?>
                            <a href="panel/conversacion/<?php echo $conv['conversacion_id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo ($conv['no_leidos'] > 0) ? 'list-group-item-primary' : ''; ?>">
                                <div>
                                    <h6 class="mb-1">
                                        Pedido #<?php echo htmlspecialchars($conv['pedido_id']); ?> - 
                                        <strong><?php echo htmlspecialchars($conv['nombre_cliente']); ?></strong>
                                    </h6>
                                    
                                    <small class="text-muted fst-italic">
                                        <?php echo htmlspecialchars(substr($conv['ultimo_mensaje'] ?? 'Conversación iniciada, sin mensajes aún.', 0, 80)) . '...'; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <?php if ($conv['no_leidos'] > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?php echo $conv['no_leidos']; ?></span>
                                    <?php endif; ?>
                                    <br>
                                    
                                    <small class="text-muted">
                                        <?php echo date("d/m/y H:i", strtotime($conv['fecha_ultimo_mensaje'] ?? $conv['fecha_creacion'])); ?>
                                    </small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>