/**
 * Sistema de Alertas Automáticas
 * Consulta la API y muestra notificaciones si hay eventos pendientes.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar en el dashboard para no ser intrusivo en cada recarga de página
    // Verificamos si la URL contiene 'dashboard' o es la raíz del sistema
    const path = window.location.pathname;
    if (path.includes('dashboard.php') || path.endsWith('/inventario_ti/') || path.endsWith('/inventario_ti/index.php')) {
        console.log('Verificando alertas del sistema...');
        checkAlertas();
    }
});

function checkAlertas() {
    // Usamos fetch para consultar la API que acabamos de crear
    // Ajusta la ruta '../api/' según desde donde se cargue este JS, 
    // o usa una ruta absoluta si prefieres: '/inventario_ti/api/obtener_alertas.php'
    const apiUrl = '/inventario_ti/api/obtener_alertas.php';

    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (data.total > 0) {
                mostrarAlertaSecuencial(data.alertas, 0);
            }
        })
        .catch(error => console.log('Sin alertas pendientes o error de conexión'));
}

function mostrarAlertaSecuencial(alertas, index) {
    if (index >= alertas.length) return;

    const alerta = alertas[index];
    
    // Configuración base de SweetAlert2
    let swalConfig = {
        icon: alerta.tipo, // success, error, warning, info
        title: alerta.titulo,
        html: alerta.mensaje,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#5a2d8c', // Color TESA
        allowOutsideClick: false
    };

    // Si la alerta tiene un link, agregamos botón para ir
    if (alerta.link) {
        swalConfig.showCancelButton = true;
        swalConfig.confirmButtonText = 'Ver Detalles';
        swalConfig.cancelButtonText = 'Cerrar';
        swalConfig.reverseButtons = true;
    }

    Swal.fire(swalConfig).then((result) => {
        if (result.isConfirmed && alerta.link) {
            window.location.href = alerta.link;
        } else {
            // Mostrar la siguiente alerta si existe
            mostrarAlertaSecuencial(alertas, index + 1);
        }
    });
}
