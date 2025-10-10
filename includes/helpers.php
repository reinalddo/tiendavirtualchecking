<?php
// includes/helpers.php

// Importa las clases de PHPMailer al inicio del archivo
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

function get_moneda_simbolo($pdo, $codigo_moneda) {
    // Esta pequeña función nos ayuda a obtener el símbolo (€, $, Bs.) a partir del código (EUR, USD, VES)
    // para no tener que consultarlo cada vez.
    static $simbolos = [];
    if (isset($simbolos[$codigo_moneda])) {
        return $simbolos[$codigo_moneda];
    }
    
    $stmt = $pdo->prepare("SELECT simbolo FROM monedas WHERE codigo = ?");
    $stmt->execute([$codigo_moneda]);
    $simbolo = $stmt->fetchColumn();
    $simbolos[$codigo_moneda] = $simbolo;
    
    return $simbolo;
}

function format_historical_price($precio_usd, $pedido_info, $pdo) {
    // Extraemos la información del pedido
    $moneda_pedido = $pedido_info['moneda_pedido'];
    $tasa_conversion = $pedido_info['tasa_conversion_pedido'];
    
    // Obtenemos el símbolo correspondiente
    $simbolo = get_moneda_simbolo($pdo, $moneda_pedido);

    // Calculamos el precio con la tasa histórica
    $precio_convertido = $precio_usd * $tasa_conversion;

    // Devolvemos el precio formateado
    return htmlspecialchars($simbolo . number_format($precio_convertido, 2));
}

/**
 * Envía un correo electrónico usando PHPMailer y la configuración de la BD.
 *
 * @param PDO $pdo La conexión a la base de datos.
 * @param string $destinatario_email La dirección de correo del destinatario.
 * @param string $destinatario_nombre El nombre del destinatario.
 * @param string $asunto El asunto del correo.
 * @param string $cuerpo_html El contenido del correo en formato HTML.
 * @param array $config_email Un array opcional con la configuración. Si no se pasa, se obtiene de la BD.
 * @return bool Devuelve true si el correo se envió con éxito, false en caso contrario.
 */
function enviar_email($pdo, $destinatario_email, $destinatario_nombre, $asunto, $cuerpo_html, $config_email = null) {
    
    // Si no se proporciona la configuración, la obtenemos de la base de datos
    if ($config_email === null) {
        $stmt_config = $pdo->query("SELECT nombre_setting, valor_setting FROM configuraciones");
        $config_list = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
        $config_email = [
            'smtp_host' => $config_list['smtp_host'] ?? '',
            'smtp_email' => $config_list['smtp_email'] ?? '',
            'smtp_password' => $config_list['smtp_password'] ?? '',
            'smtp_puerto' => $config_list['smtp_puerto'] ?? 465,
            'smtp_seguridad' => $config_list['smtp_seguridad'] ?? 'ssl',
        ];
    }
    
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host       = $config_email['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config_email['smtp_email'];
        $mail->Password   = $config_email['smtp_password'];
        $mail->SMTPSecure = ($config_email['smtp_seguridad'] == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$config_email['smtp_puerto'];
        $mail->CharSet    = 'UTF-8';

        // Correos
        $mail->setFrom($config_email['smtp_email'], 'Mi Tienda Web');
        $mail->addAddress($destinatario_email, $destinatario_nombre);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo_html;
        $mail->AltBody = strip_tags($cuerpo_html);

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Error de PHPMailer: {$mail->ErrorInfo}");
        return false;
    }
}


/**
 * Genera una plantilla de correo HTML profesional y dinámica.
 *
 * @param string $titulo El título principal que aparecerá en el correo.
 * @param string $mensaje El cuerpo principal del mensaje.
 * @param string $texto_boton El texto para el botón de llamada a la acción.
 * @param string $url_boton El enlace para el botón.
 * @param array $config El array con la configuración del sitio.
 * @return string El HTML completo del correo.
 */
function generar_plantilla_email($titulo, $mensaje, $texto_boton, $url_boton, $config) {
    // --- CAMBIO 1: Usar ABSOLUTE_URL para el logo ---
    $logo_path = !empty($config['tienda_logo']) ? ABSOLUTE_URL . 'uploads/' . $config['tienda_logo'] : ABSOLUTE_URL . 'imgredes/tienda_preview.png';

    $html = "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>" . htmlspecialchars($titulo) . "</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            .header { background-color: #343a40; padding: 20px; text-align: center; }
            .header img { max-height: 60px; width: auto; }
            .content { padding: 30px; line-height: 1.6; color: #333; }
            .content h1 { color: #343a40; }
            .button-container { text-align: center; margin: 20px 0; }
            .button { background-color: #0d6efd; color: #ffffff !important; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; }
            .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #dee2e6; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <img src='" . $logo_path . "' alt='Logo de " . htmlspecialchars($config['tienda_razon_social'] ?? 'Mi Tienda') . "'>
            </div>
            <div class='content'>
                <h1>" . htmlspecialchars($titulo) . "</h1>
                " . $mensaje . "
                <div class='button-container'>
                    <a href='" . htmlspecialchars($url_boton) . "' class='button'>" . htmlspecialchars($texto_boton) . "</a>
                </div>
                <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
                <p>Saludos,<br>El equipo de " . htmlspecialchars($config['tienda_razon_social'] ?? 'Mi Tienda Web') . "</p>
            </div>
            <div class='footer'>
                <p><strong>" . htmlspecialchars($config['tienda_razon_social'] ?? '') . "</strong></p>
                <p>RIF: " . htmlspecialchars($config['tienda_rif'] ?? '') . "</p>
                <p>&copy; " . date('Y') . ". Todos los derechos reservados.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    return $html;
}

/**
 * Convierte un string en un formato de URL amigable (slug).
 * @param string $texto El texto a convertir.
 * @return string El slug generado.
 */
function generar_slug($texto) {
    // Reemplaza caracteres no alfanuméricos con un guion
    $texto = preg_replace('~[^\pL\d]+~u', '-', $texto);
    // Translitera caracteres con acentos
    $texto = iconv('utf-8', 'us-ascii//TRANSLIT', $texto);
    // Elimina caracteres no deseados
    $texto = preg_replace('~[^-\w]+~', '', $texto);
    // Pasa a minúsculas
    $texto = strtolower(trim($texto, '-'));
    // Elimina guiones duplicados
    $texto = preg_replace('~-+~', '-', $texto);

    if (empty($texto)) {
        return 'n-a';
    }
    return $texto;
}


/**
 * Verifica si hay una sesión de administrador activa.
 * Si no la hay, redirige a la página de login y detiene el script.
 */
function verificar_sesion_admin() {
    // Aseguramos que la sesión esté iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
        // Redirigimos a la URL amigable del login
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
}

?>