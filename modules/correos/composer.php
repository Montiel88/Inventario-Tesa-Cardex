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
        /* ═══════════════════════════════════════
           TOKENS
        ═══════════════════════════════════════ */
        :root {
            --c1: #2b1b3e;
            --c2: #6b2b8c;
            --c3: #f3b229;
            --c4: #0ea5e9;
            --c-ok: #10b981;
            --c-warn: #f59e0b;
            --c-err: #ef4444;
            --glass: rgba(255, 255, 255, 0.92);
            --glass-border: rgba(43, 27, 62, 0.2);
            --glass-shadow: 0 8px 32px rgba(43, 27, 62, 0.12), 0 1.5px 0 rgba(255,255,255,0.9) inset;
            --text: #1e1a2f;
            --muted: #6a5c8a;
            --radius: 22px;
            --radius-sm: 14px;
            --radius-pill: 100px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text);
            overflow-x: hidden;
            background: radial-gradient(circle at 10% 30%, #f8f9fc 0%, #eef2f5 100%);
            padding: 0;
        }


        /* Fondo dinámico animado (orbes en movimiento) */
        .bg-scene {
            position: fixed; inset: 0; z-index: 0;
            background: linear-gradient(135deg, #f5f0ff 0%, #e9e4ff 35%, #fff3e8 65%, #f0f4ff 100%);
            overflow: hidden;
        }
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.55;
            animation: floatOrb linear infinite;
        }
        .orb-1 { width: 520px; height: 520px; background: radial-gradient(circle, #8b5cf6, #4c1d95); top: -120px; left: -100px; animation-duration: 20s; }
        .orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, #f3b229, #d97706); top: 40%; right: -120px; animation-duration: 26s; animation-direction: reverse; }
        .orb-3 { width: 350px; height: 350px; background: radial-gradient(circle, #0ea5e9, #38bdf8); bottom: -80px; left: 30%; animation-duration: 22s; }
        .orb-4 { width: 280px; height: 280px; background: radial-gradient(circle, #8b5cf6, #a855f7); top: 20%; left: 40%; animation-duration: 18s; opacity: 0.35; }
        @keyframes floatOrb {
            0%   { transform: translate(0,0) scale(1); }
            25%  { transform: translate(40px,-30px) scale(1.05); }
            50%  { transform: translate(-20px,50px) scale(0.95); }
            75%  { transform: translate(30px,20px) scale(1.08); }
            100% { transform: translate(0,0) scale(1); }
        }

        .bg-grid {
            position: fixed; inset: 0; z-index: 1;
            background-image:
                linear-gradient(rgba(107, 43, 140, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(107, 43, 140, 0.06) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        .page { position: relative; z-index: 2; max-width: 1180px; margin: 0 auto; padding: 2.2rem 1.5rem 5rem; animation: pageIn .65s cubic-bezier(.22,1,.36,1) both; }
        @keyframes pageIn { from { opacity:0; transform: translateY(22px); } to { opacity:1; transform:translateY(0); } }

        /* Top bar */
        .top-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2.8rem; flex-wrap: wrap; gap: 1rem; }
        .logo-block { display: flex; align-items: center; gap: 1rem; }
        .logo-gem {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, var(--c1), var(--c2));
            border-radius: 18px;
            display: grid; place-items: center;
            box-shadow: 0 6px 24px rgba(107, 43, 140, 0.45), 0 1px 0 rgba(255,255,255,0.5) inset;
            position: relative; overflow: hidden;
            animation: gemPulse 3s ease-in-out infinite;
        }
        @keyframes gemPulse {
            0%,100% { box-shadow: 0 6px 24px rgba(107,43,140,0.45), 0 1px 0 rgba(255,255,255,0.5) inset; }
            50%      { box-shadow: 0 8px 36px rgba(243,178,41,0.55), 0 1px 0 rgba(255,255,255,0.5) inset; }
        }
        .logo-gem::after { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255,255,255,0.25) 0%, transparent 55%); }
        .logo-gem i { color: white; font-size: 1.4rem; position: relative; z-index: 1; }
        .logo-text { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, var(--c1), var(--c2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; }
        .logo-sub { font-size: 0.72rem; font-weight: 500; color: var(--muted); letter-spacing: 0.1em; text-transform: uppercase; margin-top: 4px; }
        .top-right { display: flex; align-items: center; gap: .8rem; }
        .badge-urgente {
            display: inline-flex; align-items: center; gap: .45rem;
            background: rgba(239,68,68,0.12);
            border: 1.5px solid rgba(239,68,68,0.35);
            color: var(--c-err);
            border-radius: var(--radius-pill);
            padding: .35rem .9rem;
            font-size: .72rem; font-weight: 700;
            letter-spacing: .07em; text-transform: uppercase;
            animation: urgentPulse 1.8s ease infinite;
        }
        @keyframes urgentPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.3); } 50% { box-shadow: 0 0 0 8px rgba(239,68,68,0); } }
        .btn-back {
            display: flex; align-items: center; gap: .5rem;
            padding: .5rem 1.1rem;
            background: var(--glass); border: 1px solid var(--glass-border);
            border-radius: var(--radius-pill);
            color: var(--muted); font-size: .82rem; font-weight: 500;
            text-decoration: none;
            backdrop-filter: blur(12px);
            transition: all .2s;
            box-shadow: 0 2px 10px rgba(107,43,140,.08);
        }
        .btn-back:hover { color: var(--c1); border-color: rgba(107,43,140,0.4); background: rgba(255,255,255,0.85); }

        /* Layout grid */
        .composer-grid { display: grid; grid-template-columns: 1fr 390px; gap: 1.6rem; align-items: start; }
        @media (max-width: 880px) { .composer-grid { grid-template-columns: 1fr; } }

        /* Glass card */
        .g-card {
            background: var(--glass);
            border: 1.5px solid var(--glass-border);
            border-radius: var(--radius);
            backdrop-filter: blur(24px);
            box-shadow: var(--glass-shadow);
            overflow: hidden;
            position: relative;
        }
        .g-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, var(--c1), var(--c2), var(--c3));
            z-index: 1;
        }
        .g-section {
            padding: 1.6rem 1.8rem;
            border-bottom: 1px solid rgba(107,43,140,0.1);
            animation: sIn .5s cubic-bezier(.22,1,.36,1) both;
        }
        .g-section:last-child { border-bottom: none; }
        @keyframes sIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        .g-section:nth-child(1) { animation-delay:.08s; }
        .g-section:nth-child(2) { animation-delay:.16s; }
        .g-section:nth-child(3) { animation-delay:.24s; }
        .g-section:nth-child(4) { animation-delay:.32s; }
        .g-section:nth-child(5) { animation-delay:.40s; }

        .s-label { display: flex; align-items: center; gap: .55rem; font-size: .68rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); margin-bottom: 1.1rem; }
        .s-label-dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, var(--c1), var(--c2)); box-shadow: 0 0 6px rgba(107,43,140,.6); }

        /* Recipient */
        .recipient-box {
            background: linear-gradient(135deg, rgba(107,43,140,0.07), rgba(243,178,41,0.07));
            border: 1.5px solid rgba(107,43,140,0.2);
            border-radius: var(--radius-sm);
            padding: 1.1rem 1.3rem;
            display: flex; align-items: center; gap: 1rem;
            position: relative; overflow: hidden;
        }
        .recipient-box::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
            background: linear-gradient(180deg, var(--c1), var(--c2));
            border-radius: 4px 0 0 4px;
        }
        .av { width: 48px; height: 48px; min-width: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--c1), var(--c2)); display: grid; place-items: center; color: white; font-size: 1.1rem; box-shadow: 0 4px 14px rgba(107,43,140,0.35); }
        .av-name { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: var(--text); line-height: 1.2; }
        .chip-row { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .45rem; }
        .chip { font-size: .7rem; font-family: 'Fira Code', monospace; padding: .18rem .6rem; border-radius: var(--radius-pill); border: 1px solid rgba(107,43,140,0.2); background: rgba(107,43,140,0.06); color: var(--c1); }
        .chip.email { background: rgba(14,165,233,0.08); border-color: rgba(14,165,233,0.3); color: #0c6b9e; }
        .check-icon { margin-left: auto; color: var(--c-ok); font-size: 1.1rem; flex-shrink: 0; }

        /* Template pills */
        .tpl-row { display: flex; flex-wrap: wrap; gap: .55rem; }
        .tpl-btn {
            cursor: pointer; border: 1.5px solid transparent;
            border-radius: var(--radius-pill);
            padding: .42rem 1.05rem;
            font-size: .77rem; font-weight: 600;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex; align-items: center; gap: .4rem;
            transition: all .22s cubic-bezier(.34,1.56,.64,1);
            background: rgba(255,255,255,0.55);
            color: var(--muted);
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
        }
        .tpl-btn:hover { transform: translateY(-2px); color: var(--text); background: rgba(255,255,255,0.85); }
        .tpl-btn.t-red   { background: rgba(239,68,68,0.1);  border-color: rgba(239,68,68,0.4);   color: #c2410c; }
        .tpl-btn.t-blue  { background: rgba(14,165,233,0.1);   border-color: rgba(14,165,233,0.4);    color: #0c6b9e; }
        .tpl-btn.t-amber { background: rgba(245,158,11,0.12);  border-color: rgba(245,158,11,0.4);    color: #b45309; }
        .tpl-btn.t-violet{ background: rgba(107,43,140,0.1);  border-color: rgba(107,43,140,0.4);   color: var(--c1); }

        /* Form fields */
        .f-label { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--muted); margin-bottom: .5rem; }
        .f-req { color: var(--c-err); margin-left: 2px; }
        .tf {
            width: 100%;
            background: rgba(255,255,255,0.7);
            border: 1.5px solid rgba(107,43,140,0.15);
            border-radius: var(--radius-sm);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif; font-size: .9rem;
            padding: .75rem 1.1rem;
            transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none; resize: none;
        }
        .tf:focus {
            border-color: var(--c1);
            background: rgba(255,255,255,0.92);
            box-shadow: 0 0 0 4px rgba(107,43,140,0.12), 0 2px 12px rgba(107,43,140,0.1);
        }
        textarea.tf { min-height: 210px; line-height: 1.75; }

        .char-bar { height: 3px; border-radius: 99px; margin-top: .4rem; background: rgba(107,43,140,0.12); overflow: hidden; }
        .char-fill { height: 100%; background: linear-gradient(90deg, var(--c1), var(--c2)); border-radius: 99px; transition: width .2s, background .2s; }
        .char-count { font-size: .68rem; font-family: 'Fira Code', monospace; color: var(--muted); text-align: right; margin-top: .2rem; }

        /* Select2 */
        .select2-container--default .select2-selection--single { height: 48px; background: rgba(255,255,255,0.7); border: 1.5px solid rgba(107,43,140,0.15) !important; border-radius: var(--radius-sm) !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { color: var(--text); line-height: 46px; padding-left: 14px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px; }
        .select2-dropdown { background: rgba(255,255,255,0.96); border: 1.5px solid rgba(107,43,140,0.2); border-radius: 14px; }
        .select2-container--default .select2-results__option--highlighted { background: linear-gradient(90deg, var(--c1), var(--c2)); }

        /* Dropzone */
        .dropzone {
            position: relative;
            border: 2px dashed rgba(107,43,140,0.25);
            border-radius: var(--radius-sm);
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all .22s;
            background: rgba(107,43,140,0.03);
        }
        .dropzone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .dropzone:hover, .dropzone.over { border-color: var(--c1); background: rgba(107,43,140,0.07); }
        .dz-icon { font-size: 2rem; margin-bottom: .5rem; }
        .dz-text { font-size: .8rem; color: var(--muted); }
        .dz-text strong { color: var(--c1); }

        /* Attachment list with remove */
        .atag {
            display: inline-flex; align-items: center; gap: .35rem;
            background: rgba(14,165,233,0.09); border: 1px solid rgba(14,165,233,0.3);
            color: #0c6b9e; border-radius: var(--radius-pill);
            padding: .22rem .7rem; font-size: .72rem; margin: .2rem;
            animation: tagIn .3s cubic-bezier(.34,1.56,.64,1) both;
        }
        .remove-attach {
            cursor: pointer;
            margin-left: 6px;
            font-size: 0.75rem;
            color: #ef4444;
            transition: transform 0.1s;
        }
        .remove-attach:hover { transform: scale(1.2); color: #c2410c; }
        @keyframes tagIn { from { opacity:0; transform:scale(.75); } to { opacity:1; transform:scale(1); } }

        /* Preview card */
        .preview-card {
            background: white;
            border: 1.5px solid rgba(107,43,140,0.12);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(107,43,140,0.1), 0 2px 0 rgba(96, 23, 156, 0.9) inset;
        }
        .preview-topbar {
            background: linear-gradient(135deg, #fafbff, #f5f0ff);
            border-bottom: 1px solid rgba(107,43,140,0.1);
            padding: .85rem 1.2rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .mac-dots { display: flex; gap: 6px; }
        .mac-dots span { width: 11px; height: 11px; border-radius: 50%; }
        .d-r { background: #ff5f57; }
        .d-y { background: #febc2e; }
        .d-g { background: #28c840; }
        .preview-lbl {
            font-size: .65rem; font-weight: 700; letter-spacing: .1em;
            text-transform: uppercase; color: var(--muted);
            background: rgba(107,43,140,0.08); border-radius: var(--radius-pill);
            padding: .2rem .65rem; border: 1px solid rgba(107,43,140,0.15);
        }
        .preview-inner { padding: 1.3rem 1.5rem; }
        .prev-from { display: flex; align-items: center; gap: .7rem; padding-bottom: .85rem; border-bottom: 1px solid rgba(107,43,140,0.08); margin-bottom: .85rem; }
        .prev-av { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, var(--c1), var(--c2)); display: grid; place-items: center; font-size: .7rem; font-weight: 700; color: white; }
        .prev-name { font-size: .78rem; font-weight: 600; color: var(--text); }
        .prev-time { font-size: .65rem; color: var(--muted); }
        .prev-subj { font-family: 'Syne', sans-serif; font-size: .92rem; font-weight: 700; color: var(--text); margin-bottom: .75rem; line-height: 1.4; }
        .prev-body {
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text);
            overflow-x: hidden;
            background: radial-gradient(circle at 10% 30%, #f8f9fc 0%, #eef2f5 100%);
            padding: 0;
        }


        /* Stats card */
        .stats-card {
            background: var(--glass);
            border: 1.5px solid var(--glass-border);
            border-radius: var(--radius);
            backdrop-filter: blur(20px);
            padding: 1.3rem 1.6rem;
            box-shadow: var(--glass-shadow);
        }
        .stat-row { display: flex; justify-content: space-between; align-items: center; padding: .52rem 0; border-bottom: 1px solid rgba(107,43,140,0.07); }
        .stat-row:last-child { border-bottom: none; }
        .stat-l { font-size: .73rem; color: var(--muted); font-weight: 500; }
        .stat-v { font-family: 'Fira Code', monospace; font-size: .75rem; font-weight: 600; color: var(--text); }
        .stat-v.ok { color: var(--c-ok); }
        .stat-v.warn { color: var(--c-warn); }

        .progress-ring-wrap {
            display: flex; align-items: center; gap: 1rem;
            padding: 1rem 0 .5rem;
        }
        .ring-svg { flex-shrink: 0; }
        .ring-label { font-size: .82rem; color: var(--muted); }
        .ring-label strong { display: block; font-size: 1.05rem; font-family: 'Syne',sans-serif; color: var(--text); }

        .action-card {
            background: var(--glass);
            border: 1.5px solid var(--glass-border);
            border-radius: var(--radius);
            backdrop-filter: blur(20px);
            padding: 1.4rem 1.6rem;
            box-shadow: var(--glass-shadow);
        }
        .action-row { display: flex; gap: .8rem; }
        .btn-cancel-main {
            flex: 1; padding: .82rem;
            border-radius: var(--radius-sm);
            border: 1.5px solid rgba(107,43,140,0.2);
            background: rgba(255,255,255,0.5);
            color: var(--muted); font-family: 'Plus Jakarta Sans',sans-serif;
            font-size: .85rem; font-weight: 600;
            cursor: pointer; transition: all .2s;
            text-align: center; text-decoration: none;
            display: grid; place-items: center;
        }
        .btn-cancel-main:hover { background: white; color: var(--text); }
        .btn-send-main {
            flex: 2; padding: .88rem 1.4rem;
            border-radius: var(--radius-sm); border: none;
            background: linear-gradient(135deg, var(--c1) 0%, var(--c2) 100%);
            color: white;
            font-family: 'Plus Jakarta Sans',sans-serif; font-size: .88rem; font-weight: 700;
            cursor: pointer; transition: all .22s;
            display: flex; align-items: center; justify-content: center; gap: .6rem;
            box-shadow: 0 6px 24px rgba(107,43,140,0.4), 0 1px 0 rgba(129, 32, 32, 0.29) inset;
            position: relative; overflow: hidden;
        }
        .btn-send-main::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.18), transparent); transition: left .55s; }
        .btn-send-main:hover { transform: translateY(-2px); box-shadow: 0 10px 36px rgba(107,43,140,0.5); }
        .btn-send-main:hover::before { left: 100%; }
        .btn-send-main:active { transform: translateY(0); }
        .action-note { font-size: .67rem; color: var(--muted); text-align: center; margin-top: .75rem; display: flex; align-items: center; justify-content: center; gap: .35rem; }
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

