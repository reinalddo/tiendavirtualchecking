<?php
// dynamic_styles.php
header("Content-Type: text/css"); // Indica al navegador que esto es un archivo CSS
require_once 'includes/config.php'; // Carga la configuración desde config.php ($config)

// --- Get settings with defaults ---
$header_bg = htmlspecialchars($config['header_bg_color'] ?? '#343a40');
$header_font = htmlspecialchars($config['header_font_color'] ?? '#ffffff');
$header_size = htmlspecialchars($config['header_font_size'] ?? '16') . 'px';
$header_family = htmlspecialchars($config['header_font_family'] ?? 'system-ui, sans-serif');

$footer_bg = htmlspecialchars($config['footer_bg_color'] ?? '#212529');
$footer_font = htmlspecialchars($config['footer_font_color'] ?? '#ffffff');
$footer_size = htmlspecialchars($config['footer_font_size'] ?? '14') . 'px';
$footer_family = $header_family; // Footer uses header font

$dropdown_bg = htmlspecialchars($config['dropdown_bg_color'] ?? '#ffffff');
$dropdown_font = htmlspecialchars($config['dropdown_font_color'] ?? '#212529');

$search_button_bg = htmlspecialchars($config['search_button_bg_color'] ?? '#198754');
$search_button_font = htmlspecialchars($config['search_button_font_color'] ?? '#ffffff');

$cat_filter_bg = htmlspecialchars($config['cat_filter_bg_color'] ?? '#f8f9fa');
$cat_filter_font = htmlspecialchars($config['cat_filter_font_color'] ?? '#6c757d');
$cat_filter_active_bg = htmlspecialchars($config['cat_filter_active_bg_color'] ?? '#343a40');
$cat_filter_active_font = htmlspecialchars($config['cat_filter_active_font_color'] ?? '#ffffff');

$primary_button_bg = htmlspecialchars($config['primary_button_bg_color'] ?? '#0d6efd');
$primary_button_font = htmlspecialchars($config['primary_button_font_color'] ?? '#ffffff');

// Determinar color de borde (si es gradiente, usa un color sólido aproximado o transparente)
$primary_button_border = $primary_button_bg;
if (strpos($primary_button_bg, 'gradient') !== false) {
    // Opción 1: Borde transparente
     $primary_button_border = 'transparent';
    // Opción 2: Intentar extraer el primer color (más complejo, mejor transparente)
    // preg_match('/#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})/', $primary_button_bg, $matches);
    // $primary_button_border = $matches[0] ?? 'transparent';
}


// --- Determine Footer Copyright Background ---
$footer_copyright_bg = 'rgba(0, 0, 0, 0.2)'; // Default slightly darker black
// If footer_bg is a solid color (hex), calculate a darker transparent version
if (preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $footer_bg)) {
    // Basic darkening (reduce brightness slightly, add alpha) - might need a library for precise color math
     $footer_copyright_bg = 'rgba(0, 0, 0, 0.2)'; // Fallback to dark transparent if calculation is complex
     // For a simpler approach, you could just use a darker fixed color or keep the default rgba(0,0,0,0.2)
}
// --- Generar las reglas CSS ---
?>
/* Dynamic Styles - Generado por PHP */

/* ================== HEADER ================== */
header .navbar {
    background: <?php echo $header_bg; ?>; /* Aplica color o gradiente */
    font-family: <?php echo $header_family; ?>;
}

/* Color y tamaño para enlaces principales, marca y toggles */
header .navbar-brand,
header .nav-link,
header #cartDropdown, /* Icono carrito */
header #notificationsDropdown /* Icono campana */
{
    color: <?php echo $header_font; ?> !important;
    font-size: <?php echo $header_size; ?>;
}

/* Color para el nombre de usuario y flecha en el menú desplegable */
header .nav-item.dropdown > .nav-link {
     color: <?php echo $header_font; ?> !important;
}

/* Aplicamos al <header> principal usando 'body' para aumentar especificidad */
body header.sticky-top {
    background: <?php echo $header_bg; ?> !important; /* Aplica color/gradiente y usa !important */
    font-family: <?php echo $header_family; ?>;
}
/* Forzamos el navbar interior a ser transparente */
body header.sticky-top nav.navbar { /* También aumentamos especificidad aquí */
    background: transparent !important;
    font-family: <?php echo $header_family; ?>; /* Asegura que la fuente se herede */
}

/* Estilos Hover/Active para enlaces */
header .nav-link:hover,
header .nav-link.active,
header .navbar-brand:hover {
     color: <?php echo $header_font; ?> !important;
     opacity: 0.8; /* Efecto visual leve */
}

/* Ajuste específico para el toggle del menú hamburguesa */
header .navbar-toggler-icon {
    /* Intenta usar el color de fuente del header para el icono */
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='<?php echo str_replace('#', '%23', $header_font); ?>' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

/* ===== INICIO: Estilos Barra de Búsqueda (Refinados) ===== */
header #search-input {
    /*min-width: 300px; */
}

/* Regla principal para el botón */
header #search-form button.btn { /* Apunta a .btn en lugar de .btn-outline-success */
    background-color: <?php echo $search_button_bg; ?> !important;
    color: <?php echo $search_button_font; ?> !important;
    border-color: <?php echo $search_button_bg; ?> !important; /* Borde del mismo color */
    box-shadow: none !important; /* Quita sombras extra */
}
/* Regla Hover/Focus */
header #search-form button.btn:hover,
header #search-form button.btn:focus {
    background-color: <?php echo $search_button_bg; ?> !important; /* Mantiene el fondo */
    color: <?php echo $search_button_font; ?> !important; /* Mantiene el texto */
    border-color: <?php echo $search_button_bg; ?> !important; /* Mantiene el borde */
    opacity: 0.85; /* Ligera opacidad */
    box-shadow: none !important; /* Quita el brillo azul de focus */
}
/* ===== FIN: Estilos Barra de Búsqueda ===== */

/* ===== INICIO: Estilos Filtro Categorías Index ===== */
#category-filter-nav .nav-link {
    color: <?php echo $cat_filter_font; ?> !important;
    background-color: <?php echo $cat_filter_bg; ?> !important;
    border: 1px solid transparent; /* Para mantener tamaño consistente */
    margin: 0 3px; /* Pequeño espacio entre botones */
}

/* Estilo Hover para botones normales */
#category-filter-nav .nav-link:not(.active):hover {
    opacity: 0.85;
}

/* Estilo para el botón ACTIVO */
#category-filter-nav .nav-link.active {
    color: <?php echo $cat_filter_active_font; ?> !important;
    background-color: <?php echo $cat_filter_active_bg; ?> !important;
    border-color: <?php echo $cat_filter_active_bg; ?> !important; /* Borde del mismo color */
    transform: scale(1.05); /* Mantiene el efecto de escala */
}
/* ===== FIN: Estilos Filtro Categorías Index ===== */

/* ===== INICIO: Estilos Botón Primario ===== */
/* Aplicar a todos los botones .btn-primary y a los que usan la clase .button */
.btn-primary,
.button {
    background: <?php echo $primary_button_bg; ?> !important; /* Permite gradiente */
    color: <?php echo $primary_button_font; ?> !important;
    border-color: <?php echo $primary_button_border; ?> !important; /* Borde coincide o es transparente */
    box-shadow: none !important;
    /* Considera añadir padding, border-radius si quieres unificar más */
    /* padding: .5rem 1rem; */
    /* border-radius: .3rem; */
}

/* Estilo Hover/Focus */
.btn-primary:hover,
.button:hover,
.btn-primary:focus,
.button:focus {
    background: <?php echo $primary_button_bg; ?> !important; /* Mantiene fondo */
    color: <?php echo $primary_button_font; ?> !important; /* Mantiene texto */
    border-color: <?php echo $primary_button_border; ?> !important; /* Mantiene borde */
    opacity: 0.85; /* Ligera opacidad */
    box-shadow: none !important;
}
/* ===== FIN: Estilos Botón Primario ===== */

/* ================== MENÚ DESPLEGABLE ================== */
header .dropdown-menu {
    background-color: <?php echo $dropdown_bg; ?>;
    font-family: <?php echo $header_family; ?>; /* Hereda fuente del header */
    border: 1px solid rgba(0,0,0,.1); /* Borde sutil */
}

/* Color y tamaño para items del desplegable */
header .dropdown-item {
    color: <?php echo $dropdown_font; ?> !important;
    font-size: calc(<?php echo $header_size; ?> * 0.95); /* Ligeramente más pequeño */
    padding: .5rem 1rem;
}

/* Color de fondo y fuente al pasar el ratón */
header .dropdown-item:hover,
header .dropdown-item:focus {
    color: <?php echo $dropdown_font; ?> !important;
    background-color: rgba(0, 0, 0, 0.05); /* Sombra ligera */
}

/* Separador en el desplegable */
header .dropdown-divider {
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

/* ================== FOOTER ================== */
footer {
    background: <?php echo $footer_bg; ?>;
    color: <?php echo $footer_font; ?>;
    font-size: <?php echo $footer_size; ?>;
    font-family: <?php echo $footer_family; ?>;
}

/* Color for links AND general text inside footer */
footer,
footer p,
footer a,
footer .footer-dw,
footer .bi /* Icons */
{
    color: <?php echo $footer_font; ?> !important;
}
footer a {
     text-decoration: none;
}
footer a:hover {
     opacity: 0.8;
     text-decoration: underline;
}

/* Style for the copyright sub-section */
footer .text-center.p-3 {
    background-color: <?php echo $footer_copyright_bg; ?>; /* Apply calculated/default darker bg */
    opacity: 0.9; /* Slightly less prominent */
}
footer .text-center p { /* Ensure copyright text also uses main footer color */
     color: <?php echo $footer_font; ?> !important;
     opacity: 0.8;
}

/* ================== WHATSAPP BUTTON (Exclude - REGLA MÁS ESPECÍFICA) ================== */
/* Al añadir 'footer a' aumentamos la especificidad para anular 'footer a' general */
footer a.whatsapp-float {
    background-color: #25d366 !important; /* Color verde original */
    color: #FFF !important;             /* Color blanco original del icono */
    /* Otros estilos originales si los hubiera (ej. box-shadow) se mantienen */
}
footer a.whatsapp-float:hover {
     color: #FFF !important; /* Asegura que el color no cambie en hover */
     opacity: 1; /* Podrías querer quitar la opacidad del hover general */
     transform: scale(1.1); /* Mantiene la animación original */
}
/* Aseguramos que el ícono dentro también sea blanco */
footer a.whatsapp-float i.bi-whatsapp {
    color: #FFF !important;
}