<?php
require_once 'includes/header.php';
require_once 'includes/db_connection.php';

$titulo_pagina = "Todos los Productos";
$categoria_slug = $_GET['categoria_slug'] ?? null; // Leemos el slug de la categoría

// Preparamos la base de la consulta SQL
$sql = "SELECT p.*,
            (SELECT gal.url FROM producto_galeria gal WHERE gal.producto_id = p.id AND gal.tipo = 'imagen' ORDER BY gal.orden ASC, gal.id ASC LIMIT 1) as imagen_principal
        FROM productos p
        ";

$params = [];

// Si se especifica una categoría, modificamos la consulta
if ($categoria_slug) {
    // Si se especifica un slug, modificamos la consulta para filtrar
    $sql .= " JOIN producto_categorias pc ON p.id = pc.producto_id
              JOIN categorias c ON pc.categoria_id = c.id
              WHERE p.es_activo = 1 AND c.slug = ?";
    $params[] = $categoria_slug;

    // Obtenemos el nombre de la categoría para el título
    $stmt_cat = $pdo->prepare("SELECT nombre FROM categorias WHERE slug = ?");
    $stmt_cat->execute([$categoria_slug]);
    $nombre_cat = $stmt_cat->fetchColumn();
    if ($nombre_cat) {
        $titulo_pagina = "Productos en: " . htmlspecialchars($nombre_cat);
    }
} else {
    $sql .= " WHERE p.es_activo = 1 ";
}

$sql .= " ORDER BY p.fecha_creacion DESC ";

// Ejecutamos la consulta final
$stmt_productos = $pdo->prepare($sql);
$stmt_productos->execute($params);
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

?>

    <div class="container-fluid py-4"> 
        <div class="row">
            <div class="col-lg-12">

            <div class="main-content">
                <h1><?php echo $titulo_pagina; ?></h1>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-4">
                    <?php if (empty($productos)): ?>
                        <div class="col">
                            <p>No se encontraron productos.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($productos as $producto): ?>
                            <?php include 'includes/product_card.php'; // Incluimos la tarjeta reutilizable ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>