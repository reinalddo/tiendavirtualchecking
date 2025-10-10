<?php
require_once 'includes/config.php';
//var_dump($_GET['q']);exit;
// Verificamos si hemos llegado a esta página con el parámetro 'q'
/*
if (isset($_GET['q'])) {
    $termino_busqueda = $_GET['q'];
    if (!empty($termino_busqueda)) {
        // Si hay un término de búsqueda, construimos la URL amigable
        $url_amigable =  'buscar/' . urlencode($termino_busqueda);
        
        // Redirigimos permanentemente (código 301) a la nueva URL y detenemos el script
        header('Location: ' . $url_amigable, true, 301);
        exit();
    }
}
*/
// El .htaccess nos pasa el término con guiones (ej. "zapatos-de-cuero") a través de $_GET['q']
/*
$ruta = $_SERVER['REQUEST_URI'];
$ruta = str_replace("/buscar/", "", $ruta);
$slug_busqueda = $_GET['q'] ?? $ruta;
*/
$slug_busqueda = '';

// Primero, intentamos obtener el término de la forma estándar (que el .htaccess debería proporcionar)
if (isset($_GET['q'])) {
    $slug_busqueda = $_GET['q'];
} 
// Si eso falla (como en tu WAMP), usamos tu método de respaldo para extraerlo de la URL
elseif (strpos($_SERVER['REQUEST_URI'], '/buscar/') !== false) {
    // La función basename() es ideal para obtener la última parte de una URL
    $slug_busqueda = basename($_SERVER['REQUEST_URI']);
}

// Decodificamos el término por si contiene caracteres especiales (ej. %20 por espacios)
$slug_busqueda = urldecode($slug_busqueda);

if (empty($slug_busqueda)) {
    // Si no hay término, mostramos el mensaje por defecto
    $productos = [];
    $titulo_pagina = "Por favor, introduce un término de búsqueda.";
    $termino_busqueda = '';
} else {
    // Si hay un término con guiones, lo convertimos de nuevo a un texto con espacios
    $termino_busqueda = str_replace('-', ' ', $slug_busqueda);
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
require_once 'includes/header.php';

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