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
            actualizarBadge(parseInt(data && data.unread_count ? data.unread_count : 0, 10) || 0);
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
<!-- UX Loading GLOBAL — Feedback visual en botones, forms y operaciones largas -->
<script>window.__APP_BASE__ = '/inventario_ti';</script>
<script src="/inventario_ti/assets/js/uploading.js"></script>
<!-- Sistema de Alertas -->
<script src="/inventario_ti/assets/js/alertas_sistema.js"></script>
<?php ob_end_flush(); ?>

<?php
// =============================================================
// SOPORTE GLOBAL: Popups GRANDES de éxito/error
// Guarda en $_SESSION['success_grande'] = ['title' => '', 'text' => '', 'url_ok' => '', 'text_ok' => '']
// o $_SESSION['error_grande'] = ... y se mostrará como modal grande bloqueante
// =============================================================
$__popupGrandeSuccess = null;
if (!empty($_SESSION['success_grande']) && is_array($_SESSION['success_grande'])) {
    $__popupGrandeSuccess = $_SESSION['success_grande'];
    unset($_SESSION['success_grande']);
}
$__popupGrandeError = null;
if (!empty($_SESSION['error_grande']) && is_array($_SESSION['error_grande'])) {
    $__popupGrandeError = $_SESSION['error_grande'];
    unset($_SESSION['error_grande']);
}
?>

<?php if ($__popupGrandeSuccess): ?>
<script>
(function(){
    function lanzar(){
        if (typeof Swal === 'undefined') { setTimeout(lanzar, 100); return; }
        const title = <?php echo json_encode($__popupGrandeSuccess['title'] ?? '¡Éxito!'); ?>;
        const texto = <?php echo json_encode($__popupGrandeSuccess['text'] ?? ''); ?>;
        const btnOkText = <?php echo json_encode($__popupGrandeSuccess['text_ok'] ?? 'Continuar'); ?>;
        const url = <?php echo json_encode($__popupGrandeSuccess['url_ok'] ?? ''); ?>;
        Swal.fire({
            icon: 'success',
            title: title,
            html: texto,
            confirmButtonColor: '#5a2d8c',
            confirmButtonText: btnOkText,
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then(function(){
            if (url) window.location.href = url;
        });
    }
    lanzar();
})();
</script>
<?php endif; ?>

<?php if ($__popupGrandeError): ?>
<script>
(function(){
    function lanzar(){
        if (typeof Swal === 'undefined') { setTimeout(lanzar, 100); return; }
        const title = <?php echo json_encode($__popupGrandeError['title'] ?? 'Ocurrió un error'); ?>;
        const texto = <?php echo json_encode($__popupGrandeError['text'] ?? ''); ?>;
        const btnOkText = <?php echo json_encode($__popupGrandeError['text_ok'] ?? 'Entendido'); ?>;
        Swal.fire({
            icon: 'error',
            title: title,
            html: texto,
            confirmButtonColor: '#dc3545',
            confirmButtonText: btnOkText,
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    }
    lanzar();
})();
</script>
<?php endif; ?>

<?php
// =============================================================
// SOPORTE ESPECÍFICO: Popups de MÓVIMIENTOS (Traspaso / Devolución)
// Siempre se leen ANTES del cierre de </body> para que el navegador los ejecute.
// =============================================================
$__popupT = null;
if (!empty($_SESSION['ui_popup_traspaso']) && is_array($_SESSION['ui_popup_traspaso'])) {
    $__popupT = $_SESSION['ui_popup_traspaso'];
    unset($_SESSION['ui_popup_traspaso']);
}
$__popupD = null;
if (!empty($_SESSION['ui_popup_devolucion']) && is_array($_SESSION['ui_popup_devolucion'])) {
    $__popupD = $_SESSION['ui_popup_devolucion'];
    unset($_SESSION['ui_popup_devolucion']);
}
$__popupM = null;
if (!empty($_SESSION['ui_popup_traspaso_multiple']) && is_array($_SESSION['ui_popup_traspaso_multiple'])) {
    $__popupM = $_SESSION['ui_popup_traspaso_multiple'];
    unset($_SESSION['ui_popup_traspaso_multiple']);
}
$__popupP = null;
if (!empty($_SESSION['ui_popup_prestamo_rapido']) && is_array($_SESSION['ui_popup_prestamo_rapido'])) {
    $__popupP = $_SESSION['ui_popup_prestamo_rapido'];
    unset($_SESSION['ui_popup_prestamo_rapido']);
}
$__popupPers = null;
if (!empty($_SESSION['ui_popup_personas']) && is_array($_SESSION['ui_popup_personas'])) {
    $__popupPers = $_SESSION['ui_popup_personas'];
    unset($_SESSION['ui_popup_personas']);
}
?>

<?php if ($__popupT):
    $t = intval($__popupT['total'] ?? 0);
    $origen = htmlspecialchars($__popupT['origen_nombre'] ?? '');
    $destino = htmlspecialchars($__popupT['destino_nombre'] ?? '');
    $acta_id = intval($__popupT['acta_id'] ?? 0);
    $tieneActa = ($acta_id > 0);
    $url_acta = $tieneActa ? ("/inventario_ti/api/generar_acta_unificada.php?acta_id=" . $acta_id) : '';
    $url_otra = "/inventario_ti/modules/movimientos/traspaso.php";
    $url_dash = "/inventario_ti/modules/dashboard.php";
    $toastMsg = htmlspecialchars($_SESSION['success'] ?? ("Traspaso exitoso. Se trasladaron $t equipos correctamente."));
?>
<script>
(function(){
    function dispararPopup() {
        if (typeof Swal === 'undefined') { setTimeout(dispararPopup, 120); return; }
        const total = <?php echo $t; ?>;
        const origen = <?php echo json_encode($origen); ?>;
        const destino = <?php echo json_encode($destino); ?>;
        const urlActa = <?php echo json_encode($url_acta); ?>;
        const tieneActa = <?php echo $tieneActa ? 'true' : 'false'; ?>;
        const urlOtra = <?php echo json_encode($url_otra); ?>;
        const urlDash = <?php echo json_encode($url_dash); ?>;
        const toastMsg = <?php echo json_encode($toastMsg); ?>;

        try {
            Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 8000,
                timerProgressBar: true,
                allowOverlap: true,
                didOpen: function (toast) {
                    try {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    } catch (e) {}
                }
            }).fire({
                icon: 'success',
                title: '¡Traspaso Exitoso!',
                text: toastMsg,
                background: '#5a2d8c',
                color: '#ffffff',
                iconColor: '#fde68a'
            });
        } catch (eToast) {}

        const opts = {
            icon: 'success',
            title: '¡Traspaso Realizado con Éxito! 🎉',
            html:
                '<div class="text-start">' +
                '<div class="mb-3"><strong>Equipos trasladados: </strong><span class="badge bg-success fs-6">' + total + ' equipo' + (total === 1 ? '' : 's') + '</span></div>' +
                '<div class="mb-1"><strong>De (Custodio anterior):</strong></div>' +
                '<div class="alert alert-light py-2 px-3 mb-3 border small">' + (origen || '-') + '</div>' +
                '<div class="mb-1"><strong>A (Nuevo custodio):</strong></div>' +
                '<div class="alert alert-warning py-2 px-3 mb-3 border border-warning bg-opacity-20 small">' + (destino || '-') + '</div>' +
                (tieneActa ? '<p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i> El acta unificada ya se guardó en el módulo Actas. Puedes imprimirla también desde <strong>Movimientos → Historial</strong>.</p>' : '<p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i> Resumen del movimiento guardado en Historial.</p>') +
                '</div>',
            showCancelButton: true,
            showDenyButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            confirmButtonColor: '#198754',
            denyButtonColor: '#f3b229',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            focusConfirm: false,
            cancelButtonText: '<i class="fas fa-home me-1"></i> Volver al Inicio',
            denyButtonText: '<i class="fas fa-redo me-1"></i> Hacer Otro Traspaso',
            confirmButtonText: tieneActa ? '<i class="fas fa-file-pdf me-1"></i> Imprimir Acta (Opcional)' : '<i class="fas fa-clipboard-list me-1"></i> Ver Resumen',
            customClass: { popup: 'swal-popup-traspaso-exito' }
        };
        Swal.fire(opts).then(function(res){
            if (res.isConfirmed) {
                if (tieneActa && urlActa) {
                    try { window.open(urlActa, '_blank', 'noopener'); } catch(e) {}
                    setTimeout(function(){ window.location.href = urlOtra; }, 450);
                } else {
                    window.location.href = urlOtra;
                }
                return;
            }
            if (res.isDenied) {
                window.location.href = urlOtra;
                return;
            }
            if (res.isDismissed) {
                const reason = String(res.dismiss || '');
                if (reason === 'cancel' || reason === Swal.DismissReason.cancel) {
                    window.location.href = urlDash;
                    return;
                }
                try {
                    if (tieneActa && urlActa) {
                        if (confirm('Ocurrió un evento inesperado en el diálogo. ¿Desea ir al inicio?')) window.location.href = urlDash;
                    } else {
                        window.location.href = urlDash;
                    }
                } catch(e) { window.location.href = urlDash; }
                return;
            }
            window.location.href = urlDash;
        });
    }
    dispararPopup();
})();
</script>
<?php endif; ?>

<?php if ($__popupD):
    $estadoD = htmlspecialchars($__popupD['estado_equipo'] ?? '');
    $mensajeExtraD = htmlspecialchars($__popupD['mensaje_adicional'] ?? '');
    $actaUrlD = htmlspecialchars($__popupD['acta_url'] ?? '');
    $acta_idD = intval($__popupD['acta_id'] ?? 0);
    $tieneActaD = ($acta_idD > 0 || !empty($actaUrlD));
    $equipoNombreD = htmlspecialchars($__popupD['equipo_nombre'] ?? '');
    $personaNombreD = htmlspecialchars($__popupD['persona_nombre'] ?? '');
    $codigoEquipoD = htmlspecialchars($__popupD['codigo_equipo'] ?? '');
    $codigoActaD = htmlspecialchars($__popupD['codigo_acta'] ?? '');
    $urlOtraDev = "/inventario_ti/modules/movimientos/devolucion.php";
    $urlDashD = "/inventario_ti/modules/dashboard.php";
    $toastMsgDev = htmlspecialchars($_SESSION['success'] ?? ('Devolución registrada correctamente.' . ($codigoEquipoD ? " Equipo: $codigoEquipoD." : '')));
?>
<script>
(function(){
    function disparar(){
        if (typeof Swal === 'undefined') { setTimeout(disparar, 120); return; }
        const estado = <?php echo json_encode($estadoD); ?>;
        const mensajeExtra = <?php echo json_encode($mensajeExtraD); ?>;
        const actaUrl = <?php echo json_encode($actaUrlD); ?>;
        const tieneActa = <?php echo $tieneActaD ? 'true' : 'false'; ?>;
        const equipoNombre = <?php echo json_encode($equipoNombreD); ?>;
        const personaNombre = <?php echo json_encode($personaNombreD); ?>;
        const codigoEquipo = <?php echo json_encode($codigoEquipoD); ?>;
        const codigoActa = <?php echo json_encode($codigoActaD); ?>;
        const urlOtra = <?php echo json_encode($urlOtraDev); ?>;
        const urlDash = <?php echo json_encode($urlDashD); ?>;
        const toastMsgDev = <?php echo json_encode($toastMsgDev); ?>;

        try {
            Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 8000,
                timerProgressBar: true,
                allowOverlap: true,
                didOpen: function (toast) {
                    try {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    } catch (e) {}
                }
            }).fire({
                icon: 'success',
                title: '¡Devolución Exitosa!',
                text: toastMsgDev,
                background: '#5a2d8c',
                color: '#ffffff',
                iconColor: '#fde68a'
            });
        } catch (eToast) {}

        let html = '<div class="text-start">';
        html += '<div class="mb-3"><strong>Equipo devuelto: </strong>';
        if (codigoEquipo) html += '<span class="badge bg-success fs-6 me-2">' + codigoEquipo + '</span>';
        if (equipoNombre) html += '<span class="small">' + equipoNombre + '</span>';
        html += '</div>';
        if (personaNombre) {
            html += '<div class="mb-1"><strong>Persona (custodio anterior):</strong></div>';
            html += '<div class="alert alert-warning py-2 px-3 mb-3 border border-warning bg-opacity-20 small">' + personaNombre + '</div>';
        }
        if (estado) {
            html += '<p class="mb-2"><strong>Estado devuelto: </strong><span class="badge bg-info fs-6">' + estado + '</span></p>';
        }
        if (mensajeExtra) {
            html += '<p class="text-warning-emphasis mb-2 small"><i class="fas fa-triangle-exclamation me-1"></i>' + mensajeExtra + '</p>';
        }
        if (codigoActa) {
            html += '<p class="small mb-1"><strong>Código Acta:</strong> <span class="font-monospace">' + codigoActa + '</span></p>';
        }
        if (tieneActa) {
            html += '<p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i> El acta de devolución ya se guardó en el módulo <strong>Actas</strong>. Puedes imprimirla también desde <strong>Movimientos → Historial</strong>.</p>';
        } else {
            html += '<p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i> Resumen del movimiento guardado en Historial.</p>';
        }
        html += '</div>';

        Swal.fire({
            icon: 'success',
            title: '¡Devolución Registrada con Éxito! 🔄',
            html: html,
            showCancelButton: true,
            showDenyButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            reverseButtons: true,
            focusConfirm: false,
            confirmButtonColor: '#198754',
            denyButtonColor: '#f3b229',
            cancelButtonColor: '#6c757d',
            cancelButtonText: '<i class="fas fa-home me-1"></i> Volver al Inicio',
            denyButtonText: '<i class="fas fa-plus-circle me-1"></i> Registrar Otra Devolución',
            confirmButtonText: tieneActa ? '<i class="fas fa-file-pdf me-1"></i> Generar / Imprimir Acta' : '<i class="fas fa-clipboard-list me-1"></i> Ver Resumen'
        }).then((res) => {
            if (res.isConfirmed) {
                if (tieneActa && actaUrl) { try { window.open(actaUrl, '_blank', 'noopener'); } catch(e) {} }
                setTimeout(function(){ window.location.href = urlOtra; }, 450);
                return;
            }
            if (res.isDenied) {
                window.location.href = urlOtra;
                return;
            }
            if (res.isDismissed) {
                const reason = String(res.dismiss || '');
                if (reason === 'cancel' || (typeof Swal !== 'undefined' && typeof Swal.DismissReason !== 'undefined' && reason === Swal.DismissReason.cancel)) {
                    window.location.href = urlDash;
                    return;
                }
                try {
                    if (!confirm('Ocurrió un evento inesperado en el diálogo. ¿Desea ir al inicio?')) return;
                } catch(e) {}
                window.location.href = urlDash;
                return;
            }
            window.location.href = urlDash;
        });
    }
    disparar();
})();
</script>
<?php endif; ?>

<?php if ($__popupP):
    $prestamoIdP   = intval($__popupP['prestamo_id']   ?? 0);
    $movimientoIdP = intval($__popupP['movimiento_id'] ?? 0);
    $codigoEquipoP = htmlspecialchars($__popupP['codigo_equipo']  ?? '');
    $equipoNombreP = htmlspecialchars($__popupP['equipo_nombre']  ?? '');
    $personaNombreP= htmlspecialchars($__popupP['persona_nombre'] ?? '');
    $fechaEstP     = htmlspecialchars($__popupP['fecha_estimada'] ?? '');
    $obsP          = htmlspecialchars($__popupP['observaciones']  ?? '');
    $resumenUrlP   = htmlspecialchars($__popupP['resumen_url']    ?? ('/inventario_ti/modules/prestamos_rapidos/listar.php'));
    $urlOtroP      = "/inventario_ti/modules/prestamos_rapidos/registrar.php";
    $urlDashP      = "/inventario_ti/modules/dashboard.php";
    $toastMsgP     = htmlspecialchars($_SESSION['success'] ?? ('Préstamo rápido registrado correctamente.' . ($codigoEquipoP ? " Equipo: $codigoEquipoP." : '')));
?>
<script>
(function(){
    function dispararPopupP() {
        if (typeof Swal === 'undefined') { setTimeout(dispararPopupP, 120); return; }
        const prestamoId    = <?php echo $prestamoIdP; ?>;
        const movimientoId  = <?php echo $movimientoIdP; ?>;
        const codigoEquipo  = <?php echo json_encode($codigoEquipoP); ?>;
        const equipoNombre  = <?php echo json_encode($equipoNombreP); ?>;
        const personaNombre = <?php echo json_encode($personaNombreP); ?>;
        const fechaEst      = <?php echo json_encode($fechaEstP); ?>;
        const obs           = <?php echo json_encode($obsP); ?>;
        const resumenUrl    = <?php echo json_encode($resumenUrlP); ?>;
        const urlOtro       = <?php echo json_encode($urlOtroP); ?>;
        const urlDash       = <?php echo json_encode($urlDashP); ?>;
        const toastMsg      = <?php echo json_encode($toastMsgP); ?>;

        try {
            Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 8000,
                timerProgressBar: true,
                allowOverlap: true,
                didOpen: function (toast) {
                    try {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    } catch (e) {}
                }
            }).fire({
                icon: 'success',
                title: '¡Préstamo Rápido Registrado!',
                text: toastMsg,
                background: '#5a2d8c',
                color: '#ffffff',
                iconColor: '#fde68a'
            });
        } catch (eToast) {}

        let html = '<div class="text-start">';
        html += '<div class="mb-3"><strong>Equipo prestado: </strong>';
        if (codigoEquipo) html += '<span class="badge bg-success fs-6 me-2">' + codigoEquipo + '</span>';
        if (equipoNombre) html += '<span class="small">' + equipoNombre + '</span>';
        html += '</div>';
        if (personaNombre) {
            html += '<div class="mb-1"><strong>Persona que recibe (custodio temporal):</strong></div>';
            html += '<div class="alert alert-warning py-2 px-3 mb-3 border border-warning bg-opacity-20 small">' + personaNombre + '</div>';
        }
        if (fechaEst) {
            html += '<p class="mb-2"><strong>Devolución estimada: </strong><span class="badge bg-info fs-6">' + fechaEst + '</span></p>';
        }
        if (obs) {
            html += '<p class="mb-2 small"><strong>Observaciones:</strong> ' + obs + '</p>';
        }
        html += '<p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i> El movimiento PRESTAMO_RAPIDO #' + movimientoId + ' se guardó en Historial. Puedes gestionar la devolución desde el módulo <strong>Préstamos Rápidos → Listar</strong>.</p>';
        html += '</div>';

        Swal.fire({
            icon: 'success',
            title: '¡Préstamo Rápido Registrado con Éxito! 📤',
            html: html,
            showCancelButton: true,
            showDenyButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            reverseButtons: true,
            focusConfirm: false,
            confirmButtonColor: '#198754',
            denyButtonColor: '#f3b229',
            cancelButtonColor: '#6c757d',
            cancelButtonText: '<i class="fas fa-home me-1"></i> Volver al Inicio',
            denyButtonText: '<i class="fas fa-plus-circle me-1"></i> Registrar Otro Préstamo',
            confirmButtonText: '<i class="fas fa-clipboard-list me-1"></i> Ver Listado de Préstamos'
        }).then((res) => {
            if (res.isConfirmed) {
                window.location.href = resumenUrl;
                return;
            }
            if (res.isDenied) {
                window.location.href = urlOtro;
                return;
            }
            if (res.isDismissed) {
                const reason = String(res.dismiss || '');
                if (reason === 'cancel' || (typeof Swal !== 'undefined' && typeof Swal.DismissReason !== 'undefined' && reason === Swal.DismissReason.cancel)) {
                    window.location.href = urlDash;
                    return;
                }
                try {
                    if (!confirm('Ocurrió un evento inesperado en el diálogo. ¿Desea ir al inicio?')) return;
                } catch(e) {}
                window.location.href = urlDash;
                return;
            }
            window.location.href = urlDash;
        });
    }
    dispararPopupP();
})();
</script>
<?php endif; ?>

<?php if ($__popupPers):
    $persIdP      = intval($__popupPers['persona_id']    ?? 0);
    $persCedulaP  = htmlspecialchars($__popupPers['cedula']        ?? '');
    $persNombresP = htmlspecialchars($__popupPers['nombres']       ?? '');
    $persCargoP   = htmlspecialchars($__popupPers['cargo']         ?? '');
    $persCorreoP  = htmlspecialchars($__popupPers['correo']        ?? '');
    $persTelfP    = htmlspecialchars($__popupPers['telefono']      ?? '');
    $persObsP     = htmlspecialchars($__popupPers['observaciones'] ?? '');
    $resumenUrlP  = htmlspecialchars($__popupPers['resumen_url']   ?? ('/inventario_ti/modules/personas/listar.php'));
    $urlOtroP     = "/inventario_ti/modules/personas/agregar.php";
    $urlDashP     = "/inventario_ti/modules/dashboard.php";
    $toastMsgP    = htmlspecialchars($_SESSION['success'] ?? ('Persona agregada correctamente.' . ($persNombresP ? " Nombre: $persNombresP." : '') . ($persCedulaP ? " Cédula: $persCedulaP." : '')));
?>
<script>
(function(){
    function dispararPopupPers() {
        if (typeof Swal === 'undefined') { setTimeout(dispararPopupPers, 120); return; }
        const persId      = <?php echo $persIdP; ?>;
        const persCedula  = <?php echo json_encode($persCedulaP); ?>;
        const persNombres = <?php echo json_encode($persNombresP); ?>;
        const persCargo   = <?php echo json_encode($persCargoP); ?>;
        const persCorreo  = <?php echo json_encode($persCorreoP); ?>;
        const persTelf    = <?php echo json_encode($persTelfP); ?>;
        const persObs     = <?php echo json_encode($persObsP); ?>;
        const resumenUrl  = <?php echo json_encode($resumenUrlP); ?>;
        const urlOtro     = <?php echo json_encode($urlOtroP); ?>;
        const urlDash     = <?php echo json_encode($urlDashP); ?>;
        const toastMsg    = <?php echo json_encode($toastMsgP); ?>;

        try {
            Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 8000,
                timerProgressBar: true,
                allowOverlap: true,
                didOpen: function (toast) {
                    try {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    } catch (e) {}
                }
            }).fire({
                icon: 'success',
                title: '¡Persona Agregada!',
                text: toastMsg,
                background: '#5a2d8c',
                color: '#ffffff',
                iconColor: '#fde68a'
            });
        } catch (eToast) {}

        let html = '<div class="text-start">';
        html += '<div class="mb-3">';
        if (persCedula)  html += '<p class="mb-2"><strong>Cédula: </strong><span class="badge bg-purple-200 text-dark fs-6 me-2" style="background:#e9d5ff">' + persCedula + '</span> <span class="badge bg-secondary fs-6">ID #' + persId + '</span></p>';
        if (persNombres) html += '<p class="mb-2"><strong>Nombres: </strong>' + persNombres + '</p>';
        if (persCargo)   html += '<p class="mb-2"><strong>Cargo: </strong><span class="badge bg-info fs-6">' + persCargo + '</span></p>';
        if (persCorreo)  html += '<p class="mb-1 small"><strong>Correo: </strong>' + persCorreo + '</p>';
        if (persTelf)    html += '<p class="mb-1 small"><strong>Teléfono: </strong>' + persTelf + '</p>';
        if (persObs)     html += '<p class="mb-0 small"><strong>Observaciones: </strong>' + persObs + '</p>';
        html += '</div>';
        html += '<p class="small text-muted mt-3 mb-0"><i class="fas fa-info-circle me-1"></i> Persona registrada en el sistema. Puedes verla en el listado, editarla o asignarle equipos desde el módulo <strong>Personas</strong>.</p>';
        html += '</div>';

        Swal.fire({
            icon: 'success',
            title: '¡Persona Registrada con Éxito! 👤',
            html: html,
            showCancelButton: true,
            showDenyButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            reverseButtons: true,
            focusConfirm: false,
            confirmButtonColor: '#198754',
            denyButtonColor: '#f3b229',
            cancelButtonColor: '#6c757d',
            cancelButtonText: '<i class="fas fa-home me-1"></i> Volver al Inicio',
            denyButtonText: '<i class="fas fa-user-plus me-1"></i> Registrar Otra Persona',
            confirmButtonText: '<i class="fas fa-users-line me-1"></i> Ver Listado de Personas'
        }).then((res) => {
            if (res.isConfirmed) {
                window.location.href = resumenUrl;
                return;
            }
            if (res.isDenied) {
                window.location.href = urlOtro;
                return;
            }
            if (res.isDismissed) {
                const reason = String(res.dismiss || '');
                if (reason === 'cancel' || (typeof Swal !== 'undefined' && typeof Swal.DismissReason !== 'undefined' && reason === Swal.DismissReason.cancel)) {
                    window.location.href = urlDash;
                    return;
                }
                try {
                    if (!confirm('Ocurrió un evento inesperado en el diálogo. ¿Desea ir al inicio?')) return;
                } catch(e) {}
                window.location.href = urlDash;
                return;
            }
            window.location.href = urlDash;
        });
    }
    dispararPopupPers();
})();
</script>
<?php endif; ?>

<?php if ($__popupM):
    $tm = intval($__popupM['total'] ?? 0);
    $origenM = htmlspecialchars($__popupM['origen_nombre'] ?? '');
    $destinoM = htmlspecialchars($__popupM['destino_nombre'] ?? '');
    $acta_idM = intval($__popupM['acta_id'] ?? 0);
    $tieneActaM = ($acta_idM > 0);
    $multiple_flag = !empty($__popupM['multiple']);
    $nueva_persona_idM = intval($__popupM['nueva_persona_id'] ?? 0);
    $url_actaM = '';
    if ($tieneActaM) {
        $url_actaM = "/inventario_ti/api/generar_acta_unificada.php?acta_id=" . $acta_idM . ($multiple_flag ? '&multiple=1' : '');
    } elseif ($nueva_persona_idM > 0) {
        $url_actaM = "/inventario_ti/api/generar_acta_traspaso.php?multiple=1&nueva_persona_id=" . $nueva_persona_idM;
    }
    $url_otraM = "/inventario_ti/modules/movimientos/traspaso_multiple.php";
    $url_dashM = "/inventario_ti/modules/dashboard.php";
    $toastMsgM = htmlspecialchars($_SESSION['success'] ?? ("Traspaso múltiple exitoso. Se trasladaron $tm equipos correctamente."));
    $tituloPrincipalM = $multiple_flag ? '¡Traspaso Múltiple Realizado con Éxito! 🎉' : '¡Traspaso Realizado con Éxito! 🎉';
?>
<script>
(function(){
    function dispararPopupMultiple() {
        if (typeof Swal === 'undefined') { setTimeout(dispararPopupMultiple, 120); return; }
        const total = <?php echo $tm; ?>;
        const origen = <?php echo json_encode($origenM); ?>;
        const destino = <?php echo json_encode($destinoM); ?>;
        const urlActa = <?php echo json_encode($url_actaM); ?>;
        const tieneActa = <?php echo $tieneActaM ? 'true' : 'false'; ?>;
        const urlOtra = <?php echo json_encode($url_otraM); ?>;
        const urlDash = <?php echo json_encode($url_dashM); ?>;
        const toastMsg = <?php echo json_encode($toastMsgM); ?>;
        const tituloPpal = <?php echo json_encode($tituloPrincipalM); ?>;

        try {
            Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 8000,
                timerProgressBar: true,
                allowOverlap: true,
                didOpen: function (toast) {
                    try {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    } catch (e) {}
                }
            }).fire({
                icon: 'success',
                title: '¡Traspaso Múltiple Exitoso!',
                text: toastMsg,
                background: '#5a2d8c',
                color: '#ffffff',
                iconColor: '#fde68a'
            });
        } catch (eToast) {}

        const opts = {
            icon: 'success',
            title: tituloPpal,
            html:
                '<div class="text-start">' +
                '<div class="mb-3"><strong>Equipos trasladados: </strong><span class="badge bg-success fs-6">' + total + ' equipo' + (total === 1 ? '' : 's') + '</span></div>' +
                '<div class="mb-1"><strong>De (Custodios anteriores):</strong></div>' +
                '<div class="alert alert-light py-2 px-3 mb-3 border small">' + (origen || '-') + '</div>' +
                '<div class="mb-1"><strong>A (Nuevo custodio):</strong></div>' +
                '<div class="alert alert-warning py-2 px-3 mb-3 border border-warning bg-opacity-20 small">' + (destino || '-') + '</div>' +
                (tieneActa ? '<p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i> El acta unificada ya se guardó en el módulo <strong>Actas</strong>. Puedes imprimirla también desde <strong>Movimientos → Historial</strong>.</p>' : '<p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i> Resumen del movimiento guardado en Historial. También puedes generar el acta PDF en el siguiente paso.</p>') +
                '</div>',
            showCancelButton: true,
            showDenyButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            confirmButtonColor: '#198754',
            denyButtonColor: '#f3b229',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            focusConfirm: false,
            cancelButtonText: '<i class="fas fa-home me-1"></i> Volver al Inicio',
            denyButtonText: '<i class="fas fa-redo me-1"></i> Nuevo Traspaso Múltiple',
            confirmButtonText: tieneActa ? '<i class="fas fa-file-pdf me-1"></i> Generar / Imprimir Acta' : '<i class="fas fa-file-pdf me-1"></i> Generar Acta PDF',
            customClass: { popup: 'swal-popup-traspaso-multiple-exito' }
        };
        Swal.fire(opts).then(function(res){
            if (res.isConfirmed) {
                if (urlActa) {
                    try { window.open(urlActa, '_blank', 'noopener'); } catch(e) {}
                    setTimeout(function(){ window.location.href = urlOtra; }, 450);
                } else {
                    window.location.href = urlOtra;
                }
                return;
            }
            if (res.isDenied) {
                window.location.href = urlOtra;
                return;
            }
            if (res.isDismissed) {
                const reason = String(res.dismiss || '');
                if (reason === 'cancel' || (typeof Swal !== 'undefined' && typeof Swal.DismissReason !== 'undefined' && reason === Swal.DismissReason.cancel)) {
                    window.location.href = urlDash;
                    return;
                }
                try {
                    if (!confirm('Ocurrió un evento inesperado en el diálogo. ¿Desea ir al inicio?')) return;
                } catch(e) {}
                window.location.href = urlDash;
                return;
            }
            window.location.href = urlDash;
        });
    }
    dispararPopupMultiple();
})();
</script>
<?php endif; ?>

<?php if (isset($_SESSION['success']) && !$__popupT && !$__popupD && !$__popupP && !$__popupM && !$__popupPers): ?>
<script>
(function(){
    function dispararToastExito() {
        if (typeof Swal === 'undefined') { setTimeout(dispararToastExito, 120); return; }
        Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 8000,
            timerProgressBar: true,
            allowOverlap: true,
            didOpen: function (toast) {
                try {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                } catch(e) {}
            }
        }).fire({
            icon: 'success',
            title: '¡Éxito!',
            text: <?php echo json_encode($_SESSION['success']); ?>
        });
    }
    dispararToastExito();
})();
</script>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<script>
(function(){
    function dispararToastError() {
        if (typeof Swal === 'undefined') { setTimeout(dispararToastError, 120); return; }
        Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 12000,
            timerProgressBar: true,
            allowOverlap: true,
            didOpen: function (toast) {
                try {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                } catch(e) {}
            }
        }).fire({
            icon: 'error',
            title: 'Error',
            text: <?php echo json_encode($_SESSION['error']); ?>
        });
    }
    dispararToastError();
})();
</script>
<?php unset($_SESSION['error']); endif; ?>
</body>
</html>
