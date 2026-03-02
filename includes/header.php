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
    
    <!-- CSS Personalizado (AHORA SÍ SE VA A VER) -->
    <link rel="stylesheet" href="/inventario_ti/assets/css/estilo.css">
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
                <span class="rol-badge <?php echo $es_admin ? 'admin' : 'lector'; ?>">
                    <i class="fas <?php echo $es_admin ? 'fa-crown' : 'fa-eye'; ?>"></i>
                    <?php echo $es_admin ? 'ADMIN' : 'LECTOR'; ?>
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
                    
                    <!-- SECCIÓN DE USUARIO CON BOTÓN DE SALIR -->
                    <!-- BOTÓN DE SALIR (SIN NOMBRE) -->
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