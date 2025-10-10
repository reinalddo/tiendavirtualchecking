<?php
echo "Iniciando prueba de cURL a Google...<br>";

$url = 'https://oauth2.googleapis.com/token';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Intento de deshabilitar la verificación

$response = curl_exec($ch);
$error_number = curl_errno($ch);
$error_message = curl_error($ch);
curl_close($ch);

if ($error_number > 0) {
    echo "<h2>¡Prueba Fallida!</h2>";
    echo "<p>El servidor no puede conectar de forma segura con los servidores de Google.</p>";
    echo "<p><strong>Error de cURL Número:</strong> " . htmlspecialchars($error_number) . "</p>";
    echo "<p><strong>Mensaje de Error:</strong> " . htmlspecialchars($error_message) . "</p>";
    echo "<p>Por favor, envíe esta información al soporte técnico para que revisen la configuración de cURL y SSL en el servidor para PHP.</p>";
} else {
    echo "<h2>¡Prueba Exitosa!</h2>";
    echo "<p>La conexión cURL básica se realizó correctamente. El problema podría estar en la librería de Google.</p>";
    echo "<p>Respuesta (truncada):<br><pre>" . htmlspecialchars(substr($response, 0, 200)) . "</pre></p>";
}
?>