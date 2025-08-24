<?php
// producto_detalle.php
// --- NUEVO: PREPARAR DATOS PARA EL SCHEMA ---
require_once 'includes/header.php';
require_once 'includes/db_connection.php';

$producto_id = $_GET['id'];


$esta_en_wishlist = false;
if (isset($_SESSION['usuario_id'])) {
    $stmt_wish = $pdo->prepare("SELECT id FROM wishlist WHERE usuario_id = ? AND producto_id = ?");
    $stmt_wish->execute([$_SESSION['usuario_id'], $producto_id]);
    $esta_en_wishlist = $stmt_wish->fetch() !== false;
}

// 2. Obtener los detalles del producto de la BD
$stmt = $pdo->prepare("SELECT p.* FROM productos p WHERE p.id = ? AND p.es_activo = 1");
$stmt->execute([$producto_id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

// --- ESTA ES LA VERIFICACIÓN IMPORTANTE ---
if (!$producto) {
    // Si no se encuentra el producto, mostramos un mensaje amigable y detenemos todo.
    echo "<h1>Este producto no existe o no está disponible.</h1>";
    require_once 'includes/footer.php'; // Incluimos el footer para que la página se vea completa
    exit(); // Detenemos la ejecución del script
}

$schema_data = [
    '@context' => 'https://schema.org/',
    '@type' => 'Product',
    'name' => $producto['nombre'],
    'description' => strip_tags($producto['descripcion_html'] ?? ''),
    'sku' => 'PROD-' . $producto['id'], // SKU de ejemplo
    'image' => !empty($galeria_items) ? BASE_URL . 'uploads/' . $galeria_items[0]['url'] : '',
    'offers' => [
        '@type' => 'Offer',
        'url' => BASE_URL . 'producto_detalle.php?id=' . $producto_id,
        'priceCurrency' => 'USD', // Moneda base
        'price' => $producto['precio_usd'],
        'availability' => ($producto['stock'] > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
    ],
];
$stmt_qa = $pdo->prepare("SELECT q.*, u.nombre_pila as nombre_usuario 
                          FROM preguntas_respuestas q 
                          JOIN usuarios u ON q.usuario_id = u.id 
                          WHERE q.producto_id = ? 
                          ORDER BY q.fecha_pregunta DESC");
$stmt_qa->execute([$producto_id]);
$preguntas = $stmt_qa->fetchAll(PDO::FETCH_ASSOC);

// --- Lógica para obtener las reseñas ---
$stmt_resenas = $pdo->prepare("SELECT r.*, u.nombre_pila as nombre_usuario 
                               FROM resenas r
                               JOIN usuarios u ON r.usuario_id = u.id
                               WHERE r.producto_id = ? AND r.es_aprobada = 1
                               ORDER BY r.fecha_creacion DESC");
$stmt_resenas->execute([$producto_id]);
$resenas = $stmt_resenas->fetchAll(PDO::FETCH_ASSOC);

    // Calcular la calificación promedio
    $calificacion_promedio = 0;
    if (!empty($resenas)) {
        $total_calificaciones = 0;
        foreach ($resenas as $resena) {
            $total_calificaciones += $resena['calificacion'];
        }
        $calificacion_promedio = round($total_calificaciones / count($resenas), 1);
    }

// Añadir valoración si existen reseñas
if ($calificacion_promedio > 0 && !empty($resenas)) {
    $schema_data['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => $calificacion_promedio,
        'reviewCount' => count($resenas)
    ];
}

$schema_json = json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
// --- FIN DEL BLOQUE NUEVO ---

?>
<script type="application/ld+json">
<?php echo $schema_json; ?>
</script>
<?php

// Obtener la configuración del mapa principal al principio del archivo
$stmt_mapa = $pdo->query("SELECT valor_setting FROM configuraciones WHERE nombre_setting = 'mapa_principal'");
$mapa_principal = $stmt_mapa->fetchColumn();


$stmt_config = $pdo->query("SELECT nombre_setting, valor_setting FROM configuraciones");
$configuraciones_raw = $stmt_config->fetchAll(PDO::FETCH_ASSOC);
$config = [];
foreach ($configuraciones_raw as $setting) {
    $config[$setting['nombre_setting']] = $setting['valor_setting'];
}

// 1. Obtener y validar el ID del producto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<h1>Producto no encontrado</h1>";
    require_once 'includes/footer.php';
    exit();
}

// 3. Obtener los items de la galería para este producto (CORRECCIÓN)
$stmt_galeria = $pdo->prepare("SELECT * FROM producto_galeria WHERE producto_id = ? ORDER BY tipo ASC, orden ASC, id ASC");
$stmt_galeria->execute([$producto_id]);
$galeria_items = $stmt_galeria->fetchAll(PDO::FETCH_ASSOC);

// Preparamos las variables para las meta etiquetas dinámicas
$meta_title = htmlspecialchars($producto['nombre']) . ' | Mi Tienda Web';
// Tomamos los primeros 155 caracteres de la descripción sin HTML
$meta_description = substr(strip_tags($producto['descripcion_html'] ?? ''), 0, 155);
// Usamos la primera imagen de la galería para la vista previa
$meta_image = !empty($galeria_items) ? BASE_URL . 'uploads/' . htmlspecialchars($galeria_items[0]['url']) : BASE_URL . 'images/tienda_preview.jpg';

// 4. Lógica para ver si el usuario puede opinar
$puede_opinar = false;
$ha_comprado = false;
$ha_opinado = false;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $stmt_compra = $pdo->prepare(
        "SELECT COUNT(*) FROM pedidos p 
         JOIN pedido_detalles pd ON p.id = pd.pedido_id 
         WHERE p.usuario_id = :usuario_id AND pd.producto_id = :producto_id AND p.estado = 'Pagado'");
    $stmt_compra->execute([':usuario_id' => $usuario_id, ':producto_id' => $producto_id]);
    $ha_comprado = $stmt_compra->fetchColumn() > 0;
    $stmt_opinion = $pdo->prepare("SELECT COUNT(*) FROM resenas WHERE usuario_id = ? AND producto_id = ?");
    $stmt_opinion->execute([$usuario_id, $producto_id]);
    $ha_opinado = $stmt_opinion->fetchColumn() > 0;
    if ($ha_comprado && !$ha_opinado) {
        $puede_opinar = true;
    }
}

// Descomenta la siguiente sección si necesitas depurar de nuevo
/*
echo "<pre style='background: #eee; padding: 10px; border: 1px solid #ccc; position: fixed; top: 50px; left: 10px; z-index: 9999;'>";
echo "<strong>--- DATOS DE DEPURACIÓN ---</strong>\n";
echo "Usuario en sesión (ID): " . ($_SESSION['usuario_id'] ?? 'NO HAY SESIÓN') . "\n";
echo "Producto ID: " . $producto_id . "\n";
echo "Ha comprado (resultado consulta): " . ($ha_comprado ? 'Sí' : 'No') . "\n";
echo "Ha opinado (resultado consulta): " . ($ha_opinado ? 'No' : ""). "\n";
echo "Puede opinar (resultado final): " . ($puede_opinar ? 'Sí' : 'No') . "\n";
echo "---------------------------\n";
echo "</pre>";
*/
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/gallery-detail.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/product-detail-style.css">

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 col-lg-7">

            <div class="detail-gallery"> 
                    <div class="detail-gallery-background" id="detail-gallery-background"></div>
                    <div class="detail-gallery-main" id="detail-gallery-main">
                        <?php if (!empty($galeria_items)): $primer_item = $galeria_items[0]; ?>
                            <?php if ($primer_item['tipo'] == 'imagen'): ?>
                                <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($primer_item['url']); ?>" alt="Vista principal" class="clickable-gallery-image" data-index="0">
                            <?php elseif ($primer_item['tipo'] == 'youtube'): ?>
                                <div class="video-wrapper">
                                    <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($primer_item['url']); ?>" frameborder="0" allowfullscreen></iframe>
                                    <div class="clickable-gallery-image video-overlay" data-index="0"></div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="main-image-placeholder"></div>
                        <?php endif; ?>
                    </div>
            <div class="detail-gallery-thumbnails">
                <?php foreach ($galeria_items as $index => $item): ?>
                    <div class="detail-thumbnail" data-index="<?php echo $index; ?>" data-tipo="<?php echo $item['tipo']; ?>" data-url="<?php echo htmlspecialchars($item['url']); ?>">
                        <?php if ($item['tipo'] == 'imagen'): ?>
                            <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($item['url']); ?>" alt="Miniatura">
                        <?php elseif ($item['tipo'] == 'youtube'): ?>
                            <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($item['url']); ?>/0.jpg" alt="Miniatura de Video">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-5">

            <div class="product-info">
                <h1><?php echo htmlspecialchars($producto['nombre']); ?></h1>
                <p class="price">
                        <?php echo format_price($producto['precio_usd'], $producto['precio_descuento']); ?>
                </p>
                <p><strong>Stock disponible:</strong> <?php echo htmlspecialchars($producto['stock']); ?></p>
                <form action="carrito_acciones.php" method="POST">
                    <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                    
                    <input type="hidden" name="return_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">

                    <label for="cantidad">Cantidad:</label>
                    <input type="number" id="cantidad" name="cantidad" value="1" min="1" max="<?php echo $producto['stock']; ?>">
                    <button type="submit" name="agregar_al_carrito" class="button">Añadir al Carrito</button>
                </form>
                <div class="wishlist-container">
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <button class="wishlist-btn <?php echo $esta_en_wishlist ? 'active' : ''; ?>" data-producto-id="<?php echo $producto_id; ?>">
                            ❤ Guardar en mi Lista
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="row mt-4">
    <div class="col-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2 product-info description">
        <?php echo $producto['descripcion_html']; ?>
    </div>
    <div class="col-md-1 col-lg-2"></div>

</div>

<div class="row mt-5">
    <div class="col-12">

        <ul class="nav nav-tabs" id="product-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews-pane" type="button">Valoraciones y Reseñas</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="qa-tab" data-bs-toggle="tab" data-bs-target="#qa-pane" type="button">Preguntas y Respuestas</button>
            </li>
            <?php if (!empty($config['mostrar_mapa_en_productos']) && !empty($config['mapa_principal'])): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="map-tab" data-bs-toggle="tab" data-bs-target="#map-pane" type="button">Ubicación</button>
                </li>
            <?php endif; ?>
        </ul>

        <div class="tab-content card shadow-sm border-top-0" id="product-tabs-content">

        <div class="tab-pane fade show active p-4" id="reviews-pane">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="my-0 fw-normal fs-5">Valoraciones y Reseñas</h3>
            </div>
            <div class="card-body">
                <div class="average-rating mb-3">
                    <strong>Calificación Promedio: <?php echo $calificacion_promedio; ?> / 5</strong>
                </div>
                <div class="reviews-list">
                    <?php if (empty($resenas)): ?>
                        <p class="text-muted">Este producto aún no tiene reseñas.</p>
                    <?php else: ?>
                        <?php foreach ($resenas as $resena): ?>
                            <div class="review-item mb-3 border-bottom pb-2">
                                <strong><?php echo htmlspecialchars($resena['nombre_usuario']); ?></strong>
                                <small class="text-muted">- Calificación: <?php echo $resena['calificacion']; ?>/5</small>
                                <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($resena['comentario'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <hr>

                <div class="review-form-container">
                    <?php if ($puede_opinar): ?>
                        <h4>Deja tu valoración</h4>
                        <form action="guardar_resena.php" method="POST">
                            <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">
                            <div class="rating mb-2">
                                <input type="radio" id="star5" name="calificacion" value="5" required/><label for="star5" title="5 estrellas"></label>
                                <input type="radio" id="star4" name="calificacion" value="4" /><label for="star4" title="4 estrellas"></label>
                                <input type="radio" id="star3" name="calificacion" value="3" /><label for="star3" title="3 estrellas"></label>
                                <input type="radio" id="star2" name="calificacion" value="2" /><label for="star2" title="2 estrellas"></label>
                                <input type="radio" id="star1" name="calificacion" value="1" /><label for="star1" title="1 estrella"></label>
                            </div>
                            <div class="mb-3">
                                <textarea name="comentario" class="form-control" rows="4" placeholder="Escribe tu reseña aquí..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Enviar Reseña</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>

        <div class="tab-pane fade p-4" id="qa-pane">
        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h3 class="my-0 fw-normal fs-5">Preguntas y Respuestas</h3>
            </div>
            <div class="card-body">
                <div class="qa-section">
                    <?php if (empty($preguntas)): ?>
                        <p class="text-muted">Aún no hay preguntas para este producto.</p>
                    <?php else: ?>
                        <?php foreach ($preguntas as $qa): ?>
                            <div class="qa-item mb-3 border-bottom pb-2">
                                <p class="pregunta mb-1"><strong>P:</strong> <?php echo htmlspecialchars($qa['pregunta']); ?></p>
                                <p class="respuesta text-muted ms-3 mb-0"><strong>R:</strong> <?php echo !empty($qa['respuesta']) ? nl2br(htmlspecialchars($qa['respuesta'])) : 'Próximamente responderemos a esta pregunta.'; ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <hr>
                
                <?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_rol'] === 'cliente'): ?>
                    <h4>Haz una pregunta</h4>
                    <form action="guardar_pregunta.php" method="POST">
                         <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">
                         <div class="mb-3">
                            <textarea name="pregunta" class="form-control" rows="4" required placeholder="Escribe tu pregunta aquí..."></textarea>
                         </div>
                         <button type="submit" class="btn btn-primary">Enviar Pregunta</button>
                    </form>
                <?php elseif (!isset($_SESSION['usuario_id'])): ?>
                    <p>Debes <a href="login.php">iniciar sesión</a> para hacer una pregunta.</p>
                <?php endif; ?>
            </div>
        </div>
        </div>

        <?php if (!empty($config['mostrar_mapa_en_productos']) && !empty($config['mapa_principal'])): ?>
            <div class="tab-pane fade p-0" id="map-pane">
                <?php echo $config['mapa_principal']; ?>
            </div>
        <?php endif; ?>
        </div>

    </div>
</div>

        <div class="modal fade" id="gallery-modal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div id="modal-content-host">
                            </div>
                    </div>
                    <a class="modal-prev">&#10094;</a>
                    <a class="modal-next">&#10095;</a>
                </div>
            </div>
        </div>

<script src="<?php echo BASE_URL; ?>js/gallery-detail.js"></script>

<?php require_once 'includes/footer.php'; ?>