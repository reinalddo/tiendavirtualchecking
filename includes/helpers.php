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
?>