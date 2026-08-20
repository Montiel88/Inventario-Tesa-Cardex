<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /Inventario-Tesa-Cardex/login.php');
    exit();
}

// SOLO ADMIN PUEDE AGREGAR PERSONAS
if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=No tienes permisos para agregar personas');
    exit();
}

require_once '../../config/database.php';
require_once '../../config/notificaciones_helper.php';
include '../../includes/header.php';
require_once '../../config/validaciones.php';

// ============================================
// HELPERS PRG + FLASH PARA PERSONAS
// ============================================
$_IS_XHR_PERS = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') === 0
);

function _persona_redir($url, $extraJson = [])
{
    global $_IS_XHR_PERS;
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if ($_IS_XHR_PERS) {
        header('Content-Type: application/json; charset=utf-8');
        $payload = array_merge(['ok' => 1, 'redirect_url' => $url], $extraJson);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit();
    }
    header('Location: ' . $url);
    exit();
}

function _fallo_pers($msgErr, $restore = [])
{
    $_SESSION['error'] = $msgErr;
    if (!empty($restore)) {
        $_SESSION['flash_personas_restore'] = $restore;
    }
    _persona_redir('agregar.php?err=1');
}

$mensaje = '';
$error   = '';

// ============================================
// PROCESAR EL FORMULARIO CUANDO SE ENVÍA
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $cedula        = trim(strval($_POST['cedula']        ?? ''));
    $nombres       = trim(strval($_POST['nombres']       ?? ''));
    $correo        = trim(strval($_POST['correo']        ?? ''));
    $cargo         = trim(strval($_POST['cargo']         ?? ''));
    $telefono      = trim(strval($_POST['telefono']      ?? ''));
    $observaciones = trim(strval($_POST['observaciones'] ?? ''));

    $restore = [
        'cedula'        => $cedula,
        'nombres'       => $nombres,
        'correo'        => $correo,
        'cargo'         => $cargo,
        'telefono'      => $telefono,
        'observaciones' => $observaciones,
    ];

    $errores      = [];
    $advertencias = [];

    if (empty($cedula)) {
        $errores[] = "La cédula es obligatoria";
    } elseif (!validarCedulaEcuador($cedula)) {
        $errores[] = "La cédula no es válida. Debe tener 10 dígitos y cumplir el algoritmo ecuatoriano.";
    }

    if (empty($nombres)) {
        $errores[] = "El nombre es obligatorio";
    } elseif (!validarNombre($nombres)) {
        $errores[] = "El nombre solo puede contener letras, espacios y acentos. Mínimo 3 caracteres.";
    }

    if (!empty($telefono) && !validarTelefono($telefono)) {
        $errores[] = "El teléfono debe tener entre 7 y 10 dígitos numéricos.";
    }

    if (empty($cargo)) {
        $errores[] = "El cargo es obligatorio";
    }

    if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    } elseif (!empty($correo) && !validarDominioEmailTESA($correo)) {
        $advertencias[] = "Se recomienda usar correos institucionales @tesa.edu.ec / @estud.tesa.edu.ec. El correo $correo fue aceptado de todos modos.";
    }

    if (!empty($errores)) {
        _fallo_pers(implode("<br>", $errores), $restore);
    }

    // Check cédula duplicada (prepared)
    $id_persona = 0;
    try {
        $stmtCheck = $conn->prepare("SELECT id FROM personas WHERE cedula = ? LIMIT 1");
        if (!$stmtCheck) {
            _fallo_pers("Error preparando verificación de cédula: " . $conn->error, $restore);
        }
        $stmtCheck->bind_param('s', $cedula);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows > 0) {
            $stmtCheck->close();
            _fallo_pers("❌ La cédula $cedula ya está registrada en el sistema. No se puede duplicar.", $restore);
        }
        $stmtCheck->close();
    } catch (\Exception $eChk) {
        _fallo_pers("Error verificando cédula duplicada: " . $eChk->getMessage(), $restore);
    }

    // Transacción: INSERT persona + notificaciones + logs
    $conn->begin_transaction();
    try {
        $sqlIns = "INSERT INTO personas (cedula, nombres, correo, cargo, telefono, observaciones) VALUES (?,?,?,?,?,?)";
        $stmtIns = $conn->prepare($sqlIns);
        if (!$stmtIns) {
            throw new \Exception("Error preparando INSERT persona: " . $conn->error);
        }
        $stmtIns->bind_param('ssssss', $cedula, $nombres, $correo, $cargo, $telefono, $observaciones);
        if (!$stmtIns->execute()) {
            throw new \Exception("Error al insertar persona: " . $stmtIns->error);
        }
        $id_persona = intval($conn->insert_id);
        $stmtIns->close();

        try {
            registrar_notificacion(
                $_SESSION['user_id'],
                'success',
                '👤 Persona agregada',
                "Se agregó a {$nombres} (cédula {$cedula})",
                "/Inventario-Tesa-Cardex/modules/personas/detalle.php?id=" . $id_persona
            );
        } catch (\Exception $eNotif) { $eNotif = null; }

        try {
            require_once '../../includes/logs_functions.php';
            registrarLog($conn, 'Crear persona', "Cédula: {$cedula}, Nombre: {$nombres}", $_SESSION['user_id']);
        } catch (\Exception $eLog) { $eLog = null; }

        $conn->commit();
    } catch (\Exception $e) {
        try { $conn->rollback(); } catch (\Exception $eRb) { $eRb = null; }
        _fallo_pers("❌ Error al guardar: " . $e->getMessage(), $restore);
    }

    // ================== ÉXITO ==================
    $successMsg = "Persona registrada correctamente (ID #$id_persona).";
    if (!empty($advertencias)) {
        $successMsg .= " | " . implode(" | ", $advertencias);
    }

    $_SESSION['success'] = "✅ Persona $nombres agregada con éxito (ID #$id_persona).";
    $_SESSION['ui_popup_personas'] = [
        'persona_id'   => $id_persona,
        'cedula'       => $cedula,
        'nombres'      => $nombres,
        'cargo'        => $cargo,
        'correo'       => $correo,
        'telefono'     => $telefono,
        'observaciones'=> $observaciones,
        'resumen_url'  => '/Inventario-Tesa-Cardex/modules/personas/listar.php',
    ];
    $_SESSION['ultima_persona_agregada'] = $id_persona;
    unset($_SESSION['flash_personas_restore']);

    _persona_redir('agregar.php?ok=1', [
        'persona_id' => $id_persona,
        'cedula'     => $cedula,
        'nombres'    => $nombres,
    ]);
}

// ============================================
// GET: RESTORE FLASH + CONSTANTES JS RESTORE_*
// ============================================
$persRestore = !empty($_SESSION['flash_personas_restore']) && is_array($_SESSION['flash_personas_restore'])
    ? $_SESSION['flash_personas_restore']
    : [];
unset($_SESSION['flash_personas_restore']);

$RESTORE_CEDULA     = isset($persRestore['cedula'])        ? strval($persRestore['cedula'])        : '';
$RESTORE_NOMBRES    = isset($persRestore['nombres'])       ? strval($persRestore['nombres'])       : '';
$RESTORE_CORREO     = isset($persRestore['correo'])        ? strval($persRestore['correo'])        : '';
$RESTORE_CARGO      = isset($persRestore['cargo'])         ? strval($persRestore['cargo'])         : '';
$RESTORE_TELEFONO   = isset($persRestore['telefono'])      ? strval($persRestore['telefono'])      : '';
$RESTORE_OBS        = isset($persRestore['observaciones']) ? strval($persRestore['observaciones']) : '';
?>

<!-- ══════════════════════════════════════════════════
     ESTILOS DEL FORMULARIO + AUTOCOMPLETE
══════════════════════════════════════════════════ -->
<style>
/* ── Tarjeta principal ───────────────────────────── */
.card-agregar {
    border: none;
    border-radius: 20px;
    box-shadow: 0 8px 40px rgba(90,45,140,0.10);
    overflow: hidden;
}

.card-agregar .card-header {
    background: linear-gradient(135deg, #3d1a6e 0%, #5a2d8c 60%, #7c3aed 100%);
    border: none;
    padding: 1.4rem 1.8rem;
    position: relative;
    overflow: hidden;
}

.card-agregar .card-header::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #f3b229, transparent);
}

.card-agregar .card-header h4 {
    color: #fff;
    font-weight: 800;
    font-size: 1.15rem;
    margin: 0;
    letter-spacing: -0.3px;
}

.card-agregar .card-header h4 i { color: #f3b229; }

.card-agregar .card-body { padding: 2rem; background: #fff; }

/* ── Sección título ──────────────────────────────── */
.section-label {
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: #5a2d8c;
    border-bottom: 2px solid #f3b229;
    padding-bottom: 6px;
    margin-bottom: 1.2rem;
    margin-top: 1.4rem;
    display: flex;
    align-items: center;
    gap: 7px;
}

.section-label:first-of-type { margin-top: 0; }

/* ── Form labels ─────────────────────────────────── */
.form-label {
    font-weight: 600;
    font-size: 0.82rem;
    color: #3d1a6e;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.form-label .lbl-icon { color: #f3b229; font-size: 0.78rem; }

/* ── Inputs ──────────────────────────────────────── */
.form-control, .form-select {
    border-radius: 10px;
    border: 1.5px solid #e5dff5;
    font-size: 0.88rem;
    padding: 10px 14px;
    transition: all 0.22s ease;
    font-family: 'Outfit','Poppins',sans-serif;
    color: #1e0840;
}

.form-control:focus {
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124,58,237,0.12);
    outline: none;
}

.form-control.is-valid   { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.12); }
.form-control.is-invalid { border-color: #f43f5e; box-shadow: 0 0 0 3px rgba(244,63,94,0.10); }

/* ── Campo cédula especial ───────────────────────── */
.cedula-field-wrap { position: relative; }

.cedula-field-wrap .form-control {
    padding-right: 48px;
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 1px;
}

/* Indicador de estado en el lado derecho del input */
.cedula-status-icon {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1rem;
    padding: 0;
    border: 0;
    background: transparent;
    cursor: pointer;
    line-height: 1;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Barra de estado debajo de la cédula */
.cedula-feedback {
    margin-top: 6px;
    padding: 8px 13px;
    border-radius: 9px;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    animation: fadeSlideIn 0.2s ease;
}

@keyframes fadeSlideIn {
    from { opacity: 0; transform: translateY(-5px); }
    to   { opacity: 1; transform: translateY(0); }
}

.cedula-feedback.typing  { display:flex; background:rgba(124,58,237,0.07); border:1px solid rgba(124,58,237,0.18); color:#6d28d9; }
.cedula-feedback.loading { display:flex; background:rgba(59,130,246,0.07); border:1px solid rgba(59,130,246,0.2);  color:#2563eb; }
.cedula-feedback.success { display:flex; background:rgba(16,185,129,0.07); border:1px solid rgba(16,185,129,0.22); color:#047857; font-weight:600; }
.cedula-feedback.warning { display:flex; background:rgba(245,158,11,0.07); border:1px solid rgba(245,158,11,0.22); color:#92400e; font-size:0.76rem; }
.cedula-feedback.error   { display:flex; background:rgba(244,63,94,0.06);  border:1px solid rgba(244,63,94,0.2);   color:#be123c; }
.cedula-feedback.idle    { display:flex; background:rgba(107,114,128,0.06); border:1px solid rgba(107,114,128,0.18); color:#6b7280; }

/* Mini spinner */
.mini-spin {
    width: 13px; height: 13px;
    border: 2px solid rgba(37,99,235,0.25);
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    flex-shrink: 0;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Campo autocompletado — destello verde */
.field-autocompleted {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 3px rgba(16,185,129,0.13) !important;
    background: rgba(16,185,129,0.03) !important;
    transition: all 0.4s ease !important;
}

/* Badge SRI junto al label de nombre */
.sri-badge {
    display: none;
    align-items: center;
    gap: 4px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.8px;
    padding: 2px 7px;
    border-radius: 999px;
    text-transform: uppercase;
}

.sri-badge.visible { display: inline-flex; animation: fadeSlideIn 0.3s ease; }

/* ── Botones ─────────────────────────────────────── */
.btn-cancelar {
    border-radius: 11px;
    border: 1.5px solid #e5dff5;
    color: #6b7280;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 10px 28px;
    transition: all 0.2s;
}

.btn-cancelar:hover { background: #f4f0fa; border-color: #c4b5f4; color: #5a2d8c; }

.btn-guardar {
    background: linear-gradient(135deg, #5a2d8c, #7c3aed);
    border: none;
    color: #fff;
    border-radius: 11px;
    padding: 10px 30px;
    font-weight: 700;
    font-size: 0.88rem;
    box-shadow: 0 4px 16px rgba(90,45,140,0.3);
    transition: all 0.22s ease;
    position: relative;
    overflow: hidden;
}

.btn-guardar:hover {
    background: linear-gradient(135deg, #4a1f7a, #6b2fd4);
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(90,45,140,0.4);
    color: #fff;
}

/* ── Alert admin ─────────────────────────────────── */
.alert-admin {
    background: linear-gradient(135deg, rgba(124,58,237,0.06), rgba(90,45,140,0.04));
    border: 1px solid rgba(124,58,237,0.18);
    border-left: 4px solid #7c3aed;
    border-radius: 12px;
    color: #3d1a6e;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-9 col-lg-8 mx-auto">
            <div class="card card-agregar">

                <!-- Header -->
                <div class="card-header">
                    <h4><i class="fas fa-user-plus me-2"></i>Agregar Nueva Persona</h4>
                </div>

                <div class="card-body">

                    <!-- Alertas de éxito / error -->
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success alert-dismissible fade show rounded-3">
                            <i class="fas fa-check-circle me-2"></i><?= $mensaje ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Aviso admin -->
                    <div class="alert alert-admin mb-4 d-flex align-items-start gap-3">
                        <i class="fas fa-shield-halved fa-xl mt-1" style="color:#7c3aed; flex-shrink:0"></i>
                        <div>
                            <strong>Modo Administrador</strong>
                            <p class="mb-0 mt-1" style="font-size:0.85rem; color:#4b3b6e">
                                Al ingresar la cédula se consultará automáticamente el <strong>SRI de Ecuador</strong>
                                para autocompletar el nombre. Los campos con
                                <span class="text-danger fw-bold">*</span> son obligatorios.
                            </p>
                        </div>
                    </div>

                    <!-- ════════════════════════════
                         FORMULARIO
                    ════════════════════════════ -->
                    <form method="POST" action="" id="frmAgregarPersona">

                        <!-- ── Sección: Identificación ── -->
                        <div class="section-label">
                            <i class="fas fa-id-card"></i> Identificación
                        </div>

                        <div class="row g-3 mb-2">

                            <!-- CÉDULA -->
                            <div class="col-md-5">
                                <label class="form-label" for="cedula">
                                    <i class="fas fa-fingerprint lbl-icon"></i>
                                    Cédula <span class="text-danger">*</span>
                                </label>
                                <!-- Wrapper para el indicador de estado -->
                                <div class="cedula-field-wrap">
                                    <input type="text"
                                           id="cedula"
                                           name="cedula"
                                           class="form-control"
                                           value="<?= htmlspecialchars($RESTORE_CEDULA) ?>"
                                           placeholder="Ej: 1723456789"
                                           maxlength="10"
                                           inputmode="numeric"
                                           pattern="\d{10}"
                                           autocomplete="off"
                                           required>
                                    <!-- Ícono de estado dentro del input -->
                                    <button type="button" class="cedula-status-icon" id="cedulaStatusIcon" aria-label="Limpiar cédula">
                                        <i class="fas fa-circle-info" style="color:#9ca3af"></i>
                                    </button>
                                </div>
                                <!-- Barra de feedback debajo -->
                                <div class="cedula-feedback idle" id="cedulaFeedback">
                                    <i class="fas fa-circle-info"></i> Ingresa la cédula para consultar en el SRI
                                </div>
                                <small class="text-muted" style="font-size:0.72rem">
                                    <i class="fas fa-circle-info me-1"></i>
                                    Se consultará el SRI automáticamente al completar los 10 dígitos
                                </small>
                            </div>

                            <!-- NOMBRE — se autocompleta -->
                            <div class="col-md-7">
                                <label class="form-label" for="nombres">
                                    <i class="fas fa-user lbl-icon"></i>
                                    Nombres Completos <span class="text-danger">*</span>
                                    <!-- Badge "SRI" aparece cuando se autocompleta -->
                                    <span class="sri-badge" id="sriBadge">
                                        <i class="fas fa-check"></i> SRI
                                    </span>
                                </label>
                                <input type="text"
                                       id="nombres"
                                       name="nombres"
                                       class="form-control"
                                       value="<?= htmlspecialchars($RESTORE_NOMBRES) ?>"
                                       placeholder="Se autocompleta al ingresar la cédula"
                                       required>
                            </div>

                        </div>

                        <!-- ── Sección: Contacto ── -->
                        <div class="section-label">
                            <i class="fas fa-address-book"></i> Contacto
                        </div>

                        <div class="row g-3 mb-2">

                            <div class="col-md-6">
                                <label class="form-label" for="correo">
                                    <i class="fas fa-envelope lbl-icon"></i>
                                    Correo Electrónico
                                </label>
                                <input type="email"
                                       id="correo"
                                       name="correo"
                                       class="form-control"
                                       value="<?= htmlspecialchars($RESTORE_CORREO) ?>"
                                       placeholder="ejemplo@tesa.edu.ec">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="telefono">
                                    <i class="fas fa-phone lbl-icon"></i>
                                    Teléfono
                                </label>
                                <input type="text"
                                       id="telefono"
                                       name="telefono"
                                       class="form-control"
                                       value="<?= htmlspecialchars($RESTORE_TELEFONO) ?>"
                                       placeholder="Ej: 0987654321"
                                       maxlength="10"
                                       pattern="[0-9]{7,10}"
                                       inputmode="numeric">
                                <small class="text-muted" style="font-size:0.72rem">7 a 10 dígitos</small>
                            </div>

                        </div>

                        <!-- ── Sección: Información laboral ── -->
                        <div class="section-label">
                            <i class="fas fa-briefcase"></i> Información Laboral
                        </div>

                        <div class="row g-3 mb-2">

                            <div class="col-md-12">
                                <label class="form-label" for="cargo">
                                    <i class="fas fa-id-badge lbl-icon"></i>
                                    Cargo <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       id="cargo"
                                       name="cargo"
                                       class="form-control"
                                       value="<?= htmlspecialchars($RESTORE_CARGO) ?>"
                                       placeholder="Ej: Analista de TI, Docente, Administrativo…"
                                       required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label" for="observaciones">
                                    <i class="fas fa-note-sticky lbl-icon"></i>
                                    Observaciones
                                </label>
                                <textarea id="observaciones"
                                          name="observaciones"
                                          class="form-control"
                                          rows="3"
                                          placeholder="Notas adicionales sobre esta persona…"><?= htmlspecialchars($RESTORE_OBS) ?></textarea>
                            </div>

                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3"
                             style="border-top: 1px solid #f0eafa">
                            <a href="listar.php" class="btn btn-cancelar">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="button" class="btn btn-guardar" id="btnRegistrarPersona">
                                <span class="btn-text"><i class="fas fa-floppy-disk me-2"></i>Guardar Persona</span>
                                <span class="btn-spinner d-none spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>
                            </button>
                        </div>

                    </form>
                </div><!-- /card-body -->
            </div><!-- /card -->
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     JAVASCRIPT — AUTOCOMPLETE DE CÉDULA (SRI) + SUBMIT MANUAL
══════════════════════════════════════════════════ -->
<script>
const RESTORE_CEDULA   = <?php echo json_encode($RESTORE_CEDULA,   JSON_UNESCAPED_UNICODE); ?>;
const RESTORE_NOMBRES  = <?php echo json_encode($RESTORE_NOMBRES,  JSON_UNESCAPED_UNICODE); ?>;
const RESTORE_CORREO   = <?php echo json_encode($RESTORE_CORREO,   JSON_UNESCAPED_UNICODE); ?>;
const RESTORE_CARGO    = <?php echo json_encode($RESTORE_CARGO,    JSON_UNESCAPED_UNICODE); ?>;
const RESTORE_TELEFONO = <?php echo json_encode($RESTORE_TELEFONO, JSON_UNESCAPED_UNICODE); ?>;
const RESTORE_OBS      = <?php echo json_encode($RESTORE_OBS,      JSON_UNESCAPED_UNICODE); ?>;

(function () {
    'use strict';

    function __escapeHtmlP(str) {
        if (str === null || str === undefined) return '';
        str = String(str);
        const map = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;', '`':'&#96;' };
        return str.replace(/[&<>"'`]/g, function(ch) { return map[ch] || ch; });
    }

    function whenReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
        } else {
            fn();
        }
    }

    /* ── Referencias DOM ── */
    const inputCedula    = document.getElementById('cedula');
    const inputNombres   = document.getElementById('nombres');
    const inputCorreo    = document.getElementById('correo');
    const inputCargo     = document.getElementById('cargo');
    const inputTelefono  = document.getElementById('telefono');
    const inputObs       = document.getElementById('observaciones');
    const feedback       = document.getElementById('cedulaFeedback');
    const statusIcon     = document.getElementById('cedulaStatusIcon');
    const sriBadge       = document.getElementById('sriBadge');
    const formPers       = document.getElementById('frmAgregarPersona');
    const btnGuardar     = document.getElementById('btnRegistrarPersona');

    if (!inputCedula) return;

    let debounceTimer = null;
    let ultimaCedula  = '';
    let _btnOriginalText = '';

    function setLoading(loading, textoOri) {
        if (!btnGuardar) return;
        const txtSpan = btnGuardar.querySelector('.btn-text');
        const spnSpan = btnGuardar.querySelector('.btn-spinner');
        if (!txtSpan || !spnSpan) return;
        if (loading) {
            _btnOriginalText = txtSpan.textContent.trim();
            txtSpan.textContent = (textoOri && typeof textoOri === 'string') ? textoOri : 'Guardando…';
            spnSpan.classList.remove('d-none');
            btnGuardar.disabled = true;
            btnGuardar.style.opacity = '0.7';
            btnGuardar.style.pointerEvents = 'none';
        } else {
            txtSpan.textContent = (_btnOriginalText && _btnOriginalText.length > 0) ? _btnOriginalText : txtSpan.textContent;
            spnSpan.classList.add('d-none');
            btnGuardar.disabled = false;
            btnGuardar.style.opacity = '';
            btnGuardar.style.pointerEvents = '';
        }
    }

    whenReady(function () {
        // Restore flash solo si los campos están vacíos (no SRI o user escribió después)
        try {
            if (RESTORE_CEDULA && !inputCedula.value) {
                inputCedula.value = RESTORE_CEDULA;
            }
            if (RESTORE_NOMBRES && !inputNombres.value) {
                inputNombres.value = RESTORE_NOMBRES;
            }
            if (RESTORE_CORREO && !inputCorreo.value) {
                inputCorreo.value = RESTORE_CORREO;
            }
            if (RESTORE_CARGO && !inputCargo.value) {
                inputCargo.value = RESTORE_CARGO;
            }
            if (RESTORE_TELEFONO && !inputTelefono.value) {
                inputTelefono.value = RESTORE_TELEFONO;
            }
            if (RESTORE_OBS && !inputObs.value) {
                inputObs.value = RESTORE_OBS;
            }
        } catch (e) {}
    });

    /* ── Escuchar escritura en cédula ── */
    inputCedula.addEventListener('input', function () {
        // Solo números, máximo 10
        this.value = this.value.replace(/\D/g, '').slice(0, 10);

        const val = this.value;
        clearTimeout(debounceTimer);
        resetEstado();

        if (val.length === 0) {
            setIdle();
            return;
        }

        if (val.length < 10) {
            setIcon('clear');
            setFeedback('typing', `<i class="fas fa-keyboard"></i> ${val.length}/10 dígitos`);
            return;
        }

        // 10 dígitos → esperar 600ms y consultar
        debounceTimer = setTimeout(() => consultarSRI(val), 600);
    });

    /* ── Consulta al proxy PHP ── */
    async function consultarSRI(cedula) {
        if (cedula === ultimaCedula) return;   // no repetir misma cédula
        ultimaCedula = cedula;

        setFeedback('loading', '<span class="mini-spin"></span> Consultando SRI Ecuador…');
        setIcon('loading');
        limpiarNombre();

        try {
            const resp = await fetch(`/Inventario-Tesa-Cardex/api/consultar_cedula.php?cedula=${cedula}`);

            if (!resp.ok) throw new Error('HTTP ' + resp.status);

            const data = await resp.json();

            if (data.ok && data.nombre) {
                // ✅ Nombre encontrado
                rellenarNombre(data.nombre);
                setFeedback('success',
                    `<i class="fas fa-circle-check"></i> ${data.nombre}`
                );
                setIcon('success');

                // Nota informativa si el SRI no entregó otros datos
                if (!data.fecha_nacimiento) {
                    setTimeout(() => {
                        const nota = document.createElement('div');
                        nota.className = 'cedula-feedback warning';
                        nota.innerHTML = `<i class="fas fa-circle-info"></i>
                            El SRI solo entrega el nombre. Fecha de nacimiento y otros datos
                            deben completarse manualmente.`;
                        nota.style.display = 'flex';
                        nota.style.marginTop = '5px';
                        feedback.after(nota);
                        setTimeout(() => nota.remove(), 9000);
                    }, 400);
                }

            } else {
                // ❌ No encontrado
                setFeedback('error',
                    `<i class="fas fa-circle-xmark"></i> ${data.error || 'Cédula no encontrada en el SRI'}`
                );
                setIcon('error');
                ultimaCedula = '';   // permitir reintentar
            }

        } catch (err) {
            setFeedback('error',
                '<i class="fas fa-triangle-exclamation"></i> Error de conexión. Ingresa el nombre manualmente.'
            );
            setIcon('error');
            console.warn('[CedulaSRI]', err);
            ultimaCedula = '';
        }
    }

    /* ── Rellenar campo nombre ── */
    function rellenarNombre(nombre) {
        inputNombres.value = nombre;
        inputNombres.classList.add('field-autocompleted', 'is-valid');
        inputNombres.setAttribute('data-sri', '1');
        sriBadge.classList.add('visible');

        // Quitar clase verde después de 3 segundos
        setTimeout(() => {
            inputNombres.classList.remove('field-autocompleted');
        }, 3000);
    }

    function limpiarNombre() {
        // Si el campo fue autocompletado por SRI, limpiarlo
        if (inputNombres.getAttribute('data-sri') === '1') {
            inputNombres.value = '';
            inputNombres.classList.remove('is-valid', 'field-autocompleted');
            inputNombres.removeAttribute('data-sri');
            sriBadge.classList.remove('visible');
        }
    }

    /* ── Helpers de UI ── */
    function setFeedback(tipo, html) {
        feedback.className = `cedula-feedback ${tipo}`;
        feedback.innerHTML = html;
    }

    function setIdle() {
        feedback.className = 'cedula-feedback idle';
        feedback.innerHTML = '<i class="fas fa-circle-info"></i> Ingresa la cédula para consultar en el SRI';
        setIcon('idle');
    }

    function resetEstado() {
        inputCedula.classList.remove('is-valid', 'is-invalid');
        statusIcon.disabled = false;
    }

    function setIcon(estado) {
        statusIcon.style.display = 'inline-flex';
        statusIcon.disabled = false;
        if (estado === 'loading') {
            statusIcon.innerHTML = '<span class="mini-spin" style="display:inline-block"></span>';
            statusIcon.disabled = true;
        } else if (estado === 'success') {
            statusIcon.innerHTML = '<i class="fas fa-circle-check" style="color:#10b981"></i>';
            inputCedula.classList.add('is-valid');
        } else if (estado === 'error') {
            statusIcon.innerHTML = '<i class="fas fa-circle-xmark" style="color:#f43f5e"></i>';
            inputCedula.classList.add('is-invalid');
        } else if (estado === 'clear') {
            statusIcon.innerHTML = '<i class="fas fa-circle-xmark" style="color:#9ca3af"></i>';
        } else if (estado === 'idle') {
            statusIcon.innerHTML = '<i class="fas fa-circle-info" style="color:#9ca3af"></i>';
            statusIcon.disabled = inputCedula.value.length === 0;
        }
    }

    statusIcon.addEventListener('click', function () {
        inputCedula.value = '';
        ultimaCedula = '';
        resetEstado();
        setIdle();
        limpiarNombre();
        inputCedula.focus();
    });

    /* ── SUBMIT MANUAL VÍA XHR + Swal confirmación previa ── */
    function _do_submit() {
        if (!formPers) return;
        const cedula    = (inputCedula.value || '').trim();
        const nombres   = (inputNombres.value || '').trim();
        const correo    = (inputCorreo.value || '').trim();
        const cargo     = (inputCargo.value || '').trim();
        const telefono  = (inputTelefono.value || '').trim();
        const obs       = (inputObs.value || '').trim();

        const faltan = [];
        if (!cedula) faltan.push('La cédula es obligatoria');
        else if (!/^\d{10}$/.test(cedula)) faltan.push('La cédula debe tener 10 dígitos numéricos');

        if (!nombres) faltan.push('El nombre es obligatorio');
        else if (nombres.length < 3) faltan.push('El nombre debe tener al menos 3 caracteres');

        if (!cargo) faltan.push('El cargo es obligatorio');

        if (telefono && !/^\d{7,10}$/.test(telefono)) {
            faltan.push('Teléfono inválido (7 a 10 dígitos)');
        }

        if (correo && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
            faltan.push('Correo electrónico no válido');
        }

        if (faltan.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos obligatorios',
                html: faltan.map(x => '⚠️ ' + __escapeHtmlP(x)).join('<br>'),
                confirmButtonText: '<i class="fas fa-pen me-1"></i> Corregir',
                confirmButtonColor: '#5a2d8c',
                allowOutsideClick: false
            });
            return;
        }

        // ====== Swal confirmación antes de enviar ======
        let htmlRes = '<div class="text-start">';
        htmlRes += '<p class="mb-2"><strong>Cédula: </strong><span class="badge bg-purple-200 text-dark" style="background:#e9d5ff">' + __escapeHtmlP(cedula) + '</span></p>';
        htmlRes += '<p class="mb-2"><strong>Nombres: </strong>' + __escapeHtmlP(nombres) + '</p>';
        htmlRes += '<p class="mb-2"><strong>Cargo: </strong><span class="badge bg-info text-white">' + __escapeHtmlP(cargo) + '</span></p>';
        if (correo)   htmlRes += '<p class="mb-2 small"><strong>Correo: </strong>' + __escapeHtmlP(correo) + '</p>';
        if (telefono) htmlRes += '<p class="mb-2 small"><strong>Teléfono: </strong>' + __escapeHtmlP(telefono) + '</p>';
        if (obs)      htmlRes += '<p class="mb-1 small"><strong>Observaciones: </strong>' + __escapeHtmlP(obs) + '</p>';
        htmlRes += '</div>';

        Swal.fire({
            icon: 'question',
            title: '¿Guardar nueva persona?',
            html: htmlRes,
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check me-1"></i> Sí, guardar persona',
            cancelButtonText: '<i class="fas fa-xmark me-1"></i> Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((res) => {
            if (!res.isConfirmed) return;

            setLoading(true, 'Guardando persona…');
            try {
                const fd = new FormData(formPers);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'agregar.php', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onload = function () {
                    setLoading(false);
                    try {
                        if (xhr.status >= 200 && xhr.status < 400) {
                            let payload = null;
                            try { payload = JSON.parse(xhr.responseText || '{}'); } catch (ej) { payload = null; }
                            if (payload && payload.redirect_url) {
                                window.location.href = payload.redirect_url;
                                return;
                            }
                            // Fallback si no hay JSON
                            window.location.href = 'agregar.php?ok=1';
                            return;
                        }
                        throw new Error('HTTP ' + xhr.status);
                    } catch (errSub) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al guardar',
                            text: 'No se pudo completar el guardado. Inténtalo de nuevo.',
                            confirmButtonText: '<i class="fas fa-rotate me-1"></i> Reintentar',
                            confirmButtonColor: '#dc3545',
                            allowOutsideClick: false
                        }).then((r2) => {
                            if (r2.isConfirmed) { setTimeout(_do_submit, 200); }
                        });
                    }
                };
                xhr.onerror = function () {
                    setLoading(false);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'Revisa tu conexión a internet e inténtalo nuevamente.',
                        confirmButtonText: '<i class="fas fa-rotate me-1"></i> Reintentar',
                        confirmButtonColor: '#dc3545',
                        allowOutsideClick: false
                    }).then((r2) => {
                        if (r2.isConfirmed) { setTimeout(_do_submit, 200); }
                    });
                };
                xhr.send(fd);
            } catch (errBuild) {
                setLoading(false);
                Swal.fire({ icon: 'error', title: 'Error interno', text: String(errBuild && errBuild.message || errBuild), confirmButtonColor: '#5a2d8c' });
            }
        });
    }

    if (btnGuardar) {
        btnGuardar.addEventListener('click', function (e) {
            if (e && typeof e.preventDefault === 'function') e.preventDefault();
            if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
            _do_submit();
            return false;
        });
    }

    setIdle();
})();
</script>

<?php include '../../includes/footer.php'; ?>

