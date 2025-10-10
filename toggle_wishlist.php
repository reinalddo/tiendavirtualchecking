<?php
// toggle_wishlist.php
require_once 'includes/config.php';

// Verificaciones de seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id']) || empty($_POST['producto_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$producto_id = $_POST['producto_id'];

try {
    // Revisamos si el producto ya está en la wishlist
    $stmt_check = $pdo->prepare("SELECT id FROM wishlist WHERE usuario_id = ? AND producto_id = ?");
    $stmt_check->execute([$usuario_id, $producto_id]);
    $existe = $stmt_check->fetch();

    if ($existe) {
        // Si ya existe, lo eliminamos
        $stmt_delete = $pdo->prepare("DELETE FROM wishlist WHERE id = ?");
        $stmt_delete->execute([$existe['id']]);
        $en_wishlist = false;
    } else {
        // Si no existe, lo insertamos
        $stmt_insert = $pdo->prepare("INSERT INTO wishlist (usuario_id, producto_id) VALUES (?, ?)");
        $stmt_insert->execute([$usuario_id, $producto_id]);
        $en_wishlist = true;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'in_wishlist' => $en_wishlist]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de la base de datos.']);
}
?>