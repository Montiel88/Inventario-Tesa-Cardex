</main> <!-- Cierra el main del header -->

<style>
/* Panel de notificaciones global (Consolidado en header) */
.tn-notif-list { max-height: 420px; overflow-y: auto; }
.tn-notif-list::-webkit-scrollbar { width: 4px; }
.tn-notif-list::-webkit-scrollbar-thumb { background: rgba(124,58,237,0.4); border-radius: 4px; }

.tn-notif-item {
    padding: 14px 18px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    transition: all 0.2s ease; cursor: pointer;
    position: relative;
}

.tn-notif-item:hover { background: rgba(124,58,237,0.1); padding-left: 22px; }
.tn-notif-item.danger  { border-left: 3px solid var(--c-danger); }
.tn-notif-item.warning { border-left: 3px solid var(--c-warning); }
.tn-notif-item.success { border-left: 3px solid var(--c-success); }
.tn-notif-item.info    { border-left: 3px solid var(--c-info); }

.tn-notif-title { font-size: 0.85rem; font-weight: 700; color: #fff; margin-bottom: 4px; }
.tn-notif-msg   { font-size: 0.78rem; color: rgba(255,255,255,0.5); line-height: 1.4; }
.tn-notif-time  { font-size: 0.68rem; color: rgba(255,255,255,0.3); margin-top: 6px; display: block; }

/* Items de notificación originales (compatibilidad) */
.notification-item {
    padding: 15px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    transition: all 0.2s ease;
    cursor: pointer;
    position: relative;
    color: #fff;
}
</style>

<script>
let notificacionesGlobales = [];
let notificacionesLeidas = new Set();

function cargarNotificaciones() {
    const container = document.getElementById('notifList');
    if(!container) return;

    fetch('/inventario_ti/api/notificaciones.php')
        .then(response => response.json())
        .then(data => {
            const notificaciones = (data && data.notificaciones) ? data.notificaciones : [];
            notificacionesGlobales = notificaciones;
            actualizarBadge(notificaciones.length);
            renderizarNotificaciones(notificaciones);
        })
        .catch(error => {
            console.error('Error al cargar notificaciones:', error);
            if (container) {
                container.innerHTML = `
                    <div class="tn-notif-empty">
                        <i class="fas fa-circle-exclamation mb-2"></i>
                        <p>No se pudieron cargar</p>
                    </div>
                `;
            }
        });
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

function renderizarNotificaciones(notificaciones) {
    const container = document.getElementById('notifList');
    if (!container) return;
    
    if (notificaciones.length === 0) {
        container.innerHTML = `
            <div class="tn-notif-empty">
                <i class="fas fa-check-circle mb-2" style="color: var(--c-success)"></i>
                <p>¡Todo al día!</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = notificaciones.map((notif, index) => `
        <div class="tn-notif-item ${notif.tipo || 'info'}" onclick="window.location.href='${notif.url || '#'}'">
            <div class="tn-notif-title">${notif.titulo}</div>
            <div class="tn-notif-msg">${notif.mensaje}</div>
            <div class="tn-notif-time"><i class="fas fa-clock me-1"></i>Recién</div>
        </div>
    `).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    cargarNotificaciones();
    setInterval(cargarNotificaciones, 60000);
});
</script>

<!-- Tus scripts originales -->
<!-- Bootstrap bundle is now loaded in header.php -->
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
