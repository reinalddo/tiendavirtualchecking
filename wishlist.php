<?php
// wishlist.php
require_once 'includes/header.php';
require_once 'includes/db_connection.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener los productos de la wishlist del usuario
$sql = "SELECT p.*,
            (SELECT gal.url FROM producto_galeria gal WHERE gal.producto_id = p.id AND gal.tipo = 'imagen' ORDER BY gal.id ASC LIMIT 1) as imagen_principal
        FROM productos p
        JOIN wishlist w ON p.id = w.producto_id
        WHERE w.usuario_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC); // Corregido de . a ::
?>
    <div class="container-fluid py-4"> 
        <div class="row">
            <div class="col-lg-12">

                <div class="page-container" style="display: block;">
                    <h1>Mi Lista de Deseados</h1>

                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-4">
                            <?php if (empty($productos)): ?>
                                <div class="col">
                                    <p>No se encontraron productos.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($productos as $producto): ?>
                                    <?php include 'includes/product_card.php'; // Incluimos la tarjeta reutilizable ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>