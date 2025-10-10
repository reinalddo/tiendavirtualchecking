<?php
// admin/gestionar_monedas.php
require_once '../includes/config.php';
verificar_sesion_admin();

// --- LÓGICA DE ACCIONES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // AÑADIR NUEVA MONEDA
    if (isset($_POST['add_moneda'])) {
        $nombre = trim($_POST['nombre']);
        $codigo = strtoupper(trim($_POST['codigo']));
        $simbolo = trim($_POST['simbolo']);
        $tasa = $_POST['tasa_conversion'];
        $es_activa = isset($_POST['es_activa']) ? 1 : 0;
        
        $stmt = $pdo->prepare("INSERT INTO monedas (nombre, codigo, simbolo, tasa_conversion, es_activa) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $codigo, $simbolo, $tasa, $es_activa]);
        $_SESSION['mensaje_carrito'] = 'Moneda añadida correctamente.';
    }
    // ELIMINAR MONEDA
    if (isset($_POST['moneda_id'])) {
        $id = $_POST['moneda_id'];
        // Prevenir la eliminación de USD (moneda base)
        if ($id != 1) {
            $stmt = $pdo->prepare("DELETE FROM monedas WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['mensaje_carrito'] = 'Moneda eliminada.';
        } else {
            $_SESSION['mensaje_carrito'] = 'Error: No se puede eliminar la moneda base (USD).';
        }
    }
    header("Location: " . BASE_URL . "panel/gestionar-monedas");
    exit();
}

// Obtener todas las monedas
$monedas = $pdo->query("SELECT * FROM monedas ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>
<main>
    <div class="container-fluid py-4">
        <h1 class="h2 mb-4">Gestionar Monedas</h1>
        <div class="row">
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header"><h5 class="my-0 fw-normal">Añadir Nueva Moneda</h5></div>
                    <div class="card-body">
                        <form action="panel/gestionar-monedas" method="POST">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre:</label>
                                <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="Ej: Euro">
                            </div>
                            <div class="mb-3">
                                <label for="codigo" class="form-label">Código (3 letras):</label>
                                <input type="text" id="codigo" name="codigo" class="form-control" required maxlength="3" placeholder="Ej: EUR">
                            </div>
                            <div class="mb-3">
                                <label for="simbolo" class="form-label">Símbolo:</label>
                                <input type="text" id="simbolo" name="simbolo" class="form-control" required placeholder="Ej: €">
                            </div>
                            <div class="mb-3">
                                <label for="tasa_conversion" class="form-label">Tasa de Conversión (respecto a 1 USD):</label>
                                <input type="number" id="tasa_conversion" name="tasa_conversion" class="form-control" step="0.0001" required>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="es_activa" value="1" id="es_activa">
                                <label class="form-check-label" for="es_activa">Activar moneda</label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="add_moneda" class="btn btn-primary">Añadir Moneda</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header"><h5 class="my-0 fw-normal">Monedas Existentes</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Código</th>
                                        <th>Símbolo</th>
                                        <th>Tasa</th>
                                        <th>¿Activa?</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monedas as $moneda): ?>
                                    <tr data-moneda-id="<?php echo $moneda['id']; ?>">
                                        <td><input type="text" class="form-control form-control-sm update-moneda" name="nombre" value="<?php echo htmlspecialchars($moneda['nombre']); ?>" <?php if($moneda['id'] == 1) echo 'disabled'; ?>></td>
                                        <td><input type="text" class="form-control form-control-sm update-moneda" name="codigo" value="<?php echo htmlspecialchars($moneda['codigo']); ?>" <?php if($moneda['id'] == 1) echo 'disabled'; ?>></td>
                                        <td><input type="text" class="form-control form-control-sm update-moneda" name="simbolo" value="<?php echo htmlspecialchars($moneda['simbolo']); ?>" <?php if($moneda['id'] == 1) echo 'disabled'; ?>></td>
                                        <td><input type="number" class="form-control form-control-sm update-moneda" name="tasa_conversion" value="<?php echo htmlspecialchars($moneda['tasa_conversion']); ?>" <?php if($moneda['id'] == 1) echo 'disabled'; ?> step="0.0001"></td>
                                        <td>
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input update-moneda" type="checkbox" name="es_activa" <?php echo $moneda['es_activa'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($moneda['id'] != 1): // No se puede eliminar USD ?>
                                            <form action="panel/gestionar-monedas" method="POST" class="d-inline">
                                                <input type="hidden" name="moneda_id" value="<?php echo $moneda['id']; ?>">
                                                <button type="submit" name="delete_moneda" class="btn btn-sm btn-danger confirm-delete">Eliminar</button>
                                            </form>
                                            <?php endif; ?>
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

<script src="<?php echo BASE_URL; ?>js/currency-manager.js"></script>
<?php require_once '../includes/footer.php'; ?>