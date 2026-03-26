<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar rol (1 = admin, 2 = lector)
$es_admin = ($_SESSION['user_rol'] == 1);

require_once '../../config/database.php';
include '../../includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos de la persona
$sql_persona = "SELECT * FROM personas WHERE id = $id";
$result_persona = $conn->query($sql_persona);
if ($result_persona->num_rows == 0) {
    header('Location: listar.php');
    exit();
}
$persona = $result_persona->fetch_assoc();

// Obtener equipos asignados actualmente (no devueltos) y almacenar en array
$sql_equipos = "SELECT e.*, a.fecha_asignacion, a.observaciones as obs_asignacion
                FROM equipos e
                JOIN asignaciones a ON e.id = a.equipo_id
                WHERE a.persona_id = $id AND a.fecha_devolucion IS NULL
                ORDER BY a.fecha_asignacion DESC";
$result_equipos = $conn->query($sql_equipos);
$equipos_asignados = [];
$equipos_ids = [];
if ($result_equipos) {
    while ($row = $result_equipos->fetch_assoc()) {
        $equipos_asignados[] = $row;
        $equipos_ids[] = $row['id'];
    }
}
$total_equipos = count($equipos_asignados);

// Obtener componentes de esos equipos
$componentes_asignados = [];
if (!empty($equipos_ids)) {
    $ids_str = implode(',', $equipos_ids);
    $sql_componentes = "SELECT c.*, e.tipo_equipo, e.codigo_barras as equipo_codigo
                        FROM componentes c
                        JOIN equipos e ON c.equipo_id = e.id
                        WHERE c.equipo_id IN ($ids_str)
                        ORDER BY e.tipo_equipo, c.tipo";
    $result_componentes = $conn->query($sql_componentes);
    if ($result_componentes) {
        while ($row = $result_componentes->fetch_assoc()) {
            $componentes_asignados[] = $row;
        }
    }
}

// contar total componentes (de equipos) y preparar para la tarjeta
$total_componentes_equipos = count($componentes_asignados);

// obtener componentes asignados directamente a la persona (movimientos_componentes)
$componentes_directos = [];
$sql_directos = "SELECT c.*, mc.fecha_movimiento
                  FROM componentes c
                  JOIN movimientos_componentes mc ON c.id = mc.componente_id
                  WHERE mc.persona_id = $id
                    AND mc.tipo_movimiento = 'ASIGNACION'
                    AND NOT EXISTS (
                        SELECT 1 FROM movimientos_componentes mc2
                        WHERE mc2.componente_id = mc.componente_id
                          AND mc2.tipo_movimiento = 'DEVOLUCION'
                          AND mc2.fecha_movimiento > mc.fecha_movimiento
                    )
                  ORDER BY mc.fecha_movimiento DESC";
$result_directos = $conn->query($sql_directos);
if ($result_directos) {
    while ($row = $result_directos->fetch_assoc()) {
        $componentes_directos[] = $row;
    }
}
$total_componentes_directos = count($componentes_directos);

// total general de componentes asignados (equipos + directos)
$total_componentes = $total_componentes_equipos + $total_componentes_directos;

// Obtener componentes libres disponibles para asignación a persona
$componentes_disponibles = [];
$sql_disponibles = "SELECT * FROM componentes c
                    WHERE NOT EXISTS (
                        SELECT 1 FROM movimientos_componentes mc
                        WHERE mc.componente_id = c.id
                          AND mc.tipo_movimiento='ASIGNACION'
                          AND NOT EXISTS (
                            SELECT 1 FROM movimientos_componentes mc2
                            WHERE mc2.componente_id = mc.componente_id
                              AND mc2.tipo_movimiento='DEVOLUCION'
                              AND mc2.fecha_movimiento > mc.fecha_movimiento
                          )
                    )";
$result_disp = $conn->query($sql_disponibles);
if ($result_disp) {
    while ($row = $result_disp->fetch_assoc()) {
        $componentes_disponibles[] = $row;
    }
}
$total_componentes_disponibles = count($componentes_disponibles);

// Obtener historial de movimientos de esta persona (últimos 20)
$sql_historial = "SELECT m.*, e.tipo_equipo, e.codigo_barras
                  FROM movimientos m
                  JOIN equipos e ON m.equipo_id = e.id
                  WHERE m.persona_id = $id
                  ORDER BY m.fecha_movimiento DESC
                  LIMIT 20";
$historial = $conn->query($sql_historial);

// Obtener incidencias relacionadas con esta persona (últimas 5)
$sql_incidencias = "SELECT i.*, e.tipo_equipo, e.codigo_barras
                    FROM incidencias i
                    JOIN equipos e ON i.equipo_id = e.id
                    WHERE i.persona_id = $id
                    ORDER BY i.fecha_reporte DESC
                    LIMIT 5";
$incidencias = $conn->query($sql_incidencias);

// ============================================
// URL BASE DINÁMICA PARA EL CÓDIGO QR
// ============================================
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url_publica = 'http://192.168.100.154/inventario_ti';
?>

<style>
/* ============================================ */
/* ESTILOS PREMIUM PARA DETALLE DE PERSONA */
/* ============================================ */

/* Sobrescribir estilos base para adaptarlos al tema */
.card {
    background: rgba(20, 5, 45, 0.7) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.card-header {
    background: rgba(139, 92, 246, 0.15) !important;
    border-bottom: 1px solid var(--c-gold) !important;
}

.card-header h4, .card-header h5 {
    color: var(--c-gold) !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Tablas de información */
.info-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.info-table tr {
    background: rgba(255, 255, 255, 0.03);
    transition: all 0.3s ease;
}

.info-table tr:hover {
    background: rgba(255, 255, 255, 0.06);
    transform: translateX(5px);
}

.info-table th {
    padding: 12px 15px;
    color: var(--c-gold);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-radius: 12px 0 0 12px;
    width: 35%;
}

.info-table td {
    padding: 12px 15px;
    color: #fff;
    font-weight: 500;
    border-radius: 0 12px 12px 0;
}

/* Tarjetas de estadísticas y acciones rápidas */
.stat-card {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(20, 5, 45, 0.8)) !important;
    border: 1px solid rgba(139, 92, 246, 0.3) !important;
    border-radius: 20px !important;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    margin-bottom: 15px;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(139, 92, 246, 0.2);
    border-color: var(--c-violet) !important;
}

.stat-card h3 {
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 5px;
    background: linear-gradient(135deg, #fff 0%, var(--c-gold) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 0 10px rgba(243, 178, 41, 0.3));
}

.stat-card p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 15px;
}

/* Items de listas (Equipos/Componentes) */
.item-box {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    padding: 15px;
    margin-bottom: 12px;
    transition: all 0.3s ease;
}

.item-box:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(243, 178, 41, 0.4);
    transform: scale(1.01);
}

.item-badge {
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
}

/* Botones de acción */
.btn-action {
    border-radius: 12px;
    padding: 8px 15px;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.btn-action:hover {
    transform: translateY(-2px);
    filter: brightness(1.2);
}

/* Estilos para el modal del QR */
.qr-modal {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.85);
    backdrop-filter: blur(10px);
    display: none; align-items: center; justify-content: center;
    z-index: 10000;
}
.qr-modal-content {
    background: rgba(20, 5, 45, 0.95);
    padding: 40px;
    border-radius: 30px;
    border: 1px solid var(--c-gold);
    max-width: 450px; width: 90%;
    text-align: center;
    box-shadow: 0 0 50px rgba(243, 178, 41, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .info-table th { width: 40%; }
}

/* NUEVOS ESTILOS REESTRUCTURACIÓN */
.profile-avatar-container {
    position: relative;
    width: 100px; height: 100px;
}
.profile-avatar {
    width: 100%; height: 100%;
    background: linear-gradient(135deg, var(--c-violet), #4c1d95);
    border-radius: 30px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.1);
}
.profile-status-online {
    position: absolute; bottom: 5px; right: 5px;
    width: 22px; height: 22px;
    background: var(--c-success);
    border: 4px solid #1a0533;
    border-radius: 50%;
    box-shadow: 0 0 15px var(--c-success);
}
.stat-minimal-val {
    font-size: 2.2rem; font-weight: 900; color: var(--c-gold);
    line-height: 1; margin-bottom: 5px;
    filter: drop-shadow(0 0 10px rgba(243, 178, 41, 0.3));
}
.stat-minimal-label {
    font-size: 0.7rem; color: rgba(255,255,255,0.4);
    text-transform: uppercase; letter-spacing: 2px;
}
.item-box-premium {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 22px;
    padding: 20px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.item-box-premium:hover {
    background: rgba(255, 255, 255, 0.06);
    border-color: var(--c-gold-glow);
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.3);
}
.item-icon-box {
    width: 60px; height: 60px;
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
}
.tracking-wider { letter-spacing: 1.5px; }
.fw-900 { font-weight: 900; }
.bg-white-bg-opacity-05 { background: rgba(255,255,255,0.05) !important; }
</style>

<!-- MODAL PARA MOSTRAR QR -->
<div id="qrModal" class="qr-modal">
    <div class="qr-modal-content">
        <h4 class="text-white mb-4"><i class="fas fa-qrcode me-2 text-warning"></i>Código QR de <?php echo $persona['nombres']; ?></h4>
        <div id="qrcode-container" class="bg-white p-3 rounded-4 mb-4" style="display: inline-block;"></div>
        <p class="text-white-50 small mb-4">Escanea este código para ver los equipos de la persona</p>
        <button class="btn btn-danger rounded-pill px-5" onclick="cerrarModalQR()">Cerrar</button>
    </div>
</div>

<div class="container-fluid py-4">
    <!-- Header Principal -->
    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h4 class="mb-0"><i class="fas fa-user-circle me-2"></i>Perfil de Usuario</h4>
            <div class="d-flex flex-wrap gap-2">
                <!-- Actas -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-success dropdown-toggle rounded-pill" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-file-pdf me-1"></i>Actas
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end p-2">
                        <li><h6 class="dropdown-header small">AUDITORÍA Y CONTROL</h6></li>
                        <li><a class="dropdown-item" href="/inventario_ti/api/generar_acta_entrega.php?persona_id=<?php echo $id; ?>" target="_blank"><i class="fas fa-hand-holding me-2 text-success"></i>Acta Entrega</a></li>
                        <li><a class="dropdown-item" href="/inventario_ti/api/generar_acta_devolucion.php?persona_id=<?php echo $id; ?>" target="_blank"><i class="fas fa-undo-alt me-2 text-warning"></i>Acta Devolución</a></li>
                        <li><a class="dropdown-item" href="/inventario_ti/api/generar_descargo.php?persona_id=<?php echo $id; ?>" target="_blank"><i class="fas fa-file-signature me-2 text-info"></i>Descargo</a></li>
                    </ul>
                </div>
                
                <!-- QR -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-info dropdown-toggle rounded-pill" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-qrcode me-1"></i>QR
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end p-2">
                        <li><a class="dropdown-item" href="#" onclick="generarQR(<?php echo $id; ?>); return false;"><i class="fas fa-eye me-2"></i>Visualizar</a></li>
                        <li><a class="dropdown-item" href="/inventario_ti/api/generar_qr_persona.php?id=<?php echo $id; ?>" download><i class="fas fa-download me-2"></i>Descargar</a></li>
                    </ul>
                </div>

                <a href="historial.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-warning rounded-pill">
                    <i class="fas fa-history me-1"></i>Historial
                </a>
                
                <?php if ($es_admin): ?>
                <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                    <i class="fas fa-edit me-1"></i>Editar
                </a>
                <?php endif; ?>
                
                <a href="listar.php" class="btn btn-sm btn-outline-light rounded-pill">
                    <i class="fas fa-arrow-left me-1"></i>Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 mb-4 shadow">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['mensaje']); ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 mb-4 shadow">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- DISEÑO REESTRUCTURADO EN DOS COLUMNAS -->
    <div class="row">
        
        <!-- COLUMNA SUPERIOR: PERFIL Y ESTADÍSTICAS (Compacto) -->
        <div class="col-12 mb-4">
            <div class="card shadow-lg border-0 overflow-hidden">
                <div class="row g-0">
                    <!-- Perfil Compacto -->
                    <div class="col-lg-4 p-4 border-end border-white border-opacity-10 d-flex align-items-center">
                        <div class="d-flex align-items-center gap-4 w-100">
                            <div class="profile-avatar-container">
                                <div class="profile-avatar">
                                    <i class="fas fa-user-tie fa-3x text-white"></i>
                                </div>
                                <div class="profile-status-online"></div>
                            </div>
                            <div class="flex-grow-1">
                                <h3 class="text-white fw-900 mb-1" style="letter-spacing: -0.5px;"><?php echo $persona['nombres']; ?></h3>
                                <p class="text-warning small fw-bold mb-2 text-uppercase tracking-wider"><?php echo $persona['cargo']; ?></p>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-white bg-opacity-10 border border-white border-opacity-10 py-1 px-3 rounded-pill small">
                                        <i class="fas fa-id-card me-1 opacity-50"></i> <?php echo $persona['cedula']; ?>
                                    </span>
                                    <span class="badge bg-white bg-opacity-10 border border-white border-opacity-10 py-1 px-3 rounded-pill small">
                                        <i class="fas fa-envelope me-1 opacity-50"></i> <?php echo $persona['correo'] ?: 'Sin correo'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas e Indicadores -->
                    <div class="col-lg-8 p-4 bg-white bg-opacity-05">
                        <div class="row h-100 align-items-center">
                            <div class="col-md-4 text-center border-end border-white border-opacity-05">
                                <div class="stat-minimal">
                                    <div class="stat-minimal-val"><?php echo $total_equipos; ?></div>
                                    <div class="stat-minimal-label">Equipos</div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center border-end border-white border-opacity-05">
                                <div class="stat-minimal">
                                    <div class="stat-minimal-val"><?php echo $total_componentes; ?></div>
                                    <div class="stat-minimal-label">Componentes</div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="d-grid gap-2">
                                    <?php if ($es_admin): ?>
                                    <a href="../asignaciones/cargar_equipos.php?persona_id=<?php echo $id; ?>" class="btn btn-sm btn-success rounded-pill fw-bold">
                                        <i class="fas fa-laptop me-2"></i>Asignar Equipo
                                    </a>
                                    <a href="asignar_componente_page.php?persona_id=<?php echo $id; ?>" class="btn btn-sm btn-info text-white rounded-pill fw-bold">
                                        <i class="fas fa-microchip me-2"></i>Asignar Comp.
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMNA IZQUIERDA: EQUIPOS ASIGNADOS -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100 shadow-lg border-0">
                <div class="card-header bg-warning py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-laptop-code me-2"></i>Equipos Asignados</h5>
                        <span class="badge bg-black bg-opacity-25 rounded-pill"><?php echo $total_equipos; ?> activos</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if ($total_equipos > 0): ?>
                        <div class="equipos-grid">
                            <?php foreach ($equipos_asignados as $eq): ?>
                            <div class="item-box-premium mb-4">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="item-icon-box bg-warning bg-opacity-10 text-warning">
                                            <i class="fas <?php echo ($eq['tipo_equipo'] == 'Laptop') ? 'fa-laptop' : 'fa-desktop'; ?> fa-2x"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex justify-content-between">
                                            <h5 class="text-white fw-bold mb-0"><?php echo htmlspecialchars($eq['tipo_equipo']); ?></h5>
                                            <span class="badge bg-white bg-opacity-10 text-warning border border-warning border-opacity-25"><?php echo $eq['codigo_barras']; ?></span>
                                        </div>
                                        <p class="text-white-50 small mb-2"><?php echo htmlspecialchars($eq['marca'] . ' ' . $eq['modelo']); ?></p>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="small text-white-50"><i class="fas fa-calendar-day me-1"></i> <?php echo date('d/m/Y', strtotime($eq['fecha_asignacion'])); ?></span>
                                            <span class="small text-white-50"><i class="fas fa-shield-check me-1"></i> Garantía OK</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="d-flex flex-column gap-2">
                                            <a href="/inventario_ti/modules/equipos/detalle.php?id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-outline-info rounded-pill px-3">Ver</a>
                                            <?php if ($es_admin): ?>
                                            <a href="/inventario_ti/modules/movimientos/devolucion.php?equipo_id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">Devolver</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="empty-state-icon mb-3">
                                <i class="fas fa-laptop-slash fa-4x opacity-10"></i>
                            </div>
                            <h6 class="text-white-50">No hay equipos asignados a este usuario</h6>
                            <p class="small text-muted">Haz clic en "Asignar Equipo" para empezar</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: COMPONENTES -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100 shadow-lg border-0">
                <div class="card-header bg-info py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-white"><i class="fas fa-microchip me-2"></i>Detalle de Componentes</h5>
                        <span class="badge bg-black bg-opacity-25 rounded-pill"><?php echo $total_componentes; ?> activos</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if ($total_componentes > 0): ?>
                        <div class="row g-4">
                            <!-- Componentes Directos -->
                            <?php foreach ($componentes_directos as $comp): ?>
                            <div class="col-12">
                                <div class="item-box-premium border-info border-opacity-25">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="item-icon-box bg-info bg-opacity-10 text-info">
                                                <i class="fas fa-plug fa-lg"></i>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex justify-content-between mb-1">
                                                <h6 class="text-white fw-bold mb-0"><?php echo htmlspecialchars($comp['tipo']); ?></h6>
                                                <span class="badge bg-info text-white">Directo</span>
                                            </div>
                                            <p class="text-white-50 small mb-0"><?php echo htmlspecialchars($comp['nombre_componente']); ?></p>
                                            <small class="text-white-50 opacity-50"><?php echo htmlspecialchars($comp['marca'] . ' ' . $comp['modelo']); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-link text-white-50" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="/inventario_ti/modules/componentes/detalle.php?id=<?php echo $comp['id']; ?>">Ver detalles</a></li>
                                                    <?php if ($es_admin): ?>
                                                    <li><a class="dropdown-item text-warning" href="/inventario_ti/modules/componentes/devolver.php?id=<?php echo $comp['id']; ?>">Registrar devolución</a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- Componentes en Equipos -->
                            <?php foreach ($componentes_asignados as $comp): ?>
                            <div class="col-12">
                                <div class="item-box-premium">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="item-icon-box bg-white bg-opacity-05 text-white-50">
                                                <i class="fas fa-server fa-lg"></i>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex justify-content-between mb-1">
                                                <h6 class="text-white fw-bold mb-0"><?php echo htmlspecialchars($comp['tipo']); ?></h6>
                                                <span class="badge bg-secondary bg-opacity-25 text-white-50">En equipo</span>
                                            </div>
                                            <p class="text-white-50 small mb-0"><?php echo htmlspecialchars($comp['nombre_componente']); ?></p>
                                            <small class="text-white-50 opacity-50"><i class="fas fa-link me-1"></i> Vinc. a: <?php echo htmlspecialchars($comp['tipo_equipo']); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <a href="/inventario_ti/modules/componentes/detalle.php?id=<?php echo $comp['id']; ?>" class="btn btn-sm btn-link text-info"><i class="fas fa-external-link-alt"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-microchip fa-4x opacity-10 mb-3"></i>
                            <h6 class="text-white-50">Sin componentes adicionales</h6>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ASIGNAR COMPONENTE (Mantenido para funcionalidad) -->
<?php if ($es_admin): ?>
<style>
.modal-content { background: rgba(20, 5, 45, 0.98) !important; border: 1px solid var(--c-gold) !important; border-radius: 25px !important; }
.modal-header { border-bottom: 1px solid rgba(255,255,255,0.1) !important; }
</style>
<div class="modal fade" id="assignComponentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white"><i class="fas fa-microchip me-2 text-info"></i>Asignar Componente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="asignar_componente.php">
                <div class="modal-body">
                    <input type="hidden" name="persona_id" value="<?php echo (int)$id; ?>">
                    <?php if ($total_componentes_disponibles > 0): ?>
                        <div class="mb-3">
                            <label class="form-label text-white-50 small">COMPONENTE DISPONIBLE</label>
                            <select class="form-select bg-dark text-white border-secondary" name="componente_id" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($componentes_disponibles as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['tipo'] . ' - ' . $c['nombre_componente']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white-50 small">OBSERVACIONES</label>
                            <textarea class="form-control bg-dark text-white border-secondary" name="observaciones" rows="3"></textarea>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">No hay componentes libres.</div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <?php if ($total_componentes_disponibles > 0): ?>
                        <button type="submit" class="btn btn-info text-white rounded-pill px-4 fw-bold">Asignar</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>

<script>
// Mantengo tus scripts de QR y Actas que ya funcionaban
function generarQR(id) {
    const container = document.getElementById('qrcode-container');
    container.innerHTML = '';
    
    // Usar la misma lógica que en otras partes para generar el canvas
    const url = '<?php echo $base_url_publica; ?>/modules/personas/ver_equipos_qr.php?id=' + id;
    
    // Crear el canvas manualmente si es necesario o usar librería si está cargada
    // Asumiendo que usas qrious o similar cargado globalmente
    if (typeof QRious !== 'undefined') {
        new QRious({
            element: container.appendChild(document.createElement('canvas')),
            size: 250,
            value: url,
            level: 'H',
            foreground: '#1a0533'
        });
    } else {
        // Fallback simple si no hay librería (puedes cargarla si falta)
        container.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(url) + '" />';
    }
    
    document.getElementById('qrModal').style.display = 'flex';
}

function cerrarModalQR() {
    document.getElementById('qrModal').style.display = 'none';
}

// Cerrar modal al hacer click fuera
window.onclick = function(event) {
    const modal = document.getElementById('qrModal');
    if (event.target == modal) {
        cerrarModalQR();
    }
}
</script>