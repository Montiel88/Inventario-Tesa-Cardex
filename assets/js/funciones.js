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
    if (cedula.length !== 10 || !/^\d+$/.test(cedula)) return false;
    const digitoVerificador = parseInt(cedula[9]);
    let suma = 0;
    for (let i = 0; i < 9; i++) {
        let digito = parseInt(cedula[i]);
        if (i % 2 === 0) {
            digito *= 2;
            if (digito > 9) digito -= 9;
        }
        suma += digito;
    }
    const resto = suma % 10;
    const resultado = resto === 0 ? 0 : 10 - resto;
    return resultado === digitoVerificador;
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
