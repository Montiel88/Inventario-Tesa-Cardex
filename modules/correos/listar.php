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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: var(--c-deep);
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(124, 58, 237, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(243, 178, 41, 0.05) 0%, transparent 50%);
            font-family: var(--font);
            color: var(--c-w90);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--c-deep); }
        ::-webkit-scrollbar-thumb { background: var(--c-mid); border-radius: 10px; border: 2px solid var(--c-deep); }
        ::-webkit-scrollbar-thumb:hover { background: var(--c-violet); }

        /* Header Premium LED */
        .hero-header {
            background: linear-gradient(135deg, var(--c-deep) 0%, var(--c-bg) 50%, var(--c-mid) 100%);
            border-bottom: 2px solid rgba(243,178,41,0.3);
            position: relative;
            padding: 3rem 0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        
        .hero-header::after {
            content: '';
            position: absolute; bottom: -2px; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--c-gold), var(--c-violet), transparent);
            background-size: 200% 100%;
            animation: aurora 4s linear infinite;
        }

        @keyframes aurora {
            from { background-position: 0 0; }
            to   { background-position: 200% 0; }
        }

        .hero-title {
            font-weight: 800;
            font-size: 2.8rem;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #fff, var(--c-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 15px rgba(243,178,41,0.3));
        }

        .breadcrumb-custom {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 40px;
            padding: 0.5rem 1.5rem;
        }

        .btn-glow {
            background: linear-gradient(135deg, var(--c-violet), #4c1d95);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            border-radius: 16px;
            padding: 0.8rem 2rem;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.85rem;
        }
        .btn-glow:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 35px rgba(124, 58, 237, 0.6);
            color: #fff;
            border-color: var(--c-gold);
        }

        /* Stats LED Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        .stat-card {
            background: rgba(30, 8, 64, 0.6);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.08);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        .stat-card:hover {
            transform: translateY(-10px);
            background: rgba(30, 8, 64, 0.8);
            border-color: var(--c-violet);
            box-shadow: 0 20px 50px rgba(124, 58, 237, 0.25);
        }
        .stat-card::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, transparent, rgba(124, 58, 237, 0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }
        .stat-card:hover::before { transform: translateX(100%); }

        .stat-icon {
            width: 64px; height: 64px;
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        .stat-card.danger .stat-icon { background: rgba(244,63,94,0.15); color: var(--c-danger); border: 1px solid rgba(244,63,94,0.3); box-shadow: 0 0 20px rgba(244,63,94,0.2); }
        .stat-card.warning .stat-icon { background: rgba(245,158,11,0.15); color: var(--c-warning); border: 1px solid rgba(245,158,11,0.3); box-shadow: 0 0 20px rgba(245,158,11,0.2); }
        .stat-card.success .stat-icon { background: rgba(16,185,129,0.15); color: var(--c-success); border: 1px solid rgba(16,185,129,0.3); box-shadow: 0 0 20px rgba(16,185,129,0.2); }

        .stat-card:hover .stat-icon { transform: scale(1.1) rotate(-5deg); filter: brightness(1.2); }

        .stat-number { font-size: 3rem; font-weight: 800; color: #fff; line-height: 1; margin-bottom: 0.5rem; }
        .stat-label { font-size: 0.85rem; font-weight: 700; color: var(--c-w60); text-transform: uppercase; letter-spacing: 1.5px; }

        /* Sections LED Style */
        .section-card {
            background: rgba(30, 8, 64, 0.4);
            backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 2rem;
            margin-bottom: 3rem;
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
        }
        .section-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 2rem; padding-bottom: 1rem;
            border-bottom: 1px solid rgba(124,58,237,0.2);
        }
        .section-title { font-weight: 800; font-size: 1.6rem; color: #fff; letter-spacing: -0.5px; }
        
        /* Table LED Style */
        .table-modern { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .table-modern thead th { color: var(--c-w60); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; padding: 1rem; border: none; }
        .table-modern tbody tr { background: rgba(255,255,255,0.03); border-radius: 16px; transition: all 0.3s; }
        .table-modern tbody tr:hover { background: rgba(124,58,237,0.1); transform: scale(1.01) translateX(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .table-modern tbody td { padding: 1.2rem; border: none; vertical-align: middle; color: var(--c-w90); }
        .table-modern tbody td:first-child { border-radius: 16px 0 0 16px; border-left: 2px solid transparent; transition: border 0.3s; }
        .table-modern tbody tr:hover td:first-child { border-left-color: var(--c-gold); }
        .table-modern tbody td:last-child { border-radius: 0 16px 16px 0; }

        .btn-send {
            background: linear-gradient(135deg, var(--c-gold), #d97706);
            color: #1a0533 !important;
            font-weight: 800;
            border-radius: 12px;
            padding: 0.5rem 1.5rem;
            border: none;
            box-shadow: 0 5px 15px rgba(243,178,41,0.3);
            transition: all 0.3s;
        }
        .btn-send:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(243,178,41,0.5); filter: brightness(1.1); }

        .btn-outline-primary {
            background: transparent;
            border: 1px solid var(--c-violet);
            color: var(--c-violet);
            font-weight: 700;
            border-radius: 12px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s;
        }
        .btn-outline-primary:hover {
            background: var(--c-violet);
            color: #fff !important;
            box-shadow: 0 0 20px rgba(124, 58, 237, 0.4);
        }

        .badge-modern {
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.75rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .bg-danger { background: linear-gradient(135deg, #f43f5e, #be123c) !important; box-shadow: 0 0 15px rgba(244, 63, 94, 0.3); }
        .bg-warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; box-shadow: 0 0 15px rgba(245, 158, 11, 0.3); }
        .bg-success { background: linear-gradient(135deg, #10b981, #059669) !important; box-shadow: 0 0 15px rgba(16, 185, 129, 0.3); }
        .bg-secondary { background: rgba(255,255,255,0.1) !important; border: 1px solid rgba(255,255,255,0.1); color: var(--c-w60) !important; }

        .empty-state { text-align: center; padding: 5rem; color: var(--c-w60); }
        .empty-state i { font-size: 4rem; color: var(--c-violet); opacity: 0.5; margin-bottom: 1.5rem; }

        @media (max-width: 768px) {
            .hero-title { font-size: 2rem; }
            .table-modern thead { display: none; }
            .table-modern tbody tr { display: block; margin-bottom: 1.5rem; padding: 1rem; }
            .table-modern tbody td { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
            .table-modern tbody td:last-child { border: none; padding-top: 1rem; }
            .table-modern tbody td::before { content: attr(data-label); font-weight: 800; font-size: 0.7rem; color: var(--c-gold); text-transform: uppercase; }
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