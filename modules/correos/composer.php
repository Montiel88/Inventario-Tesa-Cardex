<?php
session_start();
require_once '../../config/database.php';

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
$rol_id = $_SESSION['rol_id'] ?? $_SESSION['user_rol'] ?? null;
if (!$usuario_id || $rol_id != 1) {
    header('Location: /inventario_ti/login.php');
    exit;
}

$tipo = $_GET['tipo'] ?? 'manual';
$persona_id = $_GET['persona_id'] ?? null;
$asignacion_id = $_GET['asignacion_id'] ?? null;
$componente_id = $_GET['componente_id'] ?? null;

$persona = null;
$equipo = null;
$plantilla = ['asunto' => '', 'mensaje' => ''];

if ($tipo == 'vencido' && $asignacion_id) {
    $stmt = $conn->prepare("SELECT a.*, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo,
                                   p.nombres, p.correo as email, p.cedula,
                                   DATEDIFF(NOW(), a.fecha_asignacion) as dias_vencido
                            FROM asignaciones a JOIN equipos e ON a.equipo_id = e.id
                            JOIN personas p ON a.persona_id = p.id WHERE a.id = ?");
    $stmt->bind_param('i', $asignacion_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $persona = $row; $equipo = $row;
        $plantilla['asunto'] = "URGENTE: Préstamo Vencido - {$row['codigo_barras']}";
        $plantilla['mensaje'] = "Estimado/a {$row['nombres']},\n\nLe informamos que su préstamo del equipo {$row['tipo_equipo']} {$row['marca']} {$row['modelo']} con código {$row['codigo_barras']} se encuentra SIN DEVOLVER desde hace {$row['dias_vencido']} días.\n\nFecha de asignación: " . date('d/m/Y', strtotime($row['fecha_asignacion'])) . "\n\nPor favor, coordine la devolución inmediata.\n\nDepartamento de Tecnología - TESA";
    }
} elseif ($tipo == 'por_vencer' && $asignacion_id) {
    $stmt = $conn->prepare("SELECT a.*, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo,
                                   p.nombres, p.correo as email, p.cedula,
                                   DATEDIFF(NOW(), a.fecha_asignacion) as dias_transcurridos
                            FROM asignaciones a JOIN equipos e ON a.equipo_id = e.id
                            JOIN personas p ON a.persona_id = p.id WHERE a.id = ?");
    $stmt->bind_param('i', $asignacion_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $persona = $row; $equipo = $row;
        $plantilla['asunto'] = "Recordatorio: Préstamo Pendiente - {$row['codigo_barras']}";
        $plantilla['mensaje'] = "Estimado/a {$row['nombres']},\n\nLe recordamos que tiene un préstamo del equipo {$row['tipo_equipo']} {$row['marca']} {$row['modelo']} con código {$row['codigo_barras']} desde hace {$row['dias_transcurridos']} día(s).\n\nFecha de asignación: " . date('d/m/Y', strtotime($row['fecha_asignacion'])) . "\n\nPor favor, planifique la devolución.\n\nDepartamento de Tecnología - TESA";
    }
}

if (!$persona) {
    $result_personas = $conn->query("SELECT id, nombres, correo as email, cedula FROM personas WHERE correo IS NOT NULL AND correo != '' ORDER BY nombres");
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redactar Correo | TESA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        :root {
            --c-bg: #120228;
            --c-deep: #0d0118;
            --c-mid: #1e0840;
            --c-violet: #7c3aed;
            --c-gold: #f3b229;
            --c-gold-lt: #ffd166;
            --c-gold-glow: rgba(243,178,41,0.35);
            --c-danger: #f43f5e;
            --c-success: #10b981;
            --c-info: #06b6d4;
            --c-warning: #f59e0b;
            --c-w90: rgba(255,255,255,0.9);
            --c-w60: rgba(255,255,255,0.6);
            --c-w15: rgba(255,255,255,0.15);
            --c-w08: rgba(255,255,255,0.08);
            --font: 'Outfit','Poppins',sans-serif;
            --radius: 20px;
            --radius-sm: 12px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: var(--font);
            color: var(--c-w90);
            overflow-x: hidden;
            background: var(--c-deep);
            padding: 0;
        }

        /* Fondo dinámico animado LED */
        .bg-scene {
            position: fixed; inset: 0; z-index: 0;
            background: linear-gradient(135deg, var(--c-deep) 0%, var(--c-bg) 100%);
            overflow: hidden;
        }
        .orb {
            position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.3;
            animation: floatOrb linear infinite;
        }
        .orb-1 { width: 500px; height: 500px; background: var(--c-violet); top: -100px; left: -100px; animation-duration: 25s; }
        .orb-2 { width: 400px; height: 400px; background: var(--c-gold); bottom: -100px; right: -100px; animation-duration: 30s; animation-direction: reverse; }
        
        @keyframes floatOrb {
            0%   { transform: translate(0,0) scale(1); }
            50%  { transform: translate(50px,50px) scale(1.1); }
            100% { transform: translate(0,0) scale(1); }
        }

        .bg-grid {
            position: fixed; inset: 0; z-index: 1;
            background-image: linear-gradient(rgba(124, 58, 237, 0.05) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(124, 58, 237, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
        }

        .page { position: relative; z-index: 2; max-width: 1200px; margin: 0 auto; padding: 3rem 1.5rem; animation: pageIn .8s cubic-bezier(.22,1,.36,1) both; }
        @keyframes pageIn { from { opacity:0; transform: translateY(30px); } to { opacity:1; transform:translateY(0); } }

        /* Top bar */
        .top-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 3rem; flex-wrap: wrap; gap: 1.5rem; }
        .logo-gem {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, var(--c-violet), var(--c-bg));
            border-radius: 18px;
            display: grid; place-items: center;
            box-shadow: 0 0 25px rgba(124, 58, 237, 0.4), 0 0 0 1px rgba(255,255,255,0.1) inset;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .logo-gem i { color: var(--c-gold); font-size: 1.6rem; filter: drop-shadow(0 0 8px var(--c-gold)); }
        .logo-text { font-family: var(--font); font-size: 2rem; font-weight: 800; color: #fff; letter-spacing: -1px; line-height: 1; }
        .logo-sub { font-size: 0.8rem; font-weight: 700; color: var(--c-w60); letter-spacing: 2px; text-transform: uppercase; margin-top: 6px; }

        .btn-back {
            display: flex; align-items: center; gap: .6rem;
            padding: .6rem 1.4rem;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            color: var(--c-w60); font-size: .85rem; font-weight: 700;
            text-decoration: none; backdrop-filter: blur(10px);
            transition: all .3s;
        }
        .btn-back:hover { color: #fff; border-color: var(--c-gold); background: rgba(255,255,255,0.1); transform: translateX(-5px); }

        /* Glass Cards LED */
        .g-card {
            background: rgba(30, 8, 64, 0.5);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: var(--radius);
            backdrop-filter: blur(30px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            overflow: hidden;
            position: relative;
        }
        .g-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--c-gold), var(--c-violet), transparent);
            z-index: 1;
        }
        .g-section { padding: 2rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .s-label { display: flex; align-items: center; gap: .8rem; font-size: .75rem; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--c-w60); margin-bottom: 1.5rem; }
        .s-label-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--c-violet); box-shadow: 0 0 12px var(--c-violet); }

        /* Recipient LED */
        .recipient-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-sm);
            padding: 1.5rem; display: flex; align-items: center; gap: 1.2rem;
            transition: all 0.3s;
        }
        .recipient-box:hover { border-color: var(--c-gold); background: rgba(255,255,255,0.06); }
        .av { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, var(--c-violet), var(--c-bg)); display: grid; place-items: center; color: var(--c-gold); font-size: 1.4rem; border: 1px solid rgba(255,255,255,0.1); }
        .av-name { font-size: 1.1rem; font-weight: 800; color: #fff; }
        .chip { font-size: .75rem; font-weight: 700; padding: .3rem .8rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: var(--c-w60); }
        .chip.email { border-color: var(--c-violet); color: var(--c-violet); }

        /* Template Buttons */
        .tpl-btn {
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            color: var(--c-w60); padding: .6rem 1.2rem; border-radius: 12px;
            font-weight: 700; transition: all 0.3s;
        }
        .tpl-btn:hover { background: var(--c-violet); color: #fff; border-color: #fff; transform: translateY(-2px); }

        /* Form Controls LED */
        .f-label { font-size: .75rem; font-weight: 800; color: var(--c-gold); text-transform: uppercase; margin-bottom: .8rem; display: block; }
        .tf {
            width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-sm); color: #fff; padding: 1rem 1.2rem;
            transition: all 0.3s; outline: none;
        }
        .tf:focus { border-color: var(--c-violet); background: rgba(255,255,255,0.08); box-shadow: 0 0 20px rgba(124, 58, 237, 0.2); }

        /* Preview Card LED */
        .preview-card { background: rgba(15, 5, 30, 0.8); border: 1px solid var(--c-gold); border-radius: var(--radius); overflow: hidden; box-shadow: 0 0 40px rgba(243, 178, 41, 0.1); }
        .preview-topbar { background: rgba(255,255,255,0.05); padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .preview-inner { padding: 2rem; background: linear-gradient(to bottom, rgba(124, 58, 237, 0.05), transparent); }
        .prev-name { font-weight: 800; color: #fff; }
        .prev-subj { font-size: 1.2rem; font-weight: 800; color: var(--c-gold); margin: 1rem 0; }
        .prev-body { color: var(--c-w90); line-height: 1.8; }

        /* Action Buttons LED */
        .btn-send-main {
            background: linear-gradient(135deg, var(--c-gold), #d97706);
            color: #1a0533 !important; font-weight: 800; border: none;
            padding: 1rem 2rem; border-radius: 14px;
            box-shadow: 0 10px 30px rgba(243, 178, 41, 0.4);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .btn-send-main:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 15px 45px rgba(243, 178, 41, 0.6); }

        /* Select2 Dark LED */
        .select2-container--default .select2-selection--single { background: rgba(255,255,255,0.04) !important; border: 1px solid rgba(255,255,255,0.1) !important; height: 54px !important; border-radius: 12px !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { color: #fff !important; line-height: 52px !important; padding-left: 1.2rem !important; }
        .select2-dropdown { background: var(--c-bg) !important; border: 1px solid var(--c-violet) !important; color: #fff !important; }
        .select2-results__option { background: transparent !important; }
        .select2-results__option--highlighted { background: var(--c-violet) !important; }
    </style>
</head>
<body>

<div class="bg-scene">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="orb orb-4"></div>
</div>
<div class="bg-grid"></div>

<div class="page">

    <div class="top-bar">
        <div class="logo-block">
            <div class="logo-gem"><i class="fas fa-paper-plane"></i></div>
            <div>
                <div class="logo-text">Redactar Correo</div>
                <div class="logo-sub">Departamento de Tecnología · TESA</div>
            </div>
        </div>
        <div class="top-right">
            <?php if ($tipo == 'vencido'): ?>
            <div class="badge-urgente">
                <span style="width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block;animation:blink 1s infinite"></span>
                Préstamo Vencido
            </div>
            <?php endif; ?>
            <a href="listar.php" class="btn-back"><i class="fas fa-arrow-left" style="font-size:.7rem"></i> Volver</a>
        </div>
    </div>

    <form id="emailForm" action="enviar.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="tipo_motivo" value="<?= $tipo ?>">
        <input type="hidden" name="asignacion_id" value="<?= $asignacion_id ?>">
        <input type="hidden" name="componente_id" value="<?= $componente_id ?>">

        <div class="composer-grid">

            <div class="g-card">
                <div class="g-section">
                    <div class="s-label">
                        <div class="s-label-dot"></div>
                        Destinatario
                    </div>

                    <?php if ($persona): ?>
                        <input type="hidden" name="persona_id" value="<?= $persona['id'] ?>">
                        <div class="recipient-box">
                            <div class="av"><i class="fas fa-user"></i></div>
                            <div style="flex:1;min-width:0">
                                <div class="av-name"><?= htmlspecialchars($persona['nombres']) ?></div>
                                <div class="chip-row">
                                    <span class="chip email"><?= htmlspecialchars($persona['email']) ?></span>
                                    <span class="chip">CI: <?= htmlspecialchars($persona['cedula']) ?></span>
                                    <?php if ($equipo): ?>
                                    <span class="chip"><?= $equipo['tipo_equipo'] ?> · <?= $equipo['codigo_barras'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <i class="fas fa-circle-check check-icon"></i>
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom:.8rem">
                            <label class="f-label">Persona <span class="f-req">*</span></label>
                            <select id="persona_select" name="persona_id_manual" required style="width:100%">
                                <option value="">Buscar por nombre o cédula…</option>
                                <?php while($p = $result_personas->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>" data-email="<?= htmlspecialchars($p['email']) ?>"
                                        data-nombres="<?= htmlspecialchars($p['nombres']) ?>"
                                        data-cedula="<?= htmlspecialchars($p['cedula']) ?>">
                                    <?= $p['nombres'] . ' — ' . $p['cedula'] ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div id="selected-person-info" class="recipient-box">
                            <div class="av"><i class="fas fa-user"></i></div>
                            <div style="flex:1;min-width:0">
                                <div class="av-name" id="sel-name"></div>
                                <div class="chip-row">
                                    <span class="chip email" id="sel-email"></span>
                                    <span class="chip" id="sel-cedula"></span>
                                </div>
                            </div>
                            <i class="fas fa-circle-check check-icon"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="g-section">
                    <div class="s-label">
                        <div class="s-label-dot" style="background:var(--c-warn);box-shadow:0 0 6px rgba(245,158,11,0.6)"></div>
                        Plantilla rápida
                    </div>
                    <div class="tpl-row">
                        <button type="button" class="tpl-btn" data-t="vencido" onclick="loadTpl('vencido')">🔴 Préstamo vencido</button>
                        <button type="button" class="tpl-btn" data-t="por_vencer" onclick="loadTpl('por_vencer')">🔵 Por vencer</button>
                        <button type="button" class="tpl-btn" data-t="recordatorio" onclick="loadTpl('recordatorio')">🟡 Recordatorio</button>
                        <button type="button" class="tpl-btn t-violet" data-t="personalizado" onclick="loadTpl('personalizado')">✏️ Personalizado</button>
                    </div>
                </div>

                <div class="g-section">
                    <div class="s-label">
                        <div class="s-label-dot" style="background:var(--c3);box-shadow:0 0 6px rgba(243,178,41,0.6)"></div>
                        Asunto
                    </div>
                    <label class="f-label">Línea de asunto <span class="f-req">*</span></label>
                    <input type="text" class="tf" id="asunto" name="asunto"
                           value="<?= htmlspecialchars($plantilla['asunto']) ?>"
                           placeholder="Escribe el asunto del correo…" required>
                </div>

                <div class="g-section">
                    <div class="s-label">
                        <div class="s-label-dot" style="background:var(--c-ok);box-shadow:0 0 6px rgba(16,185,129,0.6)"></div>
                        Mensaje
                    </div>
                    <label class="f-label">Cuerpo del correo <span class="f-req">*</span></label>
                    <textarea class="tf" id="mensaje" name="mensaje" required><?= htmlspecialchars($plantilla['mensaje']) ?></textarea>
                    <div class="char-bar"><div class="char-fill" id="charFill" style="width:0%"></div></div>
                    <div class="char-count" id="charCount">0 / 2000 caracteres</div>
                </div>

                <div class="g-section">
                    <div class="s-label">
                        <div class="s-label-dot" style="background:var(--c4);box-shadow:0 0 6px rgba(14,165,233,0.6)"></div>
                        Adjuntos
                    </div>
                    <div class="dropzone" id="dropzone">
                        <input type="file" id="adjuntos" name="adjuntos[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                        <div class="dz-icon">📎</div>
                        <div class="dz-text">Arrastra archivos o <strong>haz clic aquí</strong><br>
                        <span style="font-size:.68rem;opacity:.6">PDF · Word · Excel · Imágenes · máx 10 MB</span></div>
                    </div>
                    <div id="atag-list" style="margin-top:.6rem"></div>
                </div>

            </div><!-- /left -->

            <div class="side-col">

                <div class="preview-card" style="animation: sIn .4s .2s both">
                    <div class="preview-topbar">
                        <div class="mac-dots">
                            <span class="d-r"></span><span class="d-y"></span><span class="d-g"></span>
                        </div>
                        <div class="preview-lbl">Vista Previa</div>
                    </div>
                    <div class="preview-inner">
                        <div class="prev-from">
                            <div class="prev-av">T</div>
                            <div>
                                <div class="prev-name">Depto. Tecnología · TESA</div>
                                <div class="prev-time">ti@tesa.edu.ec · ahora mismo</div>
                            </div>
                        </div>
                        <div class="prev-subj" id="prev-subj"><?= $plantilla['asunto'] ?: '(Sin asunto)' ?></div>
                        <div class="prev-body" id="prev-body">
                            <?php if ($plantilla['mensaje']): ?>
                                <?= nl2br(htmlspecialchars($plantilla['mensaje'])) ?>
                            <?php else: ?>
                                <span style="opacity:.4">El mensaje aparecerá aquí en tiempo real…</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="stats-card" style="animation: sIn .4s .35s both">
                    <div class="s-label" style="margin-bottom:.75rem">
                        <div class="s-label-dot"></div>
                        Detalles del envío
                    </div>

                    <div class="progress-ring-wrap">
                        <svg class="ring-svg" width="56" height="56" viewBox="0 0 56 56">
                            <circle cx="28" cy="28" r="22" fill="none" stroke="rgba(107,43,140,0.1)" stroke-width="5"/>
                            <circle id="ringCircle" cx="28" cy="28" r="22" fill="none"
                                stroke="url(#rg)" stroke-width="5"
                                stroke-linecap="round"
                                stroke-dasharray="138.2"
                                stroke-dashoffset="138.2"
                                transform="rotate(-90 28 28)"
                                style="transition:stroke-dashoffset .5s ease"/>
                            <defs>
                                <linearGradient id="rg" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#6b2b8c"/>
                                    <stop offset="100%" stop-color="#f3b229"/>
                                </linearGradient>
                            </defs>
                            <text id="ringPct" x="28" y="33" text-anchor="middle"
                                  font-size="11" font-weight="700" fill="#6b2b8c" font-family="Fira Code">0%</text>
                        </svg>
                        <div class="ring-label">
                            <strong id="stat-estado">Incompleto</strong>
                            Completitud del correo
                        </div>
                    </div>

                    <div class="stat-row">
                        <span class="stat-l">Tipo de envío</span>
                        <span class="stat-v"><?= ucfirst($tipo) ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-l">Palabras</span>
                        <span class="stat-v" id="stat-words">0</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-l">Adjuntos</span>
                        <span class="stat-v" id="stat-files">0 archivos</span>
                    </div>
                </div>

                <div class="action-card" style="animation: sIn .4s .5s both">
                    <div class="action-row">
                        <a href="listar.php" class="btn-cancel-main">Cancelar</a>
                        <button type="button" class="btn-send-main" id="btnSend">
                            <i class="fas fa-paper-plane"></i> Enviar correo
                        </button>
                    </div>
                    <div class="action-note">
                        <i class="fas fa-lock" style="font-size:.65rem;color:var(--c-ok)"></i>
                        El correo se envía de forma segura e inmediata
                    </div>
                </div>

            </div><!-- /right -->

        </div><!-- /composer-grid -->
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const TPL = {
    vencido:     { asunto:'URGENTE: Préstamo Vencido – [CÓDIGO]',         mensaje:'Estimado/a [NOMBRE],\n\nLe informamos que su préstamo del equipo [EQUIPO] con código [CÓDIGO] se encuentra SIN DEVOLVER desde hace [DÍAS] días.\n\nFecha de asignación: [FECHA]\n\nPor favor, coordine la devolución inmediata con el Departamento de Tecnología.\n\nDepartamento de Tecnología – TESA' },
    por_vencer:  { asunto:'Recordatorio: Préstamo por Vencer – [CÓDIGO]', mensaje:'Estimado/a [NOMBRE],\n\nLe recordamos que su préstamo del equipo [EQUIPO] con código [CÓDIGO] vencerá en [DÍAS] día(s).\n\nFecha estimada de devolución: [FECHA]\n\nPlanifique la devolución con anticipación.\n\nDepartamento de Tecnología – TESA' },
    recordatorio:{ asunto:'Recordatorio Importante – TESA',               mensaje:'Estimado/a [NOMBRE],\n\nLe escribimos para recordarle sobre [ASUNTO].\n\nPor favor tome las acciones necesarias a la brevedad posible.\n\nGracias por su atención.\n\nDepartamento de Tecnología – TESA' },
    personalizado:{ asunto:'', mensaje:'' }
};
const tColors = { vencido:'t-red', por_vencer:'t-blue', recordatorio:'t-amber', personalizado:'t-violet' };

function loadTpl(t) {
    $('[data-t]').removeClass('t-red t-blue t-amber t-violet');
    $(`[data-t="${t}"]`).addClass(tColors[t]);
    $('#asunto').val(TPL[t].asunto).trigger('input');
    $('#mensaje').val(TPL[t].mensaje).trigger('input');
}

function updatePreview() {
    const s = $('#asunto').val().trim();
    const m = $('#mensaje').val().trim();
    $('#prev-subj').text(s || '(Sin asunto)');
    $('#prev-body').html(m ? m.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/\n/g,'<br>') : '<span style="opacity:.4">El mensaje aparecerá aquí en tiempo real…</span>');

    const len = $('#mensaje').val().length;
    const pct = Math.min(len / 2000, 1);
    $('#charFill').css({ width: (pct*100)+'%', background: pct>.9 ? 'linear-gradient(90deg,#ef4444,#f97316)' : pct>.65 ? 'linear-gradient(90deg,#f59e0b,#f97316)' : 'linear-gradient(90deg,#6b2b8c,#f3b229)' });
    $('#charCount').text(`${len.toLocaleString()} / 2000 caracteres`);

    updateStats();
}

function updateStats() {
    const m = $('#mensaje').val().trim();
    const words = m ? m.split(/\s+/).filter(w=>w).length : 0;
    $('#stat-words').text(words.toLocaleString());

    const hasDest = $('input[name="persona_id"]').val() || $('#persona_select').val();
    const hasSubj = $('#asunto').val().trim();
    const hasMsg  = m;
    const score   = (hasDest ? 34 : 0) + (hasSubj ? 33 : 0) + (hasMsg ? 33 : 0);
    const full    = score === 100;

    const circ = 138.2;
    const offset = circ - (circ * score / 100);
    document.getElementById('ringCircle').style.strokeDashoffset = offset;
    document.getElementById('ringPct').textContent = score + '%';
    const estadoEl = document.getElementById('stat-estado');
    estadoEl.textContent = full ? 'Listo ✓' : 'Incompleto';
    estadoEl.className = 'stat-v ' + (full ? 'ok' : 'warn');
}

// ---------- Gestión de archivos adjuntos con eliminación ----------
let selectedFiles = []; // array de objetos File

function updateAttachmentList() {
    const container = $('#atag-list');
    container.empty();
    if (selectedFiles.length === 0) {
        $('#stat-files').text('0 archivos');
        return;
    }
    for (let i = 0; i < selectedFiles.length; i++) {
        const f = selectedFiles[i];
        const item = $(`
            <span class="atag">
                <i class="fas fa-file" style="font-size:.7rem"></i> ${f.name}
                <i class="fas fa-times-circle remove-attach" data-index="${i}" style="cursor:pointer; margin-left:6px;"></i>
            </span>
        `);
        container.append(item);
    }
    $('#stat-files').text(selectedFiles.length + ' archivo' + (selectedFiles.length!==1?'s':''));
}

function updateFileInput() {
    const dt = new DataTransfer();
    for (let file of selectedFiles) {
        dt.items.add(file);
    }
    document.getElementById('adjuntos').files = dt.files;
}

function addFiles(fileList) {
    for (let i = 0; i < fileList.length; i++) {
        // Evitar duplicados por nombre y tamaño
        const exists = selectedFiles.some(f => f.name === fileList[i].name && f.size === fileList[i].size);
        if (!exists) selectedFiles.push(fileList[i]);
    }
    updateAttachmentList();
    updateFileInput();
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    updateAttachmentList();
    updateFileInput();
}

$(document).ready(function() {
    // Manejo de selección mediante input
    $('#adjuntos').on('change', function(e) {
        const files = e.target.files;
        addFiles(files);
    });

    // Eliminación de archivos mediante evento delegado
    $('#atag-list').on('click', '.remove-attach', function() {
        const idx = parseInt($(this).data('index'));
        if (!isNaN(idx)) removeFile(idx);
    });

    // Drag & drop
    const dropzone = document.getElementById('dropzone');
    dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('over'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('over'));
    dropzone.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('over');
        const files = e.dataTransfer.files;
        addFiles(files);
    });

    // Inicialización select2
    $('#persona_select').select2({ placeholder:'Buscar por nombre o cédula…', width:'100%' });
    $('#persona_select').on('change', function() {
        const opt = $(this).find('option:selected');
        if (opt.val()) {
            $('#sel-name').text(opt.data('nombres'));
            $('#sel-email').text(opt.data('email'));
            $('#sel-cedula').text('CI: ' + opt.data('cedula'));
            $('#selected-person-info').slideDown(250);
        } else { $('#selected-person-info').slideUp(200); }
        updateStats();
    });

    $('#asunto, #mensaje').on('input', updatePreview);
    updatePreview();
});

document.getElementById('btnSend').addEventListener('click', function(e) {
    e.preventDefault();
    const dest = $('input[name="persona_id"]').val() || $('#persona_select').val();
    const subj = $('#asunto').val().trim();
    const msg  = $('#mensaje').val().trim();
    const email= $('#sel-email').text() || $('.chip.email').first().text();

    if (!dest) { Swal.fire({ title:'Destinatario requerido', text:'Selecciona una persona antes de enviar.', icon:'warning', confirmButtonColor:'#6b2b8c' }); return; }
    if (!subj||!msg) { Swal.fire({ title:'Campos incompletos', text:'El asunto y el mensaje son obligatorios.', icon:'error', confirmButtonColor:'#6b2b8c' }); return; }

    Swal.fire({
        title: '¿Enviar correo?',
        html: `<div style="font-size:.84rem;color:#6a5c8a">Se enviará a:<br><strong style="font-size:.96rem;color:#1e1a2f">${email}</strong></div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6b2b8c',
        confirmButtonText: '<i class="fas fa-paper-plane"></i> Enviar',
        cancelButtonText: 'Cancelar'
    }).then(r => {
        if (r.isConfirmed) {
            // Enviar con AJAX
            const formData = new FormData(document.getElementById('emailForm'));
            
            fetch('/inventario_ti/modules/correos/enviar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Notificación toast de éxito
                    notificarCorreoEnviado(data.detalle.destinatario, data.detalle.asunto);
                    
                    Swal.fire({
                        title: '¡Correo Enviado!',
                        html: `<div style="font-size:.9rem;color:#10b981"><i class="fas fa-check-circle"></i> Enviado a<br><strong>${data.detalle.destinatario}</strong></div>`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'listar.php';
                    });
                } else {
                    // Notificación toast de error
                    notificarErrorCorreo(data.error || 'Error desconocido', data.detalle.destinatario);
                    
                    Swal.fire({
                        title: 'Error al Enviar',
                        html: `<div style="font-size:.9rem;color:#ef4444"><i class="fas fa-times-circle"></i><br>${data.error || 'Verifica la configuración SMTP'}</div>`,
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                notificarError('Error de conexión al enviar el correo');
                Swal.fire({
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor. Intenta nuevamente.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            });
        }
    });
});
</script>
<style>@keyframes blink { 0%,100%{opacity:1}50%{opacity:.2} }</style>
</body>
</html>

