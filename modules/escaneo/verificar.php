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
                                               placeholder="Código de barras o QR">
                                        <button class="btn btn-primary" onclick="buscarPorCodigo()">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                    </div>
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
            
            // Configuración del escáner
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
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
// BUSCAR EQUIPO POR CÓDIGO
// ============================================
function buscarPorCodigo(codigo = null) {
    if (!codigo) {
        codigo = document.getElementById('codigo_manual').value;
    }
    
    if (!codigo || codigo.length < 3) {
        Swal.fire('Atención', 'Ingrese un código válido', 'warning');
        return;
    }
    
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
                
                titulo.innerHTML = `
                    <i class="fas fa-check-circle text-success me-2"></i>
                    Equipo Encontrado
                    <span class="badge bg-${estadoClase} ms-2">${estadoTexto}</span>
                `;
                
                let html = `
                    <table class="table table-sm">
                        <tr><th>Código:</th><td>${equipo.codigo_barras || 'N/A'}</td></tr>
                        <tr><th>Tipo:</th><td>${equipo.tipo_equipo || 'N/A'}</td></tr>
                        <tr><th>Marca:</th><td>${equipo.marca || 'N/A'}</td></tr>
                        <tr><th>Modelo:</th><td>${equipo.modelo || 'N/A'}</td></tr>
                        <tr><th>Serie:</th><td>${equipo.numero_serie || 'N/A'}</td></tr>
                `;
                
                if (equipo.persona_id) {
                    html += `
                        <tr class="table-warning">
                            <th>Prestado a:</th>
                            <td>${equipo.persona_nombre || 'Desconocido'}</td>
                        </tr>
                    `;
                    acciones.innerHTML = `
                        <button class="btn btn-success w-100" onclick="window.location.href='/inventario_ti/modules/movimientos/devolucion.php?equipo_id=${equipo.id}'">
                            <i class="fas fa-undo-alt me-2"></i>Registrar Devolución
                        </button>
                    `;
                } else {
                    acciones.innerHTML = `
                        <button class="btn btn-primary w-100" onclick="window.location.href='/inventario_ti/modules/movimientos/prestamo.php?equipo_id=${equipo.id}'">
                            <i class="fas fa-hand-holding me-2"></i>Registrar Préstamo
                        </button>
                    `;
                }
                
                html += `</table>`;
                contenido.innerHTML = html;
                
                // Guardar en historial
                guardarEnHistorial(codigo, equipo.tipo_equipo);
                
            } else {
                titulo.innerHTML = '<i class="fas fa-times-circle text-danger me-2"></i>Equipo No Encontrado';
                contenido.innerHTML = `<p class="text-danger">${data.mensaje || 'No existe'}</p>`;
                acciones.innerHTML = `
                    <button class="btn btn-warning w-100" onclick="window.location.href='/inventario_ti/modules/equipos/registro_rapido.php?codigo=${codigo}'">
                        <i class="fas fa-plus-circle me-2"></i>Registrar Nuevo Equipo
                    </button>
                `;
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Error:', error);
            Swal.fire('Error', 'Error de conexión', 'error');
        });
}

// ============================================
// GUARDAR EN HISTORIAL
// ============================================
function guardarEnHistorial(codigo, equipo) {
    historial.unshift({ codigo, equipo, fecha: new Date() });
    if (historial.length > 5) historial.pop();
    
    let html = '<h6 class="mt-3">📋 Últimos escaneos:</h6>';
    historial.forEach(item => {
        let hora = item.fecha.toLocaleTimeString();
        html += `
            <div class="alert alert-sm alert-secondary py-2 mb-1" style="cursor: pointer;" 
                 onclick="document.getElementById('codigo_manual').value='${item.codigo}'; buscarPorCodigo('${item.codigo}')">
                <small><strong>${item.codigo}</strong> - ${item.equipo}<br><span class="text-muted">${hora}</span></small>
            </div>
        `;
    });
    document.getElementById('historial').innerHTML = html;
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
</script>

<?php include '../../includes/footer.php'; ?>