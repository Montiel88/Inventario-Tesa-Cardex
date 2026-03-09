</main> <!-- Cierra el main del header -->

<!-- Widget de Chat IA -->
<div id="chatWidget" class="chat-widget">
    <div class="chat-header" id="chatHeader">
        <div class="d-flex align-items-center">
            <i class="fas fa-robot me-2" style="color: #f3b229;"></i>
            <span>Asistente TESA</span>
        </div>
        <div>
            <button id="minimizeChat" class="chat-header-btn"><i class="fas fa-minus"></i></button>
            <button id="closeChat" class="chat-header-btn"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div class="chat-messages" id="chatMessages">
        <div class="message bot">
            <div class="message-content">
                ¡Hola! Soy tu asistente de inventario. ¿En qué puedo ayudarte?
            </div>
        </div>
    </div>
    <div class="chat-typing" id="chatTyping">
        <span></span><span></span><span></span>
    </div>
    <div class="chat-input-container">
        <input type="text" id="chatInput" placeholder="Escribe tu pregunta..." autocomplete="off">
        <button id="sendMessage">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- Botón flotante para abrir el chat -->
<button id="openChat" class="open-chat-btn">
    <i class="fas fa-comment-dots"></i>
</button>

<style>
/* ============================================ */
/* CHAT WIDGET - DISEÑO ELEGANTE */
/* ============================================ */
.chat-widget {
    position: fixed;
    bottom: 80px;
    right: 20px;
    width: 360px;
    max-width: calc(100% - 40px);
    background: white;
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25), 0 6px 12px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    z-index: 10000;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 0;
    transform: translateY(20px) scale(0.95);
    pointer-events: none;
    border: 2px solid #f3b229;
}

.chat-widget.show {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: all;
}

.chat-widget.minimized {
    height: 60px;
    transform: translateY(0) scale(1);
}

.chat-widget.minimized .chat-messages,
.chat-widget.minimized .chat-input-container,
.chat-widget.minimized .chat-typing {
    display: none;
}

.chat-header {
    background: linear-gradient(135deg, #5a2d8c 0%, #3d1e5e 100%);
    color: white;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #f3b229;
    font-weight: 600;
}

.chat-header-btn {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.8);
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.chat-header-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    color: white;
}

.chat-messages {
    height: 350px;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fc;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.message {
    display: flex;
    max-width: 85%;
    animation: fadeInUp 0.3s ease;
}

.message.bot {
    align-self: flex-start;
}

.message.user {
    align-self: flex-end;
}

.message-content {
    padding: 12px 16px;
    border-radius: 18px;
    word-wrap: break-word;
    font-size: 14px;
    line-height: 1.5;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.message.bot .message-content {
    background: white;
    border: 1px solid #e0e0e0;
    color: #333;
    border-bottom-left-radius: 4px;
}

.message.user .message-content {
    background: linear-gradient(135deg, #5a2d8c 0%, #3d1e5e 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.chat-typing {
    padding: 10px 20px;
    display: none;
    gap: 4px;
    align-items: center;
    background: #f8f9fc;
    border-top: 1px solid #eee;
}

.chat-typing span {
    width: 8px;
    height: 8px;
    background: #5a2d8c;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out;
}

.chat-typing span:nth-child(1) { animation-delay: 0s; }
.chat-typing span:nth-child(2) { animation-delay: 0.2s; }
.chat-typing span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.3; }
    30% { transform: translateY(-6px); opacity: 1; }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.chat-input-container {
    display: flex;
    padding: 12px;
    border-top: 1px solid #e0e0e0;
    background: white;
    gap: 8px;
}

.chat-input-container input {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 30px;
    padding: 10px 16px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}

.chat-input-container input:focus {
    border-color: #5a2d8c;
}

.chat-input-container button {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #5a2d8c 0%, #3d1e5e 100%);
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-input-container button:hover {
    transform: scale(1.05);
    background: #5a2d8c;
    box-shadow: 0 5px 15px rgba(90, 45, 140, 0.3);
}

.chat-input-container button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.open-chat-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f3b229 0%, #d49b1f 100%);
    color: #5a2d8c;
    border: none;
    font-size: 26px;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(243, 178, 41, 0.4);
    transition: all 0.3s;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
}

.open-chat-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 12px 30px rgba(243, 178, 41, 0.6);
}

@media (max-width: 480px) {
    .chat-widget {
        width: calc(100% - 30px);
        bottom: 70px;
        right: 15px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatWidget = document.getElementById('chatWidget');
    const openBtn = document.getElementById('openChat');
    const closeBtn = document.getElementById('closeChat');
    const minimizeBtn = document.getElementById('minimizeChat');
    const sendBtn = document.getElementById('sendMessage');
    const chatInput = document.getElementById('chatInput');
    const chatMessages = document.getElementById('chatMessages');
    const chatTyping = document.getElementById('chatTyping');

    // Abrir chat
    openBtn.addEventListener('click', function() {
        chatWidget.classList.add('show');
        chatWidget.classList.remove('minimized');
        openBtn.style.display = 'none';
        chatInput.focus();
    });

    // Cerrar chat
    closeBtn.addEventListener('click', function() {
        chatWidget.classList.remove('show');
        openBtn.style.display = 'flex';
    });

    // Minimizar chat
    minimizeBtn.addEventListener('click', function() {
        chatWidget.classList.toggle('minimized');
    });

    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        // Mostrar mensaje del usuario
        appendMessage(message, 'user');
        chatInput.value = '';
        chatInput.disabled = true;
        sendBtn.disabled = true;
        chatTyping.style.display = 'flex';
        scrollToBottom();

        try {
            const response = await fetch('/inventario_ti/api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });

            const raw = await response.text();
            let data = {};
            try {
                data = JSON.parse(raw);
            } catch (e) {
                throw new Error('Respuesta no JSON del backend: ' + raw.slice(0, 180));
            }
            
            chatTyping.style.display = 'none';

            if (!response.ok) {
                appendMessage('Error: ' + (data.error || `HTTP ${response.status}`), 'bot');
                return;
            }

            if (data.reply) {
                appendMessage(data.reply, 'bot');
            } else if (data.error) {
                appendMessage('Error: ' + data.error, 'bot');
            } else {
                appendMessage('No se pudo procesar la respuesta', 'bot');
            }
        } catch (error) {
            chatTyping.style.display = 'none';
            appendMessage('Error: ' + error.message, 'bot');
            console.error('Error:', error);
        } finally {
            chatInput.disabled = false;
            sendBtn.disabled = false;
            chatInput.focus();
        }
    }

    function appendMessage(text, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}`;
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.innerHTML = text.replace(/\n/g, '<br>');
        messageDiv.appendChild(contentDiv);
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });
});
</script>

<!-- Tus scripts originales (mantén los que ya tienes) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
</script>
<script src="/inventario_ti/assets/js/funciones.js"></script>
<?php ob_end_flush(); ?>
</body>
</html>
