<?php
require_once 'includes/header.php';
require_once 'includes/db_connection.php'; 
?>

    <div class="container-fluid py-7">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-9">
            <h1>Contacto</h1>
            <p>¿Tienes alguna pregunta? Envíanos un mensaje usando el formulario de abajo o contáctanos a través de nuestras redes sociales.</p>

            <div class="contact-layout">
                <div class="contact-form">
                    <h3>Formulario de Contacto</h3>
                    <form action="enviar_contacto.php" method="POST">
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
                    <h3>Nuestra Información</h3>
                    <p>
                        <strong>Email:</strong><br>
                        <a href="mailto:ventas@mitienda.com">ventas@mitienda.com</a>
                    </p>
                    <p>
                        <strong>Redes Sociales:</strong><br>
                        <a href="#">Facebook</a> | <a href="#">Instagram</a> | <a href="#">Twitter</a>
                    </p>
                <?php 
                // Obtener la configuración del mapa
                $stmt_config = $pdo->query("SELECT valor_setting FROM configuraciones WHERE nombre_setting = 'mapa_principal'");
                $mapa_principal = $stmt_config->fetchColumn();
                ?>

                <div class="page-container" style="display: block;">
                    <h1>Ubicación</h1>
                    <div class="contact-info">
                            <div class="map-container">
                                <?php if (!empty($mapa_principal) && $mapa_principal != ''): ?>
                                    <?php echo $mapa_principal; // Imprimimos el mapa desde la BD ?>
                                <?php endif; ?>
                            </div>
                        </div>
                </div>
                </div>
            </div>
            </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>