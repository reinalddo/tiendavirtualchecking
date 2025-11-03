<?php
// admin/formulario_producto.php
require_once '../includes/config.php';
verificar_sesion_admin();

$producto = [];
$categorias_producto = [];
$galeria_items = [];
$titulo = "Añadir Nuevo Producto";

// Si se recibe un ID por la URL, significa que estamos editando
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $titulo = "Editar Producto";
    $producto_id = $_GET['id'];
    
    // Obtenemos los datos del producto de la base de datos
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtenemos las categorías asociadas
    $stmt_cat = $pdo->prepare("SELECT categoria_id FROM producto_categorias WHERE producto_id = ?");
    $stmt_cat->execute([$producto_id]);
    $categorias_producto = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);

    // Obtenemos la galería
    $stmt_gal = $pdo->prepare("SELECT * FROM producto_galeria WHERE producto_id = ?");
    $stmt_gal->execute([$producto_id]);
    $galeria_items = $stmt_gal->fetchAll(PDO::FETCH_ASSOC);
}


$categorias_todas = $pdo->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
require_once '../includes/header.php';
?>

<main>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h1 class="my-0 fw-normal fs-4"><?php echo $titulo; ?></h1>
                    </div>
                    <div class="card-body">
                        <form action="panel/producto/guardar" method="POST" enctype="multipart/form-data" id="product-form">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($producto['id'] ?? ''); ?>">

                            <div class="row">
                                <div class="col-md-8">
                                <div class="mb-3">
                                <label for="sku" class="form-label">SKU (Código de Producto):</label>
                                <input type="text" id="sku" name="sku" class="form-control" value="<?php echo htmlspecialchars($producto['sku'] ?? ''); ?>" required>
                                <div id="sku-feedback" class="form-text"></div>
                                </div>
                                <div class="mb-3">
                                        <label for="nombre" class="form-label">Nombre del Producto:</label>
                                        <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="descripcion_html" class="form-label">Descripción (HTML permitido):</label>
                                        <textarea id="descripcion_html" name="descripcion_html" class="form-control" rows="10"><?php echo htmlspecialchars($producto['descripcion_html'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">

                                <div class="mb-3">
                                    <label class="form-label">Tipo de Producto:</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_producto" id="tipo_fisico" value="fisico" <?php echo ($producto['tipo_producto'] ?? 'fisico') == 'fisico' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="tipo_fisico">Físico</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_producto" id="tipo_digital" value="digital" <?php echo ($producto['tipo_producto'] ?? '') == 'digital' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="tipo_digital">Digital</label>
                                    </div>
                                </div>

                                <div class="mb-3" id="campo-archivo-digital" style="<?php echo ($producto['tipo_producto'] ?? 'fisico') == 'digital' ? '' : 'display: none;'; ?>">
                                    <label for="archivo_digital" class="form-label">Archivo Digital:</label>
                                    <input type="file" id="archivo_digital" name="archivo_digital" class="form-control">
                                    <?php if (!empty($producto['archivo_digital_nombre'])): ?>
                                        <small class="form-text text-muted">Archivo actual: <?php echo htmlspecialchars($producto['archivo_digital_nombre']); ?>. Sube uno nuevo para reemplazarlo.</small>
                                    <?php endif; ?>
                                    <small class="form-text text-muted">Ej: PDF, ZIP, JPG, MP4. Límite: 50MB</small>
                                </div>

                                <div class="mb-3">
                                        <label for="precio_usd" class="form-label">Precio Base (en USD):</label>
                                        <input type="number" id="precio_usd" name="precio_usd" class="form-control" step="0.01" value="<?php echo htmlspecialchars($producto['precio_usd'] ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="precio_descuento" class="form-label">Precio de Descuento (opcional):</label>
                                        <input type="number" id="precio_descuento" name="precio_descuento" class="form-control" step="0.01" value="<?php echo htmlspecialchars($producto['precio_descuento'] ?? ''); ?>">
                                    </div>
                                     <div class="mb-3" id="campo-stock" style="<?php echo ($producto['tipo_producto'] ?? 'fisico') == 'fisico' ? '' : 'display: none;'; ?>">
                                        <label for="stock" class="form-label">Stock:</label>
                                        <input type="number" id="stock" name="stock" class="form-control" value="<?php echo htmlspecialchars($producto['stock'] ?? 0); ?>" min="0">
                                    </div>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="es_activo" value="1" id="es_activo" <?php echo !empty($producto['es_activo']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="es_activo">Producto Activo</label>
                                            </div>
                                            <?php /* ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="es_fisico" value="1" checked id="es_fisico" <?php echo !empty($producto['es_fisico']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="es_fisico">Es Producto Físico (requiere envío)</label>
                                            </div>
                                            <?php */ ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Categorías</h5>
                                    <div class="category-list-box border p-3 rounded" style="max-height: 200px; overflow-y: auto;" id="category-list">
                                        <?php foreach ($categorias_todas as $cat): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="categorias[]" value="<?php echo $cat['id']; ?>" id="cat-<?php echo $cat['id']; ?>" <?php echo in_array($cat['id'], $categorias_producto) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="cat-<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="inline-form mt-2">
                                        <input type="text" id="new_category_code" class="form-control form-control-sm" placeholder="Código Categoría">
                                        <input type="text" id="new_category_name" class="form-control form-control-sm" placeholder="Añadir Nueva Categoría">
                                        <button type="button" id="add-category-btn" class="btn btn-sm btn-secondary">Añadir</button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>Gestionar Galería</h5>
                                    
                                    <div class="current-gallery border rounded p-2 mb-3" id="gallery-sortable-container" style="min-height: 100px;">
                                        <?php if (empty($galeria_items)): ?>
                                            <small class="text-muted">No hay imágenes ni videos en la galería.</small>
                                        <?php endif; ?>

                                        <?php foreach ($galeria_items as $item): ?>
                                            <div class="gallery-item d-flex align-items-center justify-content-between mb-2" data-id="<?php echo $item['id']; ?>">
                                                <div>
                                                    <?php if ($item['tipo'] == 'imagen'): ?>
                                                        <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($item['url']); ?>" alt="Imagen del producto" height="40" class="me-2">
                                                    <?php elseif ($item['tipo'] == 'youtube'): ?>
                                                        <i class="bi bi-youtube text-danger me-2 fs-4"></i> 
                                                        <span>Video: <?php echo htmlspecialchars($item['url']); ?></span>
                                                    <?php elseif ($item['tipo'] == 'video_archivo'): ?>
                                                        <i class="bi bi-film text-info me-2 fs-4"></i> 
                                                        <span>Video: <?php echo htmlspecialchars($item['url']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <a href="panel/galeria/item/eliminar/<?php echo $item['id']; ?>/producto/<?php echo $producto['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="imagenes" class="form-label">Añadir Nuevas Imágenes:</label>
                                        <input type="file" id="imagenes" name="imagenes[]" class="form-control" multiple accept="image/*">
                                    </div>
                                    <button type="button" class="btn btn-secondary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#mediaLibraryModal">
                                        Elegir de la Biblioteca
                                    </button>
                                    <div class="mb-3">
                                        <label for="video_youtube" class="form-label">Añadir URL de Video (YouTube):</label>
                                        <input type="text" id="video_youtube" name="video_youtube" class="form-control" placeholder="Ej: https://www.youtube.com/watch?v=...">
                                    </div>
                                    <div class="mb-3">
                                        <label for="video_archivo" class="form-label">O subir un archivo de video (MP4):</label>
                                        <input type="file" id="video_archivo" name="video_archivo" class="form-control" accept="video/mp4">
                                        <small class="form-text text-muted">Límite recomendado: 20MB.</small>
                                    </div>
                                </div>

                            </div>
                            
                            <div class="mt-4 text-end">
                                <a href="panel/gestionar_productos" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Producto</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="<?php echo BASE_URL; ?>js/media-library-modal.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="<?php echo BASE_URL; ?>js/gallery-sorter.js"></script>

<script src="https://cdn.tiny.cloud/1/hhoyc5chobu9vd749362okve2q1v2h2lgwgbsxqmh6sx7mud/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
    tinymce.init({
        selector: '#descripcion_html', // Apunta al ID de tu campo de descripción
        plugins: 'lists link image media table code help wordcount',
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | code | help'
    });

    // Lógica para mostrar/ocultar campos según el tipo de producto
    document.addEventListener('DOMContentLoaded', function() {
        const tipoFisico = document.getElementById('tipo_fisico');
        const tipoDigital = document.getElementById('tipo_digital');
        const campoStock = document.getElementById('campo-stock');
        const campoArchivo = document.getElementById('campo-archivo-digital');
        const inputStock = document.getElementById('stock'); // Input de stock
        const inputArchivo = document.getElementById('archivo_digital'); // Input de archivo

        function toggleProductFields() {
            if (tipoDigital.checked) {
                campoStock.style.display = 'none';
                campoArchivo.style.display = 'block';
                inputStock.required = false; // Stock no es requerido para digital
                // Archivo es requerido solo si es un producto nuevo digital
                // O si se está editando y no hay archivo previo
                <?php if (empty($producto['id']) || empty($producto['archivo_digital_ruta'])): ?>
                   // inputArchivo.required = true; // Descomenta si quieres que siempre sea obligatorio subir algo
                <?php else: ?>
                   // inputArchivo.required = false; // No es req si ya existe uno
                <?php endif; ?>

            } else { // Físico seleccionado
                campoStock.style.display = 'block';
                campoArchivo.style.display = 'none';
                inputStock.required = true; // Stock es requerido para físico
                inputArchivo.required = false; // Archivo no es requerido
            }
        }

        tipoFisico.addEventListener('change', toggleProductFields);
        tipoDigital.addEventListener('change', toggleProductFields);

        // Ejecutar al cargar por si se está editando
        toggleProductFields();
    });

</script>

<?php require_once '../includes/footer.php'; ?>