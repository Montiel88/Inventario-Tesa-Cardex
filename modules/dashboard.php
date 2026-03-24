<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar rol (1 = admin, 2 = lector)
$es_admin = ($_SESSION['user_rol'] == 1);

require_once '../config/database.php';
include '../includes/header.php';

// Verificar conexión
if (!$conn) {
    die("Error de conexión a la base de datos");
}

// ============================================
// ESTADÍSTICAS GENERALES
// ============================================
$total_personas = 0;
$total_equipos = 0;
$total_prestamos = 0;
$total_disponibles = 0;
$total_componentes = 0;

// Total personas
$result = $conn->query("SELECT COUNT(*) as total FROM personas");
if ($result) $total_personas = $result->fetch_assoc()['total'];

// Total equipos
$result = $conn->query("SELECT COUNT(*) as total FROM equipos");
if ($result) $total_equipos = $result->fetch_assoc()['total'];

// Préstamos activos
$result = $conn->query("SELECT COUNT(*) as total FROM asignaciones WHERE fecha_devolucion IS NULL");
if ($result) $total_prestamos = $result->fetch_assoc()['total'];

// Equipos disponibles
$result = $conn->query("SELECT COUNT(*) as total FROM equipos WHERE estado = 'Disponible' OR estado IS NULL");
if ($result) $total_disponibles = $result->fetch_assoc()['total'];

// Total componentes
$result = $conn->query("SELECT COUNT(*) as total FROM componentes");
if ($result) $total_componentes = $result->fetch_assoc()['total'];

// Últimos movimientos de equipos (incluyendo ID del equipo)
$sql_movimientos = "SELECT m.*, e.tipo_equipo as equipo, e.codigo_barras, p.nombres as persona, e.id as equipo_id
                   FROM movimientos m 
                   LEFT JOIN equipos e ON m.equipo_id = e.id 
                   LEFT JOIN personas p ON m.persona_id = p.id 
                   ORDER BY m.fecha_movimiento DESC LIMIT 5";
$result_movimientos = $conn->query($sql_movimientos);

// Últimos movimientos de componentes (incluyendo ID del componente)
$sql_movimientos_componentes = "SELECT mc.*, c.nombre_componente, c.tipo, p.nombres as persona_nombre, c.id as componente_id
                                FROM movimientos_componentes mc
                                LEFT JOIN componentes c ON mc.componente_id = c.id
                                LEFT JOIN personas p ON mc.persona_id = p.id
                                ORDER BY mc.fecha_movimiento DESC LIMIT 5";
$result_movimientos_componentes = $conn->query($sql_movimientos_componentes);

?>

<!-- ============================================ -->
<!-- ESTILOS PARA LAS TARJETAS -->
<!-- ============================================ -->
<style>
/* Estilos para la marca de agua institucional */
.brand-watermark {
    position: relative;
    overflow: hidden;
    min-height: 200px;
}
.brand-watermark::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: url('/inventario_ti/assets/img/logo-tesa.png') no-repeat center;
    background-size: contain;
    opacity: 0.03;
    transform: rotate(-5deg);
    pointer-events: none;
    z-index: 0;
}
.brand-watermark .content {
    position: relative;
    z-index: 1;
}
.institution-title {
    text-align: center;
    margin: 20px 0 30px 0;
    padding: 25px;
    background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.6) 100%);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(90, 45, 140, 0.1);
    border: 1px solid rgba(243, 178, 41, 0.2);
}
.institution-title h1 {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #5a2d8c 0%, #7b42a8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 8px;
    letter-spacing: 1px;
}
.institution-title h2 {
    font-size: 1.8rem;
    color: #f3b229;
    font-weight: 700;
    letter-spacing: 4px;
    margin-bottom: 8px;
}
.institution-title .subtitle {
    font-size: 1.1rem;
    color: #666;
    font-style: italic;
    border-top: 2px solid #f3b229;
    padding-top: 12px;
    display: inline-block;
    margin-top: 8px;
}

/* Estilos para las tarjetas del dashboard */
.dashboard-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    background: white;
    position: relative;
}
.dashboard-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #5a2d8c 0%, #f3b229 100%);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}
.dashboard-card:hover::before {
    transform: scaleX(1);
}
.dashboard-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(90, 45, 140, 0.15);
}
.dashboard-card .card-body {
    padding: 28px 20px;
    text-align: center;
    background: white;
}
.dashboard-card .card-title {
    font-size: 2.8rem;
    font-weight: 800;
    background: linear-gradient(135deg, #5a2d8c 0%, #7b42a8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 8px;
    line-height: 1;
}
.dashboard-card .card-text {
    color: #666;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-weight: 600;
    margin-bottom: 18px;
}
.dashboard-card .btn-sm {
    border-radius: 25px;
    padding: 8px 18px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border-width: 2px;
}
.dashboard-card .btn-sm:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(90, 45, 140, 0.2);
}

/* Aviso para lectores */
.lector-notice {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 18px 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    font-weight: 500;
}

/* Acciones rápidas */
.quick-actions-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}
.quick-actions-card .card-header {
    background: linear-gradient(135deg, #5a2d8c 0%, #7b42a8 100%);
    color: white;
    padding: 18px 25px;
    border: none;
    font-weight: 700;
    font-size: 1.1rem;
    letter-spacing: 0.5px;
}
.quick-actions-card .btn {
    border-radius: 12px;
    padding: 12px 20px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.quick-actions-card .btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}
.quick-actions-card .btn-primary {
    background: linear-gradient(135deg, #5a2d8c 0%, #7b42a8 100%);
}
.quick-actions-card .btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}
.quick-actions-card .btn-warning {
    background: linear-gradient(135deg, #f3b229 0%, #f5c342 100%);
    color: #5a2d8c;
}
.quick-actions-card .btn-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

/* Últimos movimientos */
.movements-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    height: 100%;
}
.movements-card .card-header {
    background: linear-gradient(135deg, rgba(90, 45, 140, 0.05) 0%, rgba(243, 178, 41, 0.05) 100%);
    border-bottom: 2px solid #f3b229;
    padding: 18px 25px;
    font-weight: 700;
    font-size: 1.05rem;
    color: #5a2d8c;
}
.movements-card .list-group-item {
    border: none;
    border-bottom: 1px solid #f1f1f1;
    padding: 15px 20px;
    transition: all 0.2s ease;
    background: transparent;
}
.movements-card .list-group-item:hover {
    background: linear-gradient(90deg, rgba(90, 45, 140, 0.05) 0%, rgba(243, 178, 41, 0.05) 100%);
    padding-left: 25px;
}
.movements-card .list-group-item:last-child {
    border-bottom: none;
}

/* Responsive */
@media (max-width: 768px) {
    .institution-title h1 { font-size: 1.8rem !important; }
    .institution-title h2 { font-size: 1.4rem !important; }
    .dashboard-card .card-title { font-size: 2.2rem !important; }
    .dashboard-card .card-body { padding: 20px 15px !important; }
    .lector-notice { padding: 15px 20px; font-size: 0.9rem; }
}
@media (max-width: 480px) {
    .institution-title h1 { font-size: 1.4rem !important; }
    .institution-title h2 { font-size: 1.1rem !important; }
    .institution-title { padding: 15px; margin: 10px 0 20px 0; }
}
</style>

<!-- ============================================ -->
<!-- CONTENIDO PRINCIPAL -->
<!-- ============================================ -->
<div class="brand-watermark">
    <div class="content">
        
        <!-- TÍTULO INSTITUCIONAL -->
        <div class="institution-title">
            <h1>INSTITUTO TECNOLÓGICO SAN ANTONIO</h1>
            <h2>TESA</h2>
            <p class="subtitle">Sistema de Gestión de Inventario y Préstamos</p>
        </div>
        
        <!-- AVISO PARA LECTORES -->
        <?php if (!$es_admin): ?>
        <div class="lector-notice">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Modo solo lectura:</strong> Puedes ver la información pero no puedes realizar acciones.
        </div>
        <?php endif; ?>
        
        <!-- TARJETAS DE ESTADÍSTICAS -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_personas; ?></h5>
                        <p class="card-text"><i class="fas fa-users me-1"></i> Personas</p>
                        <a href="/inventario_ti/modules/personas/listar.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-list me-1"></i> Ver lista
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_equipos; ?></h5>
                        <p class="card-text"><i class="fas fa-laptop me-1"></i> Equipos</p>
                        <a href="/inventario_ti/modules/equipos/listar.php" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-list me-1"></i> Ver equipos
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_prestamos; ?></h5>
                        <p class="card-text"><i class="fas fa-hand-holding me-1"></i> Préstamos Activos</p>
                        <a href="/inventario_ti/modules/movimientos/historial.php?filtro=activos" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-history me-1"></i> Ver préstamos
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_disponibles; ?></h5>
                        <p class="card-text"><i class="fas fa-check-circle me-1"></i> Equipos Disponibles</p>
                        <a href="/inventario_ti/modules/equipos/listar.php?estado=disponible" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-list me-1"></i> Ver disponibles
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEGUNDA FILA (COMPONENTES) -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_componentes; ?></h5>
                        <p class="card-text"><i class="fas fa-microchip me-1"></i> Componentes</p>
                        <a href="/inventario_ti/modules/componentes/listar.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-list me-1"></i> Ver componentes
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ACCIONES RÁPIDAS (SOLO ADMIN) -->
        <?php if ($es_admin): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card quick-actions-card">
                    <div class="card-header">
                        <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="/inventario_ti/modules/movimientos/prestamo.php" class="btn btn-primary w-100">
                                    <i class="fas fa-hand-holding me-2"></i>Registrar Préstamo
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="/inventario_ti/modules/movimientos/devolucion.php" class="btn btn-success w-100">
                                    <i class="fas fa-undo-alt me-2"></i>Registrar Devolución
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="/inventario_ti/modules/equipos/agregar.php" class="btn btn-warning w-100">
                                    <i class="fas fa-plus-circle me-2"></i>Agregar Equipo
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="/inventario_ti/modules/personas/agregar.php" class="btn btn-info w-100">
                                    <i class="fas fa-user-plus me-2"></i>Agregar Persona
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info border-0 shadow-sm">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Modo solo lectura:</strong> Las acciones rápidas solo están disponibles para administradores.
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ÚLTIMOS MOVIMIENTOS -->
        <div class="row g-4">
            <!-- Equipos -->
            <div class="col-lg-6">
                <div class="card movements-card h-100">
                    <div class="card-header">
                        <i class="fas fa-history me-2"></i>Últimos Movimientos de Equipos
                    </div>
                    <div class="card-body p-0">
                        <?php if ($result_movimientos && $result_movimientos->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while($row = $result_movimientos->fetch_assoc()): ?>
                                    <div class="list-group-item" style="position: relative;">
                                        <a href="/inventario_ti/modules/equipos/detalle.php?id=<?php echo $row['equipo_id']; ?>" class="stretched-link"></a>
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['equipo'] ?? 'N/A'); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($row['codigo_barras'] ?? ''); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted d-block"><?php echo date('d/m/Y', strtotime($row['fecha_movimiento'])); ?></small>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($row['fecha_movimiento'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <span class="badge bg-<?php echo $row['tipo_movimiento'] == 'ASIGNACION' ? 'warning' : 'success'; ?> me-2">
                                                <?php echo $row['tipo_movimiento']; ?>
                                            </span>
                                            <small class="text-muted"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($row['persona'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4 mb-0">No hay movimientos registrados</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Componentes -->
            <div class="col-lg-6">
                <div class="card movements-card h-100">
                    <div class="card-header">
                        <i class="fas fa-microchip me-2"></i>Últimos Movimientos de Componentes
                    </div>
                    <div class="card-body p-0">
                        <?php if ($result_movimientos_componentes && $result_movimientos_componentes->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while($row = $result_movimientos_componentes->fetch_assoc()): ?>
                                    <div class="list-group-item" style="position: relative;">
                                        <a href="/inventario_ti/modules/componentes/detalle.php?id=<?php echo $row['componente_id']; ?>" class="stretched-link"></a>
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['tipo'] . ': ' . $row['nombre_componente']); ?></div>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted d-block"><?php echo date('d/m/Y', strtotime($row['fecha_movimiento'])); ?></small>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($row['fecha_movimiento'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <span class="badge bg-<?php echo $row['tipo_movimiento'] == 'ASIGNACION' ? 'warning' : 'success'; ?> me-2">
                                                <?php echo $row['tipo_movimiento']; ?>
                                            </span>
                                            <small class="text-muted"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($row['persona_nombre'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4 mb-0">No hay movimientos registrados</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div> <!-- Fin content -->
</div> <!-- Fin brand-watermark -->

<?php include '../includes/footer.php'; ?>