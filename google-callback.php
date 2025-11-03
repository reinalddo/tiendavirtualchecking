<?php
// google-callback.php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';

// 1. Definimos la ruta COMPLETA al archivo de certificados en tu proyecto.
$caBundlePath = __DIR__ . '/vendor/google/auth/src/cacert.pem';

// 2. Creamos un cliente HTTP y le forzamos a usar nuestro archivo de certificados.
$httpClient = new \GuzzleHttp\Client([
    'verify' => $caBundlePath,
]);

// 3. Pasamos este cliente configurado a la librería de Google.
$client = new Google\Client();
$client->setHttpClient($httpClient);

// Crear cliente de Google
$client = new Google\Client();
$client->setClientId($config['google_client_id']);
$client->setClientSecret($config['google_client_secret']);
$redirect_uri = ABSOLUTE_URL . 'google-callback';
$client->setRedirectUri($redirect_uri);

$client->addScope('email');
$client->addScope('profile');

try {
    // Si Google nos devuelve un código, lo intercambiamos por un token de acceso
    if (isset($_GET['code'])) {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token['access_token']);

        // Obtenemos la información del perfil del usuario de Google
        $google_oauth = new Google\Service\Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email =  $google_account_info->email;
        $nombre =  $google_account_info->name;
        $avatar =  $google_account_info->picture; // Obtenemos la URL del avatar
        
        // Verificamos si el usuario ya existe en nuestra base de datos
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Si el usuario existe, actualizamos su nombre y avatar en nuestra base de datos
            $usuario_id = $usuario['id'];
            $stmt_update = $pdo->prepare("UPDATE usuarios SET nombre_pila = ?, avatar_url = ? WHERE id = ?");
            $stmt_update->execute([$nombre, $avatar, $usuario_id]);
        } else {
            // Si el usuario no existe, lo registramos y guardamos su avatar
            $stmt_insert = $pdo->prepare("INSERT INTO usuarios (nombre_pila, email, password, rol, avatar_url) VALUES (?, ?, '', 'cliente', ?)");
            $stmt_insert->execute([$nombre, $email, $avatar]);
            $usuario_id = $pdo->lastInsertId();
        }

        // Iniciamos la sesión para nuestro sitio
        session_start();
        $_SESSION['usuario_id'] = $usuario_id;
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['usuario_rol'] = 'cliente';
        $_SESSION['usuario_avatar'] = $avatar; // Guardamos en la sesión también

        // Redirigir a la página que cierra el popup
        header('Location: ' . BASE_URL . 'cerrar_popup.html');
        exit();
    }

} catch (Exception $e) {
    // Si algo sale mal, redirigimos al login con un error
    session_start();
    $_SESSION['mensaje_carrito'] = 'Error: No se pudo iniciar sesión con Google. ' . $e->getMessage();
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Si no hay código, redirigir al inicio
header('Location: ' . BASE_URL . 'index.php');
exit();
?>