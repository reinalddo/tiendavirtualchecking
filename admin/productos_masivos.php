<?php
// admin/productos_masivos.php
require_once '../includes/header.php';
require_once '../includes/db_connection.php';
// ... (Verificación de seguridad) ...

// 1. Obtenemos todas las categorías una sola vez
//$todas_las_categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- LÓGICA DE FILTRADO Y PAGINACIÓN ---
// 2. Obtenemos los productos y sus categorías asociadas
/*
$productos = $pdo->query("
    SELECT p.*, 
           GROUP_CONCAT(pg.id SEPARATOR '||') as galeria_ids,
           GROUP_CONCAT(pg.url SEPARATOR '||') as galeria_urls,
           GROUP_CONCAT(pc.categoria_id) as categorias_asignadas
    FROM productos p
    LEFT JOIN producto_galeria pg ON p.id = pg.producto_id AND pg.tipo = 'imagen'
    LEFT JOIN producto_categorias pc ON p.id = pc.producto_id
    GROUP BY p.id
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
*/
// --- LÓGICA DE FILTRADO Y PAGINACIÓN ---
$resultados_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $resultados_por_pagina;

$base_sql = "FROM productos p 
             LEFT JOIN producto_galeria pg ON p.id = pg.producto_id AND pg.tipo = 'imagen'
             LEFT JOIN producto_categorias pc ON p.id = pc.producto_id
             LEFT JOIN categorias c ON pc.categoria_id = c.id";
$where_clauses = ["1=1"];
$params = []; // Usaremos un array asociativo para los parámetros

// Filtro por búsqueda de texto
if (!empty($_GET['q'])) {
    $where_clauses[] = "(p.sku LIKE :q OR p.nombre LIKE :q OR p.descripcion_html LIKE :q)";
    $params[':q'] = '%' . $_GET['q'] . '%';
}
// Filtro por categoría
if (!empty($_GET['categoria_id'])) {
    $where_clauses[] = "pc.categoria_id = :categoria_id";
    $params[':categoria_id'] = (int)$_GET['categoria_id'];
}
$where_sql = " WHERE " . implode(" AND ", $where_clauses);

// Contar el total de resultados para la paginación
$sql_count = "SELECT COUNT(DISTINCT p.id) " . $base_sql . $where_sql;
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_resultados = $stmt_count->fetchColumn();
$total_paginas = ceil($total_resultados / $resultados_por_pagina);

// Obtener los productos para la página actual
$sql_productos = "SELECT p.*, GROUP_CONCAT(pg.id SEPARATOR '||') as galeria_ids,
           GROUP_CONCAT(pg.url SEPARATOR '||') as galeria_urls,
           GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias_nombres,
           GROUP_CONCAT(pc.categoria_id) as categorias_asignadas 
                  " . $base_sql . $where_sql . "
                  GROUP BY p.id
                  ORDER BY p.id DESC
                  LIMIT :offset, :limit";

$stmt_productos = $pdo->prepare($sql_productos);

// Vinculamos los parámetros de los filtros y de la paginación
$stmt_productos->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_productos->bindValue(':limit', $resultados_por_pagina, PDO::PARAM_INT);
foreach ($params as $key => &$val) {
    $stmt_productos->bindValue($key, $val);
}

$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Obtener todas las categorías para el selector del filtro
$todas_las_categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

?>
<main>
<div class="container-fluid py-4">
    <h1 class="h2 mb-4">Gestión Masiva de Productos</h1>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="my-0 fw-normal">Importar Productos desde Excel (.xlsx)</h5>
        </div>
        <div class="card-body">
            <p>Sube un archivo Excel para crear o actualizar productos de forma masiva.</p>
            <form action="procesar_importacion.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="archivo_excel" class="form-label">Seleccionar archivo .xlsx:</label>
                    <input class="form-control" type="file" name="archivo_excel" id="archivo_excel" accept=".xlsx" required>
                </div>
                <button type="submit" class="btn btn-primary">Validar e Importar</button>
                <a href="<?php echo BASE_URL; ?>admin/layout_importacion.xlsx" class="btn btn-secondary" download>Descargar Plantilla</a>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h5 class="my-0 fw-normal">Filtrar Productos</h5>
        </div>
        <div class="card-body">
            <form action="productos_masivos.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="q" class="form-label">Buscar por SKU, Nombre o Descripción:</label>
                    <input type="text" name="q" id="q" class="form-control" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                </div>
                <div class="col-md-5">
                    <label for="categoria_id" class="form-label">Filtrar por Categoría:</label>
                    <select name="categoria_id" id="categoria_id" class="form-select">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($todas_las_categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" <?php if (($_GET['categoria_id'] ?? '') == $categoria['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="table-responsive">
                </div>
        </div>
        <div class="card-footer">
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?php if ($i == $pagina_actual) echo 'active'; ?>">
                            <a class="page-link" href="?pagina=<?php echo $i; ?>&<?php echo http_build_query($_GET, '', '&'); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>

    
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h5 class="my-0 fw-normal">Editor Masivo de Productos</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nombre</th>
                            <th>Precio Base (USD)</th> 
                            <th>Stock</th>
                            <th>Categorías</th>
                            <th>Descripción</th>
                            <th>Galería</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                        <tr data-producto-id="<?php echo $producto['id']; ?>">
                            <td><?php echo htmlspecialchars($producto['sku']); ?></td>
                            <td><input type="text" class="form-control update-producto" name="nombre" value="<?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>"></td>
                            <td><input type="number" class="form-control form-control-sm update-producto" name="precio_usd" value="<?php echo htmlspecialchars($producto['precio_usd'] ?? ''); ?>" step="0.01"></td>
                            <td><input type="number" class="form-control form-control-sm update-producto" name="stock" value="<?php echo htmlspecialchars($producto['stock'] ?? ''); ?>"></td>

                            <td class="categories-cell">
                                <div class="row">
                                    <?php
                                    // Dividimos las categorías en 3 columnas
                                    $categorias_producto = !empty($producto['categorias_asignadas']) ? explode(',', $producto['categorias_asignadas']) : [];
                                    $categorias_por_columna = ceil(count($todas_las_categorias) / 3);
                                    $columnas = array_chunk($todas_las_categorias, $categorias_por_columna);

                                    foreach ($columnas as $columna):
                                    ?>
                                        <div class="col-4">
                                            <?php foreach ($columna as $categoria): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input update-producto-categoria" type="checkbox" 
                                                        value="<?php echo $categoria['id']; ?>" 
                                                        data-producto-id="<?php echo $producto['id']; ?>"
                                                        <?php if (in_array($categoria['id'], $categorias_producto)) echo 'checked'; ?>>
                                                    <label class="form-check-label">
                                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>

                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-descripcion" data-bs-toggle="modal" data-bs-target="#descripcionModal" data-descripcion="<?php echo htmlspecialchars($producto['descripcion_html']); ?>" data-producto-id="<?php echo $producto['id']; ?>">
                                    Editar Descripción
                                </button>
                            </td>
                            <td class="gallery-cell">
                                <div class="current-mass-gallery-preview d-flex flex-wrap gap-2 mb-2">
                                    <?php
                                    // Mostramos las imágenes existentes con su botón de eliminar
                                    if (!empty($producto['galeria_urls'])) {
                                        $imagenes = explode('||', $producto['galeria_urls']);
                                        $ids = explode('||', $producto['galeria_ids']);
                                        foreach (array_combine($ids, $imagenes) as $id => $url) {
                                            echo '
                                            <div class="preview-item position-relative" data-gallery-id="' . $id . '">
                                                <img src="' . BASE_URL . 'uploads/' . htmlspecialchars($url) . '" class="img-fluid rounded" style="width: 50px; height: 50px; object-fit: cover;">

                                                <button type="button" class="position-absolute top-0 end-0 remove-existing-image-btn" 
                                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar">
                                                    &times;
                                                </button>

                                                </div>';
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="new-images-preview d-flex flex-wrap gap-2 mb-2"></div>
                                <button type="button" class="btn btn-sm btn-secondary select-media-btn" data-bs-toggle="modal" data-bs-target="#mediaLibraryModal">Añadir Imágenes</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    </div>
<div class="modal fade" id="confirmationModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title" id="confirmationModalTitle">Confirmar Acción</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body" id="confirmationModalBody">
¿Estás seguro de que quieres realizar esta acción?
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
<button type="button" class="btn btn-danger" id="confirmActionButton">Eliminar</button>
</div>
</div>
</div>
</div>
</main>

<script src="<?php echo BASE_URL; ?>js/productos-masivos.js"></script>
<script src="<?php echo BASE_URL; ?>js/media-library-modal.js"></script>

<div class="modal fade" id="descripcionModal" tabindex="-1" aria-labelledby="descripcionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="descripcionModalLabel">Editar Descripción del Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" id="modalDescripcionHTML" rows="15"></textarea>
                <input type="hidden" id="modalProductoId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="guardarDescripcionBtn">Guardar Descripción</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.tiny.cloud/1/hhoyc5chobu9vd749362okve2q1v2h2lgwgbsxqmh6sx7mud/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="<?php echo BASE_URL; ?>js/admin-scripts.js"></script>

<?php require_once '../includes/footer.php'; ?>
<script>
    // Inicializar todos los tooltips de Bootstrap en la página
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>

