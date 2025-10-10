<?php
// admin/responder_pregunta.php (Versión Corregida)
require_once '../includes/config.php';
verificar_sesion_admin();

$pregunta_id = (int)$_GET['id'];

// --- CORRECCIÓN: OBTENEMOS LOS DATOS DE LA PREGUNTA PRIMERO ---
$stmt_pregunta = $pdo->prepare("SELECT pr.*, p.nombre as nombre_producto, u.nombre_pila as nombre_usuario, p.slug as producto_slug
                                FROM preguntas_respuestas pr
                                JOIN productos p ON pr.producto_id = p.id
                                JOIN usuarios u ON pr.usuario_id = u.id
                                WHERE pr.id = ?");
$stmt_pregunta->execute([$pregunta_id]);
$pregunta = $stmt_pregunta->fetch(PDO::FETCH_ASSOC);

if (!$pregunta) {
    // Si no se encuentra la pregunta, redirigimos
    header("Location: " . BASE_URL . "panel/ver_preguntas");
    exit();
}

// --- AHORA SÍ, PROCESAMOS EL FORMULARIO SI SE ENVIÓ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_respuesta'])) {
    $respuesta = trim($_POST['respuesta']);
    if (!empty($respuesta)) {
        $stmt_update = $pdo->prepare("UPDATE preguntas_respuestas SET respuesta = ?, fecha_respuesta = NOW() WHERE id = ?");
        $stmt_update->execute([$respuesta, $pregunta_id]);

        // --- LÓGICA DE NOTIFICACIÓN CORREGIDA ---
        $cliente_id = $pregunta['usuario_id'];
        $mensaje_cliente = "Tu pregunta sobre el producto '" . htmlspecialchars($pregunta['nombre_producto']) . "' ha sido respondida.";
        $url_notificacion = BASE_URL . 'producto/' . $pregunta['producto_slug'] . '#qa-tab';
        crear_notificacion($pdo, $cliente_id, $mensaje_cliente, $url_notificacion);
        // --- FIN DE LA CORRECCIÓN ---

        header("Location: " . BASE_URL . "panel/ver_preguntas");
        exit();
    }
}

require_once '../includes/header.php';
?>

<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Responder Pregunta</h1>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="my-0 fw-normal">Pregunta del Cliente</h5>
            </div>
            <div class="card-body">
                <p><strong>Producto:</strong> <?php echo htmlspecialchars($pregunta['nombre_producto']); ?></p>
                <p><strong>Usuario:</strong> <?php echo htmlspecialchars($pregunta['nombre_usuario']); ?></p>
                <div class="alert alert-secondary">
                    <?php echo htmlspecialchars($pregunta['pregunta']); ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h5 class="my-0 fw-normal">Tu Respuesta</h5>
            </div>
            <div class="card-body">
                <form action="panel/pregunta/responder/<?php echo htmlspecialchars($pregunta_id); ?>" method="POST">
                    <div class="mb-3">
                        <textarea class="form-control" id="respuesta" name="respuesta" rows="6" required><?php echo htmlspecialchars($pregunta['respuesta'] ?? ''); ?></textarea>
                    </div>
                    <a href="panel/ver_preguntas" class="btn btn-secondary">Volver</a>
                    <button type="submit" name="guardar_respuesta" class="btn btn-primary">Enviar Respuesta</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>