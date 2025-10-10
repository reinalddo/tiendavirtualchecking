<?php
require_once 'includes/header.php';
require_once 'includes/db_connection.php'; 

$stmt_config = $pdo->query("SELECT nombre_setting, valor_setting FROM configuraciones");
$config = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);

?>

    <div class="container-fluid py-7">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-9">
            <h1>Contacto</h1>
            <p>¿Tienes alguna pregunta? Envíanos un mensaje usando el formulario de abajo o contáctanos a través de nuestras redes sociales.</p>

            <div class="contact-layout">
                <div class="contact-form">
                    <h3>Formulario de Contacto</h3>
                    <form action="enviar-contacto" method="POST">
                        <div>
                            <label for="nombre" class="form-label">Tu Nombre:</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div>
                            <label for="email" class="form-label">Tu Email:</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div>
                            <label for="asunto" class="form-label">Asunto:</label>
                            <input type="text" class="form-control" id="asunto" name="asunto" required>
                        </div>
                        <div>
                            <label for="mensaje" class="form-label">Mensaje:</label>
                            <textarea id="mensaje" class="form-control" name="mensaje" rows="6" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar Mensaje</button>
                    </form>
                </div>
                <div class="contact-info">
                    <?php /* ?>
                    <h3>Nuestra Información</h3>
                    <p>
                        <strong>Email:</strong><br>
                        <a href="mailto:reinalddo@gmail.com">ventas@mitienda.com</a>
                    </p>
                    <?php */ ?>
                    <div class="contact-social-icons">
                        <?php
                        $redes_sociales  = [
                            'facebook' => ['icono' => 'bi-facebook'],
                            'instagram' => ['icono' => 'bi-instagram'],
                            'twitter' => ['icono' => 'bi-twitter-x'],
                            'tiktok' => ['icono' => 'bi-tiktok'],
                            'youtube' => ['icono' => 'bi-youtube']
                        ];
                            $redes_activas = [];
                            foreach ($redes_sociales as $key => $red) {
                                if (!empty($config[$key.'_activo']) && !empty($config[$key.'_url'])) {
                                    $redes_activas[$key] = $red;
                                    $redes_activas[$key]['url'] = $config[$key.'_url'];
                                }
                            }

                            // Tercero, solo si el array de redes activas NO está vacío, mostramos el título y los iconos
                            if (!empty($redes_activas)):
                            ?>
                                <p><strong>Redes Sociales:</strong></p>
                                <div class="contact-social-icons">
                                    <?php
                                    foreach ($redes_activas as $key => $red) {
                                        echo '<a href="' . htmlspecialchars($red['url']) . '" target="_blank" class="me-3 fs-2"><i class="bi ' . $red['icono'] . '"></i></a>';
                                    }
                                    ?>
                                </div>
                            <?php endif; // Fin del if que comprueba si hay redes activas ?>
                    </div>
                <?php 
                // Obtener la configuración del mapa
                $stmt_config = $pdo->query("SELECT valor_setting FROM configuraciones WHERE nombre_setting = 'mapa_principal'");
                $mapa_principal = $stmt_config->fetchColumn();
                ?>

                <?php
                // Primero, verificamos si el mapa está activo Y si el campo del código no está vacío
                if (!empty($config['mapa_principal_activo']) && !empty($config['mapa_principal'])):
                ?>
                    <div class="mt-4">
                        <h3>Ubicación</h3>
                        <div class="map-container">
                            <?php echo $config['mapa_principal']; // Imprimimos el mapa desde la BD ?>
                        </div>
                    </div>
                <?php endif; // Fin del if que comprueba el mapa ?>

                </div>
            </div>
            </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>