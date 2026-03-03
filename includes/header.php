<?php
ob_start(); // <-- AÑADIDO PARA EVITAR ERRORES DE HEADERS
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivo de permisos
require_once __DIR__ . '/../config/permisos.php';

// Verificar que el usuario tenga sesión activa (si no es login)
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page != 'login.php') {
    verificarSesion();
}

// Variables útiles para el menú
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
    
    <!-- FAVICON -->
    <link rel="apple-touch-icon" sizes="180x180" href="/inventario_ti/assets/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/inventario_ti/assets/img/favicon-96x96.png">
    <link rel="icon" type="image/svg+xml" href="/inventario_ti/assets/img/favicon.svg">
    <link rel="manifest" href="/inventario_ti/assets/img/site.webmanifest">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="/inventario_ti/assets/css/estilo.css">
    
    <!-- Estilos adicionales -->
    <style>
        /* ============================================ */
        /* ESTILOS PARA EL BADGE DE ROL */
        /* ============================================ */
        .rol-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .rol-badge.admin {
            background: #f3b229;
            color: #3d1e5e;
            box-shadow: 0 2px 10px rgba(243, 178, 41, 0.3);
        }

        .rol-badge.lector {
            background: #28a745;
            color: white;
            box-shadow: 0 2px 10px rgba(40, 167, 69, 0.3);
        }

        .rol-badge i {
            margin-right: 5px;
            font-size: 11px;
        }

        /* AVISO PARA LECTORES */
        .lector-alert {
            background: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
            border-radius: 10px;
            padding: 12px 20px;
            margin: 20px 0;
            font-size: 14px;
            color: #155724;
            display: <?php echo $es_lector ? 'block' : 'none'; ?>;
        }

        .lector-alert i {
            color: #28a745;
            margin-right: 8px;
        }

        /* BUSCADOR GLOBAL (LUPA EXPANDIBLE) */
        .search-global-container {
            display: inline-flex;
            align-items: center;
            background: rgba(255,255,255,0.15);
            border-radius: 40px;
            padding: 5px;
            margin-left: 10px;
            border: 1px solid rgba(243, 178, 41, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 40px;
            overflow: hidden;
            cursor: pointer;
            backdrop-filter: blur(5px);
            vertical-align: middle;
        }

        .search-global-container:hover,
        .search-global-container.active {
            width: 250px;
            background: rgba(255,255,255,0.25);
            border-color: #f3b229;
        }

        .search-global-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background: #f3b229;
            border-radius: 50%;
            color: #5a2d8c;
            flex-shrink: 0;
            transition: background 0.3s;
            cursor: pointer;
        }

        .search-global-container:hover .search-global-icon,
        .search-global-container.active .search-global-icon {
            background: #d49b1f;
        }

        .search-global-input {
            border: none;
            outline: none;
            padding: 0 10px;
            font-size: 14px;
            width: 0;
            opacity: 0;
            transition: width 0.3s ease, opacity 0.2s ease;
            background: transparent;
            color: white;
        }

        .search-global-input::placeholder {
            color: rgba(255,255,255,0.6);
        }

        .search-global-container:hover .search-global-input,
        .search-global-container.active .search-global-input {
            width: calc(100% - 40px);
            opacity: 1;
            padding: 0 10px;
        }

        .search-global-form {
            display: flex;
            align-items: center;
            width: 100%;
        }

        @media (max-width: 768px) {
            .search-global-container {
                width: 100%;
                margin-left: 0;
                margin-top: 10px;
            }
            .search-global-container:hover,
            .search-global-container.active {
                width: 100%;
            }
            .search-global-input {
                width: calc(100% - 40px);
                opacity: 1;
                padding: 0 10px;
            }
        }

        /* BOTÓN DE SALIR */
        .btn-logout {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 30px;
            padding: 6px 18px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
            margin-left: 10px;
        }

        .btn-logout:hover {
            background: #bb2d3b;
            transform: translateY(-2px);
            color: white;
        }

        .btn-logout i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/inventario_ti/modules/dashboard.php">
                <img src="/inventario_ti/assets/img/logo-tesa.png" 
                     alt="TESA" 
                     onerror="this.onerror=null; this.style.display='none';">
                <span>TESA Inventario</span>
                
                <!-- BADGE DE ROL - MUESTRA ADMIN O INVITADO -->
                <?php if (isset($_SESSION['user_rol'])): ?>
                <span class="rol-badge <?php echo $es_admin ? 'admin' : 'lector'; ?>">
                    <i class="fas <?php echo $es_admin ? 'fa-crown' : 'fa-eye'; ?>"></i>
                    <?php echo $es_admin ? 'ADMIN' : 'INVITADO'; ?>
                </span>
                <?php endif; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- DASHBOARD -->
                    <li class="nav-item">
                        <a class="nav-link" href="/inventario_ti/modules/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    
                    <!-- PERSONAS -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users"></i> Personas
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/inventario_ti/modules/personas/listar.php">
                                <i class="fas fa-list"></i> Listar Personas
                            </a></li>
                            <?php if ($es_admin): ?>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/personas/agregar.php">
                                <i class="fas fa-user-plus"></i> Agregar Persona
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <!-- EQUIPOS -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-laptop"></i> Equipos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/inventario_ti/modules/equipos/listar.php">
                                <i class="fas fa-list"></i> Listar Equipos
                            </a></li>
                            <?php if ($es_admin): ?>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/equipos/agregar.php">
                                <i class="fas fa-plus-circle"></i> Agregar Equipo
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <!-- MOVIMIENTOS + REPORTES + UBICACIONES -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-exchange-alt"></i> Movimientos
                        </a>
                        <ul class="dropdown-menu">
                            <!-- SUBMENÚ DE MOVIMIENTOS -->
                            <li><h6 class="dropdown-header"><i class="fas fa-arrows-alt me-2"></i>MOVIMIENTOS</h6></li>
                            <?php if ($es_admin): ?>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/prestamo.php">
                                <i class="fas fa-hand-holding"></i> Registrar Préstamo
                            </a></li>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/devolucion.php">
                                <i class="fas fa-undo-alt"></i> Registrar Devolución
                            </a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/historial.php">
                                <i class="fas fa-history"></i> Historial
                            </a></li>
                            
                            <li><hr class="dropdown-divider"></li>
                            
                            <!-- SUBMENÚ DE REPORTES -->
                            <li><h6 class="dropdown-header"><i class="fas fa-chart-bar me-2"></i>REPORTES</h6></li>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/reportes/index.php">
                                <i class="fas fa-file-alt"></i> Generar Reportes
                            </a></li>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/reportes/equipos_por_persona.php">
                                <i class="fas fa-users-cog"></i> Equipos por Persona
                            </a></li>
                            
                            <li><hr class="dropdown-divider"></li>
                            
                            <!-- SUBMENÚ DE UBICACIONES -->
                            <li><h6 class="dropdown-header"><i class="fas fa-building me-2"></i>UBICACIONES</h6></li>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/ubicaciones/listar.php">
                                <i class="fas fa-list"></i> Listar Ubicaciones
                            </a></li>
                            <?php if ($es_admin): ?>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/ubicaciones/agregar.php">
                                <i class="fas fa-plus-circle"></i> Agregar Ubicación
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <!-- ============================================ -->
                    <!-- MÓDULO ADMIN - SOLO PARA ADMIN -->
                    <!-- ============================================ -->
                    <?php if ($es_admin): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/inventario_ti/modules/admin/backup.php">
                                <i class="fas fa-database"></i> Respaldos
                            </a></li>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/admin/usuarios.php">
                                <i class="fas fa-users-cog"></i> Usuarios
                            </a></li>
                            
                            <!-- NUEVO: CONFIGURACIÓN DE ACTAS -->
                            <li><a class="dropdown-item" href="/inventario_ti/modules/admin/configuracion.php">
                                <i class="fas fa-file-pdf"></i> Configuración de Actas
                            </a></li>
                            
                            <li><a class="dropdown-item" href="/inventario_ti/modules/admin/logs.php">
                                <i class="fas fa-history"></i> Logs
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- LUPA DE BÚSQUEDA GLOBAL -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item d-flex align-items-center">
                        <div class="search-global-container" id="globalSearchContainer">
                            <div class="search-global-icon" id="globalSearchIcon">
                                <i class="fas fa-search"></i>
                            </div>
                            <form action="/inventario_ti/buscar.php" method="GET" class="search-global-form">
                                <input type="text" 
                                       name="q" 
                                       class="search-global-input" 
                                       placeholder="Buscar en el sistema..."
                                       autocomplete="off">
                            </form>
                        </div>
                    </li>
                    <?php endif; ?>
                    
                    <!-- BOTÓN DE SALIR -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a href="/inventario_ti/logout.php" 
                           class="btn-logout" 
                           onclick="return confirm('¿Estás seguro de cerrar sesión?')">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- AVISO PARA LECTORES -->
    <?php if ($es_lector): ?>
    <div class="lector-alert container">
        <i class="fas fa-info-circle"></i>
        <strong>Modo solo lectura:</strong> Puedes ver la información pero no puedes agregar, editar o eliminar registros.
    </div>
    <?php endif; ?>
    
    <!-- EL MAIN SE CIERRA EN EL FOOTER -->
    <main class="container mt-4">

    <!-- Script para el comportamiento de la lupa -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchContainer = document.getElementById('globalSearchContainer');
        const searchIcon = document.getElementById('globalSearchIcon');
        const searchInput = document.querySelector('.search-global-input');

        if (searchContainer && searchIcon && searchInput) {
            searchIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                searchContainer.classList.add('active');
                searchInput.focus();
            });

            searchInput.addEventListener('blur', function() {
                if (window.innerWidth > 768 && searchInput.value === '') {
                    searchContainer.classList.remove('active');
                }
            });

            searchContainer.addEventListener('mouseenter', function() {
                if (window.innerWidth > 768) {
                    searchContainer.classList.add('active');
                }
            });

            searchContainer.addEventListener('mouseleave', function() {
                if (window.innerWidth > 768 && searchInput.value === '') {
                    searchContainer.classList.remove('active');
                }
            });
        }
    });
    </script>