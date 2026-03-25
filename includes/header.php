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
    <meta name="theme-color" content="#5a2d8c">
    <title>Sistema de Inventario - TESA</title>

    <link rel="apple-touch-icon" sizes="180x180" href="/inventario_ti/assets/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/inventario_ti/assets/img/favicon-96x96.png">
    <link rel="icon" type="image/svg+xml" href="/inventario_ti/assets/img/favicon.svg">
    <link rel="manifest" href="/inventario_ti/assets/img/site.webmanifest">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="/inventario_ti/assets/css/estilo.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        /* Panel de notificaciones */
.notification-panel {
    position: absolute;
    top: 65px;
    right: 20px;
    width: 380px;
    max-width: calc(100vw - 40px);
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    z-index: 1050;
    display: none;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #f3b229;
    font-family: inherit;
}

.notification-panel.show {
    display: flex;
}

.notification-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: linear-gradient(135deg, #5a2d8c, #3d1e5e);
    color: white;
    font-weight: 600;
    border-bottom: 2px solid #f3b229;
}

.notification-panel-header h6 {
    margin: 0;
    font-size: 1rem;
}

.notification-panel-header .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.8;
    margin: 0;
}

.notification-list {
    max-height: 450px;
    overflow-y: auto;
    padding: 8px 0;
}

.notification-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s;
    cursor: pointer;
}

.notification-item:hover {
    background: #f9f5ff;
}

.notification-item.danger { border-left: 4px solid #dc3545; }
.notification-item.warning { border-left: 4px solid #ffc107; }
.notification-item.success { border-left: 4px solid #28a745; }
.notification-item.info { border-left: 4px solid #17a2b8; }

.notification-title {
    font-weight: 600;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.notification-title i {
    font-size: 1rem;
}

.notification-message {
    font-size: 0.85rem;
    color: #4a5568;
    margin-bottom: 4px;
}

.notification-date {
    font-size: 0.7rem;
    color: #9ca3af;
}

.notification-link {
    margin-top: 6px;
    font-size: 0.75rem;
}

.notification-link a {
    color: #5a2d8c;
    text-decoration: none;
    font-weight: 500;
}

.notification-link a:hover {
    text-decoration: underline;
}

.notification-empty {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
}

.notification-panel-footer {
    padding: 10px 16px;
    text-align: center;
    border-top: 1px solid #f0f0f0;
    background: #f8f9fa;
    font-size: 0.8rem;
}

.notification-panel-footer a {
    color: #5a2d8c;
    text-decoration: none;
}

@media (max-width: 576px) {
    .notification-panel {
        width: calc(100vw - 30px);
        right: 15px;
        left: auto;
    }
}
        .tesa-navbar {
            background: #5a2d8c;
            border-bottom: 4px solid #f3b229;
            padding: 0.8rem 0;
        }

        .tesa-brand {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border: 3px solid #f3b229;
            border-radius: 999px;
            padding: 0.35rem 0.8rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
            min-width: auto;
            max-width: 100%;
        }

        .tesa-brand img {
            height: 38px;
            width: auto;
            margin-right: 0.6rem;
            border-radius: 999px;
            border: 1px solid rgba(90, 45, 140, 0.25);
            background: #fff;
            padding: 2px 6px;
        }

        .tesa-brand-title {
            font-weight: 800;
            color: #3d1e5e;
            font-size: 1.35rem;
            line-height: 1;
            margin-right: 0.4rem;
            white-space: nowrap;
        }

        .rol-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        .rol-badge.admin {
            background: #f3b229;
            color: #40205f;
        }

        .rol-badge.lector {
            background: #28a745;
            color: #fff;
        }

        .tesa-navbar .navbar-toggler {
            border: 1px solid rgba(243, 178, 41, 0.6);
            background: rgba(255, 255, 255, 0.12);
            border-radius: 12px;
        }

        .tesa-navbar .navbar-nav {
            gap: 0.35rem;
            align-items: center;
        }

        .tesa-nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(243, 178, 41, 0.42);
            color: #fff !important;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.22s ease;
            white-space: nowrap;
        }

        .tesa-nav-btn i {
            color: #f3b229;
            margin-right: 0.45rem;
        }

        .tesa-nav-btn:hover,
        .tesa-nav-btn:focus,
        .tesa-nav-btn.dropdown-toggle.show {
            color: #fff !important;
            background: rgba(243, 178, 41, 0.2);
            border-color: #f3b229;
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.18);
        }

        .tesa-navbar .dropdown-menu {
            border-radius: 14px;
            border: 1px solid #f3b229;
            box-shadow: 0 14px 32px rgba(38, 16, 58, 0.22);
            padding: 0.5rem;
            margin-top: 0.5rem;
        }

        .tesa-navbar .dropdown-item {
            border-radius: 10px;
            padding: 0.55rem 0.75rem;
            font-weight: 500;
        }

        .tesa-navbar .dropdown-item i {
            width: 20px;
            text-align: center;
            color: #5a2d8c;
            margin-right: 0.35rem;
        }

        .tesa-navbar .dropdown-item:hover {
            background: #f3e9ff;
            color: #5a2d8c;
        }

        .tesa-navbar .dropdown-header {
            color: #5a2d8c;
            font-weight: 700;
            font-size: 0.78rem;
            letter-spacing: 0.6px;
        }

        .nav-tools {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-left: 0.4rem;
        }

        .search-global-container {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.14);
            border-radius: 999px;
            border: 1px solid rgba(243, 178, 41, 0.45);
            transition: all 0.28s ease;
            width: 36px;
            height: 36px;
            overflow: hidden;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
            margin-right: 0.4rem;
        }

        .search-global-container.active {
            width: 240px;
            background: rgba(255, 255, 255, 0.2);
            border-color: #f3b229;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .search-global-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            margin-left: 1px;
            border-radius: 50%;
            border: 0;
            background: #f3b229;
            color: #4a226f;
            flex-shrink: 0;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .search-global-toggle:hover {
            background: #e1a525;
            transform: scale(1.05);
        }

        .search-global-form {
            display: flex;
            align-items: center;
            width: 100%;
            min-width: 0;
            margin-left: 2px;
        }

        .search-global-input {
            border: 0;
            outline: 0;
            width: 0;
            opacity: 0;
            color: #fff;
            background: transparent;
            transition: width 0.25s ease, opacity 0.2s ease;
            font-size: 0.85rem;
        }

        .search-global-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-global-container.active .search-global-input {
            width: 100%;
            opacity: 1;
            padding: 0 0.4rem;
        }

        .search-global-submit,
        .search-global-close {
            border: 0;
            background: transparent;
            color: rgba(255, 255, 255, 0.9);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-right: 2px;
        }

        .search-global-container.active .search-global-submit,
        .search-global-container.active .search-global-close {
            display: inline-flex;
        }

        .search-global-submit:hover,
        .search-global-close:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
        }

        .btn-logout {
            background: #dc3545;
            color: #fff !important;
            border-radius: 999px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            padding: 0 0.8rem;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 6px 15px rgba(220, 53, 69, 0.25);
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: #bb2d3b;
            color: #fff !important;
            transform: translateY(-1px);
        }

        .btn-logout i {
            margin-right: 0.35rem;
        }

        /* Botón de notificaciones en header */
        .notification-bell-header {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(243, 178, 41, 0.2) 0%, rgba(243, 178, 41, 0.3) 100%);
            border: 2px solid #f3b229;
            color: #f3b229;
            font-size: 1.1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .notification-bell-header:hover {
            transform: scale(1.1);
            background: linear-gradient(135deg, rgba(243, 178, 41, 0.3) 0%, rgba(243, 178, 41, 0.4) 100%);
            box-shadow: 0 4px 15px rgba(243, 178, 41, 0.4);
        }

        .notification-badge-header {
            position: absolute;
            top: -3px;
            right: -3px;
            background: #e74c3c;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #5a2d8c;
            animation: badgePulseHeader 1.5s infinite;
        }

        @keyframes badgePulseHeader {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        .notification-badge-header:empty {
            display: none;
        }

        .lector-alert {
            background: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
            border-radius: 10px;
            padding: 12px 20px;
            margin: 18px auto 0;
            font-size: 14px;
            color: #155724;
        }

        @media (max-width: 1400px) {
            .tesa-brand-title {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 1199.98px) {
            .tesa-brand {
                min-width: 0;
                padding: 0.3rem 0.6rem;
            }
            
            .tesa-brand-title {
                font-size: 1.3rem;
            }

            .tesa-navbar .navbar-nav {
                padding-top: 0.8rem;
                align-items: stretch;
                margin-left: 0 !important;
            }

            .tesa-nav-btn {
                width: 100%;
                justify-content: flex-start;
            }

            .nav-tools {
                margin-left: 0;
                margin-top: 0.5rem;
                justify-content: space-between;
                gap: 0.8rem;
            }

            .search-global-container {
                width: 44px;
                max-width: 100%;
            }

            .search-global-container.active {
                width: 100%;
            }
        }

        @media (max-width: 767.98px) {
            .tesa-brand {
                min-width: 0;
                width: 100%;
                justify-content: center;
            }

            .tesa-brand img {
                height: 32px;
            }

            .tesa-brand-title {
                font-size: 1.25rem;
            }

            .rol-badge {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-xl navbar-dark tesa-navbar">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand me-3" href="/inventario_ti/modules/dashboard.php">
                <span class="tesa-brand">
                    <img src="/inventario_ti/assets/img/logo-tesa.png" alt="TESA" onerror="this.onerror=null; this.style.display='none';">
                    <span class="tesa-brand-title">TESA Inventario</span>
                    <?php if (isset($_SESSION['user_rol'])): ?>
                        <span class="rol-badge <?php echo $es_admin ? 'admin' : 'lector'; ?>">
                            <i class="fas <?php echo $es_admin ? 'fa-crown' : 'fa-eye'; ?> me-1"></i>
                            <?php echo $es_admin ? 'ADMIN' : 'INVITADO'; ?>
                        </span>
                    <?php endif; ?>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTesaMenu" aria-controls="navbarTesaMenu" aria-expanded="false" aria-label="Mostrar menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarTesaMenu">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="search-global-container ms-lg-2" id="globalSearchContainer">
                        <button type="button" class="search-global-toggle" id="globalSearchToggle" aria-label="Abrir buscador">
                            <i class="fas fa-search"></i>
                        </button>
                        <form action="/inventario_ti/buscar.php" method="GET" class="search-global-form">
                            <input type="text" name="q" class="search-global-input" placeholder="Buscar..." autocomplete="off">
                            <button type="submit" class="search-global-submit" aria-label="Buscar"><i class="fas fa-arrow-right"></i></button>
                            <button type="button" class="search-global-close" id="globalSearchClose" aria-label="Cerrar"><i class="fas fa-times"></i></button>
                        </form>
                    </div>
                <?php endif; ?>

                <ul class="navbar-nav me-auto ms-lg-2">
                    <li class="nav-item">
                        <a class="nav-link tesa-nav-btn" href="/inventario_ti/modules/dashboard.php">
                            <i class="fas fa-home"></i>Dashboard
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle tesa-nav-btn" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-users"></i>Personas
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/inventario_ti/modules/personas/listar.php"><i class="fas fa-list"></i>Listar Personas</a></li>
                            <?php if ($es_admin): ?>
                                <li><a class="dropdown-item" href="/inventario_ti/modules/personas/agregar.php"><i class="fas fa-user-plus"></i>Agregar Persona</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle tesa-nav-btn" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-laptop"></i>Equipos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/inventario_ti/modules/equipos/listar.php"><i class="fas fa-list"></i>Listar Equipos</a></li>
                            <?php if ($es_admin): ?>
                                <li><a class="dropdown-item" href="/inventario_ti/modules/equipos/agregar.php"><i class="fas fa-plus-circle"></i>Agregar Equipo</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/mantenimientos/listar.php"><i class="fas fa-tools"></i>Mantenimientos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">COMPONENTES</h6></li>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/componentes/listar.php"><i class="fas fa-microchip"></i>Listar Componentes</a></li>
                            <?php if ($es_admin): ?>
                                <li><a class="dropdown-item" href="/inventario_ti/modules/componentes/agregar.php"><i class="fas fa-plus"></i>Agregar Componente</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle tesa-nav-btn" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-exchange-alt"></i>Movimientos
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($es_admin): ?>
                                <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/traspaso.php"><i class="fas fa-exchange-alt"></i> Traspaso de Custodio</a></li>
                                <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/prestamo.php"><i class="fas fa-hand-holding"></i>Registrar Prestamo</a></li>
                                <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/devolucion.php"><i class="fas fa-undo-alt"></i>Registrar Devolucion</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/historial.php"><i class="fas fa-history"></i>Historial</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/prestamos_rapidos/listar.php"><i class="fas fa-hand-holding-heart"></i>Prestamos Rapidos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/reportes/index.php"><i class="fas fa-file-alt"></i>Generar Reportes</a></li>
                        </ul>
                    </li>

                    <?php if ($es_admin): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle tesa-nav-btn" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-envelope"></i>Correos
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/inventario_ti/modules/correos/listar.php"><i class="fas fa-inbox"></i>Gestión de Correos</a></li>
                                <li><a class="dropdown-item" href="/inventario_ti/modules/correos/composer.php"><i class="fas fa-edit"></i>Componer Correo</a></li>
                                <li><a class="dropdown-item" href="/inventario_ti/modules/correos/historial.php"><i class="fas fa-history"></i>Historial</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if ($es_admin): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle tesa-nav-btn" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-cog"></i>Admin
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/inventario_ti/modules/admin/backup.php"><i class="fas fa-database"></i>Respaldos</a></li>
                                <li><a class="dropdown-item" href="/inventario_ti/modules/admin/usuarios.php"><i class="fas fa-users-cog"></i>Usuarios</a></li>
                                <li><a class="dropdown-item" href="/inventario_ti/modules/admin/configuracion.php"><i class="fas fa-file-pdf"></i>Configuracion de Actas</a></li>
                                <li><a class="dropdown-item" href="/inventario_ti/modules/admin/logs.php"><i class="fas fa-history"></i>Logs</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Botón de Notificaciones -->
                    <li class="nav-item me-2">
                        <!-- Panel de notificaciones (inicialmente oculto) -->
<div class="notification-panel" id="notificationPanel">
    <div class="notification-panel-header">
        <h6><i class="fas fa-bell me-2"></i>Notificaciones</h6>
        <button class="btn-close" onclick="closeNotificationPanel()" aria-label="Cerrar"></button>
    </div>
    <div class="notification-list" id="notificationList">
        <div class="text-center text-muted p-3">Cargando...</div>
    </div>
    <div class="notification-panel-footer">
        <a href="/inventario_ti/modules/notificaciones/historial.php">Ver todas</a>
    </div>
</div>
                        <button class="btn notification-bell-header" id="notificationBellHeader" onclick="toggleNotificationPanel()" title="Ver notificaciones">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge-header" id="notificationBadgeHeader">0</span>
                        </button>
                    </li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a href="/inventario_ti/logout.php" class="btn-logout" onclick="return confirm('¿Estás seguro de cerrar sesión?')">
                                <i class="fas fa-sign-out-alt"></i>Salir
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <script>
    // ============================================
    // SISTEMA DE NOTIFICACIONES
    // ============================================
    let notificationPanel = null;
    let notificationList = null;
    let notificationBadge = null;
    
    function toggleNotificationPanel() {
        if (!notificationPanel) {
            notificationPanel = document.getElementById('notificationPanel');
            notificationList = document.getElementById('notificationList');
            notificationBadge = document.getElementById('notificationBadgeHeader');
        }
        
        if (notificationPanel) {
            notificationPanel.classList.toggle('show');
            if (notificationPanel.classList.contains('show')) {
                loadNotifications();
            }
        }
    }
    
    function closeNotificationPanel() {
        if (!notificationPanel) {
            notificationPanel = document.getElementById('notificationPanel');
        }
        if (notificationPanel) {
            notificationPanel.classList.remove('show');
        }
    }
    
    function loadNotifications() {
        if (!notificationList) {
            notificationList = document.getElementById('notificationList');
        }
        
        fetch('/inventario_ti/api/obtener_notificaciones.php')
            .then(response => response.json())
            .then(data => {
                if (!notificationList) return;
                
                if (data.length === 0) {
                    notificationList.innerHTML = '<div class="text-center text-muted p-3"><i class="fas fa-check-circle fa-2x mb-2"></i><p>¡Todo está al día!</p></div>';
                    updateNotificationBadge(0);
                } else {
                    let html = '';
                    data.forEach(notif => {
                        const icon = notif.icono || 'fa-bell';
                        const borderClass = notif.tipo === 'danger' ? 'danger' : (notif.tipo === 'warning' ? 'warning' : (notif.tipo === 'success' ? 'success' : 'info'));
                        html += `
                            <div class="notification-item ${borderClass}">
                                <div class="notification-title">
                                    <i class="fas ${icon}"></i> ${notif.titulo}
                                </div>
                                <div class="notification-message">${notif.mensaje}</div>
                                ${notif.url ? `<div class="notification-link"><a href="${notif.url}">Ver más</a></div>` : ''}
                            </div>
                        `;
                    });
                    notificationList.innerHTML = html;
                    updateNotificationBadge(data.length);
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                if (notificationList) {
                    notificationList.innerHTML = '<div class="text-center text-danger p-3"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>Error al cargar notificaciones</p></div>';
                }
            });
    }
    
    function updateNotificationBadge(count) {
        if (!notificationBadge) {
            notificationBadge = document.getElementById('notificationBadgeHeader');
        }
        if (notificationBadge) {
            if (count > 0) {
                notificationBadge.textContent = count > 99 ? '99+' : count;
            } else {
                notificationBadge.textContent = '';
            }
        }
    }
    
    // Cargar notificaciones al iniciar
    document.addEventListener('DOMContentLoaded', function() {
        // Cargar badge inicial
        loadNotifications();
        // Actualizar cada 2 minutos
        setInterval(loadNotifications, 120000);
    });
        
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar todos los dropdowns de Bootstrap
            var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
            
            const searchContainer = document.getElementById('globalSearchContainer');
            const searchToggle = document.getElementById('globalSearchToggle');
            const searchClose = document.getElementById('globalSearchClose');
            const searchInput = searchContainer ? searchContainer.querySelector('.search-global-input') : null;
            const searchForm = searchContainer ? searchContainer.querySelector('.search-global-form') : null;

            if (searchToggle && searchContainer) {
                searchToggle.addEventListener('click', function(e) {
                    if (!searchContainer.classList.contains('active')) {
                        e.preventDefault();
                        searchContainer.classList.add('active');
                        setTimeout(() => {
                            if (searchInput) searchInput.focus();
                        }, 300);
                    } else if (searchInput && searchInput.value.trim() !== '') {
                        // Si ya está activo y tiene texto, enviar el formulario
                        if (searchForm) searchForm.submit();
                    } else {
                        // Si está activo pero vacío, cerrar
                        searchContainer.classList.remove('active');
                    }
                });
            }

            if (searchClose && searchContainer) {
                searchClose.addEventListener('click', function() {
                    searchContainer.classList.remove('active');
                    if (searchInput) searchInput.value = '';
                });
            }

            // Permitir búsqueda al presionar Enter
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (searchInput.value.trim() !== '') {
                            if (searchForm) searchForm.submit();
                        }
                    }
                });
            }

            // Cerrar al presionar Esc
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && searchContainer && searchContainer.classList.contains('active')) {
                    searchContainer.classList.remove('active');
                }
            });
        });
    </script>

    <?php if ($es_lector): ?>
        <div class="lector-alert container">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Modo solo lectura:</strong> Puedes ver informacion pero no puedes agregar, editar o eliminar registros.
        </div>
    <?php endif; ?>
    

    <main class="container mt-4">
