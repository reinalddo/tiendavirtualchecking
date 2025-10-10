<?php
// ver_carrito.php
require_once 'includes/header.php';
require_once 'includes/db_connection.php';

$carrito = $_SESSION['carrito'] ?? [];
$productos_en_carrito = [];
$total_carrito_usd = 0; // Subtotal en USD

if (!empty($carrito)) {
    $ids = array_keys($carrito);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $pdo->prepare("SELECT id, nombre, precio_usd, precio_descuento, (SELECT gal.url FROM producto_galeria gal WHERE gal.producto_id = p.id AND gal.tipo = 'imagen' ORDER BY gal.orden ASC, gal.id ASC LIMIT 1) as imagen_principal FROM productos p WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $productos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos_db as $producto) {
        $cantidad = $carrito[$producto['id']];
        $precio_a_usar = (!empty($producto['precio_descuento']) && $producto['precio_descuento'] > 0) ? $producto['precio_descuento'] : $producto['precio_usd'];
        $subtotal_usd = $precio_a_usar * $cantidad;
        $total_carrito_usd += $subtotal_usd;
        
        $producto['cantidad'] = $cantidad;
        $producto['subtotal_usd'] = $subtotal_usd;
        $productos_en_carrito[] = $producto;
    }
}

// Lógica de cupones
$descuento_usd = 0;
$total_final_usd = $total_carrito_usd;
$cupon_aplicado = $_SESSION['cupon'] ?? null;

if ($cupon_aplicado) {
    if ($cupon_aplicado['tipo_descuento'] == 'porcentaje') {
        $descuento_usd = ($total_carrito_usd * $cupon_aplicado['valor']) / 100;
    } else {
        $descuento_usd = $cupon_aplicado['valor'];
    }
    $total_final_usd = $total_carrito_usd - $descuento_usd;
}
?>

<main>
    <div class="container-fluid py-5">
        <h1>Tu Carrito de Compras</h1>
        <div class="row">
            <div class="col-lg-8">
                <?php if (empty($productos_en_carrito)): ?>
                    <div class="alert alert-info">Tu carrito está vacío.</div>
                    <?php unset($_SESSION['cupon']); ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" colspan="2">Producto</th>
                                    <th scope="col">Precio</th>
                                    <th scope="col">Cantidad</th>
                                    <th scope="col" class="text-end">Subtotal</th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos_en_carrito as $producto): ?>
                                <tr>
                                    <td style="width: 80px;">
                                        <img src="<?php echo BASE_URL . 'uploads/' . ($producto['imagen_principal'] ?? 'placeholder.png'); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td><?php echo format_price($producto['precio_usd'], $producto['precio_descuento']); ?></td>
                                    <td><?php echo $producto['cantidad']; ?></td>
                                    <td class="text-end"><?php echo format_price($producto['subtotal_usd']); ?></td>
                                    <td class="text-end">
                                        <form action="carrito-acciones" method="POST">
                                            <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                            <button type="submit" name="eliminar_del_carrito" class="btn btn-sm btn-outline-danger">&times;</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Resumen del Pedido</h5>
                        <div class="coupon-form">
                            <?php if (isset($_SESSION['cupon'])): ?>
                                <div class="alert alert-success">
                                    Cupón "<strong><?php echo htmlspecialchars($_SESSION['cupon']['codigo']); ?></strong>" aplicado.
                                    </div>
                            <?php else: ?>
                                <form action="carrito/aplicar-cupon" method="POST" class="d-flex mb-3">
                                    <input type="text" name="codigo_cupon" class="form-control me-2" placeholder="Código de Cupón">
                                    <button type="submit" class="btn btn-secondary">Aplicar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span><?php echo format_price($total_carrito_usd); ?></span>
                            </li>
                            <?php if ($descuento_usd > 0): ?>
                            <li class="list-group-item d-flex justify-content-between text-success">
                                <span>Descuento (<?php echo htmlspecialchars($cupon_aplicado['codigo']); ?>):</span>
                                <span>- <?php echo format_price($descuento_usd); ?></span>
                            </li>
                            <?php endif; ?>
                            <li class="list-group-item d-flex justify-content-between fw-bold fs-5">
                                <span>Total:</span>
                                <span><?php echo format_price($total_final_usd); ?></span>
                            </li>
                        </ul>
                        <div class="d-grid mt-3">
                            <a href="checkout" class="btn btn-primary">Proceder al Pago</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>