<?php
// includes/header.php
require_once 'config.php'; // Incluimos nuestra nueva configuración

// Iniciar la sesión solo si no hay una activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo ABSOLUTE_URL; ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo $meta_title ?? 'Mi Tienda Web'; ?></title>
    
    <?php
    // Primero, definimos la ruta del favicon por defecto
    $favicon_path = BASE_URL . 'favicon.png';
    $favicon_type = 'image/x-icon';

    // Luego, verificamos si hay un logo personalizado configurado en el sitio
    if (!empty($config['tienda_logo'])) {
        $ruta_logo_personalizado = 'uploads/' . $config['tienda_logo'];
        
        // Y comprobamos que el archivo de ese logo realmente exista en el servidor
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . BASE_URL . $ruta_logo_personalizado)) {
            $favicon_path = BASE_URL . $ruta_logo_personalizado;
            // Obtenemos el tipo de imagen para el atributo 'type'
            $info = getimagesize($_SERVER['DOCUMENT_ROOT'] . BASE_URL . $ruta_logo_personalizado);
            if ($info) {
                $favicon_type = $info['mime'];
            }
        }
    }
    ?>
    <link rel="icon" href="<?php echo $favicon_path; ?>" type="<?php echo $favicon_type; ?>">

    <meta name="description" content="<?php echo $meta_description ?? 'Descubre nuestra selección de productos únicos y de alta calidad.'; ?>">
    
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo $meta_title ?? htmlspecialchars($config['tienda_nombre'] ?? 'Mi Tienda Web'); ?>">
    <meta property="og:description" content="<?php echo $meta_description ?? htmlspecialchars($config['tienda_descripcion_corta'] ?? ''); ?>">
    <meta property="og:url" content="<?php echo ABSOLUTE_URL . ltrim($_SERVER['REQUEST_URI'], '/'); ?>">
    <meta property="og:image" content="<?php echo $meta_image ?? ABSOLUTE_URL . 'imgredes/tienda_preview.png'; ?>">

    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="<?php echo $meta_title ?? htmlspecialchars($config['tienda_nombre'] ?? 'Mi Tienda Web'); ?>">
    <meta property="twitter:description" content="<?php echo $meta_description ?? htmlspecialchars($config['tienda_descripcion_corta'] ?? ''); ?>">
    <meta property="twitter:url" content="<?php echo ABSOLUTE_URL . ltrim($_SERVER['REQUEST_URI'], '/'); ?>">
    <meta property="twitter:image" content="<?php echo $meta_image ?? ABSOLUTE_URL . 'imgredes/tienda_preview.png'; ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header-style.css">
    <link rel="stylesheet" href="css/chat-style.css">
    <link rel="stylesheet" href="css/product-card-style.css">
    <link rel="stylesheet" href="css/gallery-detail.css">
    <link rel="stylesheet" href="css/product-detail-style.css">


    <script>const BASE_URL = '<?php echo BASE_URL; ?>';</script>

</head>
<body>
<?php /* ?> <img src="<?php echo htmlspecialchars($_SESSION['usuario_avatar'] ?? BASE_URL . '../avatar/avatar-default.png'); ?>" alt="Avatar" class="nav-avatar"> <?php */ ?>

<header class="sticky-top">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
            <?php
            $logo_header_path = !empty($config['tienda_logo']) ? 'uploads/' . $config['tienda_logo'] : null;
            if ($logo_header_path && file_exists($_SERVER['DOCUMENT_ROOT'] . BASE_URL . $logo_header_path)):
            ?>
                <img src="<?php echo BASE_URL . $logo_header_path; ?>" alt="<?php echo htmlspecialchars($config['tienda_nombre'] ?? 'Mi Tienda'); ?> Logo" style="height: 30px; margin-right: 10px;">
            <?php endif; ?>
            <?php echo htmlspecialchars($config['tienda_nombre'] ?? 'Mi Tienda'); ?>
        </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="main-nav">
                <form class="d-flex mx-auto" action="buscar" method="GET" id="search-form">
                    <input class="form-control me-2" id="search-input" type="search" name="q" placeholder="Buscar productos..." required autocomplete='off'>
                    <button class="btn btn-outline-success" type="submit">Buscar</button>
                </form>
                <div id="search-results-container"></div>

                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item me-2">
                        <a class="nav-link" href="productos">Productos</a>
                    </li>

                    <li class="nav-item dropdown me-2">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownCategorias" role="button" data-bs-toggle="dropdown">
                            Categorías
                        </a>
                        <div class="dropdown-menu dropdown-menu-categorias p-3">
                            <div class="row">
                                <?php
                                // Obtenemos solo categorías activas y con productos
                                $stmt_cats = $pdo->query("
                                    SELECT c.id, c.nombre, c.slug FROM categorias c
                                    JOIN producto_categorias pc ON c.id = pc.categoria_id
                                    GROUP BY c.id HAVING COUNT(pc.producto_id) > 0
                                    ORDER BY c.nombre ASC
                                ");
                                $categorias_menu = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Dividimos las categorías en columnas
                                $categorias_por_columna = ceil(count($categorias_menu) / 3); // 3 columnas
                                $columnas = array_chunk($categorias_menu, $categorias_por_columna);

                                foreach ($columnas as $columna):
                                ?>
                                    <div class="col-md-4">
                                        <?php foreach ($columna as $categoria): ?>
                                            <a class="dropdown-item" href="categoria/<?php echo htmlspecialchars($categoria['slug']); ?>">
                                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="contacto">Contacto</a>
                    </li>
                    
                    <?php
                    $total_items_carrito = isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0;
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" id="cartDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-cart-fill fs-4"></i>
                            <span class="badge rounded-pill bg-danger" id="cart-item-count">
                                <?php echo $total_items_carrito ?? 0; ?>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end p-3" id="mini-cart-dropdown" style="width: 350px;">
                            <p class="text-center">Tu carrito está vacío.</p>
                        </div>
                    </li>

                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="<?php echo htmlspecialchars($_SESSION['usuario_avatar'] ?? ''); ?>" alt="Avatar" class="nav-avatar me-2">
                                <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="editar-perfil">Mi Perfil</a></li> 
                                <li><a class="dropdown-item" href="perfil">Mis Pedidos</a></li>
                                <li><a class="dropdown-item" href="wishlist">Mi Lista de Deseados</a></li>
                                <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="panel">Panel Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout">Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login">Login/Registro</a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <form action="cambiar_moneda" method="POST" class="d-flex" style="margin-bottom: 0 !important;">
                            <select name="moneda_id" onchange="this.form.submit()" class="form-select form-select-sm">
                                <?php
                                $stmt_monedas = $pdo->query("SELECT * FROM monedas WHERE es_activa = 1");
                                foreach ($stmt_monedas->fetchAll(PDO::FETCH_ASSOC) as $moneda) {
                                    $selected = (isset($_SESSION['moneda']) && $_SESSION['moneda']['id'] == $moneda['id']) ? 'selected' : '';
                                    echo "<option value='{$moneda['id']}' {$selected}>{$moneda['codigo']}</option>";
                                }
                                ?>
                            </select>
                        </form>
                    </li>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell-fill fs-4"></i>
                            <span class="badge rounded-pill bg-danger" id="notification-count" style="position: absolute; top: 10px; right: -5px; display: none;"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="notification-list" style="width: 350px;">
                            <div class="text-center text-muted p-3">Cargando...</div>
                        </div>
                    </li>
                    <?php endif; ?>

                </ul>
            </div>
        </div>
    </nav>
</header>

<main>
        <?php
            if (isset($_SESSION['mensaje_carrito'])) {
                // Determinamos si es un mensaje de éxito o de error
                $message_class = strpos(strtolower($_SESSION['mensaje_carrito']), 'error') === false ? 'alert-success' : 'alert-danger';
                
                // Usamos las clases de Alerta de Bootstrap
                echo '<div class="alert ' . $message_class . ' alert-dismissible fade show" role="alert">';
                echo htmlspecialchars($_SESSION['mensaje_carrito']);
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';

                unset($_SESSION['mensaje_carrito']);
            }
        ?>