<?php
// /includes/product_card.php
?>
<div class="card h-100 shadow-sm product-card">
    <div class="card-img-top-container">
        <a href="producto/<?php echo htmlspecialchars($producto['slug']); ?>">
            <?php if (!empty($producto['imagen_principal'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($producto['imagen_principal']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
            <?php else: ?>
                <img src="images/placeholder.png" class="card-img-top" alt="Imagen no disponible">
            <?php endif; ?>
        </a>
    </div>
    <div class="card-body d-flex flex-column">
        <h5 class="card-title">
            <a href="producto/<?php echo htmlspecialchars($producto['slug']); ?>" class="text-dark text-decoration-none">
                <?php echo htmlspecialchars($producto['nombre']); ?>
            </a>
        </h5>
        <div class="card-text product-price mt-auto">
            <?php echo format_price($producto['precio_usd'], $producto['precio_descuento']); ?>
        </div>
    </div>

        <div class="card-footer bg-transparent border-top-0">
            <div class="row g-2"> 
                <div class="col"> <a href="producto/<?php echo htmlspecialchars($producto['slug']); ?>" class="btn btn-outline-secondary btn-sm w-100">Detalles</a> 
                    </div>
                <div class="col"> <form action="carrito-acciones" method="POST" class="d-grid"> 
                    <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                        <input type="hidden" name="cantidad" value="1">
                        <input type="hidden" name="return_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                        <button type="submit" name="agregar_al_carrito" class="btn btn-primary btn-sm"> 
                        <i class="bi bi-cart-plus-fill"></i> Añadir
                        </button>
                    </form>
                </div>
            </div>
        </div>
</div>