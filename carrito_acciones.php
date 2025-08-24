<?php
// carrito_acciones.php
//session_start();
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

// Inicializar el carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
    $_SESSION['moneda_carrito'] = null; // Guardará la moneda del carrito actual
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['agregar_al_carrito'])) {
        $producto_id = $_POST['producto_id'];
        $cantidad = (int)$_POST['cantidad'];
        $return_url = $_POST['return_url'] ?? 'index.php';

        // OBTENEMOS EL PRECIO BASE EN USD, NO NECESITAMOS LA MONEDA AQUÍ
        $stmt = $pdo->prepare("SELECT precio_usd FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            $_SESSION['mensaje_carrito'] = "Error: Producto no encontrado.";
            header('Location: ' . $return_url);
            exit();
        }

        // La lógica del carrito no necesita verificar la moneda, solo añadir.
        if (isset($_SESSION['carrito'][$producto_id])) {
            $_SESSION['carrito'][$producto_id] += $cantidad;
        } else {
            $_SESSION['carrito'][$producto_id] = $cantidad;
        }
        $_SESSION['mensaje_carrito'] = "¡Producto añadido al carrito!";

        // Forzamos el guardado de la sesión antes de redirigir
        session_write_close(); 

        header('Location: ' . $return_url);
        exit();
    }

    // Lógica para ELIMINAR y VACIAR (se mantiene igual)
    if (isset($_POST['eliminar_del_carrito'])) {
        $producto_id = $_POST['producto_id'];
        unset($_SESSION['carrito'][$producto_id]);
        if (empty($_SESSION['carrito'])) {
            $_SESSION['moneda_carrito'] = null;
        }
        header('Location: ' . BASE_URL . 'ver_carrito.php');
        exit();
    }
    if (isset($_POST['vaciar_carrito'])) {
        $_SESSION['carrito'] = [];
        $_SESSION['moneda_carrito'] = null;
        unset($_SESSION['cupon']);
        header('Location: ' . BASE_URL . 'ver_carrito.php');
        exit();
    }
}
?>