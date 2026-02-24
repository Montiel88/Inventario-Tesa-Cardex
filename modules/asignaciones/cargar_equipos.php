<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
require_once '../../config/database.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';

// Obtener persona_id de la URL si viene
$persona_id_seleccionada = isset($_GET['persona_id']) ? intval($_GET['persona_id']) : 0;

// ============================================
// PROCESAR EL FORMULARIO
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $tipo_equipo = $_POST['tipo_equipo'] ?? '';
    $marca = $_POST['marca'] ?? '';
    $modelo = $_POST['modelo'] ?? '';
    $serie = $_POST['numero_serie'] ?? '';
    $codigo_barras = $_POST['codigo_barras'] ?? '';
    $especificaciones = $_POST['especificaciones'] ?? '';
    $persona_id = intval($_POST['persona_id'] ?? 0);
    
    if (empty($tipo_equipo)) {
        $error = "❌ El tipo de equipo es obligatorio";
    } elseif ($persona_id == 0) {
        $error = "❌ Debe seleccionar una persona";
    } else {
        
        if (empty($codigo_barras)) {
            $result = $conn->query("SELECT MAX(id) as max_id FROM equipos");
            $row = $result->fetch_assoc();
            $next_id = ($row['max_id'] ?? 0) + 1;
            $codigo_barras = 'PRO-' . str_pad($next_id, 6, '0', STR_PAD_LEFT);
        }
        
        $sql_equipo = "INSERT INTO equipos (codigo_barras, tipo_equipo, marca, modelo, numero_serie, especificaciones, estado) 
                       VALUES ('$codigo_barras', '$tipo_equipo', '$marca', '$modelo', '$serie', '$especificaciones', 'Asignado')";
        
        if ($conn->query($sql_equipo)) {
            $equipo_id = $conn->insert_id;
            
            $sql_asignacion = "INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion) 
                              VALUES ($equipo_id, $persona_id, NOW())";
            
            if ($conn->query($sql_asignacion)) {
                $sql_movimiento = "INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento) 
                                 VALUES ($equipo_id, $persona_id, 'ASIGNACION')";
                $conn->query($sql_movimiento);
                
                $mensaje = "✅ Equipo asignado correctamente. Código: $codigo_barras";
            } else {
                $error = "❌ Error al asignar: " . $conn->error;
            }
        } else {
            $error = "❌ Error al guardar equipo: " . $conn->error;
        }
    }
}

$personas = $conn->query("SELECT id, nombres FROM personas ORDER BY nombres");
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
                                    <option value="Laptop">💻 Laptop</option>
                                    <option value="Mouse">🖱️ Mouse</option>
                                    <option value="Teclado">⌨️ Teclado</option>
                                    <option value="Monitor">🖥️ Monitor</option>
                                    <option value="Impresora">🖨️ Impresora</option>
                                    <option value="Proyector">📽️ Proyector</option>
                                    <option value="Tablet">📱 Tablet</option>
                                    <option value="Parlantes">🔊 Parlantes</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-control" id="marca" placeholder="Ej: HP, Dell">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" class="form-control" id="modelo" placeholder="Ej: Pavilion">
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
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Especificaciones</label>
                                <textarea name="especificaciones" class="form-control" rows="2" placeholder="RAM, disco, etc." id="especificaciones"></textarea>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Asignar Equipo
                            </button>
                            <a href="listar.php" class="btn btn-secondary btn-lg px-5">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Librerías para escáner -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
// ============================================
// ESCÁNER INTELIGENTE - DETECTA TIPO DE CÓDIGO
// ============================================
let html5QrCode = null;
let escaneando = false;

function abrirScanner() {
    if (escaneando) return;
    
    const container = document.getElementById('scanner-container');
    container.style.display = 'block';
    escaneando = true;
    
    // Verificar soporte
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('❌ Tu navegador no soporta cámara');
        cerrarScanner();
        return;
    }
    
    // Configuración optimizada
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
    
    // Obtener cámaras
    Html5Qrcode.getCameras().then(cameras => {
        if (cameras.length === 0) {
            alert('❌ No hay cámaras disponibles');
            cerrarScanner();
            return;
        }
        
        // Buscar cámara trasera
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
                // ============================================
                // CLASIFICACIÓN INTELIGENTE DEL CÓDIGO
                // ============================================
                
                // 1. Verificar si es un código de equipo PRO-XXXXXX
                if (decodedText.startsWith('PRO-')) {
                    document.getElementById('codigo_barras').value = decodedText;
                    alert('✅ Código de barras detectado: ' + decodedText);
                }
                // 2. Verificar si es un QR con JSON (viene de generar_qr_persona)
                else if (decodedText.startsWith('http') && decodedText.includes('ver_equipos_qr')) {
                    // Es un QR de persona, no es relevante aquí
                    alert('⚠️ Este es un QR de persona, no de equipo');
                }
                // 3. Intentar parsear como JSON (datos completos de equipo)
                else {
                    try {
                        const datos = JSON.parse(decodedText);
                        // Si tiene estructura de equipo, rellenar campos
                        if (datos.codigo || datos.serie || datos.marca || datos.modelo) {
                            if (datos.codigo) document.getElementById('codigo_barras').value = datos.codigo;
                            if (datos.serie) document.getElementById('numero_serie').value = datos.serie;
                            if (datos.marca) document.getElementById('marca').value = datos.marca;
                            if (datos.modelo) document.getElementById('modelo').value = datos.modelo;
                            if (datos.especificaciones) document.getElementById('especificaciones').value = datos.especificaciones;
                            alert('✅ Datos de equipo cargados desde QR');
                        } else {
                            // Es JSON pero no de equipo, lo ponemos en número de serie
                            document.getElementById('numero_serie').value = decodedText;
                            alert('✅ Número de serie detectado: ' + decodedText);
                        }
                    } catch (e) {
                        // 4. Si no es JSON, asumimos que es número de serie
                        document.getElementById('numero_serie').value = decodedText;
                        alert('✅ Número de serie detectado: ' + decodedText);
                    }
                }
                
                cerrarScanner();
                
                // Vibración en móviles
                if (navigator.vibrate) navigator.vibrate(100);
            },
            (errorMessage) => {
                console.log('Buscando códigos...');
            }
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

// Ingreso manual
function ingresarManual() {
    let codigo = prompt('📝 Ingresa el código manualmente:');
    if (codigo) {
        // Aplicar la misma lógica de clasificación
        if (codigo.startsWith('PRO-')) {
            document.getElementById('codigo_barras').value = codigo;
        } else {
            document.getElementById('numero_serie').value = codigo;
        }
    }
}

// Validación del formulario
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