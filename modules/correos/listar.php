<?php
session_start();
require_once '../../config/database.php';

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
$rol_id = $_SESSION['rol_id'] ?? $_SESSION['user_rol'] ?? null;
if (!$usuario_id || $rol_id != 1) {
    header('Location: /inventario_ti/login.php');
    exit;
}

$sql_vencidos = "SELECT a.id, a.fecha_asignacion,
                        e.codigo_barras, e.tipo_equipo, e.marca, e.modelo, e.id as equipo_id,
                        p.id as persona_id, p.nombres, p.correo as email, p.cedula,
                        DATEDIFF(NOW(), a.fecha_asignacion) as dias_transcurridos
                 FROM asignaciones a
                 JOIN equipos e ON a.equipo_id = e.id
                 JOIN personas p ON a.persona_id = p.id
                 WHERE a.fecha_devolucion IS NULL 
                 AND DATEDIFF(NOW(), a.fecha_asignacion) >= 30
                 AND p.correo IS NOT NULL AND p.correo != ''
                 ORDER BY dias_transcurridos DESC";
$result_vencidos = $conn->query($sql_vencidos);

$sql_por_vencer = "SELECT a.id, a.fecha_asignacion,
                          e.codigo_barras, e.tipo_equipo, e.marca, e.modelo, e.id as equipo_id,
                          p.id as persona_id, p.nombres, p.correo as email, p.cedula,
                          DATEDIFF(NOW(), a.fecha_asignacion) as dias_transcurridos
                   FROM asignaciones a
                   JOIN equipos e ON a.equipo_id = e.id
                   JOIN personas p ON a.persona_id = p.id
                   WHERE a.fecha_devolucion IS NULL 
                   AND DATEDIFF(NOW(), a.fecha_asignacion) >= 25
                   AND p.correo IS NOT NULL AND p.correo != ''
                   ORDER BY dias_transcurridos ASC";
$result_por_vencer = $conn->query($sql_por_vencer);

$sql_sin_ubicacion = "SELECT e.id as equipo_id, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo,
                             e.estado
                      FROM equipos e
                      WHERE (e.ubicacion_id IS NULL OR e.ubicacion_id = 0)
                      ORDER BY e.id DESC
                      LIMIT 50";
$result_sin_ubicacion = $conn->query($sql_sin_ubicacion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Correos | TESA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: radial-gradient(circle at 10% 30%, #f8f9fc 0%, #eef2f5 100%);
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            min-height: 100vh;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #5a2d8c; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #3d1e5e; }

        /* Header con efecto glass y animación */
        .hero-header {
            background: linear-gradient(135deg, rgba(90,45,140,0.95) 0%, rgba(123,75,168,0.95) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 3px solid rgba(243,178,41,0.5);
            position: relative;
            overflow: hidden;
        }
        .hero-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: rotate 20s infinite linear;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .hero-content {
            position: relative;
            z-index: 2;
            padding: 2.5rem 0;
        }
        .hero-title {
            font-weight: 800;
            font-size: 2.5rem;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #fff, #f3b229);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .breadcrumb-custom {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(4px);
            border-radius: 40px;
            padding: 0.5rem 1.2rem;
            display: inline-block;
        }
        .btn-glow {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.5);
            color: white;
            border-radius: 50px;
            padding: 0.6rem 1.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }
        .btn-glow:hover {
            background: white;
            color: #5a2d8c;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        /* Cards estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.8rem;
            margin-bottom: 3rem;
        }
        .stat-card {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 1.8rem;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.3);
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 20px 35px -10px rgba(90,45,140,0.2);
            border-color: #f3b229;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, rgba(243,178,41,0.1) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .stat-card:hover::after { opacity: 1; }
        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(90,45,140,0.1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #5a2d8c;
            margin-bottom: 1.2rem;
        }
        .stat-card.danger .stat-icon { background: rgba(220,53,69,0.1); color: #dc3545; }
        .stat-card.warning .stat-icon { background: rgba(243,178,41,0.1); color: #f3b229; }
        .stat-card.success .stat-icon { background: rgba(40,167,69,0.1); color: #28a745; }
        .stat-number {
            font-size: 2.8rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.3rem;
        }
        .stat-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #5b677b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Secciones de contenido */
        .section-card {
            background: white;
            border-radius: 32px;
            box-shadow: 0 20px 35px -15px rgba(0,0,0,0.05);
            padding: 1.8rem;
            margin-bottom: 2rem;
            transition: transform 0.2s;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #f0e9ff;
        }
        .section-title {
            font-weight: 700;
            font-size: 1.4rem;
            color: #1e293b;
        }
        .badge-modern {
            padding: 0.5rem 1rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        /* Tabla moderna */
        .table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .table-modern thead th {
            background: transparent;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #5b677b;
            padding: 0.8rem 1rem;
            border: none;
        }
        .table-modern tbody tr {
            background: white;
            border-radius: 20px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }
        .table-modern tbody tr:hover {
            background: #fefaf5;
            transform: translateX(5px);
            box-shadow: 0 8px 20px rgba(90,45,140,0.08);
        }
        .table-modern tbody td {
            padding: 1rem;
            border-top: none;
            vertical-align: middle;
        }
        .table-modern tbody td:first-child { border-radius: 20px 0 0 20px; }
        .table-modern tbody td:last-child { border-radius: 0 20px 20px 0; }
        .btn-action {
            border-radius: 40px;
            padding: 0.4rem 1.2rem;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
        }
        .btn-send {
            background: linear-gradient(135deg, #5a2d8c, #7b4ba8);
            color: white;
        }
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(90,45,140,0.3);
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #8b9eb0;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        @media (max-width: 768px) {
            .table-modern thead { display: none; }
            .table-modern tbody tr { display: block; margin-bottom: 1rem; border-radius: 20px; }
            .table-modern tbody td { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; }
            .table-modern tbody td::before { content: attr(data-label); font-weight: 600; width: 40%; }
        }
    </style>
</head>
<body>

<div class="hero-header">
    <div class="container hero-content">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="hero-title"><i class="fas fa-envelope-open-text me-2"></i>Gestión de Correos</h1>
                <p class="text-white-50 mt-2 mb-0">Envía notificaciones automáticas y mantén el control</p>
                <div class="mt-3">
                    <nav style="--bs-breadcrumb-divider: '›';" aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-custom">
                            <li class="breadcrumb-item"><a href="/inventario_ti/modules/dashboard.php" class="text-white text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active text-white-50" aria-current="page">Correos</li>
                        </ol>
                    </nav>
                </div>
            </div>
            <div>
                <a href="composer.php" class="btn btn-glow"><i class="fas fa-plus me-2"></i>Nuevo Correo</a>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4 pb-5">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card danger" onclick="document.getElementById('vencidos').scrollIntoView({behavior: 'smooth'})">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-number"><?= $result_vencidos->num_rows ?></div>
            <div class="stat-label">Préstamos vencidos</div>
        </div>
        <div class="stat-card warning" onclick="document.getElementById('por-vencer').scrollIntoView({behavior: 'smooth'})">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-number"><?= $result_por_vencer->num_rows ?></div>
            <div class="stat-label">Por vencer (5 días)</div>
        </div>
        <div class="stat-card success" onclick="document.getElementById('sin-ubicacion').scrollIntoView({behavior: 'smooth'})">
            <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div class="stat-number"><?= $result_sin_ubicacion->num_rows ?></div>
            <div class="stat-label">Equipos sin ubicación</div>
        </div>
    </div>

    <div class="section-card" id="vencidos">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Préstamos vencidos (>30 días)</h3>
            <span class="badge bg-danger badge-modern"><?= $result_vencidos->num_rows ?> registros</span>
        </div>
        <?php if ($result_vencidos->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr><th>Persona</th><th>Email</th><th>Equipo</th><th>Días vencido</th><th>Fecha asignación</th><th>Acciones</th> </thead>
                <tbody>
                    <?php while($row = $result_vencidos->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Persona"><strong><?= htmlspecialchars($row['nombres']) ?></strong><br><small class="text-muted"><?= $row['cedula'] ?></small></td>
                        <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
                        <td data-label="Equipo"><strong><?= $row['tipo_equipo'] ?></strong><br><small><?= $row['marca'] . ' ' . $row['modelo'] ?></small><br><small class="text-muted"><?= $row['codigo_barras'] ?></small></td>
                        <td data-label="Días"><span class="badge bg-danger"><?= $row['dias_transcurridos'] ?> días</span></td>
                        <td data-label="Fecha"><?= date('d/m/Y', strtotime($row['fecha_asignacion'])) ?></td>
                        <td data-label="Acciones">
                            <a href="composer.php?tipo=vencido&persona_id=<?= $row['persona_id'] ?>&asignacion_id=<?= $row['id'] ?>" class="btn btn-action btn-send"><i class="fas fa-envelope me-1"></i> Enviar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-check-circle"></i><h5>¡Ningún préstamo vencido!</h5><p>Todos los préstamos están al día.</p></div>
        <?php endif; ?>
    </div>

    <div class="section-card" id="por-vencer">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-clock me-2 text-warning"></i>Préstamos por vencer (≤5 días)</h3>
            <span class="badge bg-warning text-dark badge-modern"><?= $result_por_vencer->num_rows ?> registros</span>
        </div>
        <?php if ($result_por_vencer->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr><th>Persona</th><th>Email</th><th>Equipo</th><th>Días</th><th>Fecha asignación</th><th>Acciones</th> </thead>
                <tbody>
                    <?php while($row = $result_por_vencer->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Persona"><strong><?= htmlspecialchars($row['nombres']) ?></strong><br><small><?= $row['cedula'] ?></small></td>
                        <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
                        <td data-label="Equipo"><strong><?= $row['tipo_equipo'] ?></strong><br><small><?= $row['marca'] . ' ' . $row['modelo'] ?></small><br><small class="text-muted"><?= $row['codigo_barras'] ?></small></td>
                        <td data-label="Días"><span class="badge bg-warning text-dark"><?= $row['dias_transcurridos'] ?> días</span></td>
                        <td data-label="Fecha"><?= date('d/m/Y', strtotime($row['fecha_asignacion'])) ?></td>
                        <td data-label="Acciones">
                            <a href="composer.php?tipo=por_vencer&persona_id=<?= $row['persona_id'] ?>&asignacion_id=<?= $row['id'] ?>" class="btn btn-action btn-send"><i class="fas fa-envelope me-1"></i> Enviar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-check-circle"></i><h5>No hay préstamos por vencer</h5></div>
        <?php endif; ?>
    </div>

    <div class="section-card" id="sin-ubicacion">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-map-marker-alt me-2 text-success"></i>Equipos sin ubicación</h3>
            <span class="badge bg-success badge-modern"><?= $result_sin_ubicacion->num_rows ?> registros</span>
        </div>
        <?php if ($result_sin_ubicacion->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table-modern">
                <thead><tr><th>Código</th><th>Tipo</th><th>Marca/Modelo</th><th>Estado</th><th>Acciones</th> </thead>
                <tbody>
                    <?php while($row = $result_sin_ubicacion->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Código"><strong><?= $row['codigo_barras'] ?></strong></td>
                        <td data-label="Tipo"><?= $row['tipo_equipo'] ?></td>
                        <td data-label="Marca/Modelo"><?= $row['marca'] . ' ' . $row['modelo'] ?></td>
                        <td data-label="Estado"><span class="badge bg-secondary"><?= $row['estado'] ?></span></td>
                        <td data-label="Acciones">
                            <a href="/inventario_ti/modules/equipos/editar.php?id=<?= $row['equipo_id'] ?>" class="btn btn-outline-primary btn-action"><i class="fas fa-edit me-1"></i> Editar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-check-circle"></i><h5>Todos los equipos tienen ubicación</h5></div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>