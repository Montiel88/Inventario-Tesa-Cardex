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

/* =========================================================
   Extensiones: Fetch con loading global + confirm + loading
   Requieren SweetAlert2 (cargado en footer) y assets/js/uploading.js
   ========================================================= */

// Confirmar y luego ejecutar fetch con loading bloqueante
function confirmarConLoading(tituloConfirm, textoConfirm, loadingTitulo, loadingTexto, callbackAsync) {
    return Swal.fire({
        title: tituloConfirm,
        text: textoConfirm,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#5a2d8c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then(async function (res) {
        if (!res.isConfirmed) return { confirmed: false };
        try {
            if (window.UXLoading && typeof window.UXLoading.mostrarGlobal === 'function') {
                await window.UXLoading.mostrarGlobal(loadingTitulo || 'Procesando...', loadingTexto || 'Por favor espera...');
            } else {
                Swal.fire({
                    title: loadingTitulo || 'Procesando...',
                    html: (loadingTexto || 'Por favor espera...') +
                          ' <div class="spinner-border mt-3 text-primary" role="status"></div>',
                    allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false,
                    didOpen: function () { Swal.showLoading(); }
                });
            }
            const resultado = await callbackAsync();
            if (window.UXLoading && typeof window.UXLoading.cerrarGlobal === 'function') {
                window.UXLoading.cerrarGlobal();
            } else {
                try { Swal.close(); } catch (e) {}
            }
            return { confirmed: true, data: resultado };
        } catch (err) {
            if (window.UXLoading && typeof window.UXLoading.cerrarGlobal === 'function') {
                window.UXLoading.cerrarGlobal();
            } else {
                try { Swal.close(); } catch (e) {}
            }
            Swal.fire({
                icon: 'error',
                title: 'Error inesperado',
                text: (err && err.message) ? err.message : 'Revisa tu conexión e intenta nuevamente.',
                confirmButtonColor: '#5a2d8c'
            });
            throw err;
        }
    });
}

// Helper: fetch seguro que retorna JSON y antes lee text() para debuggear errores HTML
async function fetchSeguro(url, opts) {
    const r = await fetch(url, opts || {});
    const t = await r.text();
    let json = null;
    try { json = JSON.parse(t); } catch (e) {
        const msg = 'Respuesta inválida del servidor: ' + t.substring(0, 300);
        throw new Error(msg);
    }
    return { response: r, text: t, data: json };
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
