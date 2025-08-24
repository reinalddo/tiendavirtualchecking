<?php
// admin/generar_catalogo.php
require_once '../includes/header.php';
require_once '../includes/db_connection.php';
// ... (Verificación de seguridad de admin) ...

// Obtener todas las categorías para el filtro
$categorias = $pdo->query("SELECT distinct c.* 
FROM categorias c
INNER JOIN producto_categorias pc ON pc.categoria_id = c.id
ORDER BY c.nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<main>
<div class="container-fluid py-4">
    <h1 class="h2 mb-4">Generar Catálogo de Productos en PDF</h1>
    <div class="card shadow-sm">
        <div class="card-header"><h5 class="my-0 fw-normal">Configurar Catálogo</h5></div>
        <div class="card-body">
            <form action="procesar_catalogo.php" method="POST" target="_blank">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="categoria_id" class="form-label">Filtrar por Categoría:</label>
                        <select name="categoria_id" id="categoria_id" class="form-select">
                            <option value="todas">Todas las Categorías</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="stock" class="form-label">Filtrar por Stock:</label>
                        <select name="stock" id="stock" class="form-select">
                            <option value="todos">Con o Sin Stock</option>
                            <option value="con_stock">Con Stock</option>
                            <option value="sin_stock">Sin Stock</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Generar PDF</button>
            </form>
        </div>
    </div>
</div>
</main>
<?php require_once '../includes/footer.php'; ?>