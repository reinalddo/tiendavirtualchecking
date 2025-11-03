<?php
// admin/gestionar_productos.php
require_once '../includes/config.php';
verificar_sesion_admin();

// Obtener todos los productos para listarlos
$stmt = $pdo->query("SELECT p.*, 
                        GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias,
                        (SELECT COUNT(*) FROM pedido_detalles pd WHERE pd.producto_id = p.id) as total_ventas
                     FROM productos p
                     LEFT JOIN producto_categorias pc ON p.id = pc.producto_id
                     LEFT JOIN categorias c ON pc.categoria_id = c.id
                     GROUP BY p.id
                     ORDER BY p.fecha_creacion DESC");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once '../includes/header.php';
?>

<main>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h2">Gestionar Productos</h1>
            <a href="panel/producto/nuevo" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Añadir Nuevo Producto
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="my-0 fw-normal">Productos Existentes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Nombre</th>
                                <th>Precio Base (USD)</th>
                                <th>Stock</th>
                                <th>Categorías</th>
                                <th>Activo</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['sku']); ?></td>
                                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                <td>
                                    <?php if (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0): ?>
                                        <span class="text-danger fw-bold">$<?php echo htmlspecialchars(number_format($producto['precio_descuento'], 2)); ?></span>
                                        <del class="text-muted small">$<?php echo htmlspecialchars(number_format($producto['precio_usd'], 2)); ?></del>
                                    <?php else: ?>
                                        <span>$<?php echo htmlspecialchars(number_format($producto['precio_usd'], 2)); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    // Si el stock es NULL (producto digital), muestra 'N/A', si no, muestra el número
                                    echo is_null($producto['stock']) ? '<span class="text-muted">N/A (Digital)</span>' : htmlspecialchars($producto['stock']); 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($producto['categorias'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($producto['es_activo']): ?>
                                        <span class="badge bg-success">Sí</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="panel/producto/editar/<?php echo $producto['id']; ?>" class="btn btn-sm btn-secondary">Editar</a>
                                    
                                    <?php if ($producto['es_activo']): ?>
                                        <form action="panel/producto/desactivar" method="POST" class="d-inline ms-1">
                                            <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-warning">Desactivar</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="panel/producto/activar" method="POST" class="d-inline ms-1">
                                            <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Activar</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($producto['total_ventas'] == 0): ?>
                                        <form action="panel/producto/eliminar" method="POST" class="d-inline ms-1">
                                            <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger confirm-delete">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>