<?php
require_once 'includes/header.php';
require_once 'includes/db_connection.php';

$termino_busqueda = $_GET['q'] ?? '';

if (empty($termino_busqueda)) {
    $productos = [];
    $titulo_pagina = "Por favor, introduce un término de búsqueda.";
} else {
    $titulo_pagina = "Resultados para: " . htmlspecialchars($termino_busqueda);

    // Buscamos en el nombre y la descripción del producto
    $sql = "SELECT p.*,
            (SELECT gal.url FROM producto_galeria gal WHERE gal.producto_id = p.id AND gal.tipo = 'imagen' ORDER BY gal.orden ASC, gal.id ASC LIMIT 1) as imagen_principal
            FROM productos p
            WHERE p.es_activo = 1 
            AND (p.nombre LIKE :termino OR p.descripcion_html LIKE :termino)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':termino' => '%' . $termino_busqueda . '%']);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
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