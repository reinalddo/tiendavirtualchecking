<?php
// admin/gestionar_cupones.php
require_once '../includes/config.php';
verificar_sesion_admin();

// Lógica para AÑADIR/ELIMINAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_cupon'])) {
        $codigo = trim($_POST['codigo']);
        $tipo_descuento = $_POST['tipo_descuento'];
        $valor = $_POST['valor'];
        $monto_minimo_compra = !empty($_POST['monto_minimo_compra']) ? $_POST['monto_minimo_compra'] : 0.00;
        $fecha_expiracion = !empty($_POST['fecha_expiracion']) ? $_POST['fecha_expiracion'] : null;
        $stmt = $pdo->prepare("INSERT INTO cupones (codigo, tipo_descuento, valor, monto_minimo_compra, fecha_expiracion) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$codigo, $tipo_descuento, $valor, $monto_minimo_compra, $fecha_expiracion]);

    }
    if (isset($_POST['cupon_id'])) {
        $cupon_id = $_POST['cupon_id'];
        $stmt = $pdo->prepare("DELETE FROM cupones WHERE id = ?");
        $stmt->execute([$cupon_id]);
    }
    header("Location: " . BASE_URL . "panel/gestionar-cupones");
    exit();
}

$cupones = $pdo->query("SELECT * FROM cupones ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Gestionar Cupones de Descuento</h1>
        <div class="row">
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header"><h5 class="my-0 fw-normal">Añadir Nuevo Cupón</h5></div>
                    <div class="card-body">
                        <form action="panel/gestionar-cupones" method="POST">
                            <div class="mb-3">
                                <label for="codigo" class="form-label">Código del Cupón:</label>
                                <input type="text" id="codigo" name="codigo" class="form-control" required placeholder="Ej: CUPON15">
                            </div>
                            <div class="mb-3">
                                <label for="tipo_descuento" class="form-label">Tipo de Descuento:</label>
                                <select id="tipo_descuento" name="tipo_descuento" class="form-select">
                                    <option value="porcentaje">Porcentaje (%)</option>
                                    <option value="fijo">Monto Fijo ($)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="valor" class="form-label">Valor:</label>
                                <input type="number" id="valor" name="valor" class="form-control" step="0.01" required placeholder="Ej: 15 o 10.50">
                            </div>

                            <div class="mb-3">
                                <label for="monto_minimo_compra" class="form-label">Monto Mínimo de Compra (opcional):</label>
                                <input type="number" id="monto_minimo_compra" name="monto_minimo_compra" class="form-control" step="0.01" placeholder="Ej: 20.00">
                            </div>
                            <div class="mb-3">
                                <label for="fecha_expiracion" class="form-label">Fecha de Expiración (opcional):</label>
                                <input type="date" id="fecha_expiracion" name="fecha_expiracion" class="form-control">
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="add_cupon" class="btn btn-primary">Añadir Cupón</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header"><h5 class="my-0 fw-normal">Cupones Existentes</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Tipo</th>
                                        <th>Valor</th>
                                        <th>Mín. Compra</th>
                                        <th>Usos (actual/máx)</th>
                                        <th>Expiración</th>
                                        <th>¿Activo?</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cupones as $cupon): ?>
                                    <tr data-cupon-id="<?php echo $cupon['id']; ?>">
                                        <td><input type="text" class="form-control form-control-sm update-cupon" name="codigo" value="<?php echo htmlspecialchars($cupon['codigo'] ?? ''); ?>"></td>
                                        <td><?php echo htmlspecialchars($cupon['tipo_descuento']); ?></td>
                                        <td><input type="number" class="form-control form-control-sm update-cupon" name="valor" value="<?php echo htmlspecialchars($cupon['valor'] ?? ''); ?>"></td>
                                        <td><input type="number" class="form-control form-control-sm update-cupon" name="monto_minimo_compra" value="<?php echo htmlspecialchars($cupon['monto_minimo_compra'] ?? ''); ?>"></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span><?php echo $cupon['usos_actuales']; ?> /</span>
                                                <input type="number" class="form-control form-control-sm update-cupon ms-2" name="usos_maximos" value="<?php echo htmlspecialchars($cupon['usos_maximos'] ?? ''); ?>" style="width: 80px;">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                            <input type="date" class="form-control form-control-sm update-cupon" name="fecha_expiracion" value="<?php echo htmlspecialchars($cupon['fecha_expiracion'] ?? ''); ?>">
                                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2 clear-date-btn" title="Limpiar fecha">X</button>
                                            </div>
                                        </td>
                                        <td><input type="checkbox" class="form-check-input update-cupon" name="es_activo" <?php echo $cupon['es_activo'] ? 'checked' : ''; ?>></td>
                                        <td class="text-end">
                                            <form action="panel/gestionar-cupones" method="POST" class="d-inline">
                                                <input type="hidden" name="cupon_id" value="<?php echo $cupon['id']; ?>">
                                                <button type="submit" name="delete_cupon" class="btn btn-sm btn-danger confirm-delete">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="<?php echo BASE_URL; ?>js/coupon-manager.js"></script>
<?php require_once '../includes/footer.php'; ?>