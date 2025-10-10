<?php
// ajax_buscar_productos.php (Versión Corregida y Definitiva)
require_once 'includes/config.php';
//require_once 'includes/db_connection.php';

header('Content-Type: application/json');

$term = $_GET['term'] ?? '';
$resultados = [];
//var_dump($term);

if (strlen($term) >= 3) {
    // 1. Preparamos el término de búsqueda para buscar singulares y plurales
    $base_term = rtrim($term, 's');
    $search_query = $base_term . '* ' . $base_term . 's*';

    // 2. Consulta SQL corregida y más estándar
    $sql = "SELECT
                p.id, p.nombre, p.slug, p.descripcion_html,
                (SELECT gal.url FROM producto_galeria gal WHERE gal.producto_id = p.id AND gal.tipo = 'imagen' ORDER BY gal.orden ASC, gal.id ASC LIMIT 1) as imagen_principal,
                (MATCH(p.nombre) AGAINST(:termino IN BOOLEAN MODE) * 5) as relevancia_nombre,
                (MATCH(p.descripcion_html) AGAINST(:termino IN BOOLEAN MODE)) as relevancia_desc
            FROM productos p
            WHERE p.es_activo = 1
            AND MATCH(p.nombre, p.descripcion_html) AGAINST(:termino IN BOOLEAN MODE)
            ORDER BY relevancia_nombre DESC, relevancia_desc DESC
            LIMIT 5";

    $sql2 = "SELECT
                p.id, p.nombre, p.descripcion_html,
                (SELECT gal.url FROM producto_galeria gal WHERE gal.producto_id = p.id AND gal.tipo = 'imagen' ORDER BY gal.orden ASC, gal.id ASC LIMIT 1) as imagen_principal,
                (MATCH(p.nombre) AGAINST('$search_query' IN BOOLEAN MODE) * 5) as relevancia_nombre,
                (MATCH(p.descripcion_html) AGAINST('$search_query' IN BOOLEAN MODE)) as relevancia_desc
            FROM productos p
            WHERE p.es_activo = 1
            AND MATCH(p.nombre, p.descripcion_html) AGAINST('$search_query' IN BOOLEAN MODE)
            ORDER BY relevancia_nombre DESC, relevancia_desc DESC
            LIMIT 5";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':termino' => $search_query]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Formateamos los resultados (esto no cambia)
    foreach ($productos as $producto) {
        $descripcion_corta = substr(strip_tags($producto['descripcion_html']), 0, 70) . '...';
        $resultados[] = [
            'id' => $producto['id'],
            'nombre' => htmlspecialchars($producto['nombre']),
            'descripcion' => htmlspecialchars($descripcion_corta),
            'imagen_url' => BASE_URL . 'uploads/' . htmlspecialchars($producto['imagen_principal'] ?? 'placeholder.png'),
            'url_producto' => BASE_URL . 'producto/' . htmlspecialchars($producto['slug']), 
            'sql' => $sql2
        ];
    }
}

echo json_encode($resultados);
?>