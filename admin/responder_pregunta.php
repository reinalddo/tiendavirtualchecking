<?php
// admin/responder_pregunta.php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
//session_start();

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || !isset($_GET['id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$pregunta_id = $_GET['id'];

// Lógica para guardar la respuesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_respuesta'])) {
    $respuesta = trim($_POST['respuesta']);
    if (!empty($respuesta)) {
        $stmt_update = $pdo->prepare("UPDATE preguntas_respuestas SET respuesta = ?, fecha_respuesta = NOW() WHERE id = ?");
        $stmt_update->execute([$respuesta, $pregunta_id]);
        header("Location: ver_preguntas.php");
        exit();
    }
}

// Obtener los datos de la pregunta para mostrarla
$stmt_pregunta = $pdo->prepare("SELECT pr.*, p.nombre as nombre_producto, u.nombre_pila as nombre_usuario 
                                FROM preguntas_respuestas pr
                                JOIN productos p ON pr.producto_id = p.id
                                JOIN usuarios u ON pr.usuario_id = u.id
                                WHERE pr.id = ?");
$stmt_pregunta->execute([$pregunta_id]);
$pregunta = $stmt_pregunta->fetch(PDO::FETCH_ASSOC);

if (!$pregunta) {
    // Si no se encuentra la pregunta, redirigimos con un mensaje
    $_SESSION['mensaje_carrito'] = "Error: La pregunta no fue encontrada.";
    header("Location: ver_preguntas.php");
    exit();
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
                <form action="responder_pregunta.php?id=<?php echo htmlspecialchars($pregunta_id); ?>" method="POST">
                    <div class="mb-3">
                        <textarea class="form-control" id="respuesta" name="respuesta" rows="6" required><?php echo htmlspecialchars($pregunta['respuesta'] ?? ''); ?></textarea>
                    </div>
                    <a href="ver_preguntas.php" class="btn btn-secondary">Volver</a>
                    <button type="submit" name="guardar_respuesta" class="btn btn-primary">Guardar Respuesta</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>