</main> <!-- Cierra el main del header -->

<!-- ============================================ -->
<!-- SISTEMA DE NOTIFICACIONES GLOBAL -->
<!-- ============================================ -->

<!-- Panel de notificaciones -->
<div class="notification-panel-global" id="notificationPanelGlobal">
    <div class="notification-panel-header">
        <h6><i class="fas fa-bell me-2"></i>Notificaciones</h6>
        <div class="notification-actions">
            <button class="btn btn-sm btn-link" onclick="marcarTodasLeidas()" title="Marcar todas como leídas">
                <i class="fas fa-check-double"></i>
            </button>
            <button class="btn btn-sm btn-link" onclick="toggleNotificationPanel()" title="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="notification-panel-body" id="notificationPanelBody">
        <div class="text-center py-4 text-muted">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2 mb-0">Cargando notificaciones...</p>
        </div>
    </div>
    <div class="notification-panel-footer" id="notificationPanelFooter" style="display: none;">
        <small class="text-muted">Actualizado: <span id="lastUpdate"></span></small>
    </div>
</div>

<!-- Overlay para cerrar al hacer click fuera -->
<div class="notification-overlay-global" id="notificationOverlayGlobal" onclick="toggleNotificationPanel()"></div>

<style>
/* Panel de notificaciones global */
.notification-panel-global {
    position: fixed;
    top: 80px;
    right: 80px;
    width: 400px;
    max-width: calc(100vw - 100px);
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    z-index: 9999;
    transform: translateX(120%);
    transition: transform 0.3s ease;
    max-height: calc(100vh - 100px);
    overflow: hidden;
    border: 1px solid rgba(90, 45, 140, 0.2);
}

.notification-panel-global.show {
    transform: translateX(0);
}

.notification-panel-header {
    background: linear-gradient(135deg, #5a2d8c 0%, #7b42a8 100%);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 3px solid #f3b229;
}

.notification-panel-header h6 {
    margin: 0;
    font-weight: 600;
    font-size: 1rem;
}

.notification-actions {
    display: flex;
    gap: 5px;
}

.notification-actions .btn-link {
    color: white;
    padding: 5px 8px;
    text-decoration: none;
}

.notification-actions .btn-link:hover {
    color: #f3b229;
}

.notification-panel-body {
    max-height: 400px;
    overflow-y: auto;
    padding: 0;
}

.notification-panel-body::-webkit-scrollbar {
    width: 6px;
}

.notification-panel-body::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.notification-panel-body::-webkit-scrollbar-thumb {
    background: #5a2d8c;
    border-radius: 3px;
}

.notification-panel-footer {
    padding: 10px 20px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    font-size: 0.75rem;
}

/* Overlay */
.notification-overlay-global {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9998;
    display: none;
}

.notification-overlay-global.show {
    display: block;
}

/* Items de notificación */
.notification-item {
    padding: 15px 20px;
    border-bottom: 1px solid #f1f1f1;
    transition: all 0.2s ease;
    cursor: pointer;
    position: relative;
}

.notification-item:hover {
    background: linear-gradient(90deg, rgba(90, 45, 140, 0.05) 0%, transparent 100%);
    padding-left: 25px;
}

.notification-item.unread {
    background: rgba(243, 178, 41, 0.08);
    border-left: 4px solid #f3b229;
}

.notification-item.read {
    opacity: 0.7;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.notification-item-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
    font-size: 1.1rem;
}

.notification-item-text {
    flex: 1;
    min-width: 0;
}

.notification-item-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #333;
    margin-bottom: 4px;
}

.notification-item-message {
    font-size: 0.85rem;
    color: #666;
    line-height: 1.4;
}

.notification-item-time {
    font-size: 0.75rem;
    color: #999;
    margin-top: 5px;
}

.notification-item-action {
    display: inline-block;
    margin-top: 8px;
    padding: 4px 12px;
    background: rgba(90, 45, 140, 0.1);
    color: #5a2d8c;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}

.notification-item-action:hover {
    background: #5a2d8c;
    color: white;
}

.notification-item-close {
    position: absolute;
    top: 10px;
    right: 10px;
    background: transparent;
    border: none;
    color: #999;
    cursor: pointer;
    padding: 5px;
    font-size: 0.9rem;
    transition: color 0.2s;
}

.notification-item-close:hover {
    color: #e74c3c;
}

/* Estados de notificación */
.notification-item.danger .notification-item-icon { background: linear-gradient(135deg, #e74c3c, #c0392b); }
.notification-item.warning .notification-item-icon { background: linear-gradient(135deg, #f39c12, #e67e22); }
.notification-item.info .notification-item-icon { background: linear-gradient(135deg, #3498db, #2980b9); }
.notification-item.success .notification-item-icon { background: linear-gradient(135deg, #27ae60, #229954); }
.notification-item.secondary .notification-item-icon { background: linear-gradient(135deg, #95a5a6, #7f8c8d); }

/* Empty state */
.notification-empty {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.notification-empty i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 768px) {
    .notification-panel-global {
        right: 10px;
        left: 10px;
        width: auto;
        top: 70px;
        max-height: calc(100vh - 90px);
    }
    
    .notification-bell-header {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
}

/* ============================================
   NOTIFICACIONES TOAST DINÁMICAS
   ============================================ */
.toast-container {
    position: fixed;
    top: 90px;
    right: 20px;
    z-index: 10000;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 420px;
    pointer-events: none;
}

.toast-notification {
    background: white;
    border-radius: 12px;
    padding: 1rem 1rem;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: flex-start;
    gap: 12px;
    min-width: 320px;
    max-width: 420px;
    border-left: 5px solid;
    pointer-events: auto;
    backdrop-filter: blur(10px);
    animation: slideInRight 0.3s ease forwards;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.toast-notification.success {
    border-left-color: #10b981;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(16, 185, 129, 0.05) 100%);
}

.toast-notification.error {
    border-left-color: #ef4444;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(239, 68, 68, 0.05) 100%);
}

.toast-notification.warning {
    border-left-color: #f59e0b;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(245, 158, 11, 0.05) 100%);
}

.toast-notification.info {
    border-left-color: #3b82f6;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(59, 130, 246, 0.05) 100%);
}

.toast-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem;
}

.toast-notification.success .toast-icon {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}

.toast-notification.error .toast-icon {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.toast-notification.warning .toast-icon {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
}

.toast-notification.info .toast-icon {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.toast-content {
    flex: 1;
    min-width: 0;
}

.toast-title {
    font-weight: 700;
    font-size: 0.95rem;
    margin-bottom: 4px;
    color: #1e293b;
}

.toast-message {
    font-size: 0.85rem;
    color: #64748b;
    line-height: 1.5;
}

.toast-message strong {
    color: #1e293b;
    font-weight: 600;
}

.toast-message small {
    display: block;
    margin-top: 4px;
    color: #94a3b8;
}

.toast-action {
    margin-top: 6px;
    font-size: 0.8rem;
    color: #6366f1;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.toast-close {
    background: transparent;
    border: none;
    padding: 4px;
    cursor: pointer;
    color: #94a3b8;
    transition: all 0.2s;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toast-close:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #1e293b;
}

/* Animación de salida */
.toast-notification.closing {
    animation: slideOutRight 0.3s ease forwards;
}

@keyframes slideOutRight {
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .toast-container {
        top: 70px;
        right: 10px;
        left: 10px;
        max-width: none;
    }
    
    .toast-notification {
        min-width: auto;
        max-width: none;
    }
}
</style>

<script>
let notificacionesGlobales = [];
let notificacionesLeidas = new Set();

document.addEventListener('DOMContentLoaded', function() {
    cargarNotificaciones();
    // Recargar cada 2 minutos
    setInterval(cargarNotificaciones, 120000);
});

function cargarNotificaciones() {
    fetch('/inventario_ti/api/obtener_notificaciones.php')
        .then(response => response.json())
        .then(notificaciones => {
            notificacionesGlobales = notificaciones;
            actualizarBadge(notificaciones.length);
            renderizarNotificaciones(notificaciones);
            
            // Actualizar timestamp
            const now = new Date();
            document.getElementById('lastUpdate').textContent = now.toLocaleTimeString();
        })
        .catch(error => console.error('Error al cargar notificaciones:', error));
}

function actualizarBadge(cantidad) {
    const badge = document.getElementById('notificationBadgeHeader');
    if (badge) {
        if (cantidad > 0) {
            badge.textContent = cantidad > 99 ? '99+' : cantidad;
            badge.style.display = 'flex';
        } else {
            badge.textContent = '';
            badge.style.display = 'none';
        }
    }
}

function toggleNotificationPanel() {
    const panel = document.getElementById('notificationPanelGlobal');
    const overlay = document.getElementById('notificationOverlayGlobal');
    panel.classList.toggle('show');
    overlay.classList.toggle('show');
    
    if (panel.classList.contains('show')) {
        // Marcar como leídas al abrir
        notificacionesLeidas.clear();
        actualizarEstadoVisual();
    }
}

function renderizarNotificaciones(notificaciones) {
    const container = document.getElementById('notificationPanelBody');
    const footer = document.getElementById('notificationPanelFooter');
    
    if (notificaciones.length === 0) {
        container.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-check-circle"></i>
                <p>¡Todo está al día!</p>
                <small>No hay notificaciones pendientes</small>
            </div>
        `;
        footer.style.display = 'none';
        return;
    }
    
    footer.style.display = 'block';
    
    container.innerHTML = notificaciones.map((notif, index) => `
        <div class="notification-item ${notif.tipo} ${notificacionesLeidas.has(index) ? 'read' : 'unread'}" 
             onclick="irANotificacion(${index})">
            <button class="notification-item-close" onclick="event.stopPropagation(); eliminarNotificacion(${index})">
                <i class="fas fa-times"></i>
            </button>
            <div class="notification-item-content">
                <div class="notification-item-icon">
                    <i class="fas ${notif.icono}"></i>
                </div>
                <div class="notification-item-text">
                    <div class="notification-item-title">${notif.titulo}</div>
                    <div class="notification-item-message">${notif.mensaje}</div>
                    ${notif.url ? `<a href="${notif.url}" class="notification-item-action" onclick="event.stopPropagation()">
                        <i class="fas fa-arrow-right me-1"></i>${notif.url_label || 'Ver más'}
                    </a>` : ''}
                    <div class="notification-item-time">
                        <i class="fas fa-clock me-1"></i>Recién
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function irANotificacion(index) {
    const notif = notificacionesGlobales[index];
    if (notif && notif.url) {
        window.location.href = notif.url;
    }
}

function eliminarNotificacion(index) {
    notificacionesGlobales.splice(index, 1);
    actualizarBadge(notificacionesGlobales.length);
    renderizarNotificaciones(notificacionesGlobales);
    
    if (notificacionesGlobales.length === 0) {
        setTimeout(() => toggleNotificationPanel(), 500);
    }
}

function marcarTodasLeidas() {
    notificacionesLeidas.clear();
    // Marcar todas como leídas
    notificacionesGlobales.forEach((_, i) => notificacionesLeidas.add(i));
    actualizarEstadoVisual();
}

function actualizarEstadoVisual() {
    const items = document.querySelectorAll('.notification-item');
    items.forEach((item, index) => {
        if (notificacionesLeidas.has(index)) {
            item.classList.add('read');
            item.classList.remove('unread');
        } else {
            item.classList.add('unread');
            item.classList.remove('read');
        }
    });
}
</script>

<!-- Tus scripts originales -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
</script>
<!-- Sistema de Notificaciones Toast -->
<script src="/inventario_ti/js/notificaciones-toast.js"></script>
<script src="/inventario_ti/assets/js/funciones.js"></script>
<!-- Sistema de Alertas -->
<script src="/inventario_ti/assets/js/alertas_sistema.js"></script>
<?php ob_end_flush(); ?>
<?php if (isset($_SESSION['success'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: '<?php echo htmlspecialchars($_SESSION['success']); ?>',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true
    });
</script>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?php echo htmlspecialchars($_SESSION['error']); ?>',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true
    });
</script>
<?php unset($_SESSION['error']); endif; ?>
</body>
</html>
