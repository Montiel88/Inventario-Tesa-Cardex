<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin(); // Solo admin puede usar registro rápido

require_once '../../config/database.php';
include '../../includes/header.php';
?>

<!-- Agregar SweetAlert si no está incluido -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-bolt me-2"></i>Registro Rápido de Equipos</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Paso 1: Escanear código</h5>
                    
                    <!-- Botones de control de cámara -->
                    <div class="mb-3 text-center">
                        <button class="btn btn-sm btn-success" onclick="iniciarEscaneo()" id="btnIniciar">
                            <i class="fas fa-play me-1"></i>Iniciar Cámara
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="detenerEscaneo()" id="btnDetener" style="display:none;">
                            <i class="fas fa-stop me-1"></i>Detener Cámara
                        </button>
                        <button class="btn btn-sm btn-info" onclick="cambiarCamara()" id="btnCambiar" style="display:none;">
                            <i class="fas fa-sync me-1"></i>Cambiar Cámara
                        </button>
                    </div>
                    
                    <!-- Contenedor del escáner -->
                    <div id="reader" style="width: 100%; min-height: 300px; border: 2px dashed #5a2d8c; border-radius: 10px; overflow: hidden; background: #f8f9fa;"></div>
                    
                    <div class="mt-3">
                        <label><i class="fas fa-qrcode me-2"></i>Código escaneado:</label>
                        <div class="input-group">
                            <input type="text" id="codigo_barras" class="form-control" readonly placeholder="Esperando escaneo...">
                            <button class="btn btn-outline-secondary" type="button" onclick="copiarCodigo()" title="Copiar código">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button class="btn btn-outline-danger" type="button" onclick="limpiarCodigo()" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-2 text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            También puedes ingresar manualmente el código en el campo de abajo
                        </small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h5>Paso 2: Completar datos</h5>
                    <form id="formEquipo">
                        <input type="hidden" name="codigo_barras" id="hidden_codigo">
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo de equipo <span class="text-danger">*</span></label>
                            <select name="tipo_equipo" id="tipo_equipo" class="form-select" required>
                                <option value="">-- Seleccione --</option>
                                <option value="Laptop">💻 Laptop</option>
                                <option value="Mouse">🖱️ Mouse</option>
                                <option value="Teclado">⌨️ Teclado</option>
                                <option value="Monitor">🖥️ Monitor</option>
                                <option value="Impresora">🖨️ Impresora</option>
                                <option value="Proyector">📽️ Proyector</option>
                                <option value="Tablet">📱 Tablet</option>
                                <option value="Celular">📞 Celular</option>
                                <option value="Parlantes">🔊 Parlantes</option>
                                <option value="Cámara">📷 Cámara</option>
                                <option value="Otro">🔧 Otro</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" id="marca" class="form-control" placeholder="Ej: HP, Dell, Logitech">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" id="modelo" class="form-control" placeholder="Ej: Pavilion, Latitude">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Número de serie <span class="text-danger">*</span></label>
                            <input type="text" name="numero_serie" id="numero_serie" class="form-control" 
                                   placeholder="Ej: SN-123456, XCV-7890" required>
                            <small class="text-muted">Identificador único del fabricante</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Especificaciones</label>
                            <textarea name="especificaciones" id="especificaciones" class="form-control" 
                                      rows="3" placeholder="RAM, procesador, disco duro, etc."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" id="observaciones" class="form-control" 
                                      rows="2" placeholder="Notas adicionales (opcional)"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100" id="btnRegistrar">
                            <i class="fas fa-save me-2"></i>Registrar Equipo
                        </button>
                    </form>
                    
                    <div id="mensaje" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para el escáner mejorado -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
// ============================================
// VARIABLES GLOBALES
// ============================================
let html5QrCode = null;
let cameraId = null;
let escaneando = false;

// ============================================
// INICIAR ESCANEO
// ============================================
function iniciarEscaneo() {
    const reader = document.getElementById('reader');
    reader.innerHTML = ''; // Limpiar contenedor
    
    // Verificar soporte
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        Swal.fire('Error', 'Tu navegador no soporta acceso a cámara', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Iniciando cámara',
        text: 'Por favor espere...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    // Obtener cámaras disponibles
    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length > 0) {
            // Usar cámara trasera si existe
            cameraId = devices.find(d => 
                d.label.toLowerCase().includes('back') || 
                d.label.toLowerCase().includes('trasera') ||
                d.label.toLowerCase().includes('environment')
            )?.id || devices[0].id;
            
            html5QrCode = new Html5Qrcode("reader");
            
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.QR_CODE,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8
                ]
            };
            
            html5QrCode.start(
                cameraId,
                config,
                (decodedText) => {
                    // Código escaneado exitosamente
                    document.getElementById('codigo_barras').value = decodedText;
                    document.getElementById('hidden_codigo').value = decodedText;
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Código detectado!',
                        text: decodedText,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    // Vibrar en móviles
                    if (navigator.vibrate) navigator.vibrate(100);
                    
                    // Detener escáner automáticamente
                    detenerEscaneo();
                },
                (errorMessage) => {
                    // Error al escanear (normal)
                }
            ).then(() => {
                Swal.close();
                escaneando = true;
                document.getElementById('btnIniciar').style.display = 'none';
                document.getElementById('btnDetener').style.display = 'inline-block';
                document.getElementById('btnCambiar').style.display = 'inline-block';
            }).catch((error) => {
                Swal.fire('Error', 'No se pudo iniciar la cámara: ' + error, 'error');
            });
        } else {
            Swal.fire('Error', 'No se encontraron cámaras disponibles', 'error');
        }
    }).catch(error => {
        Swal.fire('Error', 'Error al acceder a cámaras: ' + error, 'error');
    });
}

// ============================================
// DETENER ESCANEO
// ============================================
function detenerEscaneo() {
    if (html5QrCode && escaneando) {
        html5QrCode.stop().then(() => {
            escaneando = false;
            document.getElementById('btnIniciar').style.display = 'inline-block';
            document.getElementById('btnDetener').style.display = 'none';
            document.getElementById('btnCambiar').style.display = 'none';
            
            // Mostrar mensaje en el lector
            document.getElementById('reader').innerHTML = '<div class="text-center p-5"><i class="fas fa-camera fa-3x text-muted mb-3"></i><p>Cámara detenida</p></div>';
        }).catch((error) => {
            console.error("Error al detener:", error);
        });
    }
}

// ============================================
// CAMBIAR CÁMARA
// ============================================
function cambiarCamara() {
    if (!html5QrCode || !escaneando) return;
    
    detenerEscaneo();
    setTimeout(() => {
        iniciarEscaneo();
    }, 500);
}

// ============================================
// COPIAR CÓDIGO
// ============================================
function copiarCodigo() {
    let codigo = document.getElementById('codigo_barras').value;
    if (codigo) {
        navigator.clipboard.writeText(codigo).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Copiado',
                text: 'Código copiado al portapapeles',
                timer: 1000,
                showConfirmButton: false
            });
        });
    }
}

// ============================================
// LIMPIAR CÓDIGO
// ============================================
function limpiarCodigo() {
    document.getElementById('codigo_barras').value = '';
    document.getElementById('hidden_codigo').value = '';
}

// ============================================
// VALIDAR Y ENVIAR FORMULARIO
// ============================================
document.getElementById('formEquipo').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validar código
    let codigo = document.getElementById('hidden_codigo').value;
    if (!codigo) {
        Swal.fire('Error', 'Primero debe escanear o ingresar un código', 'warning');
        return;
    }
    
    // Validar tipo de equipo
    let tipo = document.getElementById('tipo_equipo').value;
    if (!tipo) {
        Swal.fire('Error', 'Seleccione el tipo de equipo', 'warning');
        return;
    }
    
    // Validar número de serie
    let serie = document.getElementById('numero_serie').value.trim();
    if (!serie) {
        Swal.fire('Error', 'El número de serie es obligatorio', 'warning');
        return;
    }
    
    // Recopilar datos
    let datos = {
        codigo_barras: codigo,
        tipo_equipo: tipo,
        marca: document.getElementById('marca').value.trim(),
        modelo: document.getElementById('modelo').value.trim(),
        numero_serie: serie,
        especificaciones: document.getElementById('especificaciones').value.trim(),
        observaciones: document.getElementById('observaciones').value.trim()
    };
    
    // Mostrar carga
    Swal.fire({
        title: 'Registrando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    // Enviar datos
    fetch('/inventario_ti/api/registrar_equipo_rapido.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Registrado!',
                text: 'Equipo guardado correctamente',
                showConfirmButton: true
            }).then(() => {
                // Limpiar formulario
                document.getElementById('formEquipo').reset();
                document.getElementById('codigo_barras').value = '';
                document.getElementById('hidden_codigo').value = '';
                
                // Preguntar si quiere seguir registrando
                Swal.fire({
                    title: '¿Continuar?',
                    text: '¿Desea registrar otro equipo?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, registrar otro',
                    cancelButtonText: 'Ir al listado'
                }).then((result) => {
                    if (result.isConfirmed) {
                        iniciarEscaneo();
                    } else {
                        window.location.href = 'listar.php';
                    }
                });
            });
        } else {
            Swal.fire('Error', data.mensaje || 'Error al registrar', 'error');
        }
    })
    .catch(error => {
        Swal.fire('Error', 'Error de conexión: ' + error, 'error');
    });
});

// ============================================
// INGRESO MANUAL
// ============================================
document.getElementById('codigo_barras').addEventListener('dblclick', function() {
    Swal.fire({
        title: 'Ingresar código manualmente',
        input: 'text',
        inputLabel: 'Código de barras',
        inputPlaceholder: 'Ingrese el código',
        showCancelButton: true,
        confirmButtonText: 'Aceptar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            document.getElementById('codigo_barras').value = result.value;
            document.getElementById('hidden_codigo').value = result.value;
        }
    });
});

// ============================================
// LIMPIEZA AL SALIR
// ============================================
window.addEventListener('beforeunload', function() {
    if (html5QrCode && escaneando) {
        html5QrCode.stop().catch(() => {});
    }
});

// ============================================
// INICIAR AUTOMÁTICAMENTE
// ============================================
window.onload = function() {
    setTimeout(() => {
        iniciarEscaneo();
    }, 500);
};
</script>

<style>
/* Estilos adicionales */
#reader {
    background: #f8f9fa;
    transition: all 0.3s ease;
}

#reader canvas {
    border-radius: 10px;
    width: 100% !important;
}

.btn-sm i {
    margin-right: 4px;
}

/* Responsive */
@media (max-width: 768px) {
    #reader {
        min-height: 250px !important;
    }
    
    .btn-lg {
        font-size: 1rem;
        padding: 10px;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>