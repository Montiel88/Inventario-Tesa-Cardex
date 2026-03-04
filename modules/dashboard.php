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
    margin: 30px 0 40px 0;
    padding: 20px;
    background: rgba(255,255,255,0.5);
    border-radius: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}
.institution-title h1 {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #5a2d8c 0%, #f3b229 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 0 5px 15px rgba(90, 45, 140, 0.2);
    margin-bottom: 10px;
    animation: fadeInTitle 1s ease;
}
.institution-title h2 {
    font-size: 1.8rem;
    color: #5a2d8c;
    font-weight: 600;
    letter-spacing: 3px;
    margin-bottom: 10px;
}
.institution-title .subtitle {
    font-size: 1.2rem;
    color: #666;
    font-style: italic;
    border-top: 2px solid #f3b229;
    padding-top: 15px;
    display: inline-block;
}
@keyframes fadeInTitle {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Estilos para las tarjetas del dashboard */
.dashboard-card {
    transition: all 0.3s;
    border: none;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}
.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(90, 45, 140, 0.15);
}
.dashboard-card .card-body {
    padding: 25px 20px;
    text-align: center;
    background: white;
}
.dashboard-card .card-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #5a2d8c;
    margin-bottom: 5px;
}
.dashboard-card .card-text {
    color: #666;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 15px;
}
.dashboard-card .btn-sm {
    border-radius: 20px;
    padding: 5px 15px;
    font-size: 0.8rem;
}

/* Aviso para lectores */
.lector-notice {
    background: #28a745;
    color: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
    .institution-title h1 { font-size: 1.8rem !important; }
    .institution-title h2 { font-size: 1.4rem !important; }
    .dashboard-card .card-title { font-size: 2rem !important; }
    .dashboard-card .card-body { padding: 15px !important; }
}
@media (max-width: 480px) {
    .institution-title h1 { font-size: 1.4rem !important; }
    .institution-title h2 { font-size: 1.1rem !important; }
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
        
        <!-- TARJETAS DE ESTADÍSTICAS (PRIMERA FILA) -->
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_personas; ?></h5>
                        <p class="card-text">Personas</p>
                        <a href="/inventario_ti/modules/personas/listar.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-users me-1"></i> Ver lista
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_equipos; ?></h5>
                        <p class="card-text">Equipos</p>
                        <a href="/inventario_ti/modules/equipos/listar.php" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-laptop me-1"></i> Ver equipos
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_prestamos; ?></h5>
                        <p class="card-text">Préstamos Activos</p>
                        <a href="/inventario_ti/modules/movimientos/historial.php?filtro=activos" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-hand-holding me-1"></i> Ver préstamos
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_disponibles; ?></h5>
                        <p class="card-text">Equipos Disponibles</p>
                        <a href="/inventario_ti/modules/equipos/listar.php?estado=disponible" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-check-circle me-1"></i> Ver disponibles
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEGUNDA FILA (COMPONENTES) -->
        <div class="row mt-4">
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_componentes; ?></h5>
                        <p class="card-text">Componentes</p>
                        <a href="/inventario_ti/modules/componentes/listar.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-microchip me-1"></i> Ver componentes
                        </a>
                    </div>
                </div>
            </div>
            <!-- Aquí puedes agregar más tarjetas si lo deseas -->
        </div>
        
        <!-- ACCIONES RÁPIDAS (SOLO ADMIN) -->
        <?php if ($es_admin): ?>
        <div class="row mt-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="/inventario_ti/modules/movimientos/prestamo.php" class="btn btn-primary w-100">
                                    <i class="fas fa-hand-holding me-2"></i>Registrar Préstamo
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="/inventario_ti/modules/movimientos/devolucion.php" class="btn btn-success w-100">
                                    <i class="fas fa-undo-alt me-2"></i>Registrar Devolución
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="/inventario_ti/modules/equipos/agregar.php" class="btn btn-warning w-100">
                                    <i class="fas fa-plus-circle me-2"></i>Agregar Equipo
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
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
        <div class="row mt-2">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Las acciones rápidas solo están disponibles para administradores.
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ÚLTIMOS MOVIMIENTOS (CLIQUEABLES EN TODA LA BARRA) -->
        <div class="row mt-4">
            <!-- Equipos -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Últimos Movimientos de Equipos</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result_movimientos && $result_movimientos->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while($row = $result_movimientos->fetch_assoc()): ?>
                                    <!-- Contenedor con posición relativa para el stretched-link -->
                                    <div class="list-group-item" style="position: relative;">
                                        <a href="/inventario_ti/modules/equipos/detalle.php?id=<?php echo $row['equipo_id']; ?>" class="stretched-link"></a>
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo htmlspecialchars($row['equipo'] ?? 'N/A'); ?></strong>
                                                <small class="text-muted ms-2"><?php echo htmlspecialchars($row['codigo_barras'] ?? ''); ?></small>
                                            </div>
                                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($row['fecha_movimiento'])); ?></small>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?php echo $row['tipo_movimiento'] == 'ASIGNACION' ? 'warning' : 'success'; ?>">
                                                <?php echo $row['tipo_movimiento']; ?>
                                            </span>
                                            <small class="ms-2"><?php echo htmlspecialchars($row['persona'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">No hay movimientos registrados</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Componentes -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-microchip me-2"></i>Últimos Movimientos de Componentes</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result_movimientos_componentes && $result_movimientos_componentes->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while($row = $result_movimientos_componentes->fetch_assoc()): ?>
                                    <div class="list-group-item" style="position: relative;">
                                        <a href="/inventario_ti/modules/componentes/detalle.php?id=<?php echo $row['componente_id']; ?>" class="stretched-link"></a>
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo htmlspecialchars($row['tipo'] . ': ' . $row['nombre_componente']); ?></strong>
                                            </div>
                                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($row['fecha_movimiento'])); ?></small>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?php echo $row['tipo_movimiento'] == 'ASIGNACION' ? 'warning' : 'success'; ?>">
                                                <?php echo $row['tipo_movimiento']; ?>
                                            </span>
                                            <small class="ms-2"><?php echo htmlspecialchars($row['persona_nombre'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">No hay movimientos registrados</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div> <!-- Fin content -->
</div> <!-- Fin brand-watermark -->

<?php include '../includes/footer.php'; ?>