<?php
// admin/generar_catalogo.php
require_once '../includes/config.php';
verificar_sesion_admin();

// Obtener todas las categorías para el filtro
$categorias = $pdo->query("SELECT distinct c.* 
FROM categorias c
INNER JOIN producto_categorias pc ON pc.categoria_id = c.id
ORDER BY c.nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
require_once '../includes/header.php';
?>
<main>
<div class="container-fluid py-4">
    <h1 class="h2 mb-4">Generar Catálogo de Productos en PDF</h1>
    <div class="card shadow-sm">
        <div class="card-header"><h5 class="my-0 fw-normal">Configurar Catálogo</h5></div>
        <div class="card-body">
            <form action="panel/procesar-catalogo" method="POST" target="_blank" id="catalogo-form">
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
                <button type="submit" class="btn btn-secondary">Generar Lista (Simple)</button>
                <button type="button" id="generar-visual-btn" class="btn btn-primary">Generar Catálogo (Visual)</button>
            </form>
        </div>
    </div>
</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('catalogo-form');
    const visualBtn = document.getElementById('generar-visual-btn');

    visualBtn.addEventListener('click', function() {
        // Cambiamos el 'action' del formulario para que apunte al nuevo script
        form.action = 'panel/procesar-catalogo-visual';
        // Enviamos el formulario
        form.submit();
        // Restauramos el 'action' original por si acaso
        setTimeout(() => {
            form.action = 'panel/procesar-catalogo';
        }, 100);
    });
});
</script>
<?php require_once '../includes/footer.php'; ?>