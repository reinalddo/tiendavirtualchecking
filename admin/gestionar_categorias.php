<?php
// admin/gestionar_categorias.php
require_once '../includes/config.php';
verificar_sesion_admin();

// Lógica para AÑADIR/ELIMINAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_categoria'])) {
        $nombre = trim($_POST['nombre']);
        $codigo = trim($_POST['codigo']);

        require_once '../includes/helpers.php'; // Aseguramos que la función esté disponible
        $slug = generar_slug($nombre);

        $mostrar_en_inicio = isset($_POST['mostrar_en_inicio']) ? 1 : 0;
        if (!empty($nombre) && !empty($codigo)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO categorias (nombre, slug, codigo, mostrar_en_inicio) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nombre, $slug, $codigo, $mostrar_en_inicio]);
                $_SESSION['mensaje_carrito'] = 'Categoría añadida exitosamente.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Error de entrada duplicada
                    $_SESSION['mensaje_carrito'] = 'Error: El código de categoría "' . htmlspecialchars($codigo) . '" ya existe.';
                } else {
                    $_SESSION['mensaje_carrito'] = 'Error en la base de datos.';
                }
            }
        }
    }
    if (isset($_POST['categoria_id'])) {
        $id = $_POST['categoria_id'];
        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje_carrito'] = 'Categoría eliminada.';
    }
    header("Location: " . BASE_URL . "panel/gestionar-categorias");
    exit();
}

// Consulta para obtener categorías y el número de productos en cada una
$categorias = $pdo->query("
    SELECT c.*, COUNT(pc.producto_id) as total_productos
    FROM categorias c
    LEFT JOIN producto_categorias pc ON c.id = pc.categoria_id
    GROUP BY c.id
    ORDER BY c.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>
<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Gestionar Categorías</h1>
        <div class="row">
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header"><h5 class="my-0 fw-normal">Añadir Nueva Categoría</h5></div>
                    <div class="card-body">
                        <form action="panel/gestionar-categorias" method="POST">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre de la Categoría:</label>
                                <input type="text" id="nombre" name="nombre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="codigo" class="form-label">Código (único):</label>
                                <input type="text" id="codigo" name="codigo" class="form-control" required>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="mostrar_en_inicio" name="mostrar_en_inicio" value="1">
                                <label class="form-check-label" for="mostrar_en_inicio">Mostrar en la página de inicio</label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="add_categoria" class="btn btn-primary">Añadir Categoría</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header"><h5 class="my-0 fw-normal">Categorías Existentes</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Código</th>
                                        <th>Mostrar en Inicio</th>
                                        <th>N.º Productos</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categorias as $categoria): ?>
                                    <tr data-categoria-id="<?php echo $categoria['id']; ?>">
                                        <td><input type="text" class="form-control form-control-sm update-categoria" name="nombre" value="<?php echo htmlspecialchars($categoria['nombre'] ?? ''); ?>"></td>
                                        <td><input type="text" class="form-control form-control-sm update-categoria" name="codigo" value="<?php echo htmlspecialchars($categoria['codigo'] ?? ''); ?>"></td>
                                        <td><div class="form-check form-switch d-flex justify-content-center"><input class="form-check-input update-categoria" type="checkbox" name="mostrar_en_inicio" <?php echo $categoria['mostrar_en_inicio'] ? 'checked' : ''; ?>></div></td>
                                        <td><span class="badge bg-secondary"><?php echo $categoria['total_productos']; ?></span></td>
                                        <td class="text-end">
                                            <form action="panel/gestionar-categorias" method="POST" class="d-inline">
                                                <input type="hidden" name="categoria_id" value="<?php echo $categoria['id']; ?>">
                                                <button type="submit" name="delete_categoria" class="btn btn-sm btn-danger confirm-delete" <?php if ($categoria['total_productos'] > 0) echo 'disabled'; ?>>Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="<?php echo BASE_URL; ?>js/category-manager.js"></script>
<?php require_once '../includes/footer.php'; ?>