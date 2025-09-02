<?php
require_once 'includes/header.php';
require_once 'includes/db_connection.php';

$termino_busqueda = $_GET['q'] ?? '';

if (empty($termino_busqueda)) {
    $productos = [];
    $titulo_pagina = "Por favor, introduce un término de búsqueda.";
} else {
    $titulo_pagina = "Resultados para: " . htmlspecialchars($termino_busqueda);

    // --- NUEVA LÓGICA DE BÚSQUEDA ---
    // 1. Limpiamos y preparamos el término
    $base_term = rtrim($termino_busqueda, 's'); // Quitamos la 's' del final si existe

    // 2. Creamos un término de búsqueda que incluya la raíz y el posible plural
    // Ej: si buscas "tazas", buscará "taza*" O "tazas*"
    $search_query = $base_term . '* ' . $base_term . 's*';

$sql = "SELECT p.*,
        (SELECT gal.url FROM producto_galeria gal WHERE gal.producto_id = p.id AND gal.tipo = 'imagen' ORDER BY gal.orden ASC, gal.id ASC LIMIT 1) as imagen_principal, 
        -- Calculamos relevancias separadas y le damos más peso al nombre
        (MATCH(p.nombre) AGAINST(:termino IN BOOLEAN MODE) * 5) as relevancia_nombre,
        (MATCH(p.descripcion_html) AGAINST(:termino IN BOOLEAN MODE)) as relevancia_desc
        FROM productos p
        WHERE p.es_activo = 1 
        AND MATCH(p.nombre, p.descripcion_html) AGAINST(:termino IN BOOLEAN MODE)
        -- Ordenamos por la suma de las relevancias
        ORDER BY relevancia_nombre DESC, relevancia_desc DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':termino' => $search_query]); 
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