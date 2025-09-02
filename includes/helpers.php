<?php
// includes/helpers.php

function format_price($precio_usd, $precio_descuento = null) {
    $precio_final = ($precio_descuento > 0) ? $precio_descuento : $precio_usd;

    if (isset($_SESSION['moneda'])) {
        $moneda = $_SESSION['moneda'];
        $precio_convertido = $precio_final * $moneda['tasa_conversion'];
        $precio_original_convertido = $precio_usd * $moneda['tasa_conversion'];

        $html = '<span class="precio-final">' . htmlspecialchars($moneda['simbolo'] . number_format($precio_convertido, 2)) . '</span>';
        if ($precio_descuento > 0) {
            $html .= ' <del class="precio-original">' . htmlspecialchars($moneda['simbolo'] . number_format($precio_original_convertido, 2)) . '</del>';
        }
        return $html;
    } else {
        // Fallback si no hay sesión de moneda
        $html = '<span class="precio-final">$' . number_format($precio_final, 2) . '</span>';
        if ($precio_descuento > 0) {
            $html .= ' <del class="precio-original">$' . number_format($precio_usd, 2) . '</del>';
        }
        return $html;
    }
}

function format_chat_message($text) {
    // 1. Sanitizar todo el texto para seguridad (prevenir inyección de HTML)
    $safe_text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // 2. Expresión regular para encontrar URLs
    $url_pattern = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';

    // 3. Reemplazar las URLs encontradas con etiquetas <a>
    $linkified_text = preg_replace($url_pattern, '<a href="$0" target="_blank" rel="noopener noreferrer">$0</a>', $safe_text);

    // 4. Convertir saltos de línea a <br>
    return nl2br($linkified_text);
}

function crear_notificacion($pdo, $usuario_id, $mensaje, $url = '#') {
    $sql = "INSERT INTO notificaciones (usuario_id, mensaje, url) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id, $mensaje, $url]);
}

function obtener_admins($pdo) {
    return $pdo->query("SELECT id FROM usuarios WHERE rol = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
}


?>