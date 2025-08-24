<?php
// admin/configuracion_sitio.php
require_once '../includes/header.php';
require_once '../includes/db_connection.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

// Lógica para guardar los cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mapa_principal = $_POST['mapa_principal'];
    $mostrar_mapa = isset($_POST['mostrar_mapa_en_productos']) ? '1' : '0';

    $stmt = $pdo->prepare("UPDATE configuraciones SET valor_setting = ? WHERE nombre_setting = 'mapa_principal'");
    $stmt->execute([$mapa_principal]);

    $stmt_mostrar = $pdo->prepare("UPDATE configuraciones SET valor_setting = ? WHERE nombre_setting = 'mostrar_mapa_en_productos'");
    $stmt_mostrar->execute([$mostrar_mapa]);

    $metodos_pago = $_POST['metodos_pago_activos'];
    $stmt_pagos = $pdo->prepare("UPDATE configuraciones SET valor_setting = ? WHERE nombre_setting = 'metodos_pago_activos'");
    $stmt_pagos->execute([$metodos_pago]);

    $_SESSION['mensaje_carrito'] = '¡Configuración guardada!';
}

// Obtener la configuración actual
$stmt_config = $pdo->query("SELECT * FROM configuraciones");
$configuraciones_raw = $stmt_config->fetchAll(PDO::FETCH_ASSOC);
$config = [];
foreach ($configuraciones_raw as $setting) {
    $config[$setting['nombre_setting']] = $setting['valor_setting'];
}
?>

<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Configuración del Sitio</h1>
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="my-0 fw-normal">Ajustes Generales</h5>
                    </div>
                    <div class="card-body">
                        <form action="configuracion_sitio.php" method="POST">
                            <div class="mb-3">
                                <label for="mapa_principal" class="form-label">Código del Mapa Principal (Google Maps Embed):</label>
                                <textarea id="mapa_principal" name="mapa_principal" class="form-control" rows="6"><?php echo htmlspecialchars($config['mapa_principal'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="mostrar_mapa_en_productos" value="1" id="mostrar_mapa" <?php echo !empty($config['mostrar_mapa_en_productos']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="mostrar_mapa">
                                    Mostrar mapa en todas las páginas de productos
                                </label>
                            </div>

                            <div class="mb-3">
                                <label for="metodos_pago_activos" class="form-label">Métodos de Pago Activos:</label>
                                <select name="metodos_pago_activos" id="metodos_pago_activos" class="form-select">
                                    <option value="ambos" <?php if (($config['metodos_pago_activos'] ?? '') == 'ambos') echo 'selected'; ?>>Ambos (Tarjeta y Manual)</option>
                                    <option value="stripe" <?php if (($config['metodos_pago_activos'] ?? '') == 'stripe') echo 'selected'; ?>>Solo Ventas con Tarjeta</option>
                                    <option value="manual" <?php if (($config['metodos_pago_activos'] ?? '') == 'manual') echo 'selected'; ?>>Solo Ventas Manuales</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>