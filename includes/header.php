<?php
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

// Definir URL base
$base_url = '';
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
    <link rel="icon" type="image/png" sizes="192x192" href="/inventario_ti/assets/img/web-app-manifest-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/inventario_ti/assets/img/web-app-manifest-512x512.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --tesa-purple: #5a2d8c;
            --tesa-purple-dark: #3d1e5e;
            --tesa-purple-light: #f3e9ff;
            --tesa-gold: #f3b229;
            --tesa-lector: #28a745;
            --tesa-danger: #dc3545;
            --tesa-light-gray: #f8f9fc;
            --tesa-dark: #2d2d2d;
        }

        body {
            background: linear-gradient(135deg, var(--tesa-light-gray) 0%, #ffffff 100%);
            font-family: 'Poppins', sans-serif;
            color: var(--tesa-dark);
            min-height: 100vh;
        }

        /* BADGE DE ROL */
        .rol-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            text-transform: uppercase;
        }
        
        .rol-badge.admin {
            background: var(--tesa-gold);
            color: var(--tesa-purple-dark);
        }
        
        .rol-badge.lector {
            background: var(--tesa-lector);
            color: white;
        }

        /* AVISO DE SOLO LECTURA */
        .lector-alert {
            background: rgba(40, 167, 69, 0.1);
            border-left: 4px solid var(--tesa-lector);
            border-radius: 10px;
            padding: 12px 20px;
            margin: 20px 0;
            display: <?php echo (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 2) ? 'block' : 'none'; ?>;
        }

        .navbar {
            background: rgba(90, 45, 140, 0.95) !important;
            border-bottom: 3px solid var(--tesa-gold);
            padding: 15px 0;
        }

        .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }

        .nav-link {
            color: rgba(255,255,255,0.95) !important;
            font-weight: 500;
            padding: 8px 18px !important;
            border-radius: 30px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background: rgba(243, 178, 41, 0.2);
            color: white !important;
            transform: translateY(-2px);
        }

        .dropdown-menu {
            background: rgba(255,255,255,0.98);
            border: 1px solid rgba(90, 45, 140, 0.1);
            border-radius: 15px;
            padding: 10px;
        }

        .dropdown-item {
            border-radius: 10px;
            padding: 10px 20px;
            transition: all 0.3s;
        }

        .dropdown-item:hover {
            background: #f3e9ff;
            color: #5a2d8c;
        }

        /* SECCIÓN DE USUARIO */
        .user-section {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.1);
            border-radius: 40px;
            padding: 5px 5px 5px 20px;
            margin-left: 15px;
            border: 1px solid rgba(243, 178, 41, 0.3);
        }

        .user-name {
            color: white;
            font-weight: 500;
            margin-right: 10px;
        }

        .user-name i {
            color: var(--tesa-gold);
        }

        .btn-logout {
            background: var(--tesa-danger);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 6px 18px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: #bb2d3b;
            transform: translateY(-2px);
            color: white;
        }

        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
            .navbar-brand img {
                height: 30px;
            }
            .user-section {
                margin-left: 0;
                margin-top: 10px;
                width: 100%;
                justify-content: space-between;
            }
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
                
                <!-- BADGE DE ROL -->
                <?php if (isset($_SESSION['user_rol'])): ?>
                <span class="rol-badge <?php echo $_SESSION['user_rol'] == 1 ? 'admin' : 'lector'; ?>">
                    <i class="fas <?php echo $_SESSION['user_rol'] == 1 ? 'fa-crown' : 'fa-eye'; ?>"></i>
                    <?php echo $_SESSION['user_rol'] == 1 ? 'ADMIN' : 'LECTOR'; ?>
                </span>
                <?php endif; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/inventario_ti/modules/dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i> Personas
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/inventario_ti/modules/personas/listar.php">
                                <i class="fas fa-list me-2"></i>Listar Personas
                            </a></li>
                            <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1): ?>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/personas/agregar.php">
                                <i class="fas fa-user-plus me-2"></i>Agregar Persona
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-laptop me-1"></i> Equipos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/inventario_ti/modules/equipos/listar.php">
                                <i class="fas fa-list me-2"></i>Listar Equipos
                            </a></li>
                            <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1): ?>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/equipos/agregar.php">
                                <i class="fas fa-plus-circle me-2"></i>Agregar Equipo
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-exchange-alt me-1"></i> Movimientos
                        </a>
                        <ul class="dropdown-menu">
                            <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1): ?>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/prestamo.php">
                                <i class="fas fa-hand-holding me-2"></i>Registrar Préstamo
                            </a></li>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/devolucion.php">
                                <i class="fas fa-undo-alt me-2"></i>Registrar Devolución
                            </a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="/inventario_ti/modules/movimientos/historial.php">
                                <i class="fas fa-history me-2"></i>Historial
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- SECCIÓN DE USUARIO CON BOTÓN DE SALIR -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <div class="user-section">
                            <span class="user-name">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?>
                            </span>
                            <a href="/inventario_ti/logout.php" 
                               class="btn-logout" 
                               onclick="return confirm('¿Estás seguro de cerrar sesión?')">
                                <i class="fas fa-sign-out-alt"></i> Salir
                            </a>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- AVISO PARA LECTORES -->
    <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 2): ?>
    <div class="lector-alert container">
        <i class="fas fa-info-circle"></i>
        <strong>Modo solo lectura:</strong> Puedes ver la información pero no puedes agregar, editar o eliminar registros.
    </div>
    <?php endif; ?>
    
    <main class="container mt-4">