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
if (!isset($_SESSION['user_id'])) {
    header('Location: /Inventario-Tesa-Cardex/login.php');
    exit();
}

if ($_SESSION['user_rol'] != 1) {
    header('Location: /Inventario-Tesa-Cardex/modules/dashboard.php?error=No tienes permisos');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';
$exitos = 0;
$errores = [];

// Obtener todas las personas para el selector de nueva persona
$personas = $conn->query("SELECT id, nombres, cedula, cargo FROM personas ORDER BY nombres");

// Procesar el traspaso múltiple
$_IS_XHR_MULTIPLE = false;
function _traspaso_multiple_redir($url, $extraJson = []) {
    global $_IS_XHR_MULTIPLE;
    while (ob_get_level() > 0) { try { @ob_end_clean(); } catch (Exception $e) {} }
    @session_write_close();
    if ($GLOBALS['_IS_XHR_MULTIPLE']) {
        header('Content-Type: application/json; charset=utf-8');
        $resp = ['redirect_url' => $url];
        if (is_array($extraJson) && !empty($extraJson)) {
            foreach ($extraJson as $k => $v) { $resp[$k] = $v; }
        }
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . $url);
    }
    exit;
}

$flashRestoreMultiple = null;
$restore_nueva_persona_id = 0;
$restore_observaciones = '';
$restore_asig_ids = [];
$resumen_multiple_ok = null;
if (!empty($_SESSION['flash_traspaso_multiple_restore']) && is_array($_SESSION['flash_traspaso_multiple_restore'])) {
    $flashRestoreMultiple = $_SESSION['flash_traspaso_multiple_restore'];
    $restore_nueva_persona_id = intval($flashRestoreMultiple['nueva_persona_id'] ?? 0);
    $restore_observaciones = (string)($flashRestoreMultiple['observaciones'] ?? '');
    $restore_asig_ids = array_values(array_filter(array_map('intval', (array)($flashRestoreMultiple['asignacion_ids'] ?? []))));
    unset($_SESSION['flash_traspaso_multiple_restore']);
}
if (!empty($_GET['ok']) && !empty($_SESSION['ultimo_traspaso_multiple']) && is_array($_SESSION['ultimo_traspaso_multiple'])) {
    $resumen_multiple_ok = $_SESSION['ultimo_traspaso_multiple'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['realizar_traspaso_multiple'])) {
    $_IS_XHR_MULTIPLE = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    $asignacion_ids_raw = $_POST['asignacion_ids'] ?? [];
    $nueva_persona_id = intval($_POST['nueva_persona_id'] ?? 0);
    $observaciones_raw = trim((string)($_POST['observaciones'] ?? ''));
    $observaciones = $conn->real_escape_string($observaciones_raw);

    $asignacion_ids = array_filter(array_map('intval', (array)$asignacion_ids_raw));
    sort($asignacion_ids);
    $asignacion_ids = array_values(array_unique($asignacion_ids));

    function _fallo_multiple($msgErr, $restoreNueva, $restoreObs, $restoreIds, $code = 'err') {
        $_SESSION['error'] = trim($msgErr, "❌ ");
        $_SESSION['flash_traspaso_multiple_restore'] = [
            'nueva_persona_id' => intval($restoreNueva),
            'observaciones' => (string)$restoreObs,
            'asignacion_ids' => array_values(array_map('intval', (array)$restoreIds))
        ];
        _traspaso_multiple_redir('traspaso_multiple.php?err=1', ['status' => 'error', 'code' => $code]);
    }

    if (empty($asignacion_ids)) {
        _fallo_multiple("❌ Debe seleccionar al menos un equipo para traspasar", $nueva_persona_id, $observaciones_raw, $asignacion_ids_raw, 'err_empty');
    } elseif (!$nueva_persona_id) {
        _fallo_multiple("❌ Debe seleccionar la nueva persona", $nueva_persona_id, $observaciones_raw, $asignacion_ids_raw, 'err_persona');
    } else {
        $conn->begin_transaction();
        try {
            $cerrarStmt = $conn->prepare("UPDATE asignaciones SET fecha_devolucion = NOW(), observaciones = CONCAT(COALESCE(observaciones,''), ' | ', ?) WHERE id = ?");
            if (!$cerrarStmt) throw new Exception('Prepare UPDATE cierre asignaciones: ' . $conn->error);
            $insertAsigStmt = $conn->prepare("INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion, observaciones) VALUES (?, ?, NOW(), ?)");
            if (!$insertAsigStmt) throw new Exception('Prepare INSERT nueva asignación: ' . $conn->error);
            $movDevStmt = $conn->prepare("INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) VALUES (?, ?, 'DEVOLUCION', ?)");
            if (!$movDevStmt) throw new Exception('Prepare INSERT movimiento devolución: ' . $conn->error);
            $movAsigStmt = $conn->prepare("INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) VALUES (?, ?, 'ASIGNACION', ?)");
            if (!$movAsigStmt) throw new Exception('Prepare INSERT movimiento asignación: ' . $conn->error);

            $exitos = 0;
            $errores = [];
            $equipos_traspasados = [];
            $origenes_nombres = [];
            foreach ($asignacion_ids as $asignacion_id) {
                $asignacion_id = intval($asignacion_id);
                $actual = $conn->query("SELECT * FROM asignaciones WHERE id = $asignacion_id")->fetch_assoc();
                if (!$actual) { $errores[] = "Asignación ID $asignacion_id no encontrada"; continue; }

                $equipo_id = intval($actual['equipo_id']);
                $persona_anterior_id = intval($actual['persona_id']);

                $origen_row = $conn->query("SELECT nombres FROM personas WHERE id=$persona_anterior_id LIMIT 1")->fetch_row();
                if (!empty($origen_row[0])) $origenes_nombres[$persona_anterior_id] = $origen_row[0];

                $obsCierre = 'Traspasado a persona ID ' . $nueva_persona_id . ($observaciones ? '. ' . $observaciones : '');
                if (!$cerrarStmt->bind_param('si', $obsCierre, $asignacion_id)) throw new Exception('bind cierre: ' . $cerrarStmt->error);
                if (!$cerrarStmt->execute()) throw new Exception('execute cierre asignación ' . $asignacion_id . ': ' . $cerrarStmt->error);

                $obsNueva = 'Traspaso múltiple desde asignación ID ' . $asignacion_id . ' desde persona ID ' . $persona_anterior_id . ($observaciones ? '. ' . $observaciones : '');
                if (!$insertAsigStmt->bind_param('iis', $equipo_id, $nueva_persona_id, $obsNueva)) throw new Exception('bind insertAsig: ' . $insertAsigStmt->error);
                if (!$insertAsigStmt->execute()) throw new Exception('execute insertAsig equipo ' . $equipo_id . ': ' . $insertAsigStmt->error);

                $obsMovDev = 'Devolución por traspaso múltiple';
                if (!$movDevStmt->bind_param('iis', $equipo_id, $persona_anterior_id, $obsMovDev)) throw new Exception('bind movDev: ' . $movDevStmt->error);
                if (!$movDevStmt->execute()) throw new Exception('execute movDev equipo ' . $equipo_id . ': ' . $movDevStmt->error);

                $obsMovAsig = 'Asignación por traspaso múltiple';
                if (!$movAsigStmt->bind_param('iis', $equipo_id, $nueva_persona_id, $obsMovAsig)) throw new Exception('bind movAsig: ' . $movAsigStmt->error);
                if (!$movAsigStmt->execute()) throw new Exception('execute movAsig equipo ' . $equipo_id . ': ' . $movAsigStmt->error);

                $equipos_traspasados[] = $equipo_id;
                $exitos++;
            }

            if ($exitos <= 0) {
                $conn->rollback();
                _fallo_multiple("❌ No se pudo completar ningún traspaso", $nueva_persona_id, $observaciones_raw, $asignacion_ids_raw, 'err_no_exitos');
            }

            $codigo_acta = function_exists('generarCodigoActa') ? generarCodigoActa('traspaso') : ('TRASPASO-MULT-' . date('YmdHis'));
            $colsActa = [];
            try {
                $rCols = @$conn->query("SHOW COLUMNS FROM actas");
                if ($rCols) while ($xc = $rCols->fetch_assoc()) $colsActa[$xc['Field']] = true;
            } catch (Exception $eCols) {}
            $columnas = [];
            $valores = [];
            $placeholdersActa = [];
            $typesActa = '';
            $fecha_generacion = date('Y-m-d H:i:s');
            $motivo = $observaciones ? $observaciones : 'Traspaso múltiple desde módulo Movimientos';
            $usuario_id_val = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
            $equipos_ids_str = implode(',', $equipos_traspasados);
            foreach ([
                'codigo_acta' => ['s', $codigo_acta],
                'tipo_acta' => ['s', 'traspaso'],
                'persona_id' => ['i', $nueva_persona_id],
                'usuario_id' => ['i', $usuario_id_val],
                'equipos_ids' => ['s', $equipos_ids_str],
                'motivo' => ['s', $motivo],
                'fecha_generacion' => ['s', $fecha_generacion]
            ] as $col => $def) {
                if (!empty($colsActa[$col])) {
                    $columnas[] = "`$col`";
                    $placeholdersActa[] = '?';
                    $typesActa .= $def[0];
                    $valores[] = $def[1];
                }
            }
            $acta_insertada_id = null;
            if (count($columnas) >= 2) {
                $insertActa = $conn->prepare("INSERT INTO actas (" . implode(',', $columnas) . ") VALUES (" . implode(',', $placeholdersActa) . ")");
                if ($insertActa) {
                    if ($insertActa->bind_param($typesActa, ...$valores)) {
                        if ($insertActa->execute()) {
                            $acta_insertada_id = $conn->insert_id;
                        }
                    }
                }
            }

            $conn->commit();
            $nombre_destino = $conn->query("SELECT nombres FROM personas WHERE id=".intval($nueva_persona_id))->fetch_row()[0] ?? '';
            $origenes_texto = '';
            $arr_origenes = array_values(array_unique($origenes_nombres));
            if (count($arr_origenes) === 1) {
                $origenes_texto = $arr_origenes[0];
            } elseif (count($arr_origenes) > 1) {
                $origenes_texto = implode(', ', array_slice($arr_origenes, 0, 3)) . (count($arr_origenes) > 3 ? ' (+' . (count($arr_origenes)-3) . ' más)' : '');
            } else {
                $origenes_texto = 'Custodios varios';
            }

            $_SESSION['ultimo_traspaso_multiple'] = [
                'total' => $exitos,
                'nueva_persona_id' => $nueva_persona_id,
                'cantidad' => $exitos,
                'asignacion_ids' => $asignacion_ids,
                'equipos_ids' => $equipos_traspasados,
                'acta_id' => $acta_insertada_id ?? null,
                'codigo_acta' => $codigo_acta,
                'observaciones' => $observaciones_raw
            ];
            $_SESSION['success'] = "Traspaso múltiple exitoso. Se trasladaron $exitos equipos correctamente.";
            $_SESSION['ui_popup_traspaso_multiple'] = [
                'total' => $exitos,
                'origen_nombre' => $origenes_texto,
                'destino_nombre' => $nombre_destino,
                'acta_id' => $acta_insertada_id ?? null,
                'codigo_acta' => $codigo_acta,
                'multiple' => true,
                'nueva_persona_id' => $nueva_persona_id
            ];
            _traspaso_multiple_redir('traspaso_multiple.php?ok=1', ['status' => 'ok', 'total' => $exitos, 'acta_id' => $acta_insertada_id ?? null]);

        } catch (Exception $e) {
            $conn->rollback();
            _fallo_multiple("❌ Error: " . $e->getMessage(), $nueva_persona_id, $observaciones_raw, $asignacion_ids_raw, 'err_exception');
        }
    }
}

// Obtener equipos asignados para el selector
$sql_asignaciones = "SELECT a.id as asignacion_id, 
                            a.fecha_asignacion,
                            e.id as equipo_id,
                            e.codigo_barras,
                            e.tipo_equipo,
                            e.marca,
                            e.modelo,
                            e.numero_serie,
                            p.id as persona_actual_id,
                            p.nombres as persona_actual_nombre,
                            p.cedula as persona_actual_cedula,
                            p.cargo as persona_actual_cargo
                     FROM asignaciones a
                     JOIN equipos e ON a.equipo_id = e.id
                     JOIN personas p ON a.persona_id = p.id
                     WHERE a.fecha_devolucion IS NULL
                     ORDER BY p.nombres, e.tipo_equipo";

$asignaciones = $conn->query($sql_asignaciones);
$RESTORE_NUEVA_ID = intval($restore_nueva_persona_id);
$RESTORE_OBS = json_encode($restore_observaciones, JSON_UNESCAPED_UNICODE);
$RESTORE_CHECK_IDS = json_encode($restore_asig_ids, JSON_UNESCAPED_UNICODE);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Traspaso Múltiple de Equipos</h4>
                    <small class="text-muted d-block" style="color:#222"><strong>Total asignaciones activas: <?php echo $conn->query("SELECT COUNT(*) FROM asignaciones WHERE fecha_devolucion IS NULL")->fetch_row()[0]; ?></strong></small>
                </div>
                <div class="card-body">

                    <form method="POST" id="formTraspasoMultiple">
                        <input type="hidden" name="realizar_traspaso_multiple" value="1">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nueva persona (nuevo custodio) *</label>
                                <select name="nueva_persona_id" id="nueva_persona_id" class="form-control select2" required>
                                    <option value="">-- Seleccione la nueva persona --</option>
                                    <?php
                                    $personas->data_seek(0);
                                    while($p = $personas->fetch_assoc()):
                                        $sel = ($RESTORE_NUEVA_ID > 0 && intval($p['id']) === $RESTORE_NUEVA_ID) ? ' selected' : '';
                                    ?>
                                        <option value="<?php echo $p['id']; ?>"<?php echo $sel; ?>>
                                            <?php echo $p['nombres'] . ' - ' . $p['cedula'] . ' (' . $p['cargo'] . ')'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Observaciones del traspaso</label>
                                <textarea name="observaciones" id="observaciones" class="form-control" rows="1" placeholder="Motivo del traspaso, condiciones, etc."><?php echo htmlspecialchars($restore_observaciones); ?></textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <input type="checkbox" id="selectAllEquipos">
                                <strong>Seleccionar todos los equipos</strong>
                            </label>
                            <p class="text-muted small">Marque los equipos que desea traspasar a la nueva persona</p>
                        </div>

                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-bordered table-hover table-sm" id="tablaEquipos">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="checkAll" checked>
                                        </th>
                                        <th>Custodio Actual</th>
                                        <th>Equipo</th>
                                        <th>Código</th>
                                        <th>Serie</th>
                                        <th>Fecha Asignación</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyEquiposMultiple">
                                    <?php
                                    $asignaciones2 = $conn->query($sql_asignaciones);
                                    while($a = $asignaciones2->fetch_assoc()):
                                        $asigId = intval($a['asignacion_id']);
                                        $checked = in_array($asigId, $restore_asig_ids, true) ? ' checked' : ' checked';
                                        if (!empty($_GET['err']) && count($restore_asig_ids) > 0) {
                                            $checked = in_array($asigId, $restore_asig_ids, true) ? ' checked' : '';
                                        }
                                    ?>
                                    <tr class="fila-equipo-multiple">
                                        <td>
                                            <input type="checkbox" name="asignacion_ids[]"
                                                   value="<?php echo $a['asignacion_id']; ?>"
                                                   class="equipo-checkbox"<?php echo $checked; ?>
                                                   data-equipo="<?php echo htmlspecialchars($a['tipo_equipo'] . ' ' . $a['marca'] . ' ' . $a['modelo']); ?>"
                                                   data-codigo="<?php echo $a['codigo_barras']; ?>">
                                        </td>
                                        <td>
                                            <strong><?php echo $a['persona_actual_nombre']; ?></strong><br>
                                            <small class="text-muted"><?php echo $a['persona_actual_cedula']; ?></small>
                                        </td>
                                        <td><?php echo $a['tipo_equipo'] . ' ' . $a['marca'] . ' ' . $a['modelo']; ?></td>
                                        <td><span class="badge bg-primary"><?php echo $a['codigo_barras']; ?></span></td>
                                        <td><?php echo $a['numero_serie'] ?: 'N/A'; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($a['fecha_asignacion'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Equipos seleccionados:</strong> <span id="countSelected">0</span>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="/Inventario-Tesa-Cardex/modules/movimientos/historial.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="button" name="realizar_traspaso_multiple_btn" class="btn btn-warning" id="btnTraspasar" disabled>
                                <i class="fas fa-exchange-alt me-2"></i>Realizar Traspaso Múltiple
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    function initTraspasoMultipleModule() {
        const RESTORE_NUEVA_ID = <?php echo $RESTORE_NUEVA_ID; ?>;
        const RESTORE_OBS = <?php echo $RESTORE_OBS; ?>;
        const RESTORE_CHECK_IDS = <?php echo $RESTORE_CHECK_IDS; ?>;
        const form = document.getElementById('formTraspasoMultiple');
        const tbody = document.getElementById('tbodyEquiposMultiple');
        const checkAll = document.getElementById('checkAll');
        const selectAllEquipos = document.getElementById('selectAllEquipos');
        const checkboxes = tbody ? tbody.querySelectorAll('input[type="checkbox"].equipo-checkbox') : document.querySelectorAll('.equipo-checkbox');
        const btnTraspasar = document.getElementById('btnTraspasar');
        const countSelected = document.getElementById('countSelected');
        const selectDestino = document.getElementById('nueva_persona_id');
        const txtObs = document.getElementById('observaciones');
        const actionUrl = 'traspaso_multiple.php';

        function updateCount() {
            const checked = tbody ? tbody.querySelectorAll('input[type="checkbox"].equipo-checkbox:checked') : document.querySelectorAll('.equipo-checkbox:checked');
            const n = checked.length;
            countSelected.textContent = n;
            btnTraspasar.disabled = n === 0;
            btnTraspasar.innerHTML = '<i class="fas fa-exchange-alt me-2"></i>Realizar Traspaso Múltiple (' + n + ' Equipo' + (n === 1 ? '' : 's') + ')';
        }

        if (RESTORE_NUEVA_ID > 0 && selectDestino) {
            try { selectDestino.value = String(RESTORE_NUEVA_ID); } catch(e) {}
        }
        if (RESTORE_OBS && txtObs) { try { txtObs.value = RESTORE_OBS; } catch(e) {} }
        if (Array.isArray(RESTORE_CHECK_IDS) && RESTORE_CHECK_IDS.length > 0 && checkboxes) {
            checkboxes.forEach(function(cb){
                const v = parseInt(cb.value, 10);
                if (!isNaN(v) && RESTORE_CHECK_IDS.indexOf(v) >= 0) { cb.checked = true; }
            });
        }

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateCount();
            });
        }
        if (selectAllEquipos) {
            selectAllEquipos.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                if (checkAll) checkAll.checked = this.checked;
                updateCount();
            });
        }
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const allChecked = Array.from(checkboxes).every(c => c.checked);
                if (checkAll) checkAll.checked = allChecked;
                updateCount();
            });
        });

        updateCount();

        if (btnTraspasar) {
            btnTraspasar.addEventListener('click', function(ev){
                if (btnTraspasar.disabled) return;
                const checks = tbody ? tbody.querySelectorAll('input[type="checkbox"].equipo-checkbox:checked') : document.querySelectorAll('.equipo-checkbox:checked');
                const checksArr = Array.prototype.slice.call(checks);
                const n = checksArr.length;
                const nueva = selectDestino ? selectDestino.value : '';
                if (!nueva || parseInt(nueva, 10) <= 0) {
                    try {
                        Swal.fire({ icon:'error', title:'Falta destino', text:'Debe seleccionar la NUEVA persona (custodio destino).', confirmButtonText:'Aceptar' });
                    } catch(eSwal) { alert('Debe seleccionar la nueva persona (destino).'); }
                    return;
                }
                if (n <= 0) {
                    try {
                        Swal.fire({ icon:'error', title:'Sin equipos', text:'Debe marcar al menos UN equipo con el checkbox.', confirmButtonText:'Aceptar' });
                    } catch(eSwal) { alert('Marque al menos un equipo.'); }
                    return;
                }
                try {
                    if (selectDestino && typeof Swal !== 'undefined') {
                        const destTxt = (selectDestino.options[selectDestino.selectedIndex] || {}).text || 'la persona seleccionada';
                        Swal.fire({
                            icon: 'question',
                            title: 'Confirmar Traspaso Múltiple',
                            html: 'Se transferirán <strong>' + n + ' equipo' + (n===1?'':'s') + '</strong> a:<br><strong>' + destTxt + '</strong>. <br><br>¿Continuar?',
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-check me-1"></i> Sí, traspasar',
                            cancelButtonText: '<i class="fas fa-times me-1"></i> Cancelar',
                            confirmButtonColor: '#f3b229',
                            cancelButtonColor: '#6c757d'
                        }).then(function(r){
                            if (r.isConfirmed) { _do_submit_traspaso_multiple(); }
                        });
                        return;
                    }
                } catch(eConfirm) {}
                _do_submit_traspaso_multiple();

                function _do_submit_traspaso_multiple() {
                    try {
                        btnTraspasar.disabled = true;
                        btnTraspasar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
                        const params = new URLSearchParams();
                        params.append('realizar_traspaso_multiple', '1');
                        params.append('nueva_persona_id', String(parseInt(nueva,10)));
                        checksArr.forEach(function(cb){ params.append('asignacion_ids[]', cb.value); });
                        params.append('observaciones', (txtObs ? txtObs.value : ''));
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', actionUrl, true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                        xhr.onload = function() {
                            try { btnTraspasar.disabled = false; } catch(e1){}
                            try { updateCount(); } catch(e2){}
                            let data = null;
                            try { data = JSON.parse(xhr.responseText || '{}'); } catch(eParse){ data = null; }
                            if (data && typeof data === 'object' && data.redirect_url) {
                                try { window.location.href = data.redirect_url; return; } catch(eLoc){}
                            }
                            const respUrl = xhr.responseURL || '';
                            if (respUrl && respUrl.length > 0) { try { window.location.href = respUrl; return; } catch(eLoc){} }
                            try { window.location.reload(); } catch(eRel){}
                        };
                        xhr.onerror = function() {
                            try { btnTraspasar.disabled = false; updateCount(); } catch(e){}
                            try { Swal.fire({ icon:'error', title:'Error de red', text:'No se pudo conectar con el servidor. Revise conexión e intente nuevamente.', confirmButtonText:'Aceptar' }); }
                            catch(eSwal) { alert('Error de red al realizar el traspaso.'); }
                        };
                        xhr.send(params.toString());
                    } catch(e) {
                        try { btnTraspasar.disabled = false; updateCount(); } catch(e2){}
                        try { form && form.submit(); } catch(e3){}
                    }
                }
            });
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTraspasoMultipleModule, { once: true });
    } else {
        initTraspasoMultipleModule();
    }
})();
</script>

<?php include '../../includes/footer.php'; ?>

