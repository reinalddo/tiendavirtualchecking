<?php
// google-login.php
require_once 'includes/config.php';

// 1. Definimos la ruta COMPLETA al archivo de certificados en tu proyecto.
$caBundlePath = __DIR__ . '/vendor/google/auth/src/cacert.pem';

// 2. Creamos un cliente HTTP y le forzamos a usar nuestro archivo de certificados.
$httpClient = new \GuzzleHttp\Client([
    'verify' => $caBundlePath,
]);

// 3. Pasamos este cliente configurado a la librería de Google.
$client = new Google\Client();
$client->setHttpClient($httpClient);

$client->setClientId($config['google_client_id']);
$client->setClientSecret($config['google_client_secret']);

$redirect_uri = ABSOLUTE_URL . 'google-callback';
$client->setRedirectUri($redirect_uri);

$client->addScope('email');
$client->addScope('profile');

// Generar la URL de autenticación y redirigir
$auth_url = $client->createAuthUrl();
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit();
?>