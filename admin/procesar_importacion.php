<?php
// admin/procesar_importacion.php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connection.php';

// Verificación de seguridad de admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

if (isset($_FILES['archivo_excel']) && $_FILES['archivo_excel']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['archivo_excel']['tmp_name'];
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo);
        $worksheet = $spreadsheet->getActiveSheet();
        $filas = $worksheet->toArray();
        array_shift($filas); // Quitamos la fila del encabezado

        $errores = [];
        $datos_validos = [];
        $monedas_disponibles = $pdo->query("SELECT codigo FROM monedas")->fetchAll(PDO::FETCH_COLUMN);

        // --- FASE 1: VALIDACIÓN ---
        foreach ($filas as $numero_fila => $fila) {
            $fila_actual = $numero_fila + 2;
            $sku = trim($fila[0] ?? '');
            $nombre = trim($fila[1] ?? '');
            $precio = $fila[2] ?? '';
            $codigo_moneda = trim($fila[3] ?? '');
            $stock = $fila[4] ?? '';
            $clave_categoria = trim($fila[5] ?? '');
            $nombre_categoria = trim($fila[6] ?? '');
            $publicar = strtoupper(trim($fila[7] ?? ''));

            if (empty($sku)) { $errores[] = "Fila {$fila_actual}: El SKU es obligatorio."; }
            if (!in_array($codigo_moneda, $monedas_disponibles)) { $errores[] = "Fila {$fila_actual}: El código de moneda '{$codigo_moneda}' no es válido."; }
            if (empty($nombre_categoria)) { $errores[] = "Fila {$fila_actual}: El Nombre de la Categoría es obligatorio."; }
            if (!in_array($publicar, ['S', 'N'])) { $errores[] = "Fila {$fila_actual}: El campo Publicar debe ser 'S' o 'N'."; }
            
            if (empty($errores)) {
                $datos_validos[] = [
                    'sku' => $sku,
                    'nombre' => empty($nombre) ? $sku : $nombre,
                    'precio_usd' => is_numeric($precio) ? (float)$precio : 0.0,
                    'stock' => is_numeric($stock) ? (int)$stock : 0,
                    'clave_categoria' => $clave_categoria,
                    'nombre_categoria' => $nombre_categoria,
                    'es_activo' => ($publicar == 'S') ? 1 : 0
                ];
            }
        }

        // --- FASE 2: DECISIÓN ---
        if (!empty($errores)) {
            $_SESSION['import_errors'] = $errores;
        } else {
            // --- FASE 3: IMPORTACIÓN ---
            $pdo->beginTransaction();
            $ids_productos_procesados = [];
            
            foreach ($datos_validos as $data) {
                // Lógica de producto (INSERT o UPDATE)
                $stmt_check = $pdo->prepare("SELECT id FROM productos WHERE sku = ?");
                $stmt_check->execute([$data['sku']]);
                $producto_existente = $stmt_check->fetch();

                if ($producto_existente) {
                    $producto_id = $producto_existente['id'];
                    $sql = "UPDATE productos SET nombre = ?, precio_usd = ?, stock = ?, es_activo = ? WHERE id = ?";
                    $pdo->prepare($sql)->execute([$data['nombre'], $data['precio_usd'], $data['stock'], $data['es_activo'], $producto_id]);
                } else {
                    $sql = "INSERT INTO productos (nombre, sku, precio_usd, stock, es_activo) VALUES (?, ?, ?, ?, ?)";
                    $pdo->prepare($sql)->execute([$data['nombre'], $data['sku'], $data['precio_usd'], $data['stock'], $data['es_activo']]);
                    $producto_id = $pdo->lastInsertId();
                }

                $ids_productos_procesados[] = $producto_id;
                
                // --- LÓGICA DE CATEGORÍAS ---
                $categoria_id = null;
                // 1. Buscar o crear la categoría
                if (!empty($data['clave_categoria'])) {
                    $stmt_cat = $pdo->prepare("SELECT id, nombre FROM categorias WHERE codigo = ?");
                    $stmt_cat->execute([$data['clave_categoria']]);
                    $cat_existente = $stmt_cat->fetch();

                    if ($cat_existente) {
                        $categoria_id = $cat_existente['id'];
                        // Si el nombre es diferente, lo actualizamos
                        if ($cat_existente['nombre'] !== $data['nombre_categoria']) {
                            $pdo->prepare("UPDATE categorias SET nombre = ? WHERE id = ?")->execute([$data['nombre_categoria'], $categoria_id]);
                        }
                    } else {
                        $pdo->prepare("INSERT INTO categorias (nombre, codigo) VALUES (?, ?)")->execute([$data['nombre_categoria'], $data['clave_categoria']]);
                        $categoria_id = $pdo->lastInsertId();
                    }
                }

                // 2. Asociar la categoría al producto
                if ($producto_id && $categoria_id) {
                    // Al editar, primero borramos las asociaciones antiguas
                    if ($producto_existente) {
                        $pdo->prepare("DELETE FROM producto_categorias WHERE producto_id = ?")->execute([$producto_id]);
                    }
                    // Insertamos la nueva asociación
                    $pdo->prepare("INSERT INTO producto_categorias (producto_id, categoria_id) VALUES (?, ?)")->execute([$producto_id, $categoria_id]);
                }
            }
            
            $pdo->commit();
            $_SESSION['productos_importados_ids'] = $ids_productos_procesados;
            $_SESSION['mensaje_carrito'] = '¡Importación completada exitosamente!';
        }
        
        header('Location: ' . BASE_URL . 'admin/productos_masivos.php');
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $_SESSION['import_errors'] = ['Error al procesar el archivo: ' . $e->getMessage()];
        header('Location: ' . BASE_URL . 'admin/productos_masivos.php');
        exit();
    }
}
?>