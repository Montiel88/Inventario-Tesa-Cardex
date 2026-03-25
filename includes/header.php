<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/permisos.php';

$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php') {
    verificarSesion();
}

$es_admin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1;
$es_lector = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 2;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="theme-color" content="#1a0533">
    <title>Sistema de Inventario - TESA</title>

    <link rel="apple-touch-icon" sizes="180x180" href="/inventario_ti/assets/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/inventario_ti/assets/img/favicon-96x96.png">
    <link rel="icon" type="image/svg+xml" href="/inventario_ti/assets/img/favicon.svg">
    <link rel="manifest" href="/inventario_ti/assets/img/site.webmanifest">

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="/inventario_ti/assets/css/estilo.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
    /* =============================================================
       TESA PREMIUM NAVBAR  —  DARK LUXURY GLASSMORPHISM
    ============================================================= */
    :root {
        --c-bg:         #0a0118;
        --c-deep:       #05000a;
        --c-mid:        #160530;
        --c-violet:     #8b5cf6;
        --c-violet-glow: rgba(139, 92, 246, 0.4);
        --c-gold:       #f3b229;
        --c-gold-lt:    #ffd166;
        --c-gold-glow:  rgba(243, 178, 41, 0.4);
        --c-danger:     #f43f5e;
        --c-success:    #10b981;
        --c-info:       #06b6d4;
        --c-warning:    #f59e0b;
        --c-w90:        rgba(255,255,255,0.9);
        --c-w60:        rgba(255,255,255,0.6);
        --c-w15:        rgba(255,255,255,0.15);
        --c-w08:        rgba(255,255,255,0.08);
        --h:            68px;
        --r:            12px;
        --ease:         cubic-bezier(.4,0,.2,1);
        --font:         'Outfit','Poppins',sans-serif;
        front :var(--c-bg)
        -cal_from_jd :var(--c-mid)
    }

    * { box-sizing: border-box; }
    body { 
        font-family: var(--font); 
        background: var(--c-deep); 
        color: #fff;
        margin: 0; 
        min-height: 100vh;
        overflow-x: hidden;
        position: relative;
    }

    /* ── Global Readable Cards & Alerts ──────────────── */
    .card {
        background: rgba(255, 255, 255, 0.04) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 20px !important;
        color: #fff !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
    }

    .card-header {
        background: rgba(255, 255, 255, 0.03) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #fff !important;
        padding: 1rem 1.5rem !important;
    }

    .card-title, .card-text, .h1, .h2, .h3, .h4, .h5, .h6, h1, h2, h3, h4, h5, h6 {
        color: #fff !important;
    }

    .text-muted {
        color: rgba(255, 255, 255, 0.6) !important;
    }

    .alert {
        background: rgba(255, 255, 255, 0.05) !important;
        backdrop-filter: blur(10px) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 16px !important;
        color: #fff !important;
    }

    .alert-warning { border-left: 4px solid var(--c-gold) !important; }
    .alert-info { border-left: 4px solid var(--c-info) !important; }
    .alert-success { border-left: 4px solid var(--c-success) !important; }
    .alert-danger { border-left: 4px solid var(--c-danger) !important; }

    /* Fix for light cards in some modules */
    [class*="bg-white"], [style*="background-color: white"], [style*="background: white"] {
        background-color: rgba(255, 255, 255, 0.05) !important;
        color: #fff !important;
    }

    /* Tables legibility */
    .table { color: #fff !important; }
    .table thead th { color: var(--c-gold) !important; border-bottom: 2px solid rgba(243, 178, 41, 0.3) !important; }
    .table td { border-color: rgba(255, 255, 255, 0.05) !important; }
    .table-hover tbody tr:hover { background-color: rgba(255, 255, 255, 0.03) !important; }

    /* ── Spectacular LED Background ────────────────────── */
    body::before {
        content: '';
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: 
            radial-gradient(circle at 10% 10%, var(--c-violet-glow) 0%, transparent 40%),
            radial-gradient(circle at 90% 10%, var(--c-gold-glow) 0%, transparent 40%),
            radial-gradient(circle at 50% 50%, #1a0533 0%, var(--c-deep) 100%);
        z-index: -1;
        pointer-events: none;
    }

    body::after {
        content: '';
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
        z-index: -1;
        pointer-events: none;
        opacity: 0.4;
    }

    /* ── Navbar shell ─────────────────────────────────── */
    .tn {
        position: sticky;
        top: 0;
        z-index: 10000;
        height: var(--h);
        background: linear-gradient(108deg,
            var(--c-deep) 0%,
            var(--c-bg) 35%,
            #42148aff 70%,
            var(--c-mid) 100%) !important;
        border-bottom: 1px solid rgba(212, 151, 20, 0.3) !important;
        box-shadow:
            0 1px 0 rgba(68, 66, 66, 0.05) inset,
            0 10px 40px rgba(62, 60, 60, 0.6),
            0 0 0 1px rgba(51, 18, 109, 0.15);
        overflow: visible;
    }

    /* Aurora sweep on top */
    .tn::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 2px;
        background: linear-gradient(90deg,
            transparent 0%,
            var(--c-violet) 20%,
            var(--c-gold) 50%,
            var(--c-violet) 80%,
            transparent 100%);
        background-size: 200% 100%;
        animation: aurora 4s linear infinite;
        pointer-events: none;
    }

    @keyframes aurora {
        from { background-position: 0 0; }
        to   { background-position: 200% 0; }
    }

    /* Noise overlay */
    .tn::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.025'/%3E%3C/svg%3E");
        pointer-events: none;
        opacity: 0.5;
    }

    .tn .cf {
        height: var(--h);
        padding: 0 1.5rem;
        display: flex;
        align-items: center;
        gap: 8px;
        position: relative;
        z-index: 1;
    }

    /* ── Brand ────────────────────────────────────────── */
    .tn-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none !important;
        flex-shrink: 0;
        padding: 4px 14px 4px 4px;
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.07);
        background: rgba(255,255,255,0.04);
        transition: all 0.28s var(--ease);
        position: relative;
        overflow: hidden;
    }

    .tn-brand::before {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(135deg, var(--c-gold-glow), transparent 60%);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .tn-brand:hover { 
        border-color: rgba(243,178,41,0.5) !important; 
        box-shadow: 0 0 30px rgba(243,178,41,0.2) !important;
        transform: scale(1.02);
    }
    .tn-brand:hover::before { opacity: 1; }

    .tn-logo-ring {
        width: 46px; height: 46px;
        border-radius: 14px;
        background: linear-gradient(135deg, #fff, #f0e8ff);
        border: 2px solid var(--c-gold);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 0 0 3px rgba(243,178,41,0.12), 0 4px 18px rgba(0,0,0,0.35);
        flex-shrink: 0;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .tn-brand:hover .tn-logo-ring {
        box-shadow: 0 0 0 4px rgba(243,178,41,0.3), 0 6px 25px rgba(243,178,41,0.3);
        transform: rotate(-5deg) scale(1.05);
    }

    .tn-logo-ring img { width: 38px; height: 38px; object-fit: contain; border-radius: 10px; }

    .tn-brand-text { display: flex; flex-direction: column; gap: 2px; }

    .tn-brand-title {
        font-family: var(--font);
        font-weight: 800;
        font-size: 1.05rem;
        line-height: 1;
        color: #fff;
        letter-spacing: -0.5px;
    }

    .tn-brand-title em {
        font-style: normal;
        background: linear-gradient(135deg, var(--c-gold), var(--c-gold-lt));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .tn-role {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 0.58rem; font-weight: 800; letter-spacing: 1.2px;
        text-transform: uppercase; width: fit-content;
    }

    .tn-role.admin  { background: linear-gradient(135deg, var(--c-gold), #e8a520); color: #1a0533; box-shadow: 0 2px 10px rgba(243,178,41,0.4); }
    .tn-role.lector { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 2px 10px rgba(16,185,129,0.4); }

    /* ── Separator ────────────────────────────────────── */
    .tn-sep {
        width: 1px; height: 28px;
        background: linear-gradient(180deg, transparent, rgba(255,255,255,0.14) 50%, transparent);
        flex-shrink: 0; margin: 0 4px;
    }

    /* ── Nav items wrapper ────────────────────────────── */
    .tn-items {
        display: flex; align-items: center; gap: 2px;
        flex: 1; min-width: 0; overflow: visible;
    }

    /* ── Nav button ───────────────────────────────────── */
    .tn-btn {
        position: relative;
        display: inline-flex; align-items: center; gap: 8px;
        height: 42px; padding: 0 15px;
        border-radius: 14px;
        background: transparent;
        border: 1px solid transparent;
        color: rgba(255, 255, 255, 0.7) !important;
        font-family: var(--font); font-weight: 600; font-size: 0.85rem;
        text-decoration: none !important;
        white-space: nowrap;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer; overflow: hidden;
    }

    /* Shimmer sweep */
    .tn-btn::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(110deg,
            rgba(255,255,255,0) 30%,
            rgba(255,255,255,0.12) 50%,
            rgba(255,255,255,0) 70%);
        transform: translateX(-100%);
        transition: transform 0.6s ease;
    }

    .tn-btn:hover::after,
    .tn-btn.show::after { transform: translateX(100%); }

    .tn-btn:hover,
    .tn-btn.show {
        background: rgba(124, 58, 237, 0.15) !important;
        border-color: rgba(124, 58, 237, 0.4) !important;
        color: #fff !important;
        transform: translateY(-2px);
        box-shadow: 
            0 8px 25px rgba(124, 58, 237, 0.25),
            0 0 0 1px rgba(255, 255, 255, 0.08) inset !important;
    }

    /* Icon */
    .tn-btn .bi {
        width: 20px; height: 20px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.9rem; flex-shrink: 0;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .tn-btn:hover .bi,
    .tn-btn.show .bi {
        color: var(--c-gold) !important;
        filter: drop-shadow(0 0 8px rgba(243, 178, 41, 0.7));
        transform: scale(1.25) rotate(-5deg);
    }

    /* Custom caret */
    .tn-btn.dropdown-toggle::before {
        content: '';
        display: none;
    }

    .tn-btn.dropdown-toggle {
        padding-right: 10px;
    }

    .tn-caret {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.4);
        transition: all 0.3s var(--ease);
        margin-left: 3px;
    }

    .tn-btn.show .tn-caret { transform: rotate(180deg) scale(1.1); color: var(--c-gold); }

    /* ── Dropdown menu ────────────────────────────────── */
    .tn .dropdown-menu {
        background: rgba(15, 5, 30, 0.98) !important;
        backdrop-filter: blur(20px) saturate(1.7) !important;
        -webkit-backdrop-filter: blur(20px) saturate(1.7) !important;
        border: 1px solid rgba(124, 58, 237, 0.3) !important;
        border-top: 1px solid rgba(243, 178, 41, 0.4) !important;
        border-radius: 20px !important;
        box-shadow: 
            0 30px 60px rgba(0, 0, 0, 0.7),
            0 0 0 1px rgba(255, 255, 255, 0.05) inset !important;
        padding: 10px !important;
        min-width: 240px !important;
        margin-top: 12px !important;
        animation: menuIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        z-index: 10001 !important;
        overflow: hidden !important;
    }

    @keyframes menuIn {
        from { opacity: 0; transform: translateY(-12px) scale(0.95); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .tn .dropdown-item {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 14px !important;
        border-radius: 12px !important;
        font-family: var(--font); font-size: 0.88rem !important; font-weight: 500 !important;
        color: rgba(255, 255, 255, 0.85) !important;
        transition: all 0.25s var(--ease) !important;
        position: relative; overflow: hidden;
        text-decoration: none !important;
        margin-bottom: 2px;
    }

    .tn .dropdown-item:hover {
        background: linear-gradient(90deg, rgba(124, 58, 237, 0.25), rgba(124, 58, 237, 0.1)) !important;
        color: #fff !important;
        padding-left: 18px !important;
        box-shadow: 0 4px 15px rgba(124, 58, 237, 0.2);
    }

    .tn .dropdown-item .di {
        width: 34px; height: 34px;
        border-radius: 10px;
        background: rgba(124, 58, 237, 0.15);
        border: 1px solid rgba(124, 58, 237, 0.25);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem; color: var(--c-gold);
        flex-shrink: 0;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .tn .dropdown-item:hover .di {
        background: rgba(243, 178, 41, 0.2);
        border-color: var(--c-gold);
        color: #fff;
        transform: scale(1.15) rotate(-5deg);
        box-shadow: 0 0 15px rgba(243, 178, 41, 0.3);
    }

    .tn .dropdown-header {
        font-size: 0.65rem; font-weight: 800;
        color: rgba(243, 178, 41, 0.65);
        letter-spacing: 1.5px; text-transform: uppercase;
        padding: 10px 14px 6px;
    }

    .tn .dropdown-divider {
        border-color: rgba(255, 255, 255, 0.1) !important; 
        margin: 6px 10px !important;
    }

    /* ── Right tools ──────────────────────────────────── */
    .tn-tools {
        display: flex; align-items: center; gap: 6px;
        flex-shrink: 0; margin-left: auto;
    }

    /* ── Icon buttons (search/bell) ───────────────────── */
    .tn-icon-btn {
        width: 38px; height: 38px;
        border-radius: var(--r);
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.09);
        color: rgba(255,255,255,0.55);
        font-size: 0.88rem;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: all 0.22s var(--ease);
        position: relative; flex-shrink: 0;
    }

    .tn-icon-btn:hover {
        background: rgba(124, 58, 237, 0.25) !important;
        border-color: rgba(124, 58, 237, 0.5) !important;
        color: var(--c-gold) !important;
        transform: translateY(-3px) scale(1.08);
        box-shadow: 
            0 10px 25px rgba(124, 58, 237, 0.35), 
            0 0 20px rgba(243, 178, 41, 0.15);
    }

    .tn-icon-btn i { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .tn-icon-btn:hover i { transform: scale(1.3) rotate(-10deg); filter: drop-shadow(0 0 8px rgba(243, 178, 41, 0.6)); }

    #notifBtn:hover i { animation: bellRing 0.5s ease; }
    @keyframes bellRing {
        0%,100% { transform: scale(1.3) rotate(0); }
        25%      { transform: scale(1.3) rotate(-20deg); }
        75%      { transform: scale(1.3) rotate(20deg); }
    }

    /* Notification badge */
    .tn-badge {
        position: absolute; top: -6px; right: -6px;
        min-width: 20px; height: 20px;
        background: linear-gradient(135deg, var(--c-danger), #ff4d6d);
        color: #fff; font-size: 0.65rem; font-weight: 800;
        border-radius: 999px;
        display: flex; align-items: center; justify-content: center;
        border: 2px solid var(--c-bg); padding: 0 4px;
        box-shadow: 0 0 15px rgba(244, 63, 94, 0.5);
        animation: badgePulse 2.4s ease-in-out infinite;
    }

    @keyframes badgePulse {
        0%,100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(244, 63, 94, 0.6); }
        50%      { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(244, 63, 94, 0); }
    }

    .tn-badge:empty { display: none; }

    /* ── Logout ───────────────────────────────────────── */
    .tn-logout {
        display: inline-flex; align-items: center; gap: 8px;
        height: 42px; padding: 0 20px;
        border-radius: 14px;
        background: linear-gradient(135deg, #f43f5e, #e11d48);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #fff !important;
        font-family: var(--font); font-size: 0.88rem; font-weight: 700;
        text-decoration: none !important;
        box-shadow: 0 6px 20px rgba(244, 63, 94, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        flex-shrink: 0; white-space: nowrap;
        position: relative; overflow: hidden;
        letter-spacing: 0.3px;
    }

    .tn-logout::before {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), transparent);
        opacity: 0; transition: opacity 0.3s;
    }

    .tn-logout:hover {
        background: linear-gradient(135deg, #e11d48, #be123c);
        color: #fff !important;
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 12px 30px rgba(244, 63, 94, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.2) inset;
    }

    .tn-logout:hover i { transform: translateX(3px) scale(1.1); }
    .tn-logout i { transition: transform 0.3s; }

    /* ── Mobile toggler ───────────────────────────────── */
    .tn-toggler {
        width: 38px; height: 38px;
        border-radius: var(--r);
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.12);
        display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 5px;
        cursor: pointer; padding: 0; flex-shrink: 0;
        transition: all 0.22s var(--ease);
    }

    .tn-toggler:hover { background: rgba(124,58,237,0.2); border-color: rgba(124,58,237,0.4); }
    .tn-toggler span { display: block; width: 18px; height: 2px; background: rgba(255,255,255,0.7); border-radius: 2px; }

    /* ── Search overlay ───────────────────────────────── */
    .tn-search-overlay {
        position: fixed;
        top: var(--h); left: 0; right: 0;
        z-index: 1039;
        display: none;
        justify-content: center;
        padding: 16px 24px;
        background: rgba(10,1,22,0.93);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border-bottom: 1px solid rgba(124,58,237,0.18);
        box-shadow: 0 18px 50px rgba(0,0,0,0.45);
    }

    .tn-search-overlay.active {
        display: flex;
        animation: searchIn 0.22s var(--ease);
    }

    @keyframes searchIn {
        from { opacity: 0; transform: translateY(-10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .tn-search-box {
        display: flex; align-items: center;
        width: 100%; max-width: 680px;
        background: rgba(255,255,255,0.06);
        border: 1.5px solid rgba(243,178,41,0.38);
        border-radius: 16px; overflow: hidden;
        box-shadow: 0 0 0 4px rgba(243,178,41,0.07), 0 8px 36px rgba(0,0,0,0.4);
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .tn-search-box:focus-within {
        border-color: var(--c-gold);
        box-shadow: 0 0 0 4px rgba(243,178,41,0.15), 0 8px 36px rgba(0,0,0,0.4);
    }

    .tn-s-icon { padding: 0 16px; color: var(--c-gold); font-size: 1rem; flex-shrink: 0; }

    .tn-s-input {
        flex: 1; background: transparent; border: none; outline: none;
        color: #fff; font-family: var(--font); font-size: 0.95rem; padding: 13px 0;
    }

    .tn-s-input::placeholder { color: rgba(255,255,255,0.28); }

    .tn-s-hint {
        padding: 0 14px; font-size: 0.68rem;
        color: rgba(255,255,255,0.22); white-space: nowrap; flex-shrink: 0;
    }

    .tn-s-close {
        width: 48px; height: 48px;
        background: rgba(255,255,255,0.04);
        border: none; border-left: 1px solid rgba(255,255,255,0.07);
        color: rgba(255,255,255,0.45); font-size: 1rem; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; transition: all 0.18s;
    }

    .tn-s-close:hover { background: rgba(244,63,94,0.2); color: #fff; }

    /* ── Notification panel ───────────────────────────── */
    .tn-notif-panel {
        position: fixed;
        top: calc(var(--h) + 10px); right: 20px;
        width: 370px; max-width: calc(100vw - 32px);
        background: rgba(12,2,26,0.97);
        backdrop-filter: blur(22px);
        -webkit-backdrop-filter: blur(22px);
        border: 1px solid rgba(124,58,237,0.22);
        border-radius: 20px;
        box-shadow: 0 32px 80px rgba(0,0,0,0.65), 0 0 0 1px rgba(255,255,255,0.04) inset;
        z-index: 1050;
        display: none; flex-direction: column; overflow: hidden;
    }

    .tn-notif-panel.show {
        display: flex;
        animation: panelIn 0.22s var(--ease);
    }

    @keyframes panelIn {
        from { opacity: 0; transform: translateY(-10px) scale(0.97); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .tn-notif-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px;
        background: linear-gradient(135deg, rgba(124,58,237,0.28), rgba(90,45,140,0.18));
        border-bottom: 1px solid rgba(124,58,237,0.18);
    }

    .tn-notif-head h6 {
        margin: 0; font-family: var(--font);
        font-size: 0.88rem; font-weight: 700; color: #fff;
        display: flex; align-items: center; gap: 8px;
    }

    .tn-notif-head h6 i { color: var(--c-gold); }

    .tn-notif-x {
        width: 28px; height: 28px;
        border-radius: 8px;
        background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.09);
        color: rgba(255,255,255,0.55); font-size: 0.75rem;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: all 0.18s;
    }

    .tn-notif-x:hover { background: rgba(244,63,94,0.2); color: #fff; }

    .tn-notif-list { max-height: 420px; overflow-y: auto; }
    .tn-notif-list::-webkit-scrollbar { width: 4px; }
    .tn-notif-list::-webkit-scrollbar-thumb { background: rgba(124,58,237,0.4); border-radius: 4px; }

    .tn-notif-item {
        padding: 12px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        transition: background 0.18s; cursor: pointer;
    }

    .tn-notif-item:hover { background: rgba(124,58,237,0.1); }
    .tn-notif-item.danger  { border-left: 3px solid var(--c-danger); }
    .tn-notif-item.warning { border-left: 3px solid var(--c-warning); }
    .tn-notif-item.success { border-left: 3px solid var(--c-success); }
    .tn-notif-item.info    { border-left: 3px solid var(--c-info); }

    .tn-notif-title { font-size: 0.82rem; font-weight: 700; color: var(--c-w90); margin-bottom: 3px; display: flex; align-items: center; gap: 7px; }
    .tn-notif-msg   { font-size: 0.77rem; color: rgba(255,255,255,0.48); }
    .tn-notif-link a { font-size: 0.72rem; color: var(--c-gold); opacity: 0.8; text-decoration: none; font-weight: 600; transition: opacity 0.2s; }
    .tn-notif-link a:hover { opacity: 1; }

    .tn-notif-empty { text-align: center; padding: 36px 20px; color: rgba(255,255,255,0.28); font-size: 0.85rem; }

    .tn-notif-foot {
        padding: 10px; text-align: center;
        border-top: 1px solid rgba(255,255,255,0.06);
        background: rgba(255,255,255,0.02);
    }

    .tn-notif-foot a { font-size: 0.78rem; color: rgba(243,178,41,0.8); text-decoration: none; font-weight: 600; transition: color 0.2s; }
    .tn-notif-foot a:hover { color: var(--c-gold); }

    /* ── Lector bar ───────────────────────────────────── */
    .tn-lector-bar {
        background: linear-gradient(90deg, rgba(16,185,129,0.1), rgba(16,185,129,0.03));
        border-bottom: 1px solid rgba(16,185,129,0.18);
        padding: 9px 24px;
        display: flex; align-items: center; gap: 10px;
        font-family: var(--font); font-size: 0.8rem; color: rgba(16,185,129,0.9);
    }

    .tn-lector-bar i { color: var(--c-success); font-size: 0.9rem; }

    /* ── Responsive ───────────────────────────────────── */
    @media (max-width: 1199.98px) {
        .tn .navbar-collapse {
            position: absolute; top: var(--h); left: 0; right: 0;
            background: rgba(10,1,20,0.98);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            border-bottom: 1px solid rgba(124,58,237,0.22);
            padding: 12px 16px 16px;
            box-shadow: 0 18px 48px rgba(0,0,0,0.55);
            z-index: 1038;
            animation: colIn 0.22s var(--ease);
        }

        @keyframes colIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .tn-items { flex-direction: column; align-items: stretch; gap: 3px; overflow: visible; }
        .tn-btn   { width: 100%; height: 44px; border-radius: 10px; font-size: 0.87rem; justify-content: flex-start; }
        .tn-tools { margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.07); flex-wrap: wrap; }
        .tn-logout { width: 100%; justify-content: center; height: 44px; }
        .tn-sep { display: none; }
    }

    @media (max-width: 575px) {
        .tn-brand-text { display: none; }
        .tn-notif-panel { right: 8px; left: 8px; width: auto; }
    }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════
     TESA PREMIUM NAVBAR
═══════════════════════════════════════════ -->
<nav class="navbar navbar-expand-xl tn">
    <div class="container-fluid cf">

        <!-- Brand -->
        <a class="tn-brand" href="/inventario_ti/modules/dashboard.php">
            <div class="tn-logo-ring">
                <img src="/inventario_ti/assets/img/logo-tesa.png" alt="TESA"
                     onerror="this.style.display='none'">
            </div>
            <div class="tn-brand-text">
                <span class="tn-brand-title">TESA <em>Inventario</em></span>
                <?php if (isset($_SESSION['user_rol'])): ?>
                <span class="tn-role <?= $es_admin ? 'admin' : 'lector' ?>">
                    <i class="fas <?= $es_admin ? 'fa-crown' : 'fa-eye' ?>"></i>
                    <?= $es_admin ? 'ADMIN' : 'INVITADO' ?>
                </span>
                <?php endif; ?>
            </div>
        </a>

        <!-- Mobile toggler -->
        <button class="tn-toggler navbar-toggler ms-auto me-2"
                type="button" data-bs-toggle="collapse" data-bs-target="#tnMenu"
                aria-controls="tnMenu" aria-expanded="false" aria-label="Menú">
            <span></span><span></span><span></span>
        </button>

        <!-- Collapse -->
        <div class="collapse navbar-collapse" id="tnMenu">

            <div class="tn-sep ms-3 me-1"></div>

            <!-- Nav items -->
            <div class="tn-items">

                <!-- Dashboard -->
                <a class="tn-btn" href="/inventario_ti/modules/dashboard.php">
                    <span class="bi"><i class="fas fa-house-chimney"></i></span>
                    Dashboard
                </a>

                <!-- Personas -->
                <div class="dropdown">
                    <a class="tn-btn dropdown-toggle" href="#"
                       data-bs-toggle="dropdown" role="button" aria-expanded="false">
                        <span class="bi"><i class="fas fa-users"></i></span>
                        Personas
                        <i class="fas fa-chevron-down tn-caret"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/inventario_ti/modules/personas/listar.php">
                            <span class="di"><i class="fas fa-list-ul"></i></span>Listar Personas
                        </a></li>
                        <?php if ($es_admin): ?>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/personas/agregar.php">
                            <span class="di"><i class="fas fa-user-plus"></i></span>Agregar Persona
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Equipos -->
                <div class="dropdown">
                    <a class="tn-btn dropdown-toggle" href="#"
                       data-bs-toggle="dropdown" role="button" aria-expanded="false">
                        <span class="bi"><i class="fas fa-desktop"></i></span>
                        Equipos
                        <i class="fas fa-chevron-down tn-caret"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/inventario_ti/modules/equipos/listar.php">
                            <span class="di"><i class="fas fa-list-ul"></i></span>Listar Equipos
                        </a></li>
                        <?php if ($es_admin): ?>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/equipos/agregar.php">
                            <span class="di"><i class="fas fa-plus-circle"></i></span>Agregar Equipo
                        </a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/mantenimientos/listar.php">
                            <span class="di"><i class="fas fa-screwdriver-wrench"></i></span>Mantenimientos
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header"><i class="fas fa-microchip me-1"></i>Componentes</h6></li>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/componentes/listar.php">
                            <span class="di"><i class="fas fa-microchip"></i></span>Listar Componentes
                        </a></li>
                        <?php if ($es_admin): ?>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/componentes/agregar.php">
                            <span class="di"><i class="fas fa-plus"></i></span>Agregar Componente
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Movimientos -->
                <div class="dropdown">
                    <a class="tn-btn dropdown-toggle" href="#"
                       data-bs-toggle="dropdown" role="button" aria-expanded="false">
                        <span class="bi"><i class="fas fa-right-left"></i></span>
                        Movimientos
                        <i class="fas fa-chevron-down tn-caret"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if ($es_admin): ?>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/traspaso.php">
                            <span class="di"><i class="fas fa-shuffle"></i></span>Traspaso de Custodio
                        </a></li>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/prestamo.php">
                            <span class="di"><i class="fas fa-hand-holding"></i></span>Registrar Préstamo
                        </a></li>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/devolucion.php">
                            <span class="di"><i class="fas fa-rotate-left"></i></span>Registrar Devolución
                        </a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/historial.php">
                            <span class="di"><i class="fas fa-clock-rotate-left"></i></span>Historial
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/prestamos_rapidos/listar.php">
                            <span class="di"><i class="fas fa-hand-holding-heart"></i></span>Préstamos Rápidos
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/reportes/index.php">
                            <span class="di"><i class="fas fa-file-chart-column"></i></span>Generar Reportes
                        </a></li>
                    </ul>
                </div>

                <?php if ($es_admin): ?>
                <!-- Correos -->
                <div class="dropdown">
                    <a class="tn-btn dropdown-toggle" href="#"
                       data-bs-toggle="dropdown" role="button" aria-expanded="false">
                        <span class="bi"><i class="fas fa-envelope-open-text"></i></span>
                        Correos
                        <i class="fas fa-chevron-down tn-caret"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/inventario_ti/modules/correos/listar.php">
                            <span class="di"><i class="fas fa-inbox"></i></span>Gestión de Correos
                        </a></li>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/correos/composer.php">
                            <span class="di"><i class="fas fa-pen-to-square"></i></span>Componer Correo
                        </a></li>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/correos/historial.php">
                            <span class="di"><i class="fas fa-clock-rotate-left"></i></span>Historial
                        </a></li>
                    </ul>
                </div>

                <!-- Admin -->
                <div class="dropdown">
                    <a class="tn-btn dropdown-toggle" href="#"
                       data-bs-toggle="dropdown" role="button" aria-expanded="false">
                        <span class="bi"><i class="fas fa-shield-halved"></i></span>
                        Admin
                        <i class="fas fa-chevron-down tn-caret"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/inventario_ti/modules/admin/backup.php">
                            <span class="di"><i class="fas fa-database"></i></span>Respaldos
                        </a></li>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/admin/usuarios.php">
                            <span class="di"><i class="fas fa-users-gear"></i></span>Usuarios
                        </a></li>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/admin/configuracion.php">
                            <span class="di"><i class="fas fa-file-pdf"></i></span>Config. de Actas
                        </a></li>
                        <li><a class="dropdown-item" href="/inventario_ti/modules/admin/logs.php">
                            <span class="di"><i class="fas fa-scroll"></i></span>Logs del Sistema
                        </a></li>
                    </ul>
                </div>
                <?php endif; ?>

            </div><!-- /tn-items -->

            <!-- Right tools -->
            <div class="tn-tools ms-2">

                <?php if (isset($_SESSION['user_id'])): ?>
                <button class="tn-icon-btn" id="searchTrigger" type="button" title="Buscar (Ctrl+K)">
                    <i class="fas fa-magnifying-glass"></i>
                </button>
                <?php endif; ?>

                <div class="tn-sep"></div>

                <button class="tn-icon-btn" id="notifBtn"
                        onclick="toggleNotificationPanel()" type="button" title="Notificaciones">
                    <i class="fas fa-bell"></i>
                    <span class="tn-badge" id="notificationBadgeHeader"></span>
                </button>

                <div class="tn-sep"></div>

                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/inventario_ti/logout.php" class="tn-logout"
                   onclick="return confirm('¿Estás seguro de cerrar sesión?')">
                    <i class="fas fa-arrow-right-from-bracket"></i>Salir
                </a>
                <?php endif; ?>

            </div>

        </div><!-- /collapse -->
    </div>
</nav>

<!-- Search overlay -->
<?php if (isset($_SESSION['user_id'])): ?>
<div class="tn-search-overlay" id="searchOverlay">
    <form action="/inventario_ti/buscar.php" method="GET" class="tn-search-box">
        <span class="tn-s-icon"><i class="fas fa-magnifying-glass"></i></span>
        <input type="text" name="q" id="searchInput" class="tn-s-input"
               placeholder="Buscar equipos, personas, componentes…" autocomplete="off">
        <span class="tn-s-hint">ESC para cerrar &nbsp;·&nbsp; Ctrl+K</span>
        <button type="button" class="tn-s-close" id="searchClose">
            <i class="fas fa-xmark"></i>
        </button>
    </form>
</div>
<?php endif; ?>

<script>
/* Search System */
(function(){
    const t=document.getElementById('searchTrigger'),
          o=document.getElementById('searchOverlay'),
          i=document.getElementById('searchInput'),
          c=document.getElementById('searchClose');

    const open=()=>{ if(!o)return; o.classList.add('active'); setTimeout(()=>i&&i.focus(),80); };
    const shut=()=>{ if(!o)return; o.classList.remove('active'); if(i)i.value=''; };

    if(t)t.addEventListener('click',open);
    if(c)c.addEventListener('click',shut);
    document.addEventListener('keydown',e=>{
        if(e.key==='Escape')shut();
        if((e.ctrlKey||e.metaKey)&&e.key==='k'){e.preventDefault();open();}
    });
})();
</script>

<?php if ($es_lector): ?>
<div class="tn-lector-bar">
    <i class="fas fa-circle-info"></i>
    <span><strong>Modo solo lectura:</strong> Puedes ver información pero no puedes agregar, editar ni eliminar registros.</span>
</div>
<?php endif; ?>

<main class="container mt-4">
