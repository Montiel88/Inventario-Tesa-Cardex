/**
 * Sistema de Notificaciones Toast Dinámicas
 * Muestra notificaciones emergentes temporales para feedback de operaciones
 */

// Cola de notificaciones
let toastQueue = [];
let toastContainer = null;

/**
 * Inicializa el contenedor de toasts en el DOM
 */
function initToastContainer() {
    if (!document.getElementById('toastContainer')) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
        toastContainer = container;
    } else {
        toastContainer = document.getElementById('toastContainer');
    }
}

/**
 * Muestra una notificación toast
 * @param {string} tipo - 'success', 'error', 'warning', 'info'
 * @param {string} titulo - Título de la notificación
 * @param {string} mensaje - Mensaje detallado
 * @param {string} url - URL opcional para redirigir al hacer click
 * @param {number} duracion - Duración en ms (default 5000)
 */
function mostrarToast(tipo, titulo, mensaje, url = null, duracion = 5000) {
    initToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast-notification ${tipo}`;
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    
    const iconos = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const icono = iconos[tipo] || iconos.info;
    
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${icono}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${titulo}</div>
            <div class="toast-message">${mensaje}</div>
            ${url ? `<div class="toast-action"><i class="fas fa-arrow-right"></i> Click para ver</div>` : ''}
        </div>
        <button class="toast-close" onclick="cerrarToast(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Hacer clickeable si tiene URL
    if (url) {
        toast.style.cursor = 'pointer';
        toast.onclick = function(e) {
            if (!e.target.closest('.toast-close')) {
                window.location.href = url;
            }
        };
    }
    
    toastContainer.appendChild(toast);
    
    // Animación de entrada
    setTimeout(() => {
        toast.style.transition = 'all 0.3s ease';
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto-cerrar
    if (duracion > 0) {
        setTimeout(() => {
            cerrarToast(toast.querySelector('.toast-close'));
        }, duracion);
    }
}

/**
 * Cierra una notificación toast
 */
function cerrarToast(element) {
    const toast = element.closest('.toast-notification');
    if (toast) {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }
}

/**
 * Notificaciones específicas para operaciones del sistema
 */

// Éxito: Equipo asignado
function notificarEquipoAsignado(equipo, persona, url = null) {
    mostrarToast(
        'success',
        '✅ Equipo Asignado Correctamente',
        `<strong>${equipo}</strong> ha sido asignado a <strong>${persona}</strong>`,
        url || '/inventario_ti/modules/asignaciones/listar.php',
        5000
    );
}

// Error: Falló asignación
function notificarErrorAsignacion(error) {
    mostrarToast(
        'error',
        '❌ Error al Asignar Equipo',
        error || 'No se pudo completar la asignación. Verifica los datos e intenta nuevamente.',
        null,
        6000
    );
}

// Éxito: Persona creada/actualizada
function notificarPersonaCreada(nombre, url = null) {
    mostrarToast(
        'success',
        '✅ Persona Añadida Correctamente',
        `<strong>${nombre}</strong> ha sido registrado en el sistema`,
        url || '/inventario_ti/modules/personas/listar.php',
        5000
    );
}

// Éxito: Persona actualizada
function notificarPersonaActualizada(nombre) {
    mostrarToast(
        'success',
        '✅ Persona Actualizada',
        `Los datos de <strong>${nombre}</strong> han sido actualizados`,
        '/inventario_ti/modules/personas/listar.php',
        4000
    );
}

// Éxito: Componente asignado
function notificarComponenteAsignado(componente, equipo) {
    mostrarToast(
        'success',
        '✅ Componente Asignado',
        `<strong>${componente}</strong> asignado a <strong>${equipo}</strong>`,
        '/inventario_ti/modules/componentes/listar.php',
        5000
    );
}

// Error: Componente falló
function notificarErrorComponente(error) {
    mostrarToast(
        'error',
        '❌ Error con Componente',
        error || 'No se pudo asignar el componente',
        null,
        5000
    );
}

// Éxito: Correo enviado
function notificarCorreoEnviado(destinatario, asunto) {
    mostrarToast(
        'success',
        '✉️ Correo Enviado Correctamente',
        `Enviado a <strong>${destinatario}</strong><br><small>${asunto}</small>`,
        '/inventario_ti/modules/correos/historial.php',
        5000
    );
}

// Error: Correo falló
function notificarErrorCorreo(error, destinatario) {
    mostrarToast(
        'error',
        '❌ Error al Enviar Correo',
        `Destinatario: <strong>${destinatario}</strong><br>${error || 'Verifica la configuración SMTP'}`,
        '/inventario_ti/modules/correos/historial.php',
        7000
    );
}

// Éxito: Equipo creado/actualizado
function notificarEquipoCreado(codigo) {
    mostrarToast(
        'success',
        '✅ Equipo Registrado',
        `Equipo <strong>${codigo}</strong> añadido al inventario`,
        '/inventario_ti/modules/equipos/listar.php',
        4000
    );
}

// Advertencia: Stock bajo
function notificarStockBajo(equipo, cantidad) {
    mostrarToast(
        'warning',
        '⚠️ Stock Bajo',
        `${equipo}: Solo quedan <strong>${cantidad}</strong> unidades`,
        '/inventario_ti/modules/equipos/listar.php',
        6000
    );
}

// Info: Mantenimiento programado
function notificarMantenimientoProgramado(equipo, fecha) {
    mostrarToast(
        'info',
        '🔧 Mantenimiento Programado',
        `${equipo} - Fecha: <strong>${fecha}</strong>`,
        '/inventario_ti/modules/mantenimientos/listar.php',
        5000
    );
}

// Éxito: Préstamo registrado
function notificarPrestamoRegistrado(equipo, persona) {
    mostrarToast(
        'success',
        '✅ Préstamo Registrado',
        `${equipo} prestado a <strong>${persona}</strong>`,
        '/inventario_ti/modules/prestamos/listar.php',
        5000
    );
}

// Éxito: Devolución registrada
function notificarDevolucionRegistrada(equipo, persona) {
    mostrarToast(
        'success',
        '✅ Devolución Registrada',
        `${equipo} devuelto por <strong>${persona}</strong>`,
        '/inventario_ti/modules/prestamos/listar.php',
        5000
    );
}

// Advertencia: Préstamo vencido
function notificarPrestamoVencido(equipo, persona, dias) {
    mostrarToast(
        'warning',
        '⏰ Préstamo Vencido',
        `${equipo} - ${persona} tiene <strong>${dias} días</strong> de retraso`,
        '/inventario_ti/modules/asignaciones/listar.php',
        7000
    );
}

// Éxito: Usuario creado
function notificarUsuarioCreado(usuario) {
    mostrarToast(
        'success',
        '✅ Usuario Creado',
        `Usuario <strong>${usuario}</strong> registrado en el sistema`,
        '/inventario_ti/modules/usuarios/listar.php',
        4000
    );
}

// Error genérico
function notificarError(mensaje, url = null) {
    mostrarToast(
        'error',
        '❌ Error',
        mensaje,
        url,
        6000
    );
}

// Éxito genérico
function notificarExito(mensaje, url = null) {
    mostrarToast(
        'success',
        '✅ Éxito',
        mensaje,
        url,
        4000
    );
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initToastContainer();
});

// Exportar funciones para uso global
window.mostrarToast = mostrarToast;
window.cerrarToast = cerrarToast;
window.notificarEquipoAsignado = notificarEquipoAsignado;
window.notificarErrorAsignacion = notificarErrorAsignacion;
window.notificarPersonaCreada = notificarPersonaCreada;
window.notificarPersonaActualizada = notificarPersonaActualizada;
window.notificarComponenteAsignado = notificarComponenteAsignado;
window.notificarErrorComponente = notificarErrorComponente;
window.notificarCorreoEnviado = notificarCorreoEnviado;
window.notificarErrorCorreo = notificarErrorCorreo;
window.notificarEquipoCreado = notificarEquipoCreado;
window.notificarStockBajo = notificarStockBajo;
window.notificarMantenimientoProgramado = notificarMantenimientoProgramado;
window.notificarPrestamoRegistrado = notificarPrestamoRegistrado;
window.notificarDevolucionRegistrada = notificarDevolucionRegistrada;
window.notificarPrestamoVencido = notificarPrestamoVencido;
window.notificarUsuarioCreado = notificarUsuarioCreado;
window.notificarError = notificarError;
window.notificarExito = notificarExito;
