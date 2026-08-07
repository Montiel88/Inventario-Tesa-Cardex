document.addEventListener('DOMContentLoaded', function() {
    const path = window.location.pathname;
    if (path.includes('dashboard.php') || path.endsWith('/inventario_ti/') || path.endsWith('/inventario_ti/index.php')) {
        checkAlertas();
    }
});

function checkAlertas() {
    if (typeof Swal === 'undefined') return;
    fetch('/inventario_ti/api/notificaciones.php')
        .then(response => response.json())
        .then(data => {
            const notificaciones = (data && Array.isArray(data.notificaciones)) ? data.notificaciones : [];
            if (notificaciones.length === 0) return;
            const alerta = notificaciones.find(n => n && (n.tipo === 'danger' || n.tipo === 'warning') && (n.leida === undefined || n.leida === 0));
            if (alerta) mostrarAlerta(alerta);
        })
        .catch(() => {});
}

function mostrarAlerta(alerta) {
    Swal.fire({
        icon: alerta.tipo === 'danger' ? 'error' : 'warning',
        title: alerta.titulo || 'Alerta',
        html: alerta.mensaje || '',
        confirmButtonText: 'Ver Detalles',
        confirmButtonColor: '#5a2d8c',
        allowOutsideClick: false,
        timer: 7000,
        timerProgressBar: true
    }).then((result) => {
        if (result.isConfirmed && alerta.url) {
            window.location.href = alerta.url;
        }
    });
}
