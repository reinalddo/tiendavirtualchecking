<?php
// ajax_marcar_notificacion_leida.php
require_once 'includes/config.php'; // Usamos config.php que ya inicia la sesión y conecta a la BD

header('Content-Type: application/json');

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    // Si la seguridad falla, devolvemos un error claro
    http_response_code(403); // Código de "Acceso Prohibido"
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

$notificacion_id = (int)$_POST['id'];
$usuario_id = $_SESSION['usuario_id'];

try {
    $sql = "UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$notificacion_id, $usuario_id]);

    // Verificamos si realmente se actualizó una fila
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        // Esto puede pasar si el ID no existe o no pertenece al usuario
        echo json_encode(['success' => false, 'error' => 'Notificación no encontrada o ya marcada.']);
    }

} catch (PDOException $e) {
    // En caso de un error de base de datos
    http_response_code(500); // Error del servidor
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos.']);
}
?>