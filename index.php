<?php
// index.php
require_once 'includes/header.php';
require_once 'includes/db_connection.php';

try {
    // Consulta para los slides de la galería de inicio
    $stmt_gallery = $pdo->query("SELECT * FROM hero_gallery WHERE es_activo = 1 ORDER BY orden ASC");
    $slides = $stmt_gallery->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para los productos
    $sql_productos = "SELECT p.*,
                        (SELECT gal.url FROM producto_galeria gal WHERE gal.producto_id = p.id AND gal.tipo = 'imagen' ORDER BY gal.orden ASC, gal.id ASC LIMIT 1) as imagen_principal,
                        GROUP_CONCAT(pc.categoria_id) as categorias_ids
                      FROM productos p
                      LEFT JOIN producto_categorias pc ON p.id = pc.producto_id
                      WHERE p.es_activo = 1 
                      GROUP BY p.id
                      ORDER BY p.fecha_creacion DESC";
    $stmt_productos = $pdo->prepare($sql_productos);
    $stmt_productos->execute();
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

    // 2. OBTENER LAS CATEGORÍAS DESTACADAS
$stmt_cat_destacadas = $pdo->query("
        SELECT DISTINCT c.id, c.nombre, c.slug 
        FROM categorias c
        JOIN producto_categorias pc ON c.id = pc.categoria_id
        JOIN productos p ON pc.producto_id = p.id
        WHERE c.mostrar_en_inicio = 1 AND p.es_activo = 1
        ORDER BY c.nombre ASC
    ");
    $categorias_destacadas = $stmt_cat_destacadas->fetchAll(PDO::FETCH_ASSOC);

    // 3. OBTENER TESTIMONIOS/RESEÑAS 
    $stmt_testimonios = $pdo->query("
        SELECT r.calificacion, r.comentario, u.nombre_pila, u.avatar_manual, u.avatar_url
        FROM resenas r
        JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.es_aprobada = 1 AND r.comentario IS NOT NULL AND r.comentario != ''
        ORDER BY r.fecha_creacion DESC
        LIMIT 6
    ");
    $testimonios = $stmt_testimonios->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: No se pudo obtener la información de la base de datos. " . $e->getMessage());
}

?>

<main>
    <div class="container-fluid py-4"> 
        <div class="row">
            <div class="col-lg-12">
                <section class="hero-gallery mb-4">
                    <?php if (!empty($slides)): ?>
                        <div class="slider">
                            <?php foreach ($slides as $slide): ?>
                                <div class="slide">
                                    <a href="<?php echo htmlspecialchars($slide['enlace_url'] ?? '#'); ?>">
                                        <img src="<?php echo BASE_URL . 'uploads/hero/' . htmlspecialchars($slide['imagen_url']); ?>" alt="<?php echo htmlspecialchars($slide['titulo'] ?? ''); ?>">
                                        <?php if (!empty($slide['titulo'])): ?>
                                            <div class="slide-caption">
                                                <h3><?php echo htmlspecialchars($slide['titulo']); ?></h3>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <h2>Promociones Destacadas</h2>
                    <?php endif; ?>
                </section>

                <h2>Nuestros Productos</h2>

                <?php if (!empty($categorias_destacadas)): ?>
                <ul class="nav nav-pills justify-content-center mb-4" id="category-filter-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-category-id="todos">Todos</a>
                    </li>
                    <?php foreach ($categorias_destacadas as $categoria): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-category-id="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-4" id="product-grid">
                    <?php if (empty($productos)): ?>
                        <div class="col">
                            <p>No se encontraron productos.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($productos as $producto): ?>
                            <?php 
                            // Preparamos los IDs de categoría para el atributo de datos
                            $data_categorias = ',' . ($producto['categorias_ids'] ?? '') . ',';
                            ?>
                            <div class="col product-filter-item" data-category-ids="<?php echo $data_categorias; ?>">
                                <?php include 'includes/product_card.php'; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
</main>

<?php if (!empty($testimonios)): ?>
<section class="testimonials-section bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">Lo que dicen nuestros clientes</h2>
        <div class="row">
            <?php foreach ($testimonios as $testimonio): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 text-center shadow-sm">
                        <div class="card-body">
                            <img src="<?php echo htmlspecialchars($testimonio['avatar_manual'] ? BASE_URL . 'uploads/avatars/' . $testimonio['avatar_manual'] : ($testimonio['avatar_url'] ?? BASE_URL . 'avatar/avatar-default.png')); ?>" alt="Avatar de cliente" class="testimonial-avatar rounded-circle mb-3">
                            <p class="card-text">"<?php echo nl2br(htmlspecialchars($testimonio['comentario'])); ?>"</p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <div class="testimonial-rating">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="bi <?php echo $i < $testimonio['calificacion'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <h5 class="mt-2 mb-0"><?php echo htmlspecialchars($testimonio['nombre_pila']); ?></h5>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>