<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// SOLO ADMIN PUEDE VER LOGS
if ($_SESSION['user_rol'] != 1) {
    header('Location: /inventario_ti/modules/dashboard.php?error=No tienes permisos para ver logs');
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/logs_functions.php';
include '../../includes/header.php';

// ============================================
// OBTENER PARÁMETROS DE FILTRO
// ============================================
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_accion = $_GET['accion'] ?? '';
$filtro_desde = $_GET['desde'] ?? date('Y-m-01');
$filtro_hasta = $_GET['hasta'] ?? date('Y-m-d');
$pagina = intval($_GET['pagina'] ?? 1);
$por_pagina = 50;
$inicio = ($pagina - 1) * $por_pagina;

// ============================================
// CONSTRUIR CONSULTA
// ============================================
$sql_base = "FROM logs l 
             LEFT JOIN usuarios u ON l.usuario_id = u.id 
             WHERE l.fecha BETWEEN ? AND ?";

$params = [$filtro_desde, $filtro_hasta];
$types = 'ss';

if (!empty($filtro_usuario)) {
    $sql_base .= " AND (u.nombre_usuario LIKE ? OR l.usuario_id = ?)";
    $params[] = "%{$filtro_usuario}%";
    $params[] = $filtro_usuario;
    $types .= 'si';
}

if (!empty($filtro_accion)) {
    $sql_base .= " AND l.accion LIKE ?";
    $params[] = "%{$filtro_accion}%";
    $types .= 's';
}

// Contar total
$sql_count = "SELECT COUNT(*) as total $sql_base";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $por_pagina);

// Obtener registros
$sql = "SELECT l.*, u.nombre_usuario, u.email 
        $sql_base 
        ORDER BY l.fecha DESC 
        LIMIT $inicio, $por_pagina";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ============================================
// OBTENER ESTADÍSTICAS
// ============================================
$sql_stats = "SELECT 
              COUNT(*) as total,
              COUNT(DISTINCT usuario_id) as usuarios_unicos,
              DATE(MIN(fecha)) as primer_log,
              DATE(MAX(fecha)) as ultimo_log
              FROM logs
              WHERE fecha BETWEEN ? AND ?";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param('ss', $filtro_desde, $filtro_hasta);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// ============================================
// OBTENER LISTA DE USUARIOS PARA FILTRO
// ============================================
$usuarios = $conn->query("SELECT id, nombre_usuario FROM usuarios ORDER BY nombre_usuario");

// ============================================
// ACCIONES MÁS COMUNES
// ============================================
$acciones_comunes = $conn->query("
    SELECT accion, COUNT(*) as cantidad 
    FROM logs 
    WHERE fecha BETWEEN '$filtro_desde' AND '$filtro_hasta'
    GROUP BY accion 
    ORDER BY cantidad DESC 
    LIMIT 10
");
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border-left: 5px solid #6366f1;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1" style="color: #f8fafc; font-weight: 800;">
                                <i class="fas fa-clipboard-list me-3"></i>Registro de Logs del Sistema
                            </h2>
                            <p class="mb-0" style="color: #94a3b8;">Auditoría y seguimiento de actividades</p>
                        </div>
                        <div>
                            <button class="btn btn-outline-light btn-sm" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Imprimir
                            </button>
                            <a href="logs_exportar.php" class="btn btn-primary btn-sm" style="background: #6366f1; border: none;">
                                <i class="fas fa-download me-2"></i>Exportar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #6366f1;">
                <div class="card-body text-center p-4">
                    <i class="fas fa-list fa-2x mb-3" style="color: #6366f1;"></i>
                    <h3 class="mb-0" style="color: #1e293b; font-weight: 800;"><?php echo number_format($stats['total'] ?? 0); ?></h3>
                    <p class="text-muted mb-0 small text-uppercase fw-bold">Total Logs</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #10b981;">
                <div class="card-body text-center p-4">
                    <i class="fas fa-users fa-2x mb-3" style="color: #10b981;"></i>
                    <h3 class="mb-0" style="color: #1e293b; font-weight: 800;"><?php echo number_format($stats['usuarios_unicos'] ?? 0); ?></h3>
                    <p class="text-muted mb-0 small text-uppercase fw-bold">Usuarios Activos</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #f59e0b;">
                <div class="card-body text-center p-4">
                    <i class="fas fa-calendar-alt fa-2x mb-3" style="color: #f59e0b;"></i>
                    <h3 class="mb-0" style="color: #1e293b; font-weight: 800;"><?php echo $stats['primer_log'] ? date('d/m/Y', strtotime($stats['primer_log'])) : 'N/A'; ?></h3>
                    <p class="text-muted mb-0 small text-uppercase fw-bold">Primer Registro</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #3b82f6;">
                <div class="card-body text-center p-4">
                    <i class="fas fa-clock fa-2x mb-3" style="color: #3b82f6;"></i>
                    <h3 class="mb-0" style="color: #1e293b; font-weight: 800;"><?php echo $stats['ultimo_log'] ? date('d/m/Y', strtotime($stats['ultimo_log'])) : 'N/A'; ?></h3>
                    <p class="text-muted mb-0 small text-uppercase fw-bold">Último Registro</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header" style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
            <h5 class="mb-0" style="color: #1e293b; font-weight: 700;">
                <i class="fas fa-filter me-2"></i>Filtros de Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="logs.php" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Usuario</label>
                    <input type="text" class="form-control" name="usuario" 
                           value="<?php echo htmlspecialchars($filtro_usuario); ?>" 
                           placeholder="Nombre o ID">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Acción</label>
                    <input type="text" class="form-control" name="accion" 
                           value="<?php echo htmlspecialchars($filtro_accion); ?>" 
                           placeholder="Tipo de acción">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Desde</label>
                    <input type="date" class="form-control" name="desde" 
                           value="<?php echo htmlspecialchars($filtro_desde); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Hasta</label>
                    <input type="date" class="form-control" name="hasta" 
                           value="<?php echo htmlspecialchars($filtro_hasta); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100" style="background: #6366f1; border: none;">
                        <i class="fas fa-search me-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Tabla de Logs -->
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <h5 class="mb-0" style="color: #1e293b; font-weight: 700;">
                        <i class="fas fa-table me-2"></i>Registros Found: <?php echo number_format($total_registros); ?>
                    </h5>
                    <span class="badge" style="background: #6366f1; color: white;">
                        Página <?php echo $pagina; ?> de <?php echo max(1, $total_paginas); ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: #f1f5f9;">
                                <tr>
                                    <th class="ps-4" style="width: 60px;">#</th>
                                    <th style="width: 150px;">Fecha</th>
                                    <th style="width: 120px;">Usuario</th>
                                    <th style="width: 200px;">Acción</th>
                                    <th>Detalle</th>
                                    <th style="width: 130px;">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($log = $result->fetch_assoc()): ?>
                                    <tr class="<?php echo esAccionCritica($log['accion']) ? 'table-warning' : ''; ?>">
                                        <td class="ps-4">
                                            <span class="badge rounded-pill" style="background: #e2e8f0; color: #64748b;">
                                                <?php echo $log['id']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold" style="color: #1e293b;">
                                                    <?php echo date('d/m/Y', strtotime($log['fecha'])); ?>
                                                </span>
                                                <small class="text-muted">
                                                    <?php echo date('H:i:s', strtotime($log['fecha'])); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($log['nombre_usuario']): ?>
                                                <span class="fw-medium" style="color: #6366f1;">
                                                    <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($log['nombre_usuario']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Sistema</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: <?php echo getColorParaAccion($log['accion']); ?>; color: white;">
                                                <?php echo htmlspecialchars($log['accion']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($log['detalle'] ?? '-'); ?></small>
                                        </td>
                                        <td>
                                            <code style="color: #ef4444; font-size: 0.85rem;">
                                                <i class="fas fa-globe me-1"></i><?php echo htmlspecialchars($log['ip']); ?>
                                            </code>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No se encontraron registros de logs</p>
                                            <small class="text-muted">Intenta ajustar los filtros</small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                <div class="card-footer" style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                    <nav aria-label="Paginación">
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>&usuario=<?php echo urlencode($filtro_usuario); ?>&accion=<?php echo urlencode($filtro_accion); ?>&desde=<?php echo urlencode($filtro_desde); ?>&hasta=<?php echo urlencode($filtro_hasta); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <?php if ($i == 1 || $i == $total_paginas || abs($i - $pagina) <= 2): ?>
                                <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?>&usuario=<?php echo urlencode($filtro_usuario); ?>&accion=<?php echo urlencode($filtro_accion); ?>&desde=<?php echo urlencode($filtro_desde); ?>&hasta=<?php echo urlencode($filtro_hasta); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php elseif (abs($i - $pagina) == 3): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>&usuario=<?php echo urlencode($filtro_usuario); ?>&accion=<?php echo urlencode($filtro_accion); ?>&desde=<?php echo urlencode($filtro_desde); ?>&hasta=<?php echo urlencode($filtro_hasta); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Panel Lateral -->
        <div class="col-lg-3">
            <!-- Acciones Más Comunes -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header" style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <h6 class="mb-0 fw-bold" style="color: #1e293b;">
                        <i class="fas fa-chart-bar me-2"></i>Top Acciones
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php while($accion = $acciones_comunes->fetch_assoc()): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                            <small class="text-truncate" style="max-width: 180px;">
                                <?php echo htmlspecialchars($accion['accion']); ?>
                            </small>
                            <span class="badge rounded-pill" style="background: #6366f1; color: white;">
                                <?php echo number_format($accion['cantidad']); ?>
                            </span>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Info -->
            <div class="card shadow-sm border-0">
                <div class="card-header" style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <h6 class="mb-0 fw-bold" style="color: #1e293b;">
                        <i class="fas fa-info-circle me-2"></i>Información
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        <i class="fas fa-check-circle text-success me-1"></i>
                        Los logs se guardan automáticamente
                    </p>
                    <p class="small text-muted mb-2">
                        <i class="fas fa-shield-alt text-primary me-1"></i>
                        Solo administradores pueden ver
                    </p>
                    <p class="small text-muted mb-0">
                        <i class="fas fa-clock text-warning me-1"></i>
                        Se conservan indefinidamente
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// ============================================
// FUNCIONES AUXILIARES
// ============================================
function esAccionCritica($accion) {
    $acciones = ['Eliminar', 'Borrar', 'Login fallido', 'Acceso denegado'];
    foreach ($acciones as $critica) {
        if (stripos($accion, $critica) !== false) return true;
    }
    return false;
}

function getColorParaAccion($accion) {
    $accion_lower = strtolower($accion);
    if (stripos($accion, 'crear') !== false || stripos($accion, 'agregar') !== false || stripos($accion, 'insertar') !== false) return '#10b981';
    if (stripos($accion, 'actualizar') !== false || stripos($accion, 'editar') !== false || stripos($accion, 'modificar') !== false) return '#3b82f6';
    if (stripos($accion, 'eliminar') !== false || stripos($accion, 'borrar') !== false || stripos($accion, 'suprimir') !== false) return '#ef4444';
    if (stripos($accion, 'login') !== false || stripos($accion, 'sesión') !== false) return '#6366f1';
    if (stripos($accion, 'exportar') !== false || stripos($accion, 'imprimir') !== false) return '#f59e0b';
    return '#64748b';
}
?>

<?php include '../../includes/footer.php'; ?>
