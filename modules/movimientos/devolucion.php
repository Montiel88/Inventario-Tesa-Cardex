<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /Inventario-Tesa-Cardex/login.php');
    exit();
}
require_once '../../config/database.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';

$_IS_XHR_DEV = false;
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') === 0) {
    $_IS_XHR_DEV = true;
}
function _devolucion_redir($url, $extraJson = []) {
    global $_IS_XHR_DEV;
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    @session_write_close();
    if ($_IS_XHR_DEV) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge(['redirect_url' => $url], $extraJson), JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . $url);
    }
    exit();
}

$flash_devolucion_restore = null;
if (isset($_SESSION['flash_devolucion_restore']) && is_array($_SESSION['flash_devolucion_restore'])) {
    $flash_devolucion_restore = $_SESSION['flash_devolucion_restore'];
    unset($_SESSION['flash_devolucion_restore']);
}
$flash_devolucion = null;
if (isset($_SESSION['flash_devolucion'])) {
    $flash_devolucion = $_SESSION['flash_devolucion'];
    unset($_SESSION['flash_devolucion']);
}

function _fallo_devolucion($msgErr, $restore = []) {
    $_SESSION['error'] = $msgErr;
    if (!empty($restore)) {
        $_SESSION['flash_devolucion_restore'] = $restore;
    }
    _devolucion_redir('devolucion.php?err=1');
}

// ============================================
// PROCESAR DEVOLUCIÓN SI SE ENVÍA EL FORMULARIO
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['equipo_id'])) {
    $equipo_id = intval($_POST['equipo_id']);
    $observacion = trim($_POST['observacion'] ?? '');
    $estado_equipo = trim($_POST['estado_equipo'] ?? '');
    $condiciones = trim($_POST['condiciones'] ?? '');

    $restore = [
        'equipo_id' => $equipo_id,
        'estado_equipo' => $estado_equipo,
        'condiciones' => $condiciones,
        'observacion' => $observacion,
    ];

    if (empty($estado_equipo)) {
        _fallo_devolucion('❌ Debe seleccionar el estado del equipo', $restore);
    }
    if ($equipo_id <= 0) {
        _fallo_devolucion('❌ Equipo inválido', $restore);
    }

    $stmt_ver = $conn->prepare("SELECT a.id, a.persona_id, a.observaciones, p.nombres as persona_nombre,
                                e.tipo_equipo, e.codigo_barras, e.marca, e.modelo, e.numero_serie
                                FROM asignaciones a
                                JOIN personas p ON a.persona_id = p.id
                                JOIN equipos e ON a.equipo_id = e.id
                                WHERE a.equipo_id = ? AND a.fecha_devolucion IS NULL LIMIT 1");
    $stmt_ver->bind_param('i', $equipo_id);
    $stmt_ver->execute();
    $result = $stmt_ver->get_result();
    $stmt_ver->close();

    if (!$result || $result->num_rows <= 0) {
        _fallo_devolucion('❌ Este equipo no está prestado actualmente', $restore);
    }
    $asignacion = $result->fetch_assoc();

    // Procesar foto si se subió
    $foto_devolucion = '';
    if (isset($_FILES['foto_equipo']) && is_uploaded_file($_FILES['foto_equipo']['tmp_name'] ?? '')) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto_equipo']['name'] ?? '';
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed, true)) {
            $carpeta_fotos = '../../uploads/devoluciones/';
            if (!file_exists($carpeta_fotos)) {
                @mkdir($carpeta_fotos, 0777, true);
            }
            $nuevo_nombre = 'devolucion_' . $equipo_id . '_' . date('YmdHis') . '.' . $ext;
            $destino = $carpeta_fotos . $nuevo_nombre;
            if (@move_uploaded_file($_FILES['foto_equipo']['tmp_name'], $destino)) {
                $foto_devolucion = 'uploads/devoluciones/' . $nuevo_nombre;
            }
        }
    }

    $conn->begin_transaction();
    $acta_insertada_id = 0;
    $codigo_acta = '';
    try {
        // 1. Actualizar la asignación con fecha de devolución
        $stmt_upd = $conn->prepare("UPDATE asignaciones SET fecha_devolucion = NOW(), observaciones = ? WHERE id = ? LIMIT 1");
        $stmt_upd->bind_param('si', $observacion, $asignacion['id']);
        $stmt_upd->execute();
        $stmt_upd->close();

        // 2. Actualizar estado y crear mantenimiento si es necesario
        if ($estado_equipo == 'BUENO') {
            $nuevo_estado = 'Disponible';
        } else {
            $nuevo_estado = 'En mantenimiento';
            $descripcion_manto = "Equipo ingresado a mantenimiento por devolución en estado: $estado_equipo";
            if (!empty($condiciones)) {
                $descripcion_manto .= " - Condiciones: $condiciones";
            }
            $obs_manto = 'Generado automáticamente por devolución';
            $uid = intval($_SESSION['user_id']);
            $stmt_manto = $conn->prepare("INSERT INTO mantenimientos (equipo_id, fecha_ingreso, tipo_mantenimiento, descripcion, observaciones, created_by) VALUES (?, NOW(), 'correctivo', ?, ?, ?)");
            $stmt_manto->bind_param('isssi', $equipo_id, $descripcion_manto, $obs_manto, $uid);
            $stmt_manto->execute();
            $stmt_manto->close();
        }

        $stmt_eq = $conn->prepare("UPDATE equipos SET estado = ? WHERE id = ? LIMIT 1");
        $stmt_eq->bind_param('si', $nuevo_estado, $equipo_id);
        $stmt_eq->execute();
        $stmt_eq->close();

        // 3. Registrar movimiento
        $stmt_mov = $conn->prepare("INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones, estado_equipo, condiciones, foto_devolucion) VALUES (?, ?, 'DEVOLUCION', ?, ?, ?, ?)");
        $persona_id_int = intval($asignacion['persona_id']);
        $stmt_mov->bind_param('iissss', $equipo_id, $persona_id_int, $observacion, $estado_equipo, $condiciones, $foto_devolucion);
        $stmt_mov->execute();
        $stmt_mov->close();

        // 4. Generar ACTA DEVOLUCIÓN en BD
        $cols_actas = [];
        $res_cols = $conn->query("SHOW COLUMNS FROM actas");
        if ($res_cols) {
            while ($row_c = $res_cols->fetch_assoc()) {
                $cols_actas[strtolower($row_c['Field'])] = $row_c['Field'];
            }
            $res_cols->free();
        }
        $campos = [];
        $marcadores = [];
        $tipos = '';
        $valores = [];

        $codigo_acta = 'DEV-' . date('YmdHis');
        if (function_exists('generarCodigoActa')) {
            try {
                $tmp_cod = call_user_func('generarCodigoActa', 'devolucion');
                if (!empty($tmp_cod)) $codigo_acta = $tmp_cod;
            } catch (\Exception $ignored) {
                $ignored = null;
            }
        }

        function _acta_agregar_si(&$cols, &$campos, &$marcadores, &$tipos, &$valores, $nombre, $tipo, $valor) {
            $key = strtolower($nombre);
            if (isset($cols[$key])) {
                $campos[] = "`" . $cols[$key] . "`";
                $marcadores[] = "?";
                $tipos .= $tipo;
                $valores[] = $valor;
            }
        }

        _acta_agregar_si($cols_actas, $campos, $marcadores, $tipos, $valores, 'codigo_acta', 's', $codigo_acta);
        _acta_agregar_si($cols_actas, $campos, $marcadores, $tipos, $valores, 'tipo_acta', 's', 'devolucion');
        _acta_agregar_si($cols_actas, $campos, $marcadores, $tipos, $valores, 'persona_id', 'i', $persona_id_int);
        _acta_agregar_si($cols_actas, $campos, $marcadores, $tipos, $valores, 'usuario_id', 'i', intval($_SESSION['user_id']));
        _acta_agregar_si($cols_actas, $campos, $marcadores, $tipos, $valores, 'equipos_ids', 's', (string)$equipo_id);
        $motivo_acta = "Devolución de {$asignacion['tipo_equipo']} ({$asignacion['codigo_barras']}) - Estado: $estado_equipo";
        if (!empty($observacion)) $motivo_acta .= ". $observacion";
        _acta_agregar_si($cols_actas, $campos, $marcadores, $tipos, $valores, 'motivo', 's', $motivo_acta);
        _acta_agregar_si($cols_actas, $campos, $marcadores, $tipos, $valores, 'estado_equipo', 's', $estado_equipo);
        if (!empty($condiciones)) {
            _acta_agregar_si($cols_actas, $campos, $marcadores, $tipos, $valores, 'condiciones', 's', $condiciones);
        }
        _acta_agregar_si($cols_actas, $campos, $marcadores, $tipos, $valores, 'fecha_generacion', 's', date('Y-m-d H:i:s'));
        if (!empty($foto_devolucion)) {
            _acta_agregar_si($cols_actas, $campos, $marcadores, $tipos, $valores, 'foto_devolucion', 's', $foto_devolucion);
        }

        if (!empty($campos)) {
            $sql_acta_ins = "INSERT INTO actas (" . implode(',', $campos) . ") VALUES (" . implode(',', $marcadores) . ")";
            $stmt_acta = $conn->prepare($sql_acta_ins);
            if ($stmt_acta) {
                if (!empty($valores)) {
                    $stmt_acta->bind_param($tipos, ...$valores);
                }
                $stmt_acta->execute();
                $acta_insertada_id = intval($conn->insert_id);
                $stmt_acta->close();
            }
        }

        $conn->commit();

        // URL ACTA
        $acta_url = '';
        if ($acta_insertada_id > 0) {
            $acta_url = "/Inventario-Tesa-Cardex/api/generar_acta_devolucion.php?acta_id=" . $acta_insertada_id;
        } else {
            $acta_url = "/Inventario-Tesa-Cardex/api/generar_acta_devolucion.php?persona_id=" . $persona_id_int . "&equipo_id=" . $equipo_id;
        }

        $mensaje_adicional = ($estado_equipo != 'BUENO') ? 'Se ha creado un registro automático en Mantenimientos.' : '';
        $equipo_nombre = trim($asignacion['tipo_equipo'] . ' ' . ($asignacion['marca'] ?? '') . ' ' . ($asignacion['modelo'] ?? ''));
        $persona_nombre = $asignacion['persona_nombre'] ?? '';
        $codigo_equipo = $asignacion['codigo_barras'] ?? '';

        $_SESSION['flash_devolucion'] = [
            'estado_equipo' => $estado_equipo,
            'mensaje_adicional' => $mensaje_adicional,
            'acta_url' => $acta_url,
            'acta_id' => $acta_insertada_id,
            'codigo_acta' => $codigo_acta,
            'equipo_nombre' => $equipo_nombre,
            'persona_nombre' => $persona_nombre,
            'codigo_equipo' => $codigo_equipo,
        ];
        $_SESSION['ui_popup_devolucion'] = [
            'estado_equipo' => $estado_equipo,
            'mensaje_adicional' => $mensaje_adicional,
            'acta_url' => $acta_url,
            'acta_id' => $acta_insertada_id,
            'codigo_acta' => $codigo_acta,
            'equipo_nombre' => $equipo_nombre,
            'persona_nombre' => $persona_nombre,
            'codigo_equipo' => $codigo_equipo,
        ];
        $_SESSION['success'] = "Devolución registrada correctamente. Equipo devuelto: {$codigo_equipo}.";
        $_SESSION['ultima_devolucion'] = [
            'equipo_id' => $equipo_id,
            'acta_id' => $acta_insertada_id,
        ];

        // Registrar notificación
        require_once '../../config/notificaciones_helper.php';
        try {
            registrar_notificacion(
                $_SESSION['user_id'],
                'success',
                '🔄 Devolución registrada',
                "Equipo {$asignacion['tipo_equipo']} ({$asignacion['codigo_barras']}) devuelto por {$asignacion['persona_nombre']}",
                "/Inventario-Tesa-Cardex/modules/equipos/detalle.php?id={$equipo_id}"
            );
        } catch (\Exception $eNotif) { $eNotif = null; }

        // Registrar log
        require_once '../../includes/logs_functions.php';
        try {
            registrarLog($conn, 'Devolución equipo', "Equipo: {$asignacion['codigo_barras']}, Persona: {$asignacion['persona_nombre']}", $_SESSION['user_id']);
        } catch (\Exception $eLog) { $eLog = null; }

        _devolucion_redir('devolucion.php?ok=1', [
            'acta_id' => $acta_insertada_id,
            'codigo_acta' => $codigo_acta,
        ]);

    } catch (\Exception $e) {
        try { $conn->rollback(); } catch (\Exception $eRb) { $eRb = null; }
        _fallo_devolucion('❌ Error al registrar devolución: ' . $e->getMessage(), $restore);
    }
}

// ============================================
// OBTENER LISTA DE EQUIPOS PRESTADOS
// ============================================
$sql_prestados = "SELECT 
                    a.id as asignacion_id, 
                    a.fecha_asignacion, 
                    a.observaciones as obs_asignacion,
                    e.id as equipo_id, 
                    e.codigo_barras, 
                    e.tipo_equipo, 
                    e.marca, 
                    e.modelo,
                    e.numero_serie,
                    p.id as persona_id, 
                    p.nombres, 
                    p.cedula
                  FROM asignaciones a
                  INNER JOIN equipos e ON a.equipo_id = e.id
                  INNER JOIN personas p ON a.persona_id = p.id
                  WHERE a.fecha_devolucion IS NULL
                  ORDER BY a.fecha_asignacion DESC";

$result_prestados = $conn->query($sql_prestados);

$equipo_seleccionado = isset($_GET['equipo_id']) ? intval($_GET['equipo_id']) : 0;
if (empty($equipo_seleccionado) && !empty($flash_devolucion_restore['equipo_id'])) {
    $equipo_seleccionado = intval($flash_devolucion_restore['equipo_id']);
}

$RESTORE_ESTADO = !empty($flash_devolucion_restore['estado_equipo']) ? json_encode($flash_devolucion_restore['estado_equipo']) : 'null';
$RESTORE_CONDICIONES = !empty($flash_devolucion_restore['condiciones']) ? json_encode($flash_devolucion_restore['condiciones']) : 'null';
$RESTORE_OBS = !empty($flash_devolucion_restore['observacion']) ? json_encode($flash_devolucion_restore['observacion']) : 'null';
?>

<!-- ============================================ -->
<!-- ESTILOS ADICIONALES PARA EL FORMULARIO -->
<!-- ============================================ -->
<style>
    .devolucion-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        background: rgba(20, 5, 45, 0.7) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    .devolucion-card .card-header {
        background: rgba(139, 92, 246, 0.15) !important;
        border-bottom: 1px solid rgba(243, 178, 41, 0.35) !important;
    }
    .devolucion-card .card-header h4,
    .devolucion-card .card-header a,
    .devolucion-card .card-header i {
        color: #fff !important;
    }
    .devolucion-card .card-body {
        color: rgba(255, 255, 255, 0.9) !important;
    }
    .btn-devolver {
        border-radius: 30px;
        padding: 8px 20px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-devolver:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(90,45,140,0.3);
    }
    .estado-badge {
        font-size: 0.9rem;
        padding: 5px 10px;
    }
    #formularioDevolucion {
        background: rgba(15, 5, 30, 0.6);
        border-radius: 15px;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid rgba(255, 255, 255, 0.12);
    }
    #formularioDevolucion h5,
    #formularioDevolucion label,
    #formularioDevolucion small {
        color: rgba(255, 255, 255, 0.85) !important;
    }
    #formularioDevolucion .form-control,
    #formularioDevolucion .form-select,
    #formularioDevolucion textarea,
    #formularioDevolucion input[type="file"] {
        background: rgba(255, 255, 255, 0.06) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
    }
    #formularioDevolucion .form-control::placeholder,
    #formularioDevolucion textarea::placeholder {
        color: rgba(255, 255, 255, 0.35) !important;
    }
    #formularioDevolucion select option {
        background-color: #1a0533 !important;
        color: #fff !important;
    }
    .devolucion-card .table {
        color: rgba(255, 255, 255, 0.9) !important;
    }
    .devolucion-card .table thead th {
        color: rgba(255, 255, 255, 0.9) !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
    }
    .devolucion-card .table td {
        border-color: rgba(255, 255, 255, 0.08) !important;
    }
    .swal-popup-traspaso-exito { box-shadow: 0 20px 60px rgba(90, 45, 140, 0.25) !important; }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card devolucion-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <a href="/Inventario-Tesa-Cardex/modules/dashboard.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <h4 class="mb-0"><i class="fas fa-undo-alt me-2"></i>Registrar Devolución de Equipo</h4>
                    <a href="historial.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-history me-1"></i> Historial
                    </a>
                </div>
                <div class="card-body">

                    <!-- Tabla de equipos prestados -->
                    <?php if ($result_prestados && $result_prestados->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Equipo</th>
                                        <th>Persona</th>
                                        <th>Fecha préstamo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result_prestados->fetch_assoc()): ?>
                                    <tr data-equipo-row="<?php echo intval($row['equipo_id']); ?>">
                                        <td><?php echo htmlspecialchars($row['codigo_barras']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['tipo_equipo'] . ' ' . $row['marca'] . ' ' . $row['modelo']); ?>
                                            <br><small><?php echo htmlspecialchars($row['numero_serie'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['nombres']); ?>
                                            <br><small><?php echo htmlspecialchars($row['cedula']); ?></small>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_asignacion'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning btn-devolver" 
                                                    data-equipo-id="<?php echo intval($row['equipo_id']); ?>"
                                                    data-equipo-nombre="<?php echo htmlspecialchars($row['tipo_equipo'] . ' ' . $row['marca'] . ' ' . $row['modelo']); ?>"
                                                    data-persona-nombre="<?php echo htmlspecialchars($row['nombres']); ?>">
                                                <i class="fas fa-undo-alt"></i> Devolver
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center py-4">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <h5>No hay equipos prestados actualmente</h5>
                            <p>Todos los equipos están disponibles o no hay préstamos registrados.</p>
                            <a href="prestamo.php" class="btn btn-primary mt-2">
                                <i class="fas fa-hand-holding me-2"></i>Registrar nuevo préstamo
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario de devolución -->
                    <div id="formularioDevolucion" style="display: none;">
                        <h5 class="mb-3"><i class="fas fa-undo-alt me-2"></i>Detalles de la devolución</h5>
                        <form method="POST" enctype="multipart/form-data" id="devolucionForm">
                            <input type="hidden" name="equipo_id" id="equipo_id" value="">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Estado del equipo <span class="text-danger">*</span></label>
                                    <select name="estado_equipo" id="estado_equipo" class="form-control" required>
                                        <option value="">-- Seleccione --</option>
                                        <option value="BUENO">✅ Bueno</option>
                                        <option value="REGULAR">⚠️ Regular</option>
                                        <option value="MALO">❌ Malo</option>
                                        <option value="DAÑADO">🔧 Dañado (requiere mantenimiento)</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Condiciones adicionales</label>
                                    <select name="condiciones" id="condiciones" class="form-control">
                                        <option value="">-- Normal --</option>
                                        <option value="CON_ACCESORIOS">Con accesorios completos</option>
                                        <option value="SIN_ACCESORIOS">Sin accesorios</option>
                                        <option value="CON_FALLAS">Con fallas</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Foto del equipo (opcional)</label>
                                    <input type="file" name="foto_equipo" id="foto_equipo" class="form-control" accept="image/*">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observacion" id="observacion" class="form-control" rows="2" placeholder="Detalles adicionales..."></textarea>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" id="cancelarDevolucion">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="btnRegistrarDevolucion">
                                    <i class="fas fa-save me-2"></i>Registrar devolución
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function initDevolucionModule() {
        if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined') {
            // no-op si quisieramos jQuery, pero usamos nativo
        }

        const RESTORE_ESTADO = <?php echo $RESTORE_ESTADO; ?>;
        const RESTORE_CONDICIONES = <?php echo $RESTORE_CONDICIONES; ?>;
        const RESTORE_OBS = <?php echo $RESTORE_OBS; ?>;
        const AUTO_EQUIPO_ID = <?php echo intval($equipo_seleccionado); ?>;

        const botones = document.querySelectorAll('.btn-devolver');
        const formulario = document.getElementById('formularioDevolucion');
        const equipoIdInput = document.getElementById('equipo_id');
        const cancelarBtn = document.getElementById('cancelarDevolucion');
        const estadoSel = document.getElementById('estado_equipo');
        const condSel = document.getElementById('condiciones');
        const obsText = document.getElementById('observacion');
        const fotoInput = document.getElementById('foto_equipo');
        const btnRegistrar = document.getElementById('btnRegistrarDevolucion');

        function abrirFormulario(equipoId, equipoNombre, personaNombre) {
            if (!equipoId) return;
            equipoIdInput.value = String(equipoId);
            if (RESTORE_ESTADO && estadoSel) {
                estadoSel.value = String(RESTORE_ESTADO);
            }
            if (RESTORE_CONDICIONES && condSel) {
                condSel.value = String(RESTORE_CONDICIONES);
            }
            if (RESTORE_OBS && obsText) {
                obsText.value = String(RESTORE_OBS);
            }
            formulario.style.display = 'block';
            try {
                formulario.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } catch (e) {}
        }

        botones.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const equipoId = this.getAttribute('data-equipo-id');
                const equipoNombre = this.getAttribute('data-equipo-nombre') || '';
                const personaNombre = this.getAttribute('data-persona-nombre') || '';
                abrirFormulario(equipoId, equipoNombre, personaNombre);
            });
        });

        if (AUTO_EQUIPO_ID > 0) {
            let equipoNombre = '', personaNombre = '';
            const row = document.querySelector('tr[data-equipo-row="' + AUTO_EQUIPO_ID + '"]');
            if (row) {
                const btn = row.querySelector('.btn-devolver');
                if (btn) {
                    equipoNombre = btn.getAttribute('data-equipo-nombre') || '';
                    personaNombre = btn.getAttribute('data-persona-nombre') || '';
                }
            }
            abrirFormulario(String(AUTO_EQUIPO_ID), equipoNombre, personaNombre);
        }

        if (cancelarBtn) {
            cancelarBtn.addEventListener('click', function() {
                formulario.style.display = 'none';
                equipoIdInput.value = '';
                try {
                    const f = document.getElementById('devolucionForm');
                    if (f) f.reset();
                } catch (e) {}
            });
        }

        function _do_submit_devolucion() {
            try {
                if (!estadoSel || !estadoSel.value) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Estado requerido',
                        text: 'Debe seleccionar el estado del equipo antes de continuar.',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }
            } catch (eVal) {}

            try {
                if (btnRegistrar) {
                    btnRegistrar.disabled = true;
                    const originalInner = btnRegistrar.innerHTML;
                    btnRegistrar.setAttribute('data-original', originalInner);
                    btnRegistrar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
                }
            } catch (eDi) {}

            const form = document.getElementById('devolucionForm');
            const fd = new FormData(form);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'devolucion.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function() {
                try {
                    if (btnRegistrar && btnRegistrar.hasAttribute('data-original')) {
                        btnRegistrar.innerHTML = btnRegistrar.getAttribute('data-original');
                        btnRegistrar.disabled = false;
                    }
                } catch (eRe) {}
                let redir = '';
                try {
                    const txt = String(xhr.responseText || '').trim();
                    if (txt && txt.charAt(0) === '{') {
                        const data = JSON.parse(txt);
                        if (data && data.redirect_url) {
                            redir = String(data.redirect_url);
                        }
                    }
                } catch (eJ) {}
                if (!redir && xhr.responseURL) {
                    try {
                        const u = new URL(xhr.responseURL, window.location.href);
                        redir = u.pathname + u.search;
                    } catch (eU) {}
                }
                if (redir) {
                    window.location.href = redir;
                    return;
                }
                try {
                    Swal.fire({
                        icon: 'error',
                        title: 'Respuesta inesperada',
                        text: 'No se pudo procesar la respuesta del servidor. Se intentará recargar.',
                        confirmButtonText: 'Recargar'
                    }).then(function() {
                        window.location.reload();
                    });
                } catch (eS) {
                    window.location.reload();
                }
            };
            xhr.onerror = function() {
                try {
                    if (btnRegistrar && btnRegistrar.hasAttribute('data-original')) {
                        btnRegistrar.innerHTML = btnRegistrar.getAttribute('data-original');
                        btnRegistrar.disabled = false;
                    }
                } catch (eR) {}
                try {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de red',
                        text: 'No se pudo conectar con el servidor. Revise su conexión e intente nuevamente.',
                        confirmButtonText: 'Intentar de nuevo',
                        showCancelButton: true,
                        cancelButtonText: 'Cancelar'
                    }).then(function(r) {
                        if (r.isConfirmed) _do_submit_devolucion();
                    });
                } catch (eSw) {
                    window.location.reload();
                }
            };
            try {
                xhr.send(fd);
            } catch (eSend) {
                try {
                    if (btnRegistrar && btnRegistrar.hasAttribute('data-original')) {
                        btnRegistrar.innerHTML = btnRegistrar.getAttribute('data-original');
                        btnRegistrar.disabled = false;
                    }
                } catch (eRB) {}
                try {
                    Swal.fire({ icon: 'error', title: 'Error al enviar', text: String(eSend.message || eSend), confirmButtonText: 'Aceptar' });
                } catch (e2) {}
            }
        }

        if (btnRegistrar) {
            btnRegistrar.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (btnRegistrar.disabled) return;
                try {
                    if (estadoSel && !estadoSel.value) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Estado requerido',
                            text: 'Debe seleccionar el estado del equipo antes de continuar.',
                            confirmButtonText: 'Entendido'
                        });
                        return;
                    }
                } catch (eVal) {}

                let equipoNombre = '';
                let personaNombre = '';
                let codigoEquipo = '';
                try {
                    const eid = equipoIdInput ? String(equipoIdInput.value || '') : '';
                    if (eid) {
                        const row = document.querySelector('tr[data-equipo-row="' + eid + '"]');
                        if (row) {
                            const tds = row.querySelectorAll('td');
                            if (tds && tds.length >= 4) {
                                codigoEquipo = (tds[0].textContent || '').trim();
                                equipoNombre = (tds[1].textContent || '').replace(/\s+/g, ' ').trim();
                                personaNombre = (tds[2].textContent || '').replace(/\s+/g, ' ').trim();
                            }
                        }
                    }
                } catch (e1) {}

                let estadoTxt = '';
                try {
                    if (estadoSel && estadoSel.value) {
                        const o = estadoSel.options[estadoSel.selectedIndex];
                        estadoTxt = o ? (o.textContent || '').trim() : String(estadoSel.value);
                    }
                } catch (eSt) {}

                let htmlConfirm = '<div class="text-start">';
                if (codigoEquipo) htmlConfirm += '<p class="mb-2"><strong>Código equipo:</strong> ' + codigoEquipo + '</p>';
                if (equipoNombre) htmlConfirm += '<p class="mb-2"><strong>Equipo:</strong> ' + equipoNombre + '</p>';
                if (personaNombre) htmlConfirm += '<p class="mb-2"><strong>Persona:</strong> ' + personaNombre + '</p>';
                if (estadoTxt) htmlConfirm += '<p class="mb-0"><strong>Estado registrado:</strong> ' + estadoTxt + '</p>';
                htmlConfirm += '</div>';

                try {
                    Swal.fire({
                        icon: 'question',
                        title: 'Confirmar Devolución',
                        html: htmlConfirm,
                        confirmButtonText: '<i class="fas fa-check me-1"></i> Sí, devolver',
                        cancelButtonText: '<i class="fas fa-times me-1"></i> Cancelar',
                        showCancelButton: true,
                        allowOutsideClick: false,
                        confirmButtonColor: '#198754',
                        cancelButtonColor: '#6c757d'
                    }).then(function(res) {
                        if (res.isConfirmed) {
                            _do_submit_devolucion();
                        }
                    });
                } catch (eSw) {
                    _do_submit_devolucion();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDevolucionModule, { once: true });
    } else {
        initDevolucionModule();
    }
})();
</script>

<?php include '../../includes/footer.php'; ?>

