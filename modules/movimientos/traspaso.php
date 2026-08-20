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
require_once '../../config/actas_config.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';
$traspaso_ok_data = null;

$total_asignaciones_activas = $conn->query("SELECT COUNT(*) FROM asignaciones WHERE fecha_devolucion IS NULL")->fetch_row()[0];
$personas_origen = $conn->query("SELECT id, nombres, cedula, cargo FROM personas WHERE activo=1 OR activo IS NULL ORDER BY nombres");
$personas_destino = $conn->query("SELECT id, nombres, cedula, cargo FROM personas WHERE activo=1 OR activo IS NULL ORDER BY nombres");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['realizar_traspaso_masivo'])) {
    $asignacion_ids_raw = $_POST['asignacion_ids'] ?? [];
    $origen_persona_id = intval($_POST['origen_persona_id'] ?? 0);
    $nueva_persona_id = intval($_POST['nueva_persona_id'] ?? 0);
    $observaciones = $conn->real_escape_string(trim($_POST['observaciones'] ?? ''));

    $asignacion_ids = array_filter(array_map('intval', (array)$asignacion_ids_raw));
    sort($asignacion_ids);
    $asignacion_ids = array_values(array_unique($asignacion_ids));

    $_IS_XHR = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    function _traspaso_responder_redir($url, $extraJson = []) {
        global $_IS_XHR;
        while (ob_get_level() > 0) { try { @ob_end_clean(); } catch (Exception $e) {} }
        @session_write_close();
        if ($GLOBALS['_IS_XHR']) {
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

    if ($origen_persona_id <= 0 || $nueva_persona_id <= 0) {
        $error = '❌ Debes seleccionar la persona de origen y la nueva persona de destino.';
        $_SESSION['error'] = trim($error, "❌ ");
        $_SESSION['flash_traspaso_restore'] = [
            'origen_persona_id' => $origen_persona_id,
            'nueva_persona_id' => $nueva_persona_id,
            'observaciones' => $_POST['observaciones'] ?? '',
            'asignacion_ids' => $asignacion_ids
        ];
        _traspaso_responder_redir('traspaso.php?err=1', ['status' => 'error', 'code' => 'err_vacios']);
    } elseif ($origen_persona_id === $nueva_persona_id) {
        $error = '❌ No se puede realizar un traspaso a la misma persona. Por favor selecciona un destino distinto al origen.';
        $_SESSION['error'] = trim($error, "❌ ");
        $_SESSION['flash_traspaso_restore'] = [
            'origen_persona_id' => $origen_persona_id,
            'nueva_persona_id' => $nueva_persona_id,
            'observaciones' => $_POST['observaciones'] ?? '',
            'asignacion_ids' => $asignacion_ids
        ];
        _traspaso_responder_redir('traspaso.php?err=1', ['status' => 'error', 'code' => 'err_mismo']);
    } elseif (empty($asignacion_ids)) {
        $error = '❌ No se detectó NINGÚN equipo marcado para traspasar. Marca al menos UN equipo con el checkbox.';
        $_SESSION['error'] = trim($error, "❌ ");
        $_SESSION['flash_traspaso_restore'] = [
            'origen_persona_id' => $origen_persona_id,
            'nueva_persona_id' => $nueva_persona_id,
            'observaciones' => $_POST['observaciones'] ?? '',
            'asignacion_ids' => $asignacion_ids
        ];
        _traspaso_responder_redir('traspaso.php?err=1', ['status' => 'error', 'code' => 'err_ids_vacios']);
    } else {
        try {
            $colsMov = [];
            $r = @$conn->query("SHOW COLUMNS FROM movimientos");
            if ($r) while ($x=$r->fetch_assoc()) $colsMov[$x['Field']] = true;
            if (empty($colsMov['usuario_id'])) {
                @$conn->query("ALTER TABLE movimientos ADD COLUMN `usuario_id` INT UNSIGNED NULL");
            }
        } catch (Exception $eMig){}

        $conn->begin_transaction();
        try {
            $placeholders = implode(',', array_fill(0, count($asignacion_ids), '?'));
            $stmt = $conn->prepare("SELECT a.*, p.id as persona_asignada FROM asignaciones a JOIN personas p ON a.persona_id=p.id WHERE a.id IN ($placeholders) AND a.fecha_devolucion IS NULL FOR UPDATE");
            if (!$stmt) throw new Exception('Error al preparar validación de asignaciones: ' . $conn->error);
            $types = str_repeat('i', count($asignacion_ids));
            if (!$stmt->bind_param($types, ...$asignacion_ids)) throw new Exception('Error bind_param validación: ' . $stmt->error);
            if (!$stmt->execute()) throw new Exception('Error execute validación: ' . $stmt->error);
            $resAsigs = $stmt->get_result();
            $asig_list = [];
            while ($r = $resAsigs->fetch_assoc()) {
                if (intval($r['persona_asignada']) !== $origen_persona_id) {
                    throw new Exception('Asignación ID ' . intval($r['id']) . ' pertenece a otra persona, no a la seleccionada como origen.');
                }
                $asig_list[] = $r;
            }
            if (count($asig_list) !== count($asignacion_ids)) {
                throw new Exception('Asignaciones inválidas o duplicadas.');
            }
            if (count($asig_list) === 0) {
                throw new Exception('No hay asignaciones válidas para este traspaso.');
            }

            $equipos_traspasados = [];
            $total = 0;

            $cerrarStmt = null;
            $insertAsigStmt = null;
            $movDevStmt = null;
            $movAsigStmt = null;

            foreach ($asig_list as $actual) {
                $asignacion_id = intval($actual['id']);
                $equipo_id = intval($actual['equipo_id']);
                $persona_anterior_id = intval($actual['persona_id']);

                if ($cerrarStmt === null) {
                    $cerrarStmt = $conn->prepare("UPDATE asignaciones SET fecha_devolucion = NOW(), observaciones = CONCAT(COALESCE(observaciones,''), ' | ', ?) WHERE id = ?");
                    if (!$cerrarStmt) throw new Exception('Prepare UPDATE cierre asignaciones: ' . $conn->error);
                    $insertAsigStmt = $conn->prepare("INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion, observaciones) VALUES (?, ?, NOW(), ?)");
                    if (!$insertAsigStmt) throw new Exception('Prepare INSERT nueva asignación: ' . $conn->error);
                    $movDevStmt = $conn->prepare("INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) VALUES (?, ?, 'DEVOLUCION', ?)");
                    if (!$movDevStmt) throw new Exception('Prepare INSERT movimiento devolución: ' . $conn->error);
                    $movAsigStmt = $conn->prepare("INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) VALUES (?, ?, 'ASIGNACION', ?)");
                    if (!$movAsigStmt) throw new Exception('Prepare INSERT movimiento asignación: ' . $conn->error);
                }

                $obsCierre = 'Traspasado a persona ID ' . $nueva_persona_id . ($observaciones ? '. ' . $observaciones : '');
                if (!$cerrarStmt->bind_param('si', $obsCierre, $asignacion_id)) throw new Exception('bind cierre: ' . $cerrarStmt->error);
                if (!$cerrarStmt->execute()) throw new Exception('execute cierre asignación ' . $asignacion_id . ': ' . $cerrarStmt->error);

                $obsNueva = 'Traspaso desde asignación ID ' . $asignacion_id . ' desde persona ID ' . $persona_anterior_id . ($observaciones ? '. ' . $observaciones : '');
                if (!$insertAsigStmt->bind_param('iis', $equipo_id, $nueva_persona_id, $obsNueva)) throw new Exception('bind insertAsig: ' . $insertAsigStmt->error);
                if (!$insertAsigStmt->execute()) throw new Exception('execute insertAsig equipo ' . $equipo_id . ': ' . $insertAsigStmt->error);

                $obsMovDev = 'Devolución por traspaso (masivo) a nueva persona';
                if (!$movDevStmt->bind_param('iis', $equipo_id, $persona_anterior_id, $obsMovDev)) throw new Exception('bind movDev: ' . $movDevStmt->error);
                if (!$movDevStmt->execute()) throw new Exception('execute movDev equipo ' . $equipo_id . ': ' . $movDevStmt->error);

                $obsMovAsig = 'Asignación por traspaso masivo';
                if (!$movAsigStmt->bind_param('iis', $equipo_id, $nueva_persona_id, $obsMovAsig)) throw new Exception('bind movAsig: ' . $movAsigStmt->error);
                if (!$movAsigStmt->execute()) throw new Exception('execute movAsig equipo ' . $equipo_id . ': ' . $movAsigStmt->error);

                $equipos_traspasados[] = $equipo_id;
                $total++;
            }

            try {
                $equipos_ids_str = implode(',', $equipos_traspasados);
                $codigo_acta = function_exists('generarCodigoActa') ? generarCodigoActa('traspaso') : ('TRASPASO-M-' . date('YmdHis'));
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
                $motivo = $observaciones ? $observaciones : 'Traspaso masivo realizado desde módulo Movimientos';
                $usuario_id_val = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
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
            } catch (Exception $eActa) {
                $acta_insertada_id = null;
            }

            $conn->commit();
            $traspaso_ok_data = [
                'total' => $total,
                'origen_persona_id' => $origen_persona_id,
                'nueva_persona_id' => $nueva_persona_id,
                'equipos_ids' => $equipos_traspasados,
                'asignaciones_ids' => $asignacion_ids,
                'acta_id' => $acta_insertada_id ?? null
            ];
            $_SESSION['ultimo_traspaso_masivo'] = $traspaso_ok_data;
            $_SESSION['success'] = "Traspaso exitoso. Se trasladaron $total equipos correctamente.";

            $nombre_origen = $conn->query("SELECT nombres FROM personas WHERE id=".intval($origen_persona_id))->fetch_row()[0] ?? '';
            $nombre_destino = $conn->query("SELECT nombres FROM personas WHERE id=".intval($nueva_persona_id))->fetch_row()[0] ?? '';

            $_SESSION['ui_popup_traspaso'] = [
                'total' => $total,
                'origen_nombre' => $nombre_origen,
                'destino_nombre' => $nombre_destino,
                'acta_id' => $acta_insertada_id ?? null,
                'codigo_acta' => $codigo_acta ?? ''
            ];
            $mensaje = "✅ Traspaso realizado correctamente. Se trasladaron $total equipos.";

            _traspaso_responder_redir('traspaso.php?ok=1', ['status' => 'ok', 'total' => $total, 'acta_id' => $acta_insertada_id ?? null]);

        } catch (Exception $e) {
            $conn->rollback();
            $error = "❌ Error al realizar el traspaso: " . $e->getMessage();
            $_SESSION['error'] = trim($error, "❌ ");
            $_SESSION['flash_traspaso_restore'] = [
                'origen_persona_id' => $origen_persona_id,
                'nueva_persona_id' => $nueva_persona_id,
                'observaciones' => $_POST['observaciones'] ?? '',
                'asignacion_ids' => $asignacion_ids
            ];
            _traspaso_responder_redir('traspaso.php?err=1', ['status' => 'error', 'code' => 'err_exception', 'message' => $e->getMessage()]);
        }
    }
}

// =============================================================
// FLASH RESTORE (lado GET del patrón Post-Redirect-Get)
// Recupera valores desde la sesión para recordar lo que el usuario
// seleccionó en caso de error, y muestra el resumen tras el éxito.
// =============================================================
$flashRestore = null;
$origen_persona_id = 0;
$nueva_persona_id = 0;
$observaciones_restore = '';
$asignacion_ids_restore = [];
if (!empty($_SESSION['flash_traspaso_restore']) && is_array($_SESSION['flash_traspaso_restore'])) {
    $flashRestore = $_SESSION['flash_traspaso_restore'];
    $origen_persona_id = intval($flashRestore['origen_persona_id'] ?? 0);
    $nueva_persona_id = intval($flashRestore['nueva_persona_id'] ?? 0);
    $observaciones_restore = (string)($flashRestore['observaciones'] ?? '');
    $asignacion_ids_restore = array_values(array_filter(array_map('intval', (array)($flashRestore['asignacion_ids'] ?? []))));
    unset($_SESSION['flash_traspaso_restore']);
}
if (!empty($_GET['ok']) && !empty($_SESSION['ultimo_traspaso_masivo']) && is_array($_SESSION['ultimo_traspaso_masivo'])) {
    $traspaso_ok_data = $_SESSION['ultimo_traspaso_masivo'];
    $mensaje = "✅ Traspaso realizado correctamente. Se trasladaron " . intval($traspaso_ok_data['total'] ?? 0) . " equipos.";
}
$html_dbg = '';
?>


<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Traspaso de Equipos (Cambio de Custodio)</h4>
                    <small class="text-muted d-block" style="color:#222"><strong>Total asignaciones activas en el sistema: <?php echo $total_asignaciones_activas; ?></strong></small>
                </div>
                <div class="card-body">

                    <?php if ($mensaje): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php
                            $data = $traspaso_ok_data ?? ($_SESSION['ultimo_traspaso_masivo'] ?? null);
                            if ($data) {
                                $eqListado = [];
                                if (!empty($data['equipos_ids'])) {
                                    $eqIds = array_filter(array_map('intval', (array)$data['equipos_ids']));
                                    if ($eqIds) {
                                        $pl = implode(',', array_fill(0, count($eqIds), '?'));
                                        $stmt = $conn->prepare("SELECT id, codigo_barras, tipo_equipo, marca, modelo, numero_serie FROM equipos WHERE id IN ($pl)");
                                        if ($stmt) {
                                            $types = str_repeat('i', count($eqIds));
                                            $stmt->bind_param($types, ...$eqIds);
                                            $stmt->execute();
                                            $res = $stmt->get_result();
                                            while ($e = $res->fetch_assoc()) $eqListado[] = $e;
                                        }
                                    }
                                }
                                $nombre_origen = $conn->query("SELECT nombres FROM personas WHERE id=".intval($data['origen_persona_id']))->fetch_row()[0] ?? '';
                                $nombre_destino = $conn->query("SELECT nombres FROM personas WHERE id=".intval($data['nueva_persona_id']))->fetch_row()[0] ?? '';

                                $url_acta_vieja = !empty($data['asignaciones_ids'][0]) ? ("/Inventario-Tesa-Cardex/api/generar_acta_traspaso.php?asignacion_id=" . intval($data['asignaciones_ids'][0]) . "&nueva_persona_id=" . intval($data['nueva_persona_id'])) : '';
                                $url_acta_nueva = !empty($data['acta_id']) ? ("/Inventario-Tesa-Cardex/api/generar_acta_unificada.php?acta_id=" . intval($data['acta_id'])) : '';
                        ?>
                        <div class="alert alert-info mt-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <div>
                                    <strong>Resumen del traspaso:</strong>
                                    <ul class="mt-1 mb-0 small">
                                        <li>Custodio anterior: <?php echo htmlspecialchars($nombre_origen); ?></li>
                                        <li>Nuevo custodio: <?php echo htmlspecialchars($nombre_destino); ?></li>
                                        <li>Total equipos trasladados: <?php echo count($data['equipos_ids']); ?></li>
                                    </ul>
                                </div>
                                <div>
                                    <?php if ($url_acta_nueva): ?>
                                    <a href="<?php echo $url_acta_nueva; ?>" target="_blank" class="btn btn-success btn-sm mb-1">
                                        <i class="fas fa-file-pdf me-1"></i> Generar Acta Unificada (Nuevo Módulo)
                                    </a><br>
                                    <?php endif; ?>
                                    <?php if ($url_acta_vieja): ?>
                                    <a href="<?php echo $url_acta_vieja; ?>" target="_blank" class="btn btn-outline-success btn-sm mb-1">
                                        <i class="fas fa-file-pdf me-1"></i> Acta estilo anterior (1er equipo)
                                    </a><br>
                                    <?php endif; ?>
                                    <a href="/Inventario-Tesa-Cardex/modules/actas/generar.php?tipo=traspaso&persona_id=<?php echo intval($data['nueva_persona_id']); ?>&equipos_ids=<?php echo htmlspecialchars(implode(',', $data['equipos_ids'])); ?>" target="_blank" class="btn btn-outline-warning btn-sm mb-1">
                                        <i class="fas fa-plus-circle me-1"></i> Ir a Módulo Actas
                                    </a>
                                </div>
                            </div>
                            <?php if (!empty($eqListado)): ?>
                            <small class="d-block mb-2"><strong>Equipos:</strong></small>
                            <div class="table-responsive">
                                <table class="table table-sm small mb-0" style="background:#fff;border:1px solid #e7d5a3;">
                                    <thead class="bg-warning bg-opacity-10">
                                        <tr>
                                            <th style="width:40px">#</th>
                                            <th>Código</th>
                                            <th>Tipo</th>
                                            <th>Marca</th>
                                            <th>Modelo</th>
                                            <th>Serie</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($eqListado as $i => $eq): ?>
                                        <tr>
                                            <td><strong><?php echo ($i+1); ?></strong></td>
                                            <td><span class="font-monospace" style="color:#5a2d8c;"><?php echo htmlspecialchars($eq['codigo_barras']); ?></span></td>
                                            <td><?php echo htmlspecialchars($eq['tipo_equipo']); ?></td>
                                            <td><?php echo htmlspecialchars($eq['marca'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($eq['modelo'] ?? '-'); ?></td>
                                            <td class="font-monospace text-muted"><?php echo htmlspecialchars($eq['numero_serie'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php
                            unset($_SESSION['ultimo_traspaso_masivo']);
                        ?>
                        <?php } ?>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" id="formTraspasoMasivo">
                        <input type="hidden" name="realizar_traspaso_masivo" value="1">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-user-circle me-1 text-warning"></i> 1. Custodio ANTERIOR (Origen) *</label>
                                <select name="origen_persona_id" id="origen_persona_id" class="form-control form-select" required>
                                    <option value="">-- Seleccione la persona que actualmente tiene los equipos --</option>
                                    <?php while($p = $personas_origen->fetch_assoc()): ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo ($origen_persona_id == $p['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['nombres'] . ' - ' . $p['cedula'] . ' (' . ($p['cargo'] ?? 'Sin cargo') . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted d-block mt-1">Al seleccionar una persona, se cargará automáticamente el listado con todos los equipos que tiene asignados ACTIVAMENTE.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-user-plus me-1 text-success"></i> 3. NUEVO Custodio (Destino) *</label>
                                <select name="nueva_persona_id" id="nueva_persona_id" class="form-control form-select" required>
                                    <option value="">-- Seleccione la persona que recibirá los equipos --</option>
                                    <?php while($p = $personas_destino->fetch_assoc()): ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo ($nueva_persona_id == $p['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['nombres'] . ' - ' . $p['cedula'] . ' (' . ($p['cargo'] ?? 'Sin cargo') . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted d-block mt-1">Esta persona pasará a ser el nuevo custodio oficial de los equipos marcados con check.</small>
                            </div>
                        </div>

                        <div class="card border border-warning rounded mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 bg-warning bg-opacity-10 border-0 border-bottom border-warning">
                                <div>
                                    <h6 class="mb-0"><i class="fas fa-clipboard-list me-2 text-warning"></i> 2. Equipos del custodio a traspasar (marca los checkboxes)</h6>
                                    <small id="msgResumenSeleccion" class="text-muted d-block mt-1">Primero selecciona un custodio de origen para ver sus equipos.</small>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" id="btnMarcarTodos" class="btn btn-sm btn-outline-warning" disabled>
                                        <i class="fas fa-check-double me-1"></i> Marcar Todos
                                    </button>
                                    <button type="button" id="btnDesmarcarTodos" class="btn btn-sm btn-outline-secondary" disabled>
                                        <i class="fas fa-times me-1"></i> Desmarcar Todos
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div id="cargadorEquipos" class="text-center py-5 d-none">
                                    <div class="spinner-border text-warning mb-2" role="status"></div>
                                    <div class="text-muted">Cargando equipos del custodio...</div>
                                </div>
                                <div id="sinEquiposMsg" class="text-center py-5 d-none text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Esta persona NO tiene asignaciones activas. Primero entrega equipos a la persona.
                                </div>
                                <div id="errorEquiposMsg" class="text-center py-5 d-none text-danger"></div>
                                <div id="listaEquipos" class="table-responsive d-none">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="width: 40px;" class="text-center">#</th>
                                                <th>Código / Artículo</th>
                                                <th>Serie</th>
                                                <th>Fecha Asignación</th>
                                                <th style="width: 90px;" class="text-center">Traspasar?</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyEquipos"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-sticky-note me-1 text-secondary"></i> 4. Observaciones del traspaso</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Motivo del traspaso, condiciones, estado de los equipos, etc."><?php echo htmlspecialchars($observaciones_restore); ?></textarea>
                        </div>

                        <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="mb-1"><i class="fas fa-info-circle me-2 text-primary"></i> Antes de guardar:</h6>
                                <small class="text-muted">Verifica el custodio de origen, los equipos marcados y el nuevo custodio. Esta acción registrará devoluciones + nuevas asignaciones para cada equipo.</small>
                            </div>
                            <div>
                                <span id="contadorSeleccionados" class="badge bg-secondary me-2">0 equipos marcados</span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4 flex-wrap gap-2">
                            <a href="/Inventario-Tesa-Cardex/modules/movimientos/historial.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver a Historial
                            </a>
                            <button type="button" id="btnSubmitTraspaso" class="btn btn-warning" disabled>
                                <i class="fas fa-exchange-alt me-2"></i>Realizar Traspaso (0 Equipos)
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
    function initTraspasoMasivoModule() {
    const ORIGEN = document.getElementById('origen_persona_id');
    const DESTINO = document.getElementById('nueva_persona_id');
    const cargador = document.getElementById('cargadorEquipos');
    const sinEquiposMsg = document.getElementById('sinEquiposMsg');
    const errorMsg = document.getElementById('errorEquiposMsg');
    const listaWrap = document.getElementById('listaEquipos');
    const tbody = document.getElementById('tbodyEquipos');
    const btnTodos = document.getElementById('btnMarcarTodos');
    const btnNinguno = document.getElementById('btnDesmarcarTodos');
    const resumen = document.getElementById('msgResumenSeleccion');
    const badge = document.getElementById('contadorSeleccionados');
    const submit = document.getElementById('btnSubmitTraspaso');

    function actualizarContador(){
        const checks = tbody.querySelectorAll('input[type="checkbox"].chk-equipo:checked');
        const n = checks.length;
        badge.textContent = n + ' equipo' + (n === 1 ? '' : 's') + ' marcados';
        submit.disabled = (n <= 0);
        submit.innerHTML = '<i class="fas fa-exchange-alt me-2"></i>Realizar Traspaso (' + n + ' Equipo' + (n === 1 ? '' : 's') + ')';

        tbody.querySelectorAll('tr.fila-equipo').forEach(function(tr){
            const c = tr.querySelector('input[type="checkbox"].chk-equipo');
            const lbl = tr.querySelector('.label-estado');
            const icon = tr.querySelector('.icon-estado');
            const cell = tr.querySelector('.celda-estado');
            const badgeChk = tr.querySelector('.ui-check-visual');
            if (!c) return;
            if (c.checked) {
                tr.classList.add('bg-success', 'bg-opacity-15');
                tr.classList.remove('bg-transparent');
                if (cell) {
                    cell.classList.add('bg-white', 'bg-opacity-10');
                }
                if (lbl) {
                    lbl.textContent = 'SÍ, traspasar';
                    lbl.classList.remove('text-secondary');
                    lbl.classList.add('fw-bold', 'text-success');
                }
                if (icon) {
                    icon.className = 'icon-estado fas fa-check-circle text-success me-1';
                }
                if (badgeChk) {
                    badgeChk.className = 'ui-check-visual d-inline-flex align-items-center justify-content-center rounded-3 fw-bold me-2';
                    badgeChk.style.setProperty('width', '32px', 'important');
                    badgeChk.style.setProperty('height', '32px', 'important');
                    badgeChk.style.setProperty('min-width', '32px', 'important');
                    badgeChk.style.setProperty('background', '#10b981', 'important');
                    badgeChk.style.setProperty('color', '#ffffff', 'important');
                    badgeChk.style.setProperty('border', '2px solid #0f766e', 'important');
                    badgeChk.style.setProperty('box-shadow', '0 0 0 3px rgba(16,185,129,.22)', 'important');
                    badgeChk.innerHTML = '<i class="fas fa-check" style="font-size:14px;"></i>';
                }
            } else {
                tr.classList.remove('bg-success', 'bg-opacity-15');
                tr.classList.add('bg-transparent');
                if (cell) {
                    cell.classList.remove('bg-white', 'bg-opacity-10');
                }
                if (lbl) {
                    lbl.textContent = 'No incluir';
                    lbl.classList.add('text-secondary');
                    lbl.classList.remove('fw-bold', 'text-success');
                }
                if (icon) {
                    icon.className = 'icon-estado fas fa-minus-circle text-secondary me-1';
                }
                if (badgeChk) {
                    badgeChk.className = 'ui-check-visual d-inline-flex align-items-center justify-content-center rounded-3 fw-bold me-2';
                    badgeChk.style.setProperty('width', '32px', 'important');
                    badgeChk.style.setProperty('height', '32px', 'important');
                    badgeChk.style.setProperty('min-width', '32px', 'important');
                    badgeChk.style.setProperty('background', '#ffffff', 'important');
                    badgeChk.style.setProperty('color', '#adb5bd', 'important');
                    badgeChk.style.setProperty('border', '2px solid #cbb26a', 'important');
                    badgeChk.innerHTML = '<i class="fas fa-times" style="font-size:13px; opacity:.55;"></i>';
                }
            }
        });
    }

    function setCheckboxes(valor){
        const todos = tbody.querySelectorAll('input[type="checkbox"].chk-equipo');
        todos.forEach(function(c){
            if (c.checked === !!valor) return;
            c.checked = !!valor;
            try {
                const evt = new Event('change', { bubbles: true, cancelable: true });
                c.dispatchEvent(evt);
            } catch(e){}
        });
        actualizarContador();
    }

    btnTodos.addEventListener('click', function(){ setCheckboxes(true); });
    btnNinguno.addEventListener('click', function(){ setCheckboxes(false); });

    function renderRows(asignaciones, personaId){
        tbody.innerHTML = '';
        asignaciones.forEach(function(a, idx){
            const tr = document.createElement('tr');
            tr.className = 'fila-equipo align-middle bg-transparent cursor-pointer user-select-none transition-all';
            tr.style.setProperty('cursor','pointer');
            tr.dataset.asignacionId = String(a.asignacion_id);

            const tdCheck = document.createElement('td');
            tdCheck.className = 'celda-estado text-center ps-3';
            tdCheck.style.setProperty('white-space','nowrap');
            tdCheck.innerHTML = ''
                + '<div class="d-flex align-items-center justify-content-start gap-2">'
                + '  <span class="ui-check-visual d-inline-flex align-items-center justify-content-center rounded-3 fw-bold me-1">'
                + '    <i class="fas fa-times"></i>'
                + '  </span>'
                + '  <label class="d-inline-flex align-items-center mb-0 cursor-pointer label-estado text-secondary">'
                + '    <i class="icon-estado fas fa-minus-circle text-secondary me-1"></i>'
                + '    <span>No incluir</span>'
                + '  </label>'
                + '</div>';

            const tdNum = document.createElement('td');
            tdNum.className = 'text-center';
            tdNum.innerHTML = '<strong>' + (idx+1) + '</strong>';

            const tdArt = document.createElement('td');
            tdArt.innerHTML = ''
                + '<div><strong style="color:#5a2d8c;">' + (a.codigo_barras) + '</strong></div>'
                + '<div class="small text-muted">' + (a.articulo || 'Equipo')
                + (a.equipo_estado ? (' · <span class="text-info">Estado: ' + a.equipo_estado + '</span>') : '') + '</div>';

            const tdSerie = document.createElement('td');
            tdSerie.innerHTML = '<span class="font-monospace small">' + (a.serie || 'N/A') + '</span>';

            const tdFecha = document.createElement('td');
            tdFecha.className = 'small';
            tdFecha.textContent = a.fecha_asignacion_fmt || '-';

            const inputOculto = document.createElement('input');
            inputOculto.type = 'checkbox';
            inputOculto.className = 'chk-equipo visually-hidden';
            inputOculto.name = 'asignacion_ids[]';
            inputOculto.value = String(a.asignacion_id);
            inputOculto.id = 'chk_' + a.asignacion_id;
            tdCheck.appendChild(inputOculto);

            const labelDin = tdCheck.querySelector('.label-estado');
            if (labelDin) {
                labelDin.htmlFor = 'chk_' + a.asignacion_id;
            }

            function togglear(evt){
                if (evt) {
                    const tag = (evt.target && evt.target.tagName || '').toLowerCase();
                    const esInput = evt.target && evt.target === inputOculto;
                    if (tag === 'a') return;
                    if (esInput) return;
                }
                inputOculto.checked = !inputOculto.checked;
                try {
                    const ev = new Event('change', { bubbles: true });
                    inputOculto.dispatchEvent(ev);
                } catch(e){}
            }

            tr.addEventListener('click', togglear);
            inputOculto.addEventListener('change', function(e){ e.stopPropagation(); actualizarContador(); });
            if (labelDin) labelDin.addEventListener('click', function(e){ e.stopPropagation(); togglear(null); });
            const badgeVisual = tdCheck.querySelector('.ui-check-visual');
            if (badgeVisual) badgeVisual.addEventListener('click', function(e){ e.stopPropagation(); togglear(null); });

            tr.appendChild(tdNum);
            tr.appendChild(tdArt);
            tr.appendChild(tdSerie);
            tr.appendChild(tdFecha);
            tr.appendChild(tdCheck);
            tbody.appendChild(tr);
        });
        actualizarContador();
    }

    async function cargarEquipos(personaId){
        listaWrap.classList.add('d-none');
        sinEquiposMsg.classList.add('d-none');
        errorMsg.classList.add('d-none');
        btnTodos.disabled = true;
        btnNinguno.disabled = true;
        tbody.innerHTML = '';
        actualizarContador();
        if (!personaId) {
            resumen.textContent = 'Primero selecciona un custodio de origen para ver sus equipos.';
            return;
        }
        if (DESTINO.value === String(personaId)) {
            resumen.innerHTML = '<span class="text-danger">El destino es la misma persona del origen. ¡Cámbialo!</span>';
        } else {
            resumen.textContent = 'Cargando...';
        }
        cargador.classList.remove('d-none');
        try {
            const r = await fetch('/Inventario-Tesa-Cardex/api/get_asignaciones_por_persona.php?persona_id=' + encodeURIComponent(personaId));
            const t = await r.text();
            let data;
            try { data = JSON.parse(t); } catch(e) { throw new Error('Respuesta inválida: ' + t.substring(0, 150)); }
            cargador.classList.add('d-none');
            if (!data || !data.success) { throw new Error((data && data.error) ? data.error : 'Error al cargar asignaciones.'); }
            const asignaciones = data.asignaciones || [];
            if (!asignaciones.length) {
                sinEquiposMsg.classList.remove('d-none');
                resumen.innerHTML = '<span class="text-muted">El custodio seleccionado no tiene equipos asignados activamente.</span>';
                return;
            }
            renderRows(asignaciones, personaId);
            listaWrap.classList.remove('d-none');
            btnTodos.disabled = false;
            btnNinguno.disabled = false;
            resumen.innerHTML = '<span class="text-success">Cargados ' + asignaciones.length + ' equipos activos. Marca los que deseas traspasar.</span>';
        } catch(err) {
            cargador.classList.add('d-none');
            errorMsg.classList.remove('d-none');
            errorMsg.textContent = 'No se pudo cargar los equipos: ' + err.message;
            resumen.textContent = 'Error al cargar.';
        }
    }

    ORIGEN.addEventListener('change', function(){ cargarEquipos(this.value); });
    DESTINO.addEventListener('change', function(){
        const origenId = ORIGEN.value;
        const destId = DESTINO.value;
        if (origenId && destId && String(origenId) === String(destId)) {
            resumen.innerHTML = '<span class="text-danger">¡Atención! El custodio de ORIGEN y DESTINO son la misma persona. Debes cambiar uno de ellos.</span>';
        } else if (origenId) {
            const n = tbody.querySelectorAll('input[type="checkbox"].chk-equipo:checked').length;
            resumen.innerHTML = n > 0
                ? ('<span class="text-success">' + n + ' equipos listos para ser traspasados.</span>')
                : ('<span class="text-muted">Marca al menos un equipo con el checkbox.</span>');
        }
    });

    document.getElementById('btnSubmitTraspaso').addEventListener('click', function(ev){
        ev.preventDefault();
        ev.stopPropagation();
        const form = document.getElementById('formTraspasoMasivo');

        const todosLosChecks = document.querySelectorAll('input[type="checkbox"].chk-equipo');
        todosLosChecks.forEach(function(c){
            if (c.form !== form) {
                try { form.appendChild(c); } catch(err) {}
            }
        });

        const checks = document.querySelectorAll('input[type="checkbox"].chk-equipo:checked');
        if (!ORIGEN.value || !DESTINO.value) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Selecciona el custodio de ORIGEN y el de DESTINO.', confirmButtonColor: '#5a2d8c' });
            } else {
                alert('Selecciona origen y destino.');
            }
            return false;
        }
        if (String(ORIGEN.value) === String(DESTINO.value)) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Mismo custodio', text: 'No se puede traspasar a la misma persona. Cambia el origen o el destino.', confirmButtonColor: '#5a2d8c' });
            } else {
                alert('No se puede traspasar a la misma persona.');
            }
            return false;
        }
        if (checks.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Sin equipos marcados', text: 'Marca al menos UN equipo con el checkbox para traspasar.', confirmButtonColor: '#5a2d8c' });
            } else {
                alert('Marca al menos UN equipo para realizar el traspaso.');
            }
            return false;
        }

        submit.disabled = true;
        submit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando traspaso (' + checks.length + ' equipo' + (checks.length===1?'':'s') + ')...';

        try {
            var params = new URLSearchParams();
            params.append('realizar_traspaso_masivo', '1');
            params.append('origen_persona_id', String(ORIGEN.value || ''));
            params.append('nueva_persona_id', String(DESTINO.value || ''));
            checks.forEach(function(c){ params.append('asignacion_ids[]', String(c.value)); });
            var ta = document.querySelector('textarea[name="observaciones"]');
            params.append('observaciones', ta ? (ta.value || '') : '');

            var actionUrl = form.getAttribute('action') || (location.pathname + (location.search || ''));
            var xhr = new XMLHttpRequest();
            xhr.open('POST', actionUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function() {
                try {
                    var respText = String(xhr.responseText || '');
                    var json = null;
                    try { json = JSON.parse(respText); } catch(e) { json = null; }
                    if (json && json.redirect_url) {
                        location.href = json.redirect_url;
                        return;
                    }
                } catch(tryCatch) {}
                var loc = xhr.getResponseHeader('Location') || xhr.getResponseHeader('location') || '';
                if (loc) { location.href = loc; return; }
                if (xhr.responseURL && xhr.responseURL.indexOf('?ok=') >= 0) { location.href = xhr.responseURL; return; }
                if (xhr.responseURL && xhr.responseURL.indexOf('?err=') >= 0) { location.href = xhr.responseURL; return; }
                var resp = String(xhr.responseText || '');
                if (resp.indexOf('Location:') >= 0) {
                    var m = resp.match(/Location:\s*([^\r\n]+)/i);
                    if (m && m[1]) { location.href = m[1]; return; }
                }
                if (resp.indexOf('?ok=1') >= 0) { location.href = 'traspaso.php?ok=1'; return; }
                if (resp.indexOf('?err=1') >= 0) { location.href = 'traspaso.php?err=1'; return; }
                if (xhr.status >= 200 && xhr.status < 400) { location.href = 'traspaso.php?ok=1'; return; }
                location.href = 'traspaso.php?err=1';
            };
            xhr.onerror = function() {
                submit.disabled = false;
                submit.innerHTML = '<i class="fas fa-share-alt me-2"></i>Realizar Traspaso (' + checks.length + ' Equipo' + (checks.length===1?'':'s') + ')';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo conectar con el servidor. Revisa tu conexión.', confirmButtonColor: '#dc2626' });
                } else {
                    alert('Error de conexión con el servidor.');
                }
            };
            xhr.send(params.toString());
        } catch(errSubmit) {
            submit.disabled = false;
            submit.innerHTML = '<i class="fas fa-share-alt me-2"></i>Realizar Traspaso (' + checks.length + ' Equipo' + (checks.length===1?'':'s') + ')';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error interno', text: errSubmit.message || 'Error al preparar el envío.', confirmButtonColor: '#dc2626' });
            } else {
                alert('Error al enviar el traspaso: ' + (errSubmit.message || ''));
            }
        }
        return false;
    });

    // =========================================================
    // AUTO-RESTORE (después de PRG por error):
    // Si el servidor recordó un origen, cargar sus equipos y
    // volver a marcar los checkboxes que el usuario ya había
    // seleccionado antes del error.
    // =========================================================
    const RESTORE_ORIGEN_ID = <?php echo $origen_persona_id > 0 ? intval($origen_persona_id) : '0'; ?>;
    const RESTORE_DESTINO_ID = <?php echo $nueva_persona_id > 0 ? intval($nueva_persona_id) : '0'; ?>;
    const RESTORE_MARCAR_IDS = <?php echo json_encode($asignacion_ids_restore); ?>;
    if (RESTORE_ORIGEN_ID > 0) {
        const __orig = RESTORE_ORIGEN_ID;
        const __dest = RESTORE_DESTINO_ID;
        const __ids = Array.isArray(RESTORE_MARCAR_IDS) ? RESTORE_MARCAR_IDS.map(function(x){ return String(x); }) : [];
        (function restaurarDespuesError(){
            if (typeof cargarEquipos !== 'function') { setTimeout(restaurarDespuesError, 80); return; }
            cargarEquipos(String(__orig)).then(function(){
                var esperar = setInterval(function(){
                    if (document.getElementById('cargadorEquipos').classList.contains('d-none')) {
                        clearInterval(esperar);
                        var todos = tbody.querySelectorAll('input[type="checkbox"].chk-equipo');
                        todos.forEach(function(chk){
                            if (__ids.indexOf(String(chk.value)) >= 0) {
                                chk.checked = true;
                                try { chk.dispatchEvent(new Event('change', { bubbles:true, cancelable:true })); } catch(e) {}
                            }
                        });
                        actualizarContador();
                        if (__dest > 0 && String(DESTINO.value) !== String(__dest)) {
                            DESTINO.value = String(__dest);
                            try { DESTINO.dispatchEvent(new Event('change', { bubbles:true, cancelable:true })); } catch(e) {}
                        }
                    }
                }, 80);
            }).catch(function(){});
        })();
    }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTraspasoMasivoModule, { once: true });
    } else {
        initTraspasoMasivoModule();
    }
})();
</script>

<?php include '../../includes/footer.php'; ?>

