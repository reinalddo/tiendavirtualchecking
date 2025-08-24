<?php
// includes/sidebar_categorias.php

if (!isset($pdo)) {
    // Si la conexión no existe (por si se usa en otras páginas)
    require_once 'db_connection.php';
}

$stmt_categorias_sidebar = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC");
$categorias_sidebar = $stmt_categorias_sidebar->fetchAll(PDO::FETCH_ASSOC);

$categoria_actual_id = $_GET['categoria'] ?? null;
?>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="my-0 fw-normal">Categorías</h4>
    </div>
    <div class="list-group list-group-flush">
        <?php foreach ($categorias_sidebar as $categoria): ?>
            <?php
                $active_class = ($categoria_actual_id == $categoria['id']) ? 'active' : '';
            ?>
            <a href="productos.php?categoria=<?php echo $categoria['id']; ?>" class="list-group-item list-group-item-action <?php echo $active_class; ?>">
                <?php echo htmlspecialchars($categoria['nombre']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>