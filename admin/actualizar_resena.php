<?php
// admin/actualizar_resena.php
require_once '../includes/config.php';
verificar_sesion_admin();

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || empty($_GET['id']) || empty($_GET['accion'])) {
    header('Location: ' . BASE_URL . 'login');
    exit();
}

$resena_id = $_GET['id'];
$accion = $_GET['accion'];

// Ejecutar la acción correspondiente
switch ($accion) {
    case 'aprobar':
        $stmt = $pdo->prepare("UPDATE resenas SET es_aprobada = 1 WHERE id = ?");
        $stmt->execute([$resena_id]);
        $_SESSION['mensaje_carrito'] = 'Reseña aprobada.';
        break;
    case 'rechazar':
        $stmt = $pdo->prepare("UPDATE resenas SET es_aprobada = 0 WHERE id = ?");
        $stmt->execute([$resena_id]);
        $_SESSION['mensaje_carrito'] = 'Reseña rechazada.';
        break;
    case 'eliminar':
        $stmt = $pdo->prepare("DELETE FROM resenas WHERE id = ?");
        $stmt->execute([$resena_id]);
        $_SESSION['mensaje_carrito'] = 'Reseña eliminada.';
        break;
}

// Redirigir de vuelta a la página de gestión
header('Location: ' . BASE_URL . 'panel/gestionar_resenas');
exit();
?>