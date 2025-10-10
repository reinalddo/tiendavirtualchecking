<?php
// admin/ver_conversacion.php (Versión Final Corregida)
require_once '../includes/config.php';
verificar_sesion_admin();


$conversacion_id = (int)$_GET['id'];
$admin_id = $_SESSION['usuario_id'];

// Marcar mensajes del cliente como leídos
$stmt_leido = $pdo->prepare("UPDATE mensajes SET leido = 1 WHERE conversacion_id = ? AND remitente_id != ?");
$stmt_leido->execute([$conversacion_id, $admin_id]);

// Obtener datos de la conversación
$stmt_conv = $pdo->prepare("SELECT c.*, u.nombre_pila as nombre_cliente FROM conversaciones c JOIN usuarios u ON c.cliente_id = u.id WHERE c.id = ?");
$stmt_conv->execute([$conversacion_id]);
$conversacion = $stmt_conv->fetch(PDO::FETCH_ASSOC);

if (!$conversacion) { exit('Conversación no encontrada.'); }

// Obtener los mensajes (CORREGIDO A ORDEN ASCENDENTE)
$stmt_msgs = $pdo->prepare("SELECT * FROM mensajes WHERE conversacion_id = ? ORDER BY fecha_envio DESC");
$stmt_msgs->execute([$conversacion_id]);
$mensajes = $stmt_msgs->fetchAll(PDO::FETCH_ASSOC);
require_once '../includes/header.php';
?>

<main>
    <div class="container py-4 chat-container">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h4 mb-0">Chat sobre Pedido #<?php echo htmlspecialchars($conversacion['pedido_id']); ?></h1>
                    <small>Cliente: <?php echo htmlspecialchars($conversacion['nombre_cliente']); ?></small>
                </div>
                <form action="toggle_cliente_respuesta.php" method="POST">
                    <input type="hidden" name="conversacion_id" value="<?php echo $conversacion_id; ?>">
                    <?php if ($conversacion['cliente_puede_responder']): ?>
                        <button type="submit" name="accion" value="bloquear" class="btn btn-sm btn-warning">Bloquear Cliente</button>
                    <?php else: ?>
                        <button type="submit" name="accion" value="desbloquear" class="btn btn-sm btn-success">Desbloquear Cliente</button>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body">
                <div class="chat-box" id="chat-box">
                    <?php foreach ($mensajes as $msg):
                        $msg_class = ($msg['remitente_id'] == $conversacion['cliente_id']) ? 'received' : 'sent';
                    ?>
                        <div class="message <?php echo $msg_class; ?>" data-message-id="<?php echo $msg['id']; ?>">
                            <div class="bubble">
                                <?php if (!empty($msg['mensaje'])): ?>
                                    <?php echo format_chat_message($msg['mensaje']); ?>
                                <?php endif; ?>
                                <?php if (!empty($msg['archivo_adjunto'])):
                                    $ruta_adjunto = BASE_URL . 'uploads/adjuntos/' . $msg['archivo_adjunto'];
                                    $extension = strtolower(pathinfo($msg['archivo_adjunto'], PATHINFO_EXTENSION));
                                    $es_imagen = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
                                ?>
                                    <div class="attachment mt-2">
                                        <?php if ($es_imagen): ?>
                                            <a href="<?php echo $ruta_adjunto; ?>" target="_blank"><img src="<?php echo $ruta_adjunto; ?>" class="img-thumbnail" style="max-height: 150px;"></a>
                                        <?php else: ?>
                                            <a href="<?php echo $ruta_adjunto; ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-arrow-down"></i> <?php echo htmlspecialchars($msg['nombre_original_adjunto']); ?></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="timestamp"><?php echo date("d/m/Y H:i", strtotime($msg['fecha_envio'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer">
                <form action="panel/enviar-mensaje" method="POST" id="chat-form-admin" enctype="multipart/form-data">
                    <input type="hidden" name="conversacion_id" value="<?php echo $conversacion_id; ?>">
                    <textarea name="mensaje" id="mensaje-textarea-admin" class="form-control mb-2" placeholder="Escribe tu respuesta..." rows="3"></textarea>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <label for="archivo-adjunto-admin" class="btn btn-secondary btn-sm"><i class="bi bi-paperclip"></i> Adjuntar Archivo</label>
                            <input type="file" name="archivo_adjunto" id="archivo-adjunto-admin" class="d-none" accept=".jpg, .jpeg, .png, .gif, .pdf, .doc, .docx, .txt">
                            <span id="file-name-display-admin" class="ms-2 text-muted"></span>
                        </div>
                        <button class="btn btn-primary" id="enviar-btn-admin" type="submit">Enviar</button>
                    </div>
                    <small class="form-text text-muted">Presiona Ctrl+Enter para enviar. Máx 5MB.</small>
                </form>
            </div>
        </div>
        <a href="panel/gestionar_mensajes" class="btn btn-secondary mt-3">← Volver a Conversaciones</a>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- LÓGICA DE ATAJOS Y NOMBRE DE ARCHIVO ---
    const textarea = document.getElementById('mensaje-textarea') || document.getElementById('mensaje-textarea-admin');
    const form = document.getElementById('chat-form') || document.getElementById('chat-form-admin');
    if (textarea && form) {
        textarea.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && event.ctrlKey) {
                event.preventDefault();
                form.submit();
            }
        });
    }
    const inputFile = document.getElementById('archivo-adjunto') || document.getElementById('archivo-adjunto-admin');
    const fileNameDisplay = document.getElementById('file-name-display') || document.getElementById('file-name-display-admin');
    if (inputFile && fileNameDisplay) {
        inputFile.addEventListener('change', function() {
            fileNameDisplay.textContent = this.files.length > 0 ? this.files[0].name : '';
        });
    }

    // --- LÓGICA DEL CHAT EN TIEMPO REAL (VERSIÓN FINAL) ---
    const chatBox = document.getElementById('chat-box');
    const conversacionId = <?php echo $conversacion['id']; ?>;
    const clienteId = <?php echo $conversacion['cliente_id'] ?? $cliente_id; ?>;

    function scrollToBottom() {
        if(chatBox) { chatBox.scrollTop = chatBox.scrollHeight; }
    }
    scrollToBottom();

    let ultimoIdProcesado = <?php echo !empty($mensajes) ? $mensajes[0]['id'] : 0; ?>;

    function checkForNewMessages() {
        const ultimoMensaje = chatBox.lastElementChild;
        const ultimoId = ultimoMensaje ? ultimoMensaje.dataset.messageId : 0;

        //fetch(`${BASE_URL}ajax_get_nuevos_mensajes.php?conversacion_id=${conversacionId}&ultimo_id=${ultimoId}`)
        fetch(`${BASE_URL}ajax/nuevos-mensajes?conversacion_id=${conversacionId}&ultimo_id=${ultimoIdProcesado}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                data.forEach(msg => {
                    const esMensajeDelCliente = msg.remitente_id == clienteId;
                    const esVistaDeAdmin = window.location.pathname.includes('/admin/');
                    
                    const msgClass = (msg.remitente_id == clienteId) ? 'received' : 'sent';
                    
                    // Función para formatear texto y convertir URLs a enlaces en JavaScript
                    function linkify(text) {
                        if (!text) return '';
                        const urlRegex = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
                        let linkifiedText = text.replace(urlRegex, function(url) {
                            return `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`;
                        });
                        return linkifiedText.replace(/\n/g, '<br>');
                    }

                    let messageTextHTML = linkify(msg.mensaje);
                    let attachmentHTML = '';

                    if (msg.archivo_adjunto) {
                        const rutaAdjunto = `${BASE_URL}uploads/adjuntos/${msg.archivo_adjunto}`;
                        const esImagen = ['jpg', 'jpeg', 'png', 'gif'].includes(msg.archivo_adjunto.split('.').pop().toLowerCase());
                        if (esImagen) {
                            attachmentHTML = `<div class="attachment mt-2"><a href="${rutaAdjunto}" target="_blank"><img src="${rutaAdjunto}" class="img-thumbnail" style="max-height: 150px;"></a></div>`;
                        } else {
                            attachmentHTML = `<div class="attachment mt-2"><a href="${rutaAdjunto}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-arrow-down"></i> ${msg.nombre_original_adjunto}</a></div>`;
                        }
                    }

                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message ${msgClass}`;
                    messageDiv.dataset.messageId = msg.id;
                    messageDiv.innerHTML = `
                        <div class="bubble">
                            ${messageTextHTML}
                            ${attachmentHTML}
                        </div>
                        <div class="timestamp">${new Date(msg.fecha_envio).toLocaleString()}</div>`;
                    console.log("messageDiv.innerHTML = ", messageDiv.innerHTML);
                    chatBox.prepend(messageDiv);
                    ultimoIdProcesado = msg.id; 
                });
                // Le pedimos al navegador que haga el scroll en el próximo ciclo de renderizado.
                //window.requestAnimationFrame(() => {
                    scrollToBottom();
                //});
            }
        });
    }
    setInterval(checkForNewMessages, 3000);
});
</script>
<?php require_once '../includes/footer.php'; ?>