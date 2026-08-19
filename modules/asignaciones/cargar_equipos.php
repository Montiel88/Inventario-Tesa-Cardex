<?php
if (getenv('APP_DEBUG') === '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin();

require_once '../../config/database.php'; 
require_once '../../config/listas.php';

// Obtener persona_id de la URL si viene
$persona_id_seleccionada = isset($_GET['persona_id']) ? intval($_GET['persona_id']) : 0;

// Obtener lista de personas (para select y resúmenes)
$personas = $conn->query("SELECT id, nombres, cedula FROM personas ORDER BY nombres");
$persona_select_html = '';
$personas_data = [];
while ($p = $personas->fetch_assoc()) {
    $personas_data[$p['id']] = $p['nombres'];
    $selected = ($persona_id_seleccionada == $p['id']) ? 'selected' : '';
    $persona_select_html .= '<option value="' . $p['id'] . '" ' . $selected . '>' . htmlspecialchars($p['nombres']) . '</option>';
}

// Obtener equipos en bodega (disponibles)
$equipos_bodega = $conn->query("SELECT id, codigo_barras, tipo_equipo, marca, modelo FROM equipos WHERE estado = 'Disponible' ORDER BY codigo_barras");

// ============================================
// 🔴 BLOQUE AJAX/XHR - ANTES DE CUALQUIER OUTPUT
// ============================================
$esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $esAjax) {
    header('Content-Type: application/json; charset=utf-8');
    @ob_clean();

    $persona_id = intval($_POST['persona_id'] ?? 0);
    $tipo_asignacion = $_POST['tipo_asignacion'] ?? 'nuevo';

    if ($persona_id == 0) {
        echo json_encode(['success' => false, 'errores' => ['Debe seleccionar una persona de la lista.']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verificar que la persona exista en BD
    $per_check = $conn->query("SELECT id, nombres FROM personas WHERE id = " . $persona_id);
    if (!$per_check || $per_check->num_rows === 0) {
        echo json_encode(['success' => false, 'errores' => ['La persona seleccionada no existe en la base de datos.']], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $persona_row = $per_check->fetch_assoc();
    $persona_nombre = $persona_row['nombres'];

    $equipo_id = 0;
    $equipo_info = [];
    $errores = [];

    // Iniciar transacción para ATOMICIDAD
    $conn->begin_transaction();

    try {
        if ($tipo_asignacion == 'nuevo') {
            // ============================================
            // OPCIÓN 1: CREAR EQUIPO NUEVO Y ASIGNARLO
            // ============================================
            $tipo_equipo = $conn->real_escape_string($_POST['tipo_equipo'] ?? '');
            $marca = $conn->real_escape_string($_POST['marca'] ?? '');
            $modelo = $conn->real_escape_string($_POST['modelo'] ?? '');
            $serie = $conn->real_escape_string($_POST['numero_serie'] ?? '');
            $codigo_barras = $conn->real_escape_string($_POST['codigo_barras'] ?? '');
            $especificaciones = $conn->real_escape_string($_POST['especificaciones'] ?? '');

            if (empty($tipo_equipo)) {
                $errores[] = "El tipo de equipo es obligatorio cuando se elige 'Agregar Equipo Nuevo'.";
            } else {
                if (empty($codigo_barras)) {
                    $result = $conn->query("SELECT MAX(id) as max_id FROM equipos");
                    $row = $result->fetch_assoc();
                    $next_id = ($row['max_id'] ?? 0) + 1;
                    $codigo_barras = 'PRO-' . str_pad($next_id, 6, '0', STR_PAD_LEFT);
                } else {
                    // Validar código único
                    $chk = $conn->query("SELECT id FROM equipos WHERE codigo_barras = '$codigo_barras'");
                    if ($chk && $chk->num_rows > 0) {
                        $errores[] = "El código de barras '$codigo_barras' ya está asignado a otro equipo.";
                    }
                }

                if (empty($errores)) {
                    // INSERT COINCIDIENDO con el esquema REAL (no columnas inexistentes)
                    $sql_equipo = "INSERT INTO equipos (codigo_barras, tipo_equipo, marca, modelo, numero_serie, especificaciones, estado, ubicacion_id, activo) 
                                   VALUES ('$codigo_barras', '$tipo_equipo', '$marca', '$modelo', '$serie', '$especificaciones', 'Asignado', 6, 1)";
                    
                    if (!$conn->query($sql_equipo)) {
                        $errores[] = "Error al guardar equipo: " . $conn->error;
                    } else {
                        $equipo_id = $conn->insert_id;
                        $equipo_info = [
                            'id' => $equipo_id,
                            'codigo_barras' => $codigo_barras,
                            'tipo_equipo' => $tipo_equipo,
                            'marca' => $marca,
                            'modelo' => $modelo,
                            'nuevo' => true
                        ];
                    }
                }
            }
        } else {
            // ============================================
            // OPCIÓN 2: ASIGNAR DESDE BODEGA
            // ============================================
            $equipo_id = intval($_POST['equipo_bodega_id'] ?? 0);
            if ($equipo_id == 0) {
                $errores[] = "Debe seleccionar un equipo disponible de la Bodega.";
            } else {
                $eq_check = $conn->query("SELECT id, codigo_barras, tipo_equipo, marca, modelo, estado, ubicacion_id 
                                          FROM equipos WHERE id = $equipo_id LIMIT 1");
                if (!$eq_check || $eq_check->num_rows === 0) {
                    $errores[] = "El equipo seleccionado no existe.";
                } else {
                    $eq = $eq_check->fetch_assoc();
                    if ($eq['estado'] !== 'Disponible') {
                        $errores[] = "El equipo seleccionado ya no está disponible (estado actual: {$eq['estado']}).";
                    } else {
                        // Actualizar estado y ubicación (sale de Bodega → va a la persona = queda sin ubicación fija)
                        if (!$conn->query("UPDATE equipos SET estado = 'Asignado', ubicacion_id = $persona_id WHERE id = $equipo_id")) {
                            $errores[] = "Error al actualizar estado del equipo: " . $conn->error;
                        } else {
                            $equipo_info = [
                                'id' => $equipo_id,
                                'codigo_barras' => $eq['codigo_barras'],
                                'tipo_equipo' => $eq['tipo_equipo'],
                                'marca' => $eq['marca'],
                                'modelo' => $eq['modelo'],
                                'nuevo' => false
                            ];
                        }
                    }
                }
            }
        }

        // ============================================
        // SI TODO OK, CREAR ASIGNACIÓN + MOVIMIENTO
        // ============================================
        if (empty($errores) && $equipo_id > 0) {
            $sql_asignacion = "INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion) 
                              VALUES ($equipo_id, $persona_id, NOW())";
            
            if (!$conn->query($sql_asignacion)) {
                $errores[] = "Error al crear la asignación: " . $conn->error;
            } else {
                $asignacion_id = $conn->insert_id;

                // Registrar movimiento
                if (!$conn->query("INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, fecha_movimiento, observaciones) 
                                   VALUES ($equipo_id, $persona_id, 'ASIGNACION', NOW(), 'Asignación directa desde módulo de Asignaciones')")) {
                    $errores[] = "Error al registrar el movimiento: " . $conn->error;
                }

                // Log de auditoría
                require_once '../../includes/logs_functions.php';
                $log_desc = "Equipo: {$equipo_info['codigo_barras']} ({$equipo_info['tipo_equipo']}) → Persona ID {$persona_id} ({$persona_nombre}). " . 
                            ($equipo_info['nuevo'] ? "Equipo creado en la misma operación." : "Equipo proveniente de Bodega Principal.");
                registrarLog($conn, 'Asignar equipo', $log_desc, $_SESSION['user_id']);
            }
        }

        if (!empty($errores)) {
            $conn->rollback();
            echo json_encode(['success' => false, 'errores' => $errores], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // COMMIT final
        $conn->commit();

        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'mensaje' => 'EQUIPO ASIGNADO EXITOSAMENTE',
            'asignacion_id' => $asignacion_id ?? 0,
            'equipo' => $equipo_info,
            'persona_id' => $persona_id,
            'persona_nombre' => $persona_nombre,
            'urls' => [
                'detalle_persona' => "/inventario_ti/modules/personas/detalle.php?id={$persona_id}",
                'listado_personas' => "/inventario_ti/modules/personas/listar.php",
                'otra_asignacion' => "/inventario_ti/modules/asignaciones/cargar_equipos.php?persona_id={$persona_id}",
                'inicio' => "/inventario_ti/modules/dashboard.php"
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'errores' => ['Excepción del sistema: ' . $e->getMessage()]], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ============================================
// A PARTIR DE AQUÍ: RENDER HTML VISTA NORMAL
// ============================================
include '../../includes/header.php';
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

$mensaje = '';
if (isset($_SESSION['flash_asignacion_ok'])) {
    $mensaje = (string)$_SESSION['flash_asignacion_ok'];
    unset($_SESSION['flash_asignacion_ok']);
}
?>

<style>
    .opcion-btn {
        background: rgba(90, 45, 140, 0.75) !important;
        border: 1px solid rgba(243, 178, 41, 0.35) !important;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 20px;
        color: #ffffff !important;
    }
    .opcion-btn:hover {
        background: rgba(90, 45, 140, 0.95) !important;
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
    }
    .opcion-btn.active {
        background: rgba(90, 45, 140, 0.95) !important;
        border-color: var(--c-gold, #f3b229) !important;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
        outline: 2px solid #f3b229;
    }
    .is-invalid { border-color: #dc3545 !important; }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <a class="btn btn-outline-secondary btn-sm" href="<?php echo $persona_id_seleccionada ? ('/inventario_ti/modules/personas/detalle.php?id=' . $persona_id_seleccionada) : '/inventario_ti/modules/personas/listar.php'; ?>">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <h4 class="mb-0 flex-grow-1 text-center"><i class="fas fa-plus-circle me-2"></i>Asignar Equipo a Persona</h4>
                    <span style="width: 90px;"></span>
                </div>
                <div class="card-body">
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
                    <?php endif; ?>

                    <div id="alertasContainer"></div>

                    <form id="formEquipo" method="POST" enctype="multipart/form-data" onsubmit="return false;">
    
    <div class="row mb-4">
        <div class="col-md-6">
            <label class="form-label">Persona *</label>
            <select name="persona_id" id="persona_id" class="form-control" required>
                <option value="">-- Seleccione una persona --</option>
                <?php echo $persona_select_html; ?>
            </select>
        </div>
    </div>
    
    <!-- OPCIONES DE ASIGNACIÓN -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="opcion-btn" id="opcionNuevo" onclick="seleccionarOpcion('nuevo')">
                <i class="fas fa-plus-circle fa-3x mb-2"></i>
                <h5>Agregar Equipo Nuevo</h5>
                <p class="mb-0">Crear un nuevo equipo y asignarlo</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="opcion-btn" id="opcionBodega" onclick="seleccionarOpcion('bodega')">
                <i class="fas fa-warehouse fa-3x mb-2"></i>
                <h5>Asignar desde Bodega</h5>
                <p class="mb-0">Usar un equipo disponible en inventario</p>
            </div>
        </div>
    </div>
    
    <input type="hidden" name="tipo_asignacion" id="tipo_asignacion" value="nuevo">
    
    <!-- FORMULARIO PARA EQUIPO NUEVO -->
    <div id="formNuevo">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tipo de Equipo *</label>
                <select name="tipo_equipo" id="tipo_equipo" class="form-control" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach($tipos_equipos as $valor => $etiqueta): ?>
                        <option value="<?php echo htmlspecialchars($valor); ?>"><?php echo htmlspecialchars($etiqueta); ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">📋 <?php echo count($tipos_equipos); ?> tipos disponibles</small>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Marca</label>
                <input type="text" name="marca" id="marca" class="form-control" placeholder="Ej: HP, Dell, Logitech">
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Modelo</label>
                <input type="text" name="modelo" id="modelo" class="form-control" placeholder="Ej: Pavilion, Latitude">
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Código de Barras</label>
                <input type="text" name="codigo_barras" id="codigo_barras" class="form-control" placeholder="Automático">
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Número de Serie</label>
                <input type="text" name="numero_serie" id="numero_serie" class="form-control" placeholder="Serie del fabricante">
            </div>
            
            <div class="col-md-12 mb-3">
                <label class="form-label">Especificaciones</label>
                <textarea name="especificaciones" id="especificaciones" class="form-control" rows="2" placeholder="RAM, disco, procesador, etc."></textarea>
            </div>
        </div>
    </div>
    
    <!-- FORMULARIO PARA ASIGNAR DESDE BODEGA -->
    <div id="formBodega" style="display: none;">
        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label">Seleccionar equipo de bodega *</label>
                <select name="equipo_bodega_id" id="equipo_bodega_id" class="form-control">
                    <option value="">-- Seleccione un equipo disponible --</option>
                    <?php 
                    $equipos_bodega->data_seek(0);
                    if ($equipos_bodega->num_rows > 0): 
                        while($eq = $equipos_bodega->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $eq['id']; ?>">
                            <?php echo htmlspecialchars($eq['codigo_barras'] . ' - ' . $eq['tipo_equipo'] . ' ' . $eq['marca'] . ' ' . $eq['modelo']); ?>
                        </option>
                    <?php 
                        endwhile; 
                    endif; 
                    ?>
                </select>
            </div>
        </div>
    </div>
    
    <!-- BOTÓN DE ENVÍO -->
    <div class="text-center mt-4">
        <button type="button" id="btnAsignar" class="btn btn-success btn-lg px-5">
            <i class="fas fa-save me-2"></i>Asignar Equipo
        </button>
        <a href="/inventario_ti/modules/personas/listar.php" class="btn btn-secondary btn-lg px-5">
            <i class="fas fa-arrow-left me-2"></i>Cancelar
        </a>
    </div>
</form>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Helper Escape HTML
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function mostrarErrores(errores) {
    let html = '';
    errores.forEach(err => {
        html += '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                '<i class="fas fa-exclamation-triangle me-2"></i> ❌ ' + escapeHtml(err) +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    });
    document.getElementById('alertasContainer').innerHTML = html;
    document.getElementById('alertasContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function limpiarAlertas() { document.getElementById('alertasContainer').innerHTML = ''; }

// ====== SELECCIÓN DE OPCIÓN (tab nuevo vs bodega) ======
function seleccionarOpcion(opcion) {
    const btnNuevo = document.getElementById('opcionNuevo');
    const btnBodega = document.getElementById('opcionBodega');
    const formNuevo = document.getElementById('formNuevo');
    const formBodega = document.getElementById('formBodega');
    const tipoAsignacion = document.getElementById('tipo_asignacion');
    const inputTipoEquipo = document.getElementById('tipo_equipo');
    const inputEquipoBodega = document.getElementById('equipo_bodega_id');

    [inputTipoEquipo, inputEquipoBodega, document.getElementById('persona_id')]
        .forEach(el => { if (el) el.classList.remove('is-invalid'); });

    if (opcion === 'nuevo') {
        btnNuevo.classList.add('active');
        btnBodega.classList.remove('active');
        formNuevo.style.display = 'block';
        formBodega.style.display = 'none';
        tipoAsignacion.value = 'nuevo';
        inputTipoEquipo.required = true;
        inputEquipoBodega.required = false;
    } else {
        btnNuevo.classList.remove('active');
        btnBodega.classList.add('active');
        formNuevo.style.display = 'none';
        formBodega.style.display = 'block';
        tipoAsignacion.value = 'bodega';
        inputTipoEquipo.required = false;
        inputEquipoBodega.required = true;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    seleccionarOpcion('nuevo');
});

// ====== VALIDACIÓN LOCAL ======
function validarFormulario() {
    let errores = [];
    const personaSel = document.getElementById('persona_id');
    const persona_id = parseInt(personaSel.value || '0');
    if (persona_id === 0) {
        errores.push('Debe seleccionar una persona de la lista.');
        personaSel.classList.add('is-invalid'); personaSel.focus();
    } else {
        personaSel.classList.remove('is-invalid');
    }

    const tipo = document.getElementById('tipo_asignacion').value;
    if (tipo === 'nuevo') {
        const t = document.getElementById('tipo_equipo').value.trim();
        if (!t) {
            errores.push('Debe seleccionar el tipo de equipo (Agregar Equipo Nuevo).');
            document.getElementById('tipo_equipo').classList.add('is-invalid');
        } else { document.getElementById('tipo_equipo').classList.remove('is-invalid'); }
    } else {
        const eq = parseInt(document.getElementById('equipo_bodega_id').value || '0');
        if (eq === 0) {
            errores.push('Debe seleccionar un equipo disponible de la Bodega.');
            document.getElementById('equipo_bodega_id').classList.add('is-invalid');
        } else { document.getElementById('equipo_bodega_id').classList.remove('is-invalid'); }
    }
    return errores;
}

// ====== CLICK EN ASIGNAR ======
document.getElementById('btnAsignar').addEventListener('click', function() {
    limpiarAlertas();
    const erroresLocales = validarFormulario();
    if (erroresLocales.length > 0) {
        mostrarErrores(erroresLocales);
        return;
    }

    // Resumen para confirmación
    const persona_id = document.getElementById('persona_id').value;
    const persona_nombre = document.getElementById('persona_id').options[document.getElementById('persona_id').selectedIndex].text;
    const tipo = document.getElementById('tipo_asignacion').value;
    
    let resumen = '<div class="text-start" style="font-size:0.9rem;">';
    resumen += '<p style="margin:4px 0;"><strong>👤 Persona:</strong> ' + escapeHtml(persona_nombre) + '</p>';
    resumen += '<p style="margin:4px 0;"><strong>📦 Tipo:</strong> ' + (tipo === 'nuevo' ? 'Crear y asignar Equipo NUEVO' : 'Asignar desde Bodega Principal') + '</p>';
    
    if (tipo === 'nuevo') {
        const te = document.getElementById('tipo_equipo').selectedOptions[0]?.text || '(sin especificar)';
        const marca = document.getElementById('marca').value.trim() || '(sin especificar)';
        const mod = document.getElementById('modelo').value.trim() || '(sin especificar)';
        const cod = document.getElementById('codigo_barras').value.trim() || '(se genera automáticamente)';
        resumen += '<p style="margin:4px 0;"><strong>📌 Tipo equipo:</strong> ' + escapeHtml(te) + '</p>';
        resumen += '<p style="margin:4px 0;"><strong>🏢 Marca:</strong> ' + escapeHtml(marca) + '</p>';
        resumen += '<p style="margin:4px 0;"><strong>🔧 Modelo:</strong> ' + escapeHtml(mod) + '</p>';
        resumen += '<p style="margin:4px 0;"><strong>🏷️ Código:</strong> ' + escapeHtml(cod) + '</p>';
    } else {
        const eq = document.getElementById('equipo_bodega_id').selectedOptions[0]?.text || '(sin seleccionar)';
        resumen += '<p style="margin:4px 0;"><strong>🖥️ Equipo:</strong> ' + escapeHtml(eq) + '</p>';
    }
    resumen += '</div>';

    Swal.fire({
        title: '¿Confirmar asignación de equipo?',
        html: resumen,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-1"></i> Sí, asignar',
        cancelButtonText: '<i class="fas fa-times me-1"></i> Cancelar',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
    }).then(r => {
        if (!r.isConfirmed) return;

        const btn = document.getElementById('btnAsignar');
        btn.disabled = true;
        const origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';

        Swal.fire({ title:'Asignando equipo...', text:'Por favor espere', allowOutsideClick:false, allowEscapeKey:false, didOpen: () => Swal.showLoading() });

        const fd = new FormData(document.getElementById('formEquipo'));
        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.pathname || window.location.href, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onload = function() {
            btn.disabled = false; btn.innerHTML = origHtml;
            Swal.close();
            const raw = xhr.responseText.trim();
            try {
                const resp = JSON.parse(raw);
                if (resp.success) {
                    const eqCod = resp.equipo.codigo_barras;
                    const eqNom = resp.equipo.tipo_equipo + 
                        (resp.equipo.marca ? ' ' + resp.equipo.marca : '') +
                        (resp.equipo.modelo ? ' ' + resp.equipo.modelo : '');
                    
                    Swal.fire({
                        icon: 'success',
                        title: '✅ EQUIPO ASIGNADO EXITOSAMENTE',
                        html: '<div class="text-start">' +
                              '<p><strong>👤 Persona:</strong> ' + escapeHtml(resp.persona_nombre) + '</p>' +
                              '<p><strong>🖥️ Equipo:</strong> ' + escapeHtml(eqNom) + '</p>' +
                              '<p><strong>🏷️ Código:</strong> ' + escapeHtml(eqCod) + '</p>' +
                              '<p><strong>📦 Tipo:</strong> ' + (resp.equipo.nuevo ? 'Equipo NUEVO creado y asignado' : 'Proveniente de Bodega Principal') + '</p>' +
                              '</div><hr><p class="mb-0">¿Qué desea hacer ahora?</p>',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-user me-1"></i> Ver Detalle Persona',
                        denyButtonText: '<i class="fas fa-plus-circle me-1"></i> Asignar Otro Equipo',
                        cancelButtonText: '<i class="fas fa-home me-1"></i> Volver al Inicio',
                        confirmButtonColor: '#28a745',
                        denyButtonColor: '#007bff',
                        cancelButtonColor: '#6c757d',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(sr => {
                        if (sr.isConfirmed) {
                            window.location.href = resp.urls.detalle_persona;
                        } else if (sr.isDenied) {
                            window.location.href = resp.urls.otra_asignacion;
                        } else {
                            window.location.href = resp.urls.inicio;
                        }
                    });
                } else {
                    mostrarErrores(resp.errores);
                    Swal.fire({ icon:'error', title:'No se pudo asignar', 
                        html: (resp.errores || []).map(e => '• ' + escapeHtml(e)).join('<br>'),
                        confirmButtonText:'Entendido' });
                }
            } catch (e) {
                console.error('JSON Error:', e, 'Raw:', raw);
                mostrarErrores([
                    'Respuesta inesperada del servidor.',
                    'Primeros 250 caracteres: ' + raw.substring(0, 250)
                ]);
            }
        };

        xhr.onerror = function() {
            btn.disabled = false; btn.innerHTML = origHtml;
            Swal.close();
            mostrarErrores(['Error de conexión con el servidor.']);
        };

        xhr.send(fd);
    });
});

// Bloquear submit tradicional
document.getElementById('formEquipo').addEventListener('submit', e => { e.preventDefault(); return false; });
</script>

<?php include '../../includes/footer.php'; ?>
