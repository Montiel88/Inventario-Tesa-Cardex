<?php
session_start();
require_once '../../config/permisos.php';
require_once '../../config/database.php';
verificarSesion();

include '../../includes/header.php';

// ====================== HELPERS PRG + FLASH ======================
$_IS_XHR_PR = false;
if (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') === 0
) {
    $_IS_XHR_PR = true;
}

function _prestamo_rapido_redir($url, $extraJson = []) {
    global $_IS_XHR_PR;
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if ($_IS_XHR_PR) {
        header('Content-Type: application/json; charset=utf-8');
        $payload = array_merge(['redirect_url' => $url, 'ok' => 1], $extraJson);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    header('Location: ' . $url);
    exit;
}

function _fallo_pr($msgErr, $restore = []) {
    $_SESSION['error'] = $msgErr;
    if (!empty($restore)) {
        $_SESSION['flash_pr_restore'] = $restore;
    }
    _prestamo_rapido_redir('registrar.php?err=1');
}

// ====================== POST HANDLER ======================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipo_id      = intval($_POST['equipo_id']       ?? 0);
    $persona_id     = intval($_POST['persona_id']      ?? 0);
    $fecha_estimada = trim(strval($_POST['fecha_estimada'] ?? ''));
    $observaciones  = trim(strval($_POST['observaciones']  ?? ''));

    $restore = [
        'equipo_id'      => $equipo_id,
        'persona_id'     => $persona_id,
        'fecha_estimada' => $fecha_estimada,
        'observaciones'  => $observaciones,
    ];

    if ($equipo_id <= 0 || $persona_id <= 0 || $fecha_estimada === '') {
        _fallo_pr('Todos los campos marcados con (*) son obligatorios.', $restore);
    }

    $dt = \DateTime::createFromFormat('Y-m-d', $fecha_estimada);
    if (!$dt || $dt->format('Y-m-d') !== $fecha_estimada) {
        _fallo_pr('La fecha estimada de devolución no es válida (formato YYYY-MM-DD).', $restore);
    }
    $hoy = new \DateTime('today midnight');
    if ($dt < $hoy) {
        _fallo_pr('La fecha estimada no puede ser anterior a hoy.', $restore);
    }

    $stmtEq = $conn->prepare("SELECT id, estado, codigo_barras, tipo_equipo, marca, modelo FROM equipos WHERE id = ? LIMIT 1");
    $stmtEq->bind_param('i', $equipo_id);
    $stmtEq->execute();
    $equipo = $stmtEq->get_result()->fetch_assoc();
    $stmtEq->close();
    if (!$equipo) {
        _fallo_pr('El equipo seleccionado no existe.', $restore);
    }
    if ($equipo['estado'] !== 'Disponible') {
        _fallo_pr('El equipo ya no está disponible. Estado actual: ' . $equipo['estado'], $restore);
    }

    $stmtPer = $conn->prepare("SELECT id, nombres, cedula FROM personas WHERE id = ? LIMIT 1");
    $stmtPer->bind_param('i', $persona_id);
    $stmtPer->execute();
    $persona = $stmtPer->get_result()->fetch_assoc();
    $stmtPer->close();
    if (!$persona) {
        _fallo_pr('La persona seleccionada no existe.', $restore);
    }

    $uid = intval($_SESSION['user_id'] ?? 0);
    $insertado_id = 0;
    $movimiento_id = 0;
    try {
        if (!$conn->begin_transaction()) {
            throw new \Exception('No se pudo iniciar transacción');
        }

        // 1) Prestamo rápido
        $stmtIns = $conn->prepare("INSERT INTO prestamos_rapidos
            (equipo_id, persona_id, fecha_prestamo, fecha_estimada_devolucion, observaciones, created_by)
            VALUES (?, ?, NOW(), ?, ?, ?)");
        $stmtIns->bind_param('iissi', $equipo_id, $persona_id, $fecha_estimada, $observaciones, $uid);
        if (!$stmtIns->execute()) {
            throw new \Exception('Error al registrar préstamo rápido: ' . $stmtIns->error);
        }
        $insertado_id = intval($conn->insert_id);
        $stmtIns->close();

        // 2) Estado equipo Prestado
        $stmtUpd = $conn->prepare("UPDATE equipos SET estado = 'Prestado' WHERE id = ? LIMIT 1");
        $stmtUpd->bind_param('i', $equipo_id);
        if (!$stmtUpd->execute()) {
            throw new \Exception('Error al marcar equipo como Prestado: ' . $stmtUpd->error);
        }
        $stmtUpd->close();

        // 3) Movimiento trazabilidad
        $obs_mov = 'Préstamo rápido sin acta. Fecha estimada de devolución: ' . $fecha_estimada . ($observaciones !== '' ? '. ' . $observaciones : '');
        $stmtMov = $conn->prepare("INSERT INTO movimientos
            (equipo_id, persona_id, tipo_movimiento, observaciones)
            VALUES (?, ?, 'PRESTAMO_RAPIDO', ?)");
        $stmtMov->bind_param('iis', $equipo_id, $persona_id, $obs_mov);
        if (!$stmtMov->execute()) {
            throw new \Exception('Error al registrar movimiento: ' . $stmtMov->error);
        }
        $movimiento_id = intval($conn->insert_id);
        $stmtMov->close();

        // 4) Log / notificaciones (fallo silencioso)
        try {
            if (function_exists('registrarLog')) {
                registrarLog('PRESTAMO_RAPIDO', "Registrado préstamo rápido #$insertado_id. Equipo $equipo_id a persona $persona_id. Devolución estimada: $fecha_estimada.");
            }
        } catch (\Exception $eLog) { $eLog = null; }
        try {
            if (function_exists('agregarNotificacion')) {
                agregarNotificacion($persona_id, 'info', 'Nuevo préstamo rápido', "Se te asignó el equipo {$equipo['codigo_barras']} en calidad de préstamo temporal; devolver el $fecha_estimada.");
            }
        } catch (\Exception $eNotif) { $eNotif = null; }

        if (!$conn->commit()) {
            throw new \Exception('No se pudo confirmar transacción');
        }
    } catch (\Exception $e) {
        try { $conn->rollback(); } catch (\Exception $eRb) { $eRb = null; }
        _fallo_pr('No se pudo registrar el préstamo rápido: ' . $e->getMessage(), $restore);
    }

    // ======= ARMAR DATOS PARA POPUP 3 BOTONES =======
    $codigoEquipo = strval($equipo['codigo_barras'] ?? '');
    $equipoNombre = trim(($equipo['tipo_equipo'] ?? '') . ' ' . ($equipo['marca'] ?? '') . ' ' . ($equipo['modelo'] ?? ''));
    $personaNombre = trim(($persona['nombres'] ?? '') . (empty($persona['cedula']) ? '' : (' - ' . $persona['cedula'])));
    $resumenUrl = "/inventario_ti/modules/prestamos_rapidos/listar.php";
    $urlOtro    = "/inventario_ti/modules/prestamos_rapidos/registrar.php";
    $urlDash    = "/inventario_ti/modules/dashboard.php";

    $_SESSION['success'] = "Préstamo rápido registrado correctamente. Equipo: $codigoEquipo.";
    $_SESSION['ui_popup_prestamo_rapido'] = [
        'prestamo_id'          => $insertado_id,
        'movimiento_id'        => $movimiento_id,
        'equipo_nombre'        => $equipoNombre,
        'persona_nombre'       => $personaNombre,
        'codigo_equipo'        => $codigoEquipo,
        'fecha_estimada'       => $fecha_estimada,
        'observaciones'        => $observaciones,
        'resumen_url'          => $resumenUrl,
    ];
    $_SESSION['ultimo_prestamo_rapido'] = [
        'id'             => $insertado_id,
        'movimiento_id'  => $movimiento_id,
        'equipo_id'      => $equipo_id,
        'persona_id'     => $persona_id,
    ];

    // Limpiar restore por si venía de un error anterior
    unset($_SESSION['flash_pr_restore']);

    _prestamo_rapido_redir(
        'registrar.php?ok=1',
        [
            'prestamo_id'   => $insertado_id,
            'movimiento_id' => $movimiento_id,
            'codigo_equipo' => $codigoEquipo,
            'persona'       => $persona['nombres'] ?? '',
        ]
    );
}

// ====================== GET HANDLER: RESTORE FLASH ======================
$restore = [];
if (!empty($_SESSION['flash_pr_restore']) && is_array($_SESSION['flash_pr_restore'])) {
    $restore = $_SESSION['flash_pr_restore'];
    unset($_SESSION['flash_pr_restore']);
}
$persRestId   = intval($restore['persona_id']     ?? 0);
$eqRestId     = intval($restore['equipo_id']      ?? 0);
$fecRest      = strval($restore['fecha_estimada'] ?? '');
$obsRest      = strval($restore['observaciones']  ?? '');

// Listas
$personas = $conn->query("SELECT id, nombres, cedula FROM personas ORDER BY nombres");
$equipos  = $conn->query("SELECT id, codigo_barras, tipo_equipo, marca, modelo
                          FROM equipos
                          WHERE estado = 'Disponible'
                          ORDER BY tipo_equipo, marca");

// Valores mínimos fecha
$minFecha = date('Y-m-d');
?>

<script>
const RESTORE_PER_ID = <?php echo json_encode($persRestId); ?>;
const RESTORE_EQ_ID  = <?php echo json_encode($eqRestId); ?>;
const RESTORE_FECHA  = <?php echo json_encode($fecRest); ?>;
const RESTORE_OBS    = <?php echo json_encode($obsRest); ?>;
</script>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card" style="border-radius:16px;background:linear-gradient(135deg,#2a164a 0%,#1e1038 100%);border:1px solid rgba(147,51,234,0.25);box-shadow:0 20px 60px rgba(90,45,140,0.4);">
                <div class="card-header py-4" style="background:transparent;border-bottom:2px solid rgba(243,178,41,0.6);">
                    <h4 class="mb-1 text-white"><i class="fas fa-hand-holding me-2" style="color:#f3b229;"></i>Registrar Préstamo Rápido</h4>
                    <p class="text-muted mb-0" style="color:#cbd5e1!important;">Sin acta — solo para salidas temporales de bodega (devolución manual)</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form id="formPrestamoRapido" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label text-white fw-semibold mb-2" for="pr_persona_id">Persona que recibe <span class="text-danger">*</span></label>
                            <select name="persona_id" id="pr_persona_id" class="form-control form-control-lg" required style="background:#1a0d33;color:#fff;border-color:rgba(147,51,234,0.5);min-height:48px;">
                                <option value="">-- Seleccione --</option>
                                <?php while($p = $personas->fetch_assoc()): ?>
                                    <option value="<?php echo intval($p['id']); ?>" <?php echo ($persRestId === intval($p['id']) ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($p['nombres'] . ' - ' . $p['cedula']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-white fw-semibold mb-2" for="pr_equipo_id">Equipo a prestar <span class="text-danger">*</span></label>
                            <select name="equipo_id" id="pr_equipo_id" class="form-control form-control-lg" required style="background:#1a0d33;color:#fff;border-color:rgba(147,51,234,0.5);min-height:48px;">
                                <option value="">-- Seleccione --</option>
                                <?php while($e = $equipos->fetch_assoc()): ?>
                                    <option value="<?php echo intval($e['id']); ?>" <?php echo ($eqRestId === intval($e['id']) ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($e['codigo_barras'] . ' - ' . $e['tipo_equipo'] . ' ' . $e['marca'] . ' ' . $e['modelo']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-white fw-semibold mb-2" for="pr_fecha_estimada">Fecha estimada de devolución <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="fecha_estimada"
                                   id="pr_fecha_estimada"
                                   min="<?php echo htmlspecialchars($minFecha); ?>"
                                   value="<?php echo htmlspecialchars($fecRest ?: $minFecha); ?>"
                                   class="form-control form-control-lg"
                                   required
                                   style="background:#1a0d33;color:#fff;border-color:rgba(147,51,234,0.5);min-height:48px;">
                        </div>

                        <div class="mb-5">
                            <label class="form-label text-white fw-semibold mb-2" for="pr_observaciones">Observaciones <small class="text-muted fw-normal">(opcional)</small></label>
                            <textarea name="observaciones"
                                      id="pr_observaciones"
                                      rows="3"
                                      class="form-control form-control-lg"
                                      style="background:#1a0d33;color:#fff;border-color:rgba(147,51,234,0.5);resize:vertical;"><?php echo htmlspecialchars($obsRest); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                            <a href="listar.php" class="btn btn-lg btn-secondary" type="button" style="min-width:180px;background:#475569;border-color:#475569;">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="button"
                                    id="btnRegistrarPrestamoRapido"
                                    class="btn btn-lg btn-primary"
                                    style="min-width:240px;background:linear-gradient(135deg,#7c3aed 0%,#5a2d8c 100%);border-color:#7c3aed;color:#fff;font-weight:700;">
                                <i class="fas fa-save me-2"></i><span class="btn-text">Registrar Préstamo Rápido</span>
                                <span class="btn-spinner d-none ms-2 spinner-border spinner-border-sm text-white" role="status" aria-hidden="true"></span>
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
    function __escapeHtmlP(str) {
        if (str === null || str === undefined) return '';
        str = String(str);
        const map = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;', '`':'&#96;' };
        return str.replace(/[&<>"'`]/g, function(ch) { return map[ch] || ch; });
    }
    function initPrestamoRapidoModule() {
        const formEl    = document.getElementById('formPrestamoRapido');
        const btn       = document.getElementById('btnRegistrarPrestamoRapido');
        const btnText   = btn ? btn.querySelector('.btn-text')    : null;
        const btnSpinner= btn ? btn.querySelector('.btn-spinner') : null;
        const personaSel = document.getElementById('pr_persona_id');
        const equipoSel  = document.getElementById('pr_equipo_id');
        const fechaInp   = document.getElementById('pr_fecha_estimada');
        const obsInp     = document.getElementById('pr_observaciones');

        // Restore flash (si hubo error antes)
        if (personaSel && typeof RESTORE_PER_ID !== 'undefined' && RESTORE_PER_ID > 0) {
            personaSel.value = String(RESTORE_PER_ID);
        }
        if (equipoSel && typeof RESTORE_EQ_ID !== 'undefined' && RESTORE_EQ_ID > 0) {
            equipoSel.value = String(RESTORE_EQ_ID);
        }
        if (fechaInp && typeof RESTORE_FECHA !== 'undefined' && RESTORE_FECHA) {
            fechaInp.value = RESTORE_FECHA;
        }
        if (obsInp && typeof RESTORE_OBS !== 'undefined' && RESTORE_OBS) {
            obsInp.value = RESTORE_OBS;
        }

        function setLoading(loading, textoOri) {
            if (!btn) return;
            if (loading) {
                btn.disabled = true;
                if (btnText)    btnText.textContent    = 'Guardando…';
                if (btnSpinner) btnSpinner.classList.remove('d-none');
            } else {
                btn.disabled = false;
                if (btnText)    btnText.textContent    = textoOri || 'Registrar Préstamo Rápido';
                if (btnSpinner) btnSpinner.classList.add('d-none');
            }
        }

        function _do_submit() {
            if (!formEl) return;
            const persona_id = parseInt((personaSel && personaSel.value) || '0', 10);
            const equipo_id  = parseInt((equipoSel  && equipoSel.value)  || '0', 10);
            const fecha      = (fechaInp && fechaInp.value) ? String(fechaInp.value).trim() : '';
            if (persona_id <= 0 || equipo_id <= 0 || fecha === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos obligatorios',
                    text: 'Por favor selecciona persona, equipo y fecha estimada de devolución.',
                    confirmButtonColor: '#f3b229',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
            const personaTxt = personaSel ? String(personaSel.options[personaSel.selectedIndex].textContent || '') : '';
            const equipoTxt  = equipoSel  ? String(equipoSel.options[equipoSel.selectedIndex].textContent || '') : '';
            let htmlConfirm = '<div class="text-start">';
            htmlConfirm += '<p class="mb-2"><strong>Persona que recibe:</strong><br><span class="small">' + __escapeHtmlP(personaTxt) + '</span></p>';
            htmlConfirm += '<p class="mb-2"><strong>Equipo prestado:</strong><br><span class="small">' + __escapeHtmlP(equipoTxt) + '</span></p>';
            htmlConfirm += '<p class="mb-0"><strong>Devolución estimada:</strong> <span class="badge bg-info fs-6">' + __escapeHtmlP(fecha) + '</span></p>';
            if (obsInp && obsInp.value.trim()) {
                htmlConfirm += '<p class="mt-2 mb-0 small text-muted"><strong>Obs: </strong>' + __escapeHtmlP(obsInp.value.trim()) + '</p>';
            }
            htmlConfirm += '</div>';

            Swal.fire({
                icon: 'question',
                title: '¿Confirmar préstamo rápido?',
                html: htmlConfirm,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check me-1"></i> Sí, registrar préstamo',
                cancelButtonText : '<i class="fas fa-times me-1"></i> Cancelar',
                confirmButtonColor: '#198754',
                cancelButtonColor : '#6c757d',
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                focusConfirm: false
            }).then((res) => {
                if (!res.isConfirmed) return;
                const textoOriginal = btnText ? String(btnText.textContent) : 'Registrar Préstamo Rápido';
                setLoading(true, textoOriginal);
                const fd = new FormData(formEl);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'registrar.php', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onload = function () {
                    setLoading(false, textoOriginal);
                    let data = null;
                    try { data = JSON.parse(xhr.responseText || '{}'); } catch(e) { data = null; }
                    if (xhr.status >= 200 && xhr.status < 300 && data && data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }
                    let msg = 'Respuesta inesperada del servidor al registrar préstamo rápido.';
                    if (data && data.message) msg = String(data.message);
                    else if (xhr.status === 400 || xhr.status === 500) msg = 'Error (' + xhr.status + '). Intenta nuevamente.';
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo registrar',
                        text: msg,
                        confirmButtonColor: '#b91c1c',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        if (data && data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            window.location.reload();
                        }
                    });
                };
                xhr.onerror = function () {
                    setLoading(false, textoOriginal);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo comunicar con el servidor. Revisa tu conexión e intenta nuevamente.',
                        confirmButtonColor: '#b91c1c',
                        confirmButtonText: 'Reintentar'
                    }).then(() => {
                        setTimeout(() => _do_submit(), 300);
                    });
                };
                xhr.send(fd);
            });
        }

        if (btn) {
            btn.addEventListener('click', function(e){
                e.preventDefault();
                e.stopPropagation();
                _do_submit();
            });
        }
    }

    function whenReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function once() {
                document.removeEventListener('DOMContentLoaded', once);
                fn();
            }, { once: true });
        } else {
            fn();
        }
    }
    whenReady(initPrestamoRapidoModule);
})();
</script>

<?php include '../../includes/footer.php'; ?>
