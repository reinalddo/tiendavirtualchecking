<?php
// admin/gestionar_galeria_inicio.php
require_once '../includes/header.php';
require_once '../includes/db_connection.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

$slides = $pdo->query("SELECT * FROM hero_gallery ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);

$todos_los_productos = $pdo->query("SELECT p.id, p.nombre,
(SELECT gal.url FROM producto_galeria gal WHERE gal.producto_id = p.id AND gal.tipo = 'imagen' ORDER BY gal.orden ASC, gal.id ASC LIMIT 1) as imagen_principal
FROM productos p ORDER BY p.nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

?>

<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Gestionar Galería de Inicio</h1>
        <div class="row">
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header"><h5 class="my-0 fw-normal">Añadir Nuevo Slide</h5></div>
                    <div class="card-body">
                        <form action="guardar_slide.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="imagen" class="form-label">Imagen del Slide:</label>
                                <input type="file" id="imagen" name="imagen" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título (opcional):</label>
                                <input type="text" id="titulo" name="titulo" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="enlace_url" class="form-label">URL del Enlace Manual (opcional):</label>
                                <input type="text" id="enlace_url" name="enlace_url" class="form-control" placeholder="Ej: https://misitio.com/ofertas">
                                <small class="form-text text-muted">Si seleccionas un producto, este campo será ignorado.</small>
                            </div>
                            <div class="mb-3">
                                <label for="producto_id_enlace" class="form-label">O enlazar a un producto:</label>
                                <select id="producto_id_enlace" name="producto_id_enlace" class="form-select">
                                    <option value="">-- Selecciona un producto --</option>
                                    <?php foreach ($todos_los_productos as $producto): ?>
                                    <option value="<?php echo $producto['id']; ?>" data-image="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($producto['imagen_principal'] ?? 'placeholder.png'); ?>">
                                        <?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Guardar Slide</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header"><h5 class="my-0 fw-normal">Slides Actuales</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Imagen</th>
                                        <th>Título</th>
                                        <th>Enlace</th>
                                        <th>Orden</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slides as $slide): ?>
                                    <tr data-slide-id="<?php echo $slide['id']; ?>">
                                        <td><img src="<?php echo BASE_URL . 'uploads/hero/' . htmlspecialchars($slide['imagen_url']); ?>" alt="<?php echo htmlspecialchars($slide['titulo']); ?>" class="img-fluid rounded" style="max-width: 150px;"></td>
                                        <td><input type="text" class="form-control update-field" name="titulo" value="<?php echo htmlspecialchars($slide['titulo']); ?>"></td>
                                        <td><input type="text" class="form-control update-field" name="enlace_url" value="<?php echo htmlspecialchars($slide['enlace_url']); ?>"></td>
                                        <td><input type="number" class="form-control update-field" name="orden" value="<?php echo htmlspecialchars($slide['orden']); ?>" style="width: 70px;"></td>
                                        <td class="text-end">
                                            <a href="eliminar_slide.php?id=<?php echo $slide['id']; ?>" class="btn btn-sm btn-danger confirm-delete-ajax">Eliminar</a>
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
    </div>
</main>
<script src="<?php echo BASE_URL; ?>js/media-library-modal.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<?php require_once '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#producto_id_enlace').select2({
        theme: "bootstrap-5",
        templateResult: formatProduct,
        templateSelection: formatProductSelection
    });

    function formatProduct (product) {
        if (!product.id) {
            return product.text;
        }
        var $product = $(
            '<span><img src="' + $(product.element).data('image') + '" class="img-thumbnail" style="width: 40px; margin-right: 10px;" /> ' + product.text + '</span>'
        );
        return $product;
    };

    function formatProductSelection (product) {
        return product.text;
    }
});
</script>
