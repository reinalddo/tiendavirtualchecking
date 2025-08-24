<?php
// admin/panel_admin.php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
//session_start();

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// --- NUEVO: OBTENER ESTADÍSTICAS RÁPIDAS ---
try {
    $total_productos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    $total_pedidos = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
    $total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente'")->fetchColumn();
} catch (PDOException $e) {
    // En caso de error, asignamos 0 para no romper la página
    $total_productos = $total_pedidos = $total_usuarios = 0;
}


require_once '../includes/header.php';
?>

<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Panel de Administración</h1>
        <p>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong>.</p>
        
        <div class="row">
            <div class="col-md-4 mb-3">
                <a href="gestionar_productos.php" class="text-decoration-none">
                <div class="card text-white bg-primary shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title fs-2"><?php echo $total_productos; ?></h5>
                                <p class="card-text mb-0">Productos</p>
                            </div>
                            <i class="bi bi-box-seam fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="ver_pedidos.php" class="text-decoration-none">
                <div class="card text-white bg-success shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title fs-2"><?php echo $total_pedidos; ?></h5>
                                <p class="card-text mb-0">Pedidos</p>
                            </div>
                            <i class="bi bi-receipt fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="gestionar_clientes.php" class="text-decoration-none">
                <div class="card text-white bg-info shadow">
                     <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title fs-2"><?php echo $total_usuarios; ?></h5>
                                <p class="card-text mb-0">Clientes</p>
                            </div>
                            <i class="bi bi-people-fill fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                </a>
            </div>
        </div>

        <h2 class="h4 mt-4 mb-3">Gestionar Tienda</h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">

            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <i class="bi bi-box-seam fs-1 text-primary"></i>
                        <h5 class="card-title mt-3">Gestionar Productos</h5>
                        <p class="card-text text-muted">Añadir, editar y eliminar productos.</p>
                        <a href="gestionar_productos.php" class="btn btn-primary mt-auto">Ir</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <i class="bi bi-collection-fill fs-1 text-success"></i>
                        <h5 class="card-title mt-3">Biblioteca de Medios</h5>
                        <p class="card-text text-muted">Gestionar todas las imágenes.</p>
                        <a href="gestionar_media.php" class="btn btn-success mt-auto">Ir</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <i class="bi bi-file-earmark-arrow-up-fill fs-1 text-secondary"></i>
                        <h5 class="card-title mt-3">Importación Masiva</h5>
                        <p class="card-text text-muted">Añadir o editar productos con Excel.</p>
                        <a href="productos_masivos.php" class="btn btn-secondary mt-auto">Ir</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <i class="bi bi-tags-fill fs-1 text-info"></i>
                        <h5 class="card-title mt-3">Gestionar Categorías</h5>
                        <p class="card-text text-muted">Crear y organizar las categorías.</p>
                        <a href="gestionar_categorias.php" class="btn btn-info text-white mt-auto">Ir</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <i class="bi bi-receipt fs-1 text-success"></i>
                        <h5 class="card-title mt-3">Ver Pedidos</h5>
                        <p class="card-text text-muted">Revisar y actualizar los pedidos.</p>
                        <a href="ver_pedidos.php" class="btn btn-success mt-auto">Ir</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <i class="bi bi-images fs-1 text-warning"></i>
                        <h5 class="card-title mt-3">Galería de Inicio</h5>
                        <p class="card-text text-muted">Administrar el carrusel principal.</p>
                        <a href="gestionar_galeria_inicio.php" class="btn btn-warning text-dark mt-auto">Ir</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <i class="bi bi-patch-question-fill fs-1 text-dark"></i>
                        <h5 class="card-title mt-3">Gestionar Preguntas</h5>
                        <p class="card-text text-muted">Responder a las dudas de los clientes.</p>
                        <a href="ver_preguntas.php" class="btn btn-dark mt-auto">Ir</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <i class="bi bi-chat-square-text-fill fs-1 text-info"></i>
                        <h5 class="card-title mt-3">Gestionar Reseñas</h5>
                        <p class="card-text text-muted">Aprobar y eliminar las reseñas.</p>
                        <a href="gestionar_resenas.php" class="btn btn-info text-white mt-auto">Ir</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <i class="bi bi-ticket fs-1" style="color: purple;"></i>
                        <h5 class="card-title mt-3">Gestionar Cupones</h5>
                        <p class="card-text text-muted">Crear códigos de descuento.</p>
                        <a href="gestionar_cupones.php" class="btn mt-auto" style="background-color: purple; color: white;">Ir</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <i class="bi bi-coin fs-1" style="color: orange;"></i>
                        <h5 class="card-title mt-3">Gestionar Monedas</h5>
                        <p class="card-text text-muted">Activar monedas y tasas de cambio.</p>
                        <a href="gestionar_monedas.php" class="btn mt-auto" style="background-color: orange; color: white;">Ir</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <i class="bi bi-file-earmark-pdf-fill fs-1 text-danger"></i>
                        <h5 class="card-title mt-3">Generar Catálogo</h5>
                        <p class="card-text text-muted">Crear un PDF con tus productos.</p>
                        <a href="generar_catalogo.php" class="btn btn-danger mt-auto">Ir</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <i class="bi bi-sliders fs-1 text-secondary"></i>
                        <h5 class="card-title mt-3">Configuración</h5>
                        <p class="card-text text-muted">Ajustar opciones generales del sitio.</p>
                        <a href="configuracion_sitio.php" class="btn btn-secondary mt-auto">Ir</a>
                    </div>
                </div>
            </div>

        </div>


    </div>
</main>

<?php require_once '../includes/footer.php'; ?>