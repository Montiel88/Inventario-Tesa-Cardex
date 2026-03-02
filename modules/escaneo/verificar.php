<?php
session_start();
require_once '../../config/database.php';
include '../../includes/header.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
?>

<!-- Agregar contenedor para el historial -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-qrcode me-2"></i>Verificar Equipo por Código</h4>
                </div>
                <div class="card-body">
                    
                    <!-- Estado de la cámara -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div id="estadoCamara" class="alert alert-info text-center">
                                <i class="fas fa-spinner fa-spin me-2"></i>Iniciando cámara...
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Columna izquierda: Escáner -->
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-camera me-2"></i>Escanear Código
                                    </h5>
                                    
                                    <!-- Contenedor del escáner -->
                                    <div id="reader" style="width: 100%; min-height: 300px; border: 2px dashed #5a2d8c; border-radius: 10px; overflow: hidden;"></div>
                                    
                                    <div class="mt-3 text-center">
                                        <button class="btn btn-sm btn-warning" onclick="reiniciarCamara()" id="btnReiniciar">
                                            <i class="fas fa-redo me-1"></i>Reiniciar cámara
                                        </button>
                                        <button class="btn btn-sm btn-info" onclick="cambiarCamara()" id="btnCambiar">
                                            <i class="fas fa-sync me-1"></i>Cambiar cámara
                                        </button>
                                    </div>
                                    
                                    <hr>
                                    
                                    <!-- Entrada manual -->
                                    <h5 class="mt-3">O ingresar manualmente</h5>
                                    <div class="input-group">
                                        <input type="text" id="codigo_manual" class="form-control" 
                                               placeholder="Código de barras o QR"
                                               onkeypress="if(event.key==='Enter') buscarPorCodigo()">
                                        <button class="btn btn-primary" onclick="buscarPorCodigo()">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                    </div>
                                    
                                    <!-- Historial de escaneos -->
                                    <div id="historial" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna derecha: Resultados -->
                        <div class="col-md-6">
                            <div id="resultado" style="display: none;">
                                <div class="card">
                                    <div class="card-header" id="resultado_titulo">
                                        <i class="fas fa-info-circle me-2"></i>Resultado
                                    </div>
                                    <div class="card-body">
                                        <div id="resultado_contenido"></div>
                                        <div id="resultado_acciones" class="mt-3"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mensaje cuando no hay búsqueda -->
                            <div id="sinResultado" class="text-center p-5">
                                <i class="fas fa-qrcode fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Escanea un código para ver los detalles del equipo</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts necesarios -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ============================================
// VARIABLES GLOBALES
// ============================================
let html5QrCode = null;
let cameraId = null;
let historial = [];

// ============================================
// FUNCIÓN PRINCIPAL PARA INICIAR CÁMARA
// ============================================
function iniciarCamara() {
    const estado = document.getElementById('estadoCamara');
    
    // Verificar si el navegador soporta getUserMedia
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        estado.innerHTML = '<div class="alert alert-danger">❌ Tu navegador no soporta acceso a cámara</div>';
        return;
    }
    
    estado.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Solicitando permiso de cámara...</div>';
    
    // Primero obtener la lista de cámaras
    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) {
            // Usar la cámara trasera si existe, sino la primera disponible
            cameraId = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('trasera'))?.id || devices[0].id;
            
            estado.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Iniciando cámara...</div>';
            
            // Crear instancia del escáner
            html5QrCode = new Html5Qrcode("reader");
            
            // Configuración del escáner MEJORADA
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.QR_CODE,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E
                ]
            };
            
            // Iniciar escáner
            html5QrCode.start(
                cameraId,
                config,
                (decodedText) => {
                    // Código escaneado exitosamente
                    document.getElementById('codigo_manual').value = decodedText;
                    buscarPorCodigo(decodedText);
                    
                    // Feedback visual
                    estado.innerHTML = '<div class="alert alert-success">✅ Código detectado</div>';
                    setTimeout(() => {
                        estado.innerHTML = '<div class="alert alert-success">📷 Cámara activa</div>';
                    }, 1000);
                    
                    // Vibración en móviles
                    if (navigator.vibrate) navigator.vibrate(100);
                },
                (errorMessage) => {
                    // Error al escanear (normal mientras busca)
                    // No mostramos estos errores al usuario
                }
            ).then(() => {
                estado.innerHTML = '<div class="alert alert-success">📷 Cámara activa</div>';
            }).catch((error) => {
                estado.innerHTML = `<div class="alert alert-danger">❌ Error: ${error}</div>`;
                console.error("Error al iniciar escáner:", error);
            });
        } else {
            estado.innerHTML = '<div class="alert alert-warning">⚠️ No se encontraron cámaras</div>';
        }
    }).catch(error => {
        estado.innerHTML = `<div class="alert alert-danger">❌ Error al acceder a cámaras: ${error}</div>`;
        console.error("Error al obtener cámaras:", error);
    });
}

// ============================================
// REINICIAR CÁMARA
// ============================================
function reiniciarCamara() {
    const estado = document.getElementById('estadoCamara');
    estado.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Reiniciando cámara...</div>';
    
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            html5QrCode = null;
            iniciarCamara();
        }).catch((error) => {
            console.error("Error al detener:", error);
            html5QrCode = null;
            iniciarCamara();
        });
    } else {
        iniciarCamara();
    }
}

// ============================================
// CAMBIAR ENTRE CÁMARAS
// ============================================
function cambiarCamara() {
    if (!html5QrCode) return;
    
    const estado = document.getElementById('estadoCamara');
    estado.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Cambiando cámara...</div>';
    
    html5QrCode.stop().then(() => {
        html5QrCode = null;
        
        // Obtener todas las cámaras nuevamente
        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length > 1) {
                // Si hay más de una, alternar
                let currentIndex = devices.findIndex(d => d.id === cameraId);
                let nextIndex = (currentIndex + 1) % devices.length;
                cameraId = devices[nextIndex].id;
            }
            iniciarCamara();
        });
    });
}

// ============================================
// BUSCAR EQUIPO POR CÓDIGO (VERSIÓN MEJORADA)
// ============================================
function buscarPorCodigo(codigo = null) {
    if (!codigo) {
        codigo = document.getElementById('codigo_manual').value;
    }
    
    if (!codigo || codigo.length < 3) {
        Swal.fire('Atención', 'Ingrese un código válido', 'warning');
        return;
    }
    
    // Limpiar el código (eliminar espacios)
    codigo = codigo.trim();
    
    Swal.fire({
        title: 'Buscando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    fetch(`/inventario_ti/api/buscar_producto.php?codigo=${encodeURIComponent(codigo)}`)
        .then(response => response.json())
        .then(data => {
            Swal.close();
            
            document.getElementById('resultado').style.display = 'block';
            document.getElementById('sinResultado').style.display = 'none';
            
            let titulo = document.getElementById('resultado_titulo');
            let contenido = document.getElementById('resultado_contenido');
            let acciones = document.getElementById('resultado_acciones');
            
            if (data.success) {
                let equipo = data.equipo;
                let estadoClase = equipo.persona_id ? 'warning' : 'success';
                let estadoTexto = equipo.persona_id ? 'PRESTADO' : 'DISPONIBLE';
                let personaInfo = equipo.persona_id ? equipo.persona_nombre : 'Nadie';
                
                titulo.innerHTML = `
                    <i class="fas fa-check-circle text-success me-2"></i>
                    Equipo Encontrado
                    <span class="badge bg-${estadoClase} ms-2">${estadoTexto}</span>
                `;
                
                let html = `
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <tr>
                                <th width="40%">Código:</th>
                                <td><strong>${equipo.codigo_barras || 'N/A'}</strong></td>
                            </tr>
                            <tr>
                                <th>Tipo:</th>
                                <td>${equipo.tipo_equipo || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Marca:</th>
                                <td>${equipo.marca || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Modelo:</th>
                                <td>${equipo.modelo || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Número de Serie:</th>
                                <td><span class="badge bg-secondary">${equipo.numero_serie || 'N/A'}</span></td>
                            </tr>
                            <tr>
                                <th>Especificaciones:</th>
                                <td>${equipo.especificaciones || 'Sin especificaciones'}</td>
                            </tr>
                            <tr class="table-${estadoClase}">
                                <th>Estado:</th>
                                <td><strong>${estadoTexto}</strong></td>
                            </tr>
                `;
                
                if (equipo.persona_id) {
                    html += `
                        <tr>
                            <th>Prestado a:</th>
                            <td>
                                <a href="/inventario_ti/modules/personas/detalle.php?id=${equipo.persona_id}" class="text-decoration-none">
                                    <i class="fas fa-user me-1"></i>${equipo.persona_nombre || 'Desconocido'}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Fecha préstamo:</th>
                            <td>${equipo.fecha_asignacion ? new Date(equipo.fecha_asignacion).toLocaleDateString('es-EC', {year: 'numeric', month: 'long', day: 'numeric'}) : 'Desconocida'}</td>
                        </tr>
                    `;
                }
                
                html += `</table></div>`;
                
                if (equipo.persona_id) {
                    acciones.innerHTML = `
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-success w-100" onclick="window.location.href='/inventario_ti/modules/movimientos/devolucion.php?equipo_id=${equipo.id}'">
                                    <i class="fas fa-undo-alt me-2"></i>Devolución
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-info w-100" onclick="window.location.href='/inventario_ti/modules/personas/detalle.php?id=${equipo.persona_id}'">
                                    <i class="fas fa-user me-2"></i>Ver Persona
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    acciones.innerHTML = `
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-primary w-100" onclick="window.location.href='/inventario_ti/modules/movimientos/prestamo.php?equipo_id=${equipo.id}'">
                                    <i class="fas fa-hand-holding me-2"></i>Prestar
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-warning w-100" onclick="window.location.href='/inventario_ti/modules/equipos/editar.php?id=${equipo.id}'">
                                    <i class="fas fa-edit me-2"></i>Editar
                                </button>
                            </div>
                            <div class="col-12 mt-2">
                                <button class="btn btn-secondary w-100" onclick="window.location.href='/inventario_ti/api/generar_qr_equipo.php?id=${equipo.id}'">
                                    <i class="fas fa-qrcode me-2"></i>Descargar QR
                                </button>
                            </div>
                        </div>
                    `;
                }
                
                contenido.innerHTML = html;
                
                // Guardar en historial
                guardarEnHistorial(codigo, equipo.tipo_equipo + ' - ' + (equipo.numero_serie || ''));
                
            } else {
                titulo.innerHTML = '<i class="fas fa-times-circle text-danger me-2"></i>Equipo No Encontrado';
                contenido.innerHTML = `<p class="text-danger">${data.mensaje || 'No existe el equipo'}</p>`;
                acciones.innerHTML = `
                    <div class="row">
                        <div class="col-12">
                            <button class="btn btn-warning w-100" onclick="window.location.href='/inventario_ti/modules/equipos/registro_rapido.php?codigo=${encodeURIComponent(codigo)}'">
                                <i class="fas fa-plus-circle me-2"></i>Registrar Nuevo Equipo
                            </button>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Error:', error);
            Swal.fire('Error', 'Error de conexión con el servidor', 'error');
        });
}

// ============================================
// GUARDAR EN HISTORIAL (MEJORADO)
// ============================================
function guardarEnHistorial(codigo, equipo) {
    historial.unshift({ codigo, equipo, fecha: new Date() });
    if (historial.length > 5) historial.pop();
    
    let html = '<h6 class="mt-3"><i class="fas fa-history me-2"></i>Últimos escaneos:</h6>';
    historial.forEach((item, index) => {
        let hora = item.fecha.toLocaleTimeString();
        let fecha = item.fecha.toLocaleDateString();
        html += `
            <div class="alert alert-sm alert-secondary py-2 mb-1 d-flex justify-content-between align-items-center" 
                 style="cursor: pointer; border-left: 3px solid #5a2d8c;"
                 onclick="document.getElementById('codigo_manual').value='${item.codigo}'; buscarPorCodigo('${item.codigo}')">
                <div>
                    <small><strong>${item.codigo}</strong><br>${item.equipo}</small>
                </div>
                <small class="text-muted">${fecha}<br>${hora}</small>
            </div>
        `;
    });
    
    // Verificar si existe el elemento historial
    let historialDiv = document.getElementById('historial');
    if (historialDiv) {
        historialDiv.innerHTML = html;
    }
}

// ============================================
// INICIAR CÁMARA AL CARGAR
// ============================================
window.onload = function() {
    iniciarCamara();
};

// ============================================
// LIMPIEZA AL SALIR
// ============================================
window.addEventListener('beforeunload', function() {
    if (html5QrCode) {
        html5QrCode.stop().catch(() => {});
    }
});

// ============================================
// MANEJAR ENTER EN EL INPUT
// ============================================
document.getElementById('codigo_manual').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        buscarPorCodigo();
    }
});
</script>

<style>
/* Estilos adicionales para el historial */
#historial .alert {
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

#historial .alert:hover {
    background-color: #e9ecef !important;
    transform: translateX(5px);
}

/* Responsive */
@media (max-width: 768px) {
    #reader {
        min-height: 250px !important;
    }
    
    .btn-sm {
        padding: 4px 8px;
        font-size: 0.8rem;
    }
    
    .table-sm td, .table-sm th {
        font-size: 0.85rem;
        padding: 0.5rem;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>