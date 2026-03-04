<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin(); // Solo admin puede asignar equipos

require_once '../../config/database.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';

// Obtener persona_id de la URL si viene
$persona_id_seleccionada = isset($_GET['persona_id']) ? intval($_GET['persona_id']) : 0;

// Obtener lista de personas y ubicaciones
$personas = $conn->query("SELECT id, nombres FROM personas ORDER BY nombres");
$ubicaciones = $conn->query("SELECT id, codigo_ubicacion, nombre FROM ubicaciones ORDER BY nombre");

// ============================================
// LISTA COMPLETA DE TIPOS DE EQUIPOS
// ============================================
$tipos_equipos = [
    // 💻 COMPUTADORAS Y PORTÁTILES
    'Laptop' => '💻 Laptop',
    'Desktop' => '🖥️ Desktop (PC de escritorio)',
    'All-in-One' => '🖥️ All-in-One',
    'Tablet' => '📱 Tablet',
    'iPad' => '📱 iPad',
    'Chromebook' => '💻 Chromebook',
    
    // 🖥️ MONITORES Y PANTALLAS
    'Monitor' => '🖥️ Monitor',
    'Monitor Curvo' => '🖥️ Monitor Curvo',
    'Monitor 4K' => '🖥️ Monitor 4K',
    'Pantalla Interactiva' => '📺 Pantalla Interactiva',
    'TV' => '📺 Televisor',
    'Proyector' => '📽️ Proyector',
    'Pantalla de Proyección' => '📽️ Pantalla de Proyección',
    
    // 🖱️ PERIFÉRICOS
    'Mouse' => '🖱️ Mouse',
    'Mouse Inalámbrico' => '🖱️ Mouse Inalámbrico',
    'Teclado' => '⌨️ Teclado',
    'Teclado Inalámbrico' => '⌨️ Teclado Inalámbrico',
    'Kit Teclado + Mouse' => '⌨️🖱️ Kit Teclado + Mouse',
    'Parlantes' => '🔊 Parlantes',
    'Audífonos' => '🎧 Audífonos',
    'Micrófono' => '🎤 Micrófono',
    'Webcam' => '📹 Webcam',
    
    // 🖨️ IMPRESIÓN
    'Impresora' => '🖨️ Impresora',
    'Impresora Multifuncional' => '🖨️ Impresora Multifuncional',
    'Impresora Láser' => '🖨️ Impresora Láser',
    'Impresora de Tickets' => '🧾 Impresora de Tickets',
    'Plotter' => '📐 Plotter',
    'Scanner' => '📠 Escáner',
    'Multifuncional' => '🖨️📠 Multifuncional',
    
    // 📡 REDES Y CONECTIVIDAD
    'Router' => '📶 Router',
    'Switch' => '🔀 Switch',
    'Access Point' => '📡 Access Point',
    'Repetidor WiFi' => '📶 Repetidor WiFi',
    'Módem' => '📟 Módem',
    'Firewall' => '🛡️ Firewall',
    'Tarjeta de Red' => '🔌 Tarjeta de Red',
    'Cable de Red' => '🔌 Cable de Red (UTP)',
    'Conector RJ45' => '🔌 Conector RJ45',
    'Patch Panel' => '🔌 Patch Panel',
    
    // 📦 ALMACENAMIENTO
    'Disco Duro Externo' => '💾 Disco Duro Externo',
    'SSD Externo' => '⚡ SSD Externo',
    'USB Flash Drive' => '💽 USB Flash Drive',
    'Tarjeta SD' => '💾 Tarjeta SD',
    'NAS' => '📦 NAS (Almacenamiento en Red)',
    
    // 🔌 ACCESORIOS Y CABLES
    'Cable HDMI' => '🔌 Cable HDMI',
    'Cable VGA' => '🔌 Cable VGA',
    'Cable USB' => '🔌 Cable USB',
    'Cable de Corriente' => '🔌 Cable de Corriente',
    'Adaptador HDMI-VGA' => '🔌 Adaptador HDMI a VGA',
    'Adaptador USB-C' => '🔌 Adaptador USB-C',
    'Hub USB' => '🔌 Hub USB',
    'Regulador de Voltaje' => '⚡ Regulador de Voltaje',
    'UPS' => '🔋 UPS (Respaldo de Energía)',
    'Extensión Eléctrica' => '🔌 Extensión Eléctrica',
    'Multicontacto' => '🔌 Multicontacto',
    
    // 📱 DISPOSITIVOS MÓVILES
    'Celular' => '📱 Celular',
    'Smartphone' => '📱 Smartphone',
    'Tablet Gráfica' => '✏️ Tablet Gráfica',
    'Kindle' => '📚 Kindle',
    
    // 🎥 VIDEO Y FOTOGRAFÍA
    'Cámara' => '📷 Cámara',
    'Cámara IP' => '📹 Cámara IP',
    'Cámara de Seguridad' => '📹 Cámara de Seguridad',
    'Grabador de Video' => '📼 Grabador de Video',
    'Tripie' => '🎥 Trípode',
    
    // 🎓 EQUIPOS ESPECIALES
    'Pizarra Digital' => '📝 Pizarra Digital',
    'Calculadora Científica' => '🧮 Calculadora Científica',
    'Impresora 3D' => '🖨️ Impresora 3D',
    'Escáner 3D' => '📠 Escáner 3D',
    'Realidad Virtual' => '🥽 Realidad Virtual (VR)',
    'Dron' => '🚁 Dron',
    
    // 🔧 HERRAMIENTAS Y REPUESTOS
    'Multímetro' => '⚡ Multímetro',
    'Kit de Herramientas' => '🔧 Kit de Herramientas',
    'Pulsera Anti-estática' => '⚡ Pulsera Anti-estática',
    'Soporte para Monitor' => '🖥️ Soporte para Monitor',
    'Base Refrigerante' => '❄️ Base Refrigerante',
    
    // 🪑 MUEBLES Y ESTRUCTURAS
    'Silla Ergonómica' => '🪑 Silla Ergonómica',
    'Escritorio' => '🪑 Escritorio',
    'Rack de Servidor' => '📦 Rack de Servidor',
    'Gabinete' => '📦 Gabinete',
    'Stand para Proyector' => '📽️ Stand para Proyector',
    
    // 🎛️ EQUIPOS DE SONIDO
    'Consola de Sonido' => '🎛️ Consola de Sonido',
    'Amplificador' => '🔊 Amplificador',
    'Subwoofer' => '🔊 Subwoofer',
    'Micrófono Inalámbrico' => '🎤 Micrófono Inalámbrico',
    'Cabina de Sonido' => '🔊 Cabina de Sonido',
    
    // 📋 OTROS
    'Licencia de Software' => '💿 Licencia de Software',
    'Tarjeta de Acceso' => '💳 Tarjeta de Acceso',
    'Lector de Código de Barras' => '📟 Lector de Código de Barras',
    'Impresora de Etiquetas' => '🏷️ Impresora de Etiquetas',
    'Otro' => '🔧 Otro (especificar en observaciones)'
];
?>

<style>
    #scanner-container {
        display: none;
        margin: 20px 0;
        padding: 15px;
        border: 2px dashed #5a2d8c;
        border-radius: 10px;
        background: #f9f9f9;
    }
    
    #reader {
        width: 100%;
        min-height: 300px;
        background: #000;
        border-radius: 10px;
    }
    
    .scanner-btn {
        background: #5a2d8c;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        margin: 2px;
        transition: all 0.3s;
    }
    
    .scanner-btn:hover {
        background: #3d1e5e;
        transform: scale(1.05);
    }
    
    /* Estilo para el select con scroll */
    select[multiple] {
        height: 200px;
        padding: 8px;
    }
    
    select option {
        padding: 5px;
        border-bottom: 1px solid #eee;
    }
    
    select option:hover {
        background-color: #5a2d8c;
        color: white;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Asignar Equipo a Persona</h4>
                    <div>
                        <button class="scanner-btn" onclick="abrirScanner()">
                            <i class="fas fa-camera me-2"></i>Escanear Código
                        </button>
                        <button class="scanner-btn" onclick="ingresarManual()" style="background: #28a745;">
                            <i class="fas fa-keyboard me-2"></i>Manual
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- CONTENEDOR DEL ESCÁNER -->
                    <div id="scanner-container">
                        <div class="d-flex justify-content-between mb-2">
                            <h5><i class="fas fa-camera me-2"></i>Escáner de Códigos</h5>
                            <button class="btn btn-sm btn-danger" onclick="cerrarScanner()">
                                <i class="fas fa-times"></i> Cerrar
                            </button>
                        </div>
                        <div id="reader"></div>
                        <p class="text-center text-muted mt-2">
                            <small>Apunta al código QR o código de barras</small>
                        </p>
                    </div>
                    
                    <!-- Mensajes -->
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Formulario -->
                    <form method="POST" action="" id="formEquipo">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Persona *</label>
                                <select name="persona_id" class="form-control" required>
                                    <option value="">-- Seleccione una persona --</option>
                                    <?php while($p = $personas->fetch_assoc()): ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo ($persona_id_seleccionada == $p['id']) ? 'selected' : ''; ?>>
                                            <?php echo $p['nombres']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Equipo *</label>
                                <select name="tipo_equipo" class="form-control" required>
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach($tipos_equipos as $valor => $etiqueta): ?>
                                        <option value="<?php echo $valor; ?>"><?php echo $etiqueta; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Lista completa de equipos del área TI</small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-control" id="marca" placeholder="Ej: HP, Dell, Logitech, Epson">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" class="form-control" id="modelo" placeholder="Ej: Pavilion, Latitude, Pro, MX系列">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Código de Barras</label>
                                <div class="input-group">
                                    <input type="text" name="codigo_barras" id="codigo_barras" class="form-control" placeholder="Automático">
                                    <button class="btn btn-primary" type="button" onclick="abrirScanner()">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de Serie</label>
                                <input type="text" name="numero_serie" id="numero_serie" class="form-control" placeholder="Serie del fabricante">
                            </div>
                            
                            <!-- Campo de ubicación (YA CARGA TODAS LAS UBICACIONES) -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ubicación</label>
                                <select name="ubicacion_id" class="form-control">
                                    <option value="">-- Sin ubicación --</option>
                                    <?php while($ub = $ubicaciones->fetch_assoc()): ?>
                                        <option value="<?php echo $ub['id']; ?>">
                                            <?php echo $ub['codigo_ubicacion'] . ' - ' . $ub['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted">
                                    <a href="/inventario_ti/modules/ubicaciones/listar.php" target="_blank">
                                        <i class="fas fa-external-link-alt me-1"></i>Gestionar ubicaciones
                                    </a>
                                </small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Especificaciones</label>
                                <textarea name="especificaciones" class="form-control" rows="2" placeholder="RAM, disco, procesador, color, accesorios incluidos, etc." id="especificaciones"></textarea>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Asignar Equipo
                            </button>
                            <a href="/inventario_ti/modules/personas/listar.php" class="btn btn-secondary btn-lg px-5">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>¿No encuentras la ubicación?</strong> Puedes agregar nuevas ubicaciones (Torre A, Torre B, departamentos, etc.) en 
                        <a href="/inventario_ti/modules/ubicaciones/agregar.php" class="alert-link" target="_blank">Gestión de Ubicaciones</a>.
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>¿No encuentras el tipo de equipo?</strong> Si el equipo que necesitas no está en la lista, selecciona "Otro" y especifica en observaciones.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Librerías para escáner -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
// ============================================
// ESCÁNER INTELIGENTE
// ============================================
let html5QrCode = null;
let escaneando = false;

function abrirScanner() {
    if (escaneando) return;
    
    const container = document.getElementById('scanner-container');
    container.style.display = 'block';
    escaneando = true;
    
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('❌ Tu navegador no soporta cámara');
        cerrarScanner();
        return;
    }
    
    const config = {
        fps: 30,
        qrbox: { width: 300, height: 150 },
        formatsToSupport: [
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.CODE_93,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
            Html5QrcodeSupportedFormats.QR_CODE,
            Html5QrcodeSupportedFormats.DATA_MATRIX,
            Html5QrcodeSupportedFormats.PDF_417
        ]
    };
    
    Html5Qrcode.getCameras().then(cameras => {
        if (cameras.length === 0) {
            alert('❌ No hay cámaras disponibles');
            cerrarScanner();
            return;
        }
        
        const backCamera = cameras.find(c => 
            c.label.toLowerCase().includes('back') || 
            c.label.toLowerCase().includes('trasera') ||
            c.label.toLowerCase().includes('environment')
        );
        
        const cameraId = backCamera ? backCamera.id : cameras[0].id;
        
        html5QrCode = new Html5Qrcode("reader");
        
        html5QrCode.start(
            cameraId,
            config,
            (decodedText) => {
                try {
                    const datos = JSON.parse(decodedText);
                    if (datos.codigo) document.getElementById('codigo_barras').value = datos.codigo;
                    if (datos.serie) document.getElementById('numero_serie').value = datos.serie;
                    if (datos.marca) document.getElementById('marca').value = datos.marca;
                    if (datos.modelo) document.getElementById('modelo').value = datos.modelo;
                    if (datos.especificaciones) document.getElementById('especificaciones').value = datos.especificaciones;
                    alert('✅ Datos cargados desde QR');
                } catch (e) {
                    if (decodedText.startsWith('PRO-')) {
                        document.getElementById('codigo_barras').value = decodedText;
                        alert('✅ Código de barras: ' + decodedText);
                    } else {
                        document.getElementById('numero_serie').value = decodedText;
                        alert('✅ Número de serie: ' + decodedText);
                    }
                }
                
                cerrarScanner();
                if (navigator.vibrate) navigator.vibrate(100);
            },
            (errorMessage) => {}
        ).catch(err => {
            alert('❌ Error al iniciar: ' + err);
            cerrarScanner();
        });
        
    }).catch(err => {
        alert('❌ Error al acceder a cámara: ' + err);
        cerrarScanner();
    });
}

function cerrarScanner() {
    const container = document.getElementById('scanner-container');
    container.style.display = 'none';
    escaneando = false;
    
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            html5QrCode = null;
        }).catch(() => {});
    }
}

function ingresarManual() {
    let codigo = prompt('📝 Ingresa el código manualmente:');
    if (codigo) {
        if (codigo.startsWith('PRO-')) {
            document.getElementById('codigo_barras').value = codigo;
        } else {
            document.getElementById('numero_serie').value = codigo;
        }
    }
}

document.getElementById('formEquipo').addEventListener('submit', function(e) {
    const persona = document.querySelector('select[name="persona_id"]').value;
    const tipo = document.querySelector('select[name="tipo_equipo"]').value;
    
    if (!persona || !tipo) {
        e.preventDefault();
        alert('❌ Debe seleccionar una persona y tipo de equipo');
    }
});

console.log('✅ Escáner inteligente listo');
</script>

<?php include '../../includes/footer.php'; ?>