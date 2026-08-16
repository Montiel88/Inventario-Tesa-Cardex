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
    header('Location: /inventario_ti/login.php');
    exit();
}
if ($_SESSION['user_rol'] != 1) {
    header('Location: /inventario_ti/modules/dashboard.php?error=No tienes permisos');
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['realizar_traspaso_masivo'])) {
    $asignacion_ids_raw = $_POST['asignacion_ids'] ?? [];
    $origen_persona_id = intval($_POST['origen_persona_id'] ?? 0);
    $nueva_persona_id = intval($_POST['nueva_persona_id'] ?? 0);
    $observaciones = $conn->real_escape_string(trim($_POST['observaciones'] ?? ''));

    $asignacion_ids = array_filter(array_map('intval', (array)$asignacion_ids_raw));
    sort($asignacion_ids);
    $asignacion_ids = array_values(array_unique($asignacion_ids));

    if ($origen_persona_id <= 0 || $nueva_persona_id <= 0) {
        $error = '❌ Debes seleccionar la persona de origen y la nueva persona de destino.';
    } elseif ($origen_persona_id === $nueva_persona_id) {
        $error = '❌ No se puede realizar un traspaso a la misma persona. Por favor selecciona un destino distinto al origen.';
    } elseif (empty($asignacion_ids)) {
        $error = '❌ Debes marcar al menos UN equipo para traspasar (usa los checkboxes del listado).';
    } else {
        // Auto-migración opcional (no bloqueante): agrega columnas usuario_id si la BD no las tiene.
        // No las usamos por ahora para no romper el flujo existente.
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
            if (!$stmt) throw new Exception('Error al validando asignaciones: ' . $conn->error);
            $types = str_repeat('i', count($asignacion_ids));
            $stmt->bind_param($types, ...$asignacion_ids);
            $stmt->execute();
            $resAsigs = $stmt->get_result();
            $asig_list = [];
            while ($r = $resAsigs->fetch_assoc()) {
                if (intval($r['persona_asignada']) !== $origen_persona_id) {
                    throw new Exception('Una o más asignaciones no pertenecen a la persona de origen seleccionada.');
                }
                $asig_list[] = $r;
            }
            if (count($asig_list) !== count($asignacion_ids)) {
                throw new Exception('Algunas asignaciones no existen, no están activas o no pertenecen al custodio de origen.');
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
                    $insertAsigStmt = $conn->prepare("INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion, observaciones) VALUES (?, ?, NOW(), ?)");
                    $movDevStmt = $conn->prepare("INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) VALUES (?, ?, 'DEVOLUCION', ?)");
                    $movAsigStmt = $conn->prepare("INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) VALUES (?, ?, 'ASIGNACION', ?)");
                }

                $obsCierre = 'Traspasado a persona ID ' . $nueva_persona_id . ($observaciones ? '. ' . $observaciones : '');
                $obsCierreBind = $obsCierre;
                $asigIdBind = $asignacion_id;
                $cerrarStmt->bind_param('si', $obsCierreBind, $asigIdBind);
                if (!$cerrarStmt->execute()) throw new Exception('Error cerrando asignación ID ' . $asignacion_id);

                $obsNueva = 'Traspaso desde asignación ID ' . $asignacion_id . ' desde persona ID ' . $persona_anterior_id . ($observaciones ? '. ' . $observaciones : '');
                $eqIdBind1 = $equipo_id;
                $newPersBind = $nueva_persona_id;
                $insertAsigStmt->bind_param('iis', $eqIdBind1, $newPersBind, $obsNueva);
                if (!$insertAsigStmt->execute()) throw new Exception('Error creando nueva asignación para equipo ID ' . $equipo_id);

                $obsMovDev = 'Devolución por traspaso (masivo) a nueva persona';
                $eqIdBindDev = $equipo_id;
                $persIdDev = $persona_anterior_id;
                $movDevStmt->bind_param('iis', $eqIdBindDev, $persIdDev, $obsMovDev);
                $movDevStmt->execute();

                $obsMovAsig = 'Asignación por traspaso masivo';
                $eqIdBindAsig = $equipo_id;
                $persIdAsig = $nueva_persona_id;
                $movAsigStmt->bind_param('iis', $eqIdBindAsig, $persIdAsig, $obsMovAsig);
                $movAsigStmt->execute();

                $equipos_traspasados[] = $equipo_id;
                $total++;
            }

            try {
                $equipos_ids_str = implode(',', $equipos_traspasados);
                $codigo_acta = function_exists('generarCodigoActa') ? generarCodigoActa('traspaso') : ('TRASPASO-M-' . date('YmdHis'));
                $insertActa = $conn->prepare("INSERT INTO actas (codigo_acta, tipo_acta, persona_id, usuario_id, equipos_ids, motivo, fecha_generacion) VALUES (?, 'traspaso', ?, ?, ?, ?, NOW())");
                if ($insertActa) {
                    $usuarioId = $_SESSION['user_id'];
                    $motivo = $observaciones ? $observaciones : 'Traspaso masivo realizado desde módulo Movimientos';
                    $insertActa->bind_param('siiss', $codigo_acta, $nueva_persona_id, $usuarioId, $equipos_ids_str, $motivo);
                    $insertActa->execute();
                    $acta_insertada_id = $conn->insert_id;
                } else {
                    $acta_insertada_id = null;
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
            $mensaje = "✅ Traspaso realizado correctamente. Se trasladaron $total equipos.";

        } catch (Exception $e) {
            $conn->rollback();
            $error = "❌ Error al realizar el traspaso: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Traspaso de Equipos (Cambio de Custodio)</h4>
                    <small class="text-muted d-block" style="color:#222"><strong>Total asignaciones activas en el sistema: <?php echo $total_asignaciones_activas; ?></small>
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

                                $url_acta_vieja = !empty($data['asignaciones_ids'][0]) ? ("/inventario_ti/api/generar_acta_traspaso.php?asignacion_id=" . intval($data['asignaciones_ids'][0]) . "&nueva_persona_id=" . intval($data['nueva_persona_id'])) : '';
                                $url_acta_nueva = !empty($data['acta_id']) ? ("/inventario_ti/api/generar_acta_unificada.php?acta_id=" . intval($data['acta_id'])) : '';
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
                                    <a href="/inventario_ti/modules/actas/generar.php?tipo=traspaso&persona_id=<?php echo intval($data['nueva_persona_id']); ?>&equipos_ids=<?php echo htmlspecialchars(implode(',', $data['equipos_ids'])); ?>" target="_blank" class="btn btn-outline-warning btn-sm mb-1">
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
                                            <th>Código</th><th>Artículo</th><th>Serie</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($eqListado as $eq):
                                            $art = trim(($eq['tipo_equipo'] ?? '') . ' ' . ($eq['marca'] ?? '') . ' ' . ($eq['modelo'] ?? ''));
                                            $serie = !empty($eq['numero_serie']) ? $eq['numero_serie'] : 'N/A';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($eq['codigo_barras']); ?></td>
                                            <td><?php echo htmlspecialchars($art); ?></td>
                                            <td><?php echo htmlspecialchars($serie); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                            <div class="mt-3 text-end">
                                <a href="traspaso.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-sync me-1"></i> Hacer Otro Traspaso
                                </a>
                                <a href="/inventario_ti/modules/movimientos/historial.php" class="btn btn-outline-secondary btn-sm ms-1">
                                    <i class="fas fa-history me-1"></i> Ver Historial
                                </a>
                            </div>
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
                                        <option value="<?php echo $p['id']; ?>">
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
                                        <option value="<?php echo $p['id']; ?>">
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
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Motivo del traspaso, condiciones, estado de los equipos, etc."></textarea>
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
                            <a href="/inventario_ti/modules/movimientos/historial.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver a Historial
                            </a>
                            <button type="submit" id="btnSubmitTraspaso" class="btn btn-warning" disabled>
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
                    badgeChk.style.setProperty('background', '#157347', 'important');
                    badgeChk.style.setProperty('color', '#fff', 'important');
                    badgeChk.style.setProperty('box-shadow', '0 0 0 3px rgba(21,115,71,0.25)', 'important');
                    badgeChk.innerHTML = '<i class="fas fa-check" style="font-size:15px"></i>';
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
            const r = await fetch('/inventario_ti/api/get_asignaciones_por_persona.php?persona_id=' + encodeURIComponent(personaId));
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

    document.getElementById('formTraspasoMasivo').addEventListener('submit', function(e){
        const checks = tbody.querySelectorAll('input[type="checkbox"].chk-equipo:checked');
        if (!ORIGEN.value || !DESTINO.value) {
            e.preventDefault();
            alert('Selecciona origen y destino.');
            return;
        }
        if (String(ORIGEN.value) === String(DESTINO.value)) {
            e.preventDefault();
            alert('No se puede traspasar a la misma persona.');
            return;
        }
        if (checks.length === 0) {
            e.preventDefault();
            alert('Marca al menos UN equipo para realizar el traspaso.');
            return;
        }
        submit.disabled = true;
        submit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando traspaso...';
    });
})();
</script>

<?php include '../../includes/footer.php'; ?>
