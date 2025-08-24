<?php
// /includes/product_card.php
?>
<div class="col">
    <div class="card h-100 shadow-sm product-card">
        <div class="card-img-top-container">
            <a href="producto_detalle.php?id=<?php echo $producto['id']; ?>">
                <?php if (!empty($producto['imagen_principal'])): ?>
                    <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($producto['imagen_principal']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                <?php else: ?>
                    <img src="<?php echo BASE_URL; ?>images/placeholder.png" class="card-img-top" alt="Imagen no disponible">
                <?php endif; ?>
            </a>
        </div>
        <div class="card-body d-flex flex-column">
            <h5 class="card-title">
                <a href="producto_detalle.php?id=<?php echo $producto['id']; ?>" class="text-dark text-decoration-none">
                    <?php echo htmlspecialchars($producto['nombre']); ?>
                </a>
            </h5>
            <div class="card-text product-price mt-auto">
                <?php echo format_price($producto['precio_usd'], $producto['precio_descuento']); ?>
            </div>
        </div>
        <div class="card-footer bg-transparent border-top-0">
            <a href="producto_detalle.php?id=<?php echo $producto['id']; ?>" class="btn btn-primary w-100">Ver Detalles</a>
        </div>
    </div>
</div>