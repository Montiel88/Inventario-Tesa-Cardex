// Sistema de Inventario TESA - Funciones Globales

// Mostrar notificaciones con SweetAlert
function mostrarAlerta(tipo, titulo, mensaje) {
    Swal.fire({
        icon: tipo,
        title: titulo,
        text: mensaje,
        confirmButtonColor: '#f3b229',
        timer: 3000,
        timerProgressBar: true
    });
}

// Confirmar acción antes de ejecutar
function confirmarAccion(titulo, texto, callback) {
    Swal.fire({
        title: titulo,
        text: texto,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f3b229',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            callback();
        }
    });
}

// Formatear fecha para mostrar
function formatearFecha(fecha) {
    return new Date(fecha).toLocaleDateString('es-EC', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Validar cédula ecuatoriana
function validarCedula(cedula) {
    if (cedula.length !== 10) return false;
    
    // Aquí puedes implementar la validación completa
    // Por ahora solo validamos que sea numérica
    return /^\d+$/.test(cedula);
}

// Inicializar tooltips de Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Para debugging
console.log('🚀 Sistema de Inventario TESA cargado correctamente');