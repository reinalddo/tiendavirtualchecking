<?php
// mensajes_pedido.php (Versión Final Corregida)
require_once 'includes/config.php';

// 1. Verificación de seguridad
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'login');
    exit();
}

//$pedido_id = (int)$_GET['pedido_id'];

$pedido_id = 0;
// 3. Verificamos si llegamos desde una URL amigable o un parámetro GET
if (isset($_GET['pedido_id'])) {
    $pedido_id = (int)$_GET['pedido_id'];
} else {
    // Si la URL es amigable, extraemos el ID
    $id_from_url = basename($_SERVER['REQUEST_URI']);
    if (is_numeric($id_from_url)) {
        $pedido_id = (int)$id_from_url;
    }
}

// Si no tenemos un ID de pedido válido, redirigimos
if ($pedido_id <= 0) {
    header('Location: ' . BASE_URL . 'perfil');
    exit();
}

$cliente_id = $_SESSION['usuario_id'];

// 4. Consulta SEGURA: Obtenemos la conversación SOLO SI el pedido pertenece al usuario en sesión
$stmt_conv = $pdo->prepare("SELECT c.* FROM conversaciones c JOIN pedidos p ON c.pedido_id = p.id WHERE c.pedido_id = ? AND p.usuario_id = ?");
$stmt_conv->execute([$pedido_id, $cliente_id]);
$conversacion = $stmt_conv->fetch(PDO::FETCH_ASSOC);

// 6. Si no se encontró la conversación, mostramos un mensaje de error amigable y detenemos el script
if (!$conversacion) {
    echo "<main><div class='container py-4'><div class='alert alert-danger'>No tienes permiso para ver esta conversación o no existe.</div><a href='perfil' class='btn btn-primary'>Volver a Mis Pedidos</a></div></main>";
    //require_once 'includes/footer.php';
    header('Location: ' . BASE_URL . 'perfil');
    exit();
}
// 3. Obtener todos los mensajes
$stmt_msgs = $pdo->prepare("SELECT * FROM mensajes WHERE conversacion_id = ? ORDER BY fecha_envio DESC");
$stmt_msgs->execute([$conversacion['id']]);
$mensajes = $stmt_msgs->fetchAll(PDO::FETCH_ASSOC);
require_once 'includes/header.php';
?>

<main>
    <div class="container py-4 chat-container">
        <div class="card shadow-sm">
            <div class="card-header">
                <h1 class="h4 mb-0">Mensajes sobre tu Pedido #<?php echo htmlspecialchars($pedido_id); ?></h1>
            </div>
            <div class="card-body">
                <div class="chat-box" id="chat-box">
                    <?php foreach ($mensajes as $msg):
                        $msg_class = ($msg['remitente_id'] == $cliente_id) ? 'sent' : 'received';
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
                <?php if ($conversacion['cliente_puede_responder']): ?>
                    <form action="pedido/enviar-mensaje" method="POST" id="chat-form" enctype="multipart/form-data">
                        <input type="hidden" name="conversacion_id" value="<?php echo $conversacion['id']; ?>">
                        <input type="hidden" name="pedido_id" value="<?php echo $pedido_id; ?>">
                        <textarea name="mensaje" id="mensaje-textarea" class="form-control mb-2" placeholder="Escribe tu mensaje aquí..." rows="3"></textarea>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <label for="archivo-adjunto" class="btn btn-secondary btn-sm"><i class="bi bi-paperclip"></i> Adjuntar Archivo</label>
                                <input type="file" name="archivo_adjunto" id="archivo-adjunto" class="d-none" accept=".jpg, .jpeg, .png, .gif, .pdf, .doc, .docx, .txt">
                                <span id="file-name-display" class="ms-2 text-muted"></span>
                            </div>
                            <button class="btn btn-primary" id="enviar-btn" type="submit">Enviar</button>
                        </div>
                        <small class="form-text text-muted">Presiona Ctrl+Enter para enviar. Máx 5MB.</small>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning text-center" role="alert">El administrador ha cerrado temporalmente la posibilidad de enviar nuevos mensajes.</div>
                <?php endif; ?>
            </div>
        </div>
        <a href="perfil" class="btn btn-secondary mt-3">← Volver a Mis Pedidos</a>
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

        fetch(`${BASE_URL}ajax/nuevos-mensajes?conversacion_id=${conversacionId}&ultimo_id=${ultimoIdProcesado}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                data.forEach(msg => {
                    const esMensajeDelCliente = msg.remitente_id == clienteId;
                    const esVistaDeAdmin = window.location.pathname.includes('/admin/');
                    
                    let msgClass = '';
                    if (esVistaDeAdmin) {
                        msgClass = esMensajeDelCliente ? 'received' : 'sent';
                    } else {
                        msgClass = esMensajeDelCliente ? 'sent' : 'received';
                    }
                    
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
                    console.log("messgeDIV", messageDiv);
                    messageDiv.innerHTML = `
                        <div class="bubble">
                            ${messageTextHTML}
                            ${attachmentHTML}
                        </div>
                        <div class="timestamp">${new Date(msg.fecha_envio).toLocaleString()}</div>`;
                    console.log("messgeDIV", messageDiv);
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
<?php require_once 'includes/footer.php'; ?>