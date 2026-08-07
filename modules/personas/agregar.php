<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
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

$mensaje = '';
$error = '';

// ============================================
// PROCESAR EL FORMULARIO CUANDO SE ENVÍA
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $cedula        = trim($conn->real_escape_string($_POST['cedula']));
    $nombres       = trim($conn->real_escape_string($_POST['nombres']));
    $correo        = trim($conn->real_escape_string($_POST['correo'] ?? ''));
    $cargo         = trim($conn->real_escape_string($_POST['cargo']));
    $telefono      = trim($conn->real_escape_string($_POST['telefono'] ?? ''));
    $observaciones = trim($conn->real_escape_string($_POST['observaciones'] ?? ''));
    
    $errores = [];
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
    
    if (empty($errores)) {
        $check_sql    = "SELECT id FROM personas WHERE cedula = '$cedula'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $error = "❌ La cédula $cedula ya está registrada en el sistema. No se puede duplicar.";
        } else {
            $sql = "INSERT INTO personas (cedula, nombres, correo, cargo, telefono, observaciones) 
                    VALUES ('$cedula', '$nombres', '$correo', '$cargo', '$telefono', '$observaciones')";
            
            if ($conn->query($sql)) {
                $id_persona = $conn->insert_id;
                
                registrar_notificacion(
                    $_SESSION['user_id'],
                    'success',
                    '👤 Persona agregada',
                    "Se agregó a {$nombres} (cédula {$cedula})",
                    "/inventario_ti/modules/personas/detalle.php?id=" . $id_persona
                );
                
                require_once '../../includes/logs_functions.php';
                registrarLog($conn, 'Crear persona', "Cédula: {$cedula}, Nombre: {$nombres}", $_SESSION['user_id']);
                
                $txt_sw = "Persona registrada correctamente";
                if (!empty($advertencias)) {
                    $txt_sw .= "\n\n⚠️ " . implode("\n", $advertencias);
                }
                
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: " . json_encode($txt_sw, JSON_UNESCAPED_UNICODE) . ",
                        timer: 2600,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'listar.php';
                    });
                </script>";
            } else {
                $error = "❌ Error al guardar: " . $conn->error;
            }
        }
    } else {
        $error = implode("<br>", $errores);
    }
}
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
                    <form method="POST" action="" id="formPersona">

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
                                           value="<?= isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : '' ?>"
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
                                       value="<?= isset($_POST['nombres']) ? htmlspecialchars($_POST['nombres']) : '' ?>"
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
                                       value="<?= isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : '' ?>"
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
                                       value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>"
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
                                       value="<?= isset($_POST['cargo']) ? htmlspecialchars($_POST['cargo']) : '' ?>"
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
                                          placeholder="Notas adicionales sobre esta persona…"><?= isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : '' ?></textarea>
                            </div>

                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3"
                             style="border-top: 1px solid #f0eafa">
                            <a href="listar.php" class="btn btn-cancelar">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-guardar" id="btnGuardar">
                                <i class="fas fa-floppy-disk me-2"></i>Guardar Persona
                            </button>
                        </div>

                    </form>
                </div><!-- /card-body -->
            </div><!-- /card -->
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     JAVASCRIPT — AUTOCOMPLETE DE CÉDULA (SRI)
══════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    /* ── Referencias DOM ── */
    const inputCedula  = document.getElementById('cedula');
    const inputNombres = document.getElementById('nombres');
    const feedback     = document.getElementById('cedulaFeedback');
    const statusIcon   = document.getElementById('cedulaStatusIcon');
    const sriBadge     = document.getElementById('sriBadge');

    if (!inputCedula) return;

    let debounceTimer = null;
    let ultimaCedula  = '';

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
            const resp = await fetch(`/inventario_ti/api/consultar_cedula.php?cedula=${cedula}`);

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

    /* ── Validación final al enviar ── */
    document.getElementById('formPersona')?.addEventListener('submit', function (e) {
        const cedula   = inputCedula.value;
        const telefono = document.getElementById('telefono')?.value || '';

        if (cedula && !/^\d{10}$/.test(cedula)) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Cédula inválida', text: 'La cédula debe tener exactamente 10 dígitos numéricos.', confirmButtonColor: '#5a2d8c' });
            return;
        }

        if (telefono && !/^\d{7,10}$/.test(telefono)) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Teléfono inválido', text: 'El teléfono debe tener entre 7 y 10 dígitos numéricos.', confirmButtonColor: '#5a2d8c' });
            return;
        }
    });

    setIdle();
})();
</script>

<?php include '../../includes/footer.php'; ?>
