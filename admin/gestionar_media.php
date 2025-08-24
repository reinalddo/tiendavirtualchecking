<?php
// admin/gestionar_media.php

// 1. TODA LA LÓGICA PHP PRIMERO
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
//session_start();

// Verificación de seguridad de admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// --- LÓGICA PARA ELIMINAR ---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['media_id'])) {
    $media_id = $_POST['media_id'];
    
    $stmt_find = $pdo->prepare("SELECT nombre_archivo FROM media_library WHERE id = ?");
    $stmt_find->execute([$media_id]);
    $file_to_delete = $stmt_find->fetchColumn();

    if ($file_to_delete) {
        $file_path = '../uploads/' . $file_to_delete;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    $stmt_delete = $pdo->prepare("DELETE FROM media_library WHERE id = ?");
    $stmt_delete->execute([$media_id]);

    $_SESSION['mensaje_carrito'] = 'Archivo eliminado correctamente.';
    header("Location: gestionar_media.php");
    exit();
}

// Obtener los items para mostrar en la página
$media_items = $pdo->query("SELECT * FROM media_library ORDER BY fecha_subida DESC")->fetchAll(PDO::FETCH_ASSOC);


// 2. UNA VEZ TERMINADA LA LÓGICA, INCLUIMOS EL HEADER
require_once '../includes/header.php';
?>

<main>
<div class="container-fluid py-4">
    <h1 class="h2 mb-4">Biblioteca de Medios</h1>
    <div class="card shadow-sm">
        <div class="card-header"><h5 class="my-0 fw-normal">Subir Nuevos Archivos</h5></div>
        <div class="card-body">
            <form action="upload_media.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="media_files" class="form-label">Seleccionar archivo(s):</label>
                    <input class="form-control" type="file" name="media_files[]" id="media_files" required multiple>
                </div>
                <button type="submit" class="btn btn-primary">Subir a la Biblioteca</button>
            </form>
        </div>
    </div>
    <div class="card shadow-sm mt-4">
        <div class="card-header"><h5 class="my-0 fw-normal">Archivos Existentes</h5></div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($media_items as $item): ?>
                <?php
                    // VERIFICAMOS SI EL ARCHIVO EXISTE ANTES DE MOSTRARLO
                    $file_path = '../uploads/' . $item['nombre_archivo'];
                    if (!empty($item['nombre_archivo']) && is_file($file_path)):
                ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($item['nombre_archivo']); ?>" class="card-img-top" style="height: 150px; object-fit: cover;" alt="<?php echo htmlspecialchars($item['alt_text'] ?? ''); ?>">
                        <div class="card-body">
                            <input type="text" class="form-control form-control-sm update-alt-text" data-media-id="<?php echo $item['id']; ?>" value="<?php echo htmlspecialchars($item['alt_text'] ?? ''); ?>" placeholder="Nombre/Alt text">
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <button class="btn btn-sm btn-secondary rename-media-btn" 
                                    data-media-id="<?php echo $item['id']; ?>"
                                    data-old-filename="<?php echo htmlspecialchars($item['nombre_archivo']); ?>">Renombrar</button>
                            <form action="gestionar_media.php" method="POST" class="d-inline">
                                <input type="hidden" name="media_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="delete_media" class="btn btn-sm btn-danger confirm-delete">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</main>
<?php require_once '../includes/footer.php'; ?>