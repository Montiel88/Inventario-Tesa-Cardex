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
/* ESTILOS GENERALES Y MEJORAS EN BOTONES */
/* ============================================ */
.card-header .btn-group .btn,
.card-header .btn {
    border-radius: 30px;
    padding: 0.4rem 1rem;
    font-weight: 500;
    transition: all 0.2s ease;
    border-width: 2px;
}
.card-header .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.card-header .dropdown-menu {
    border-radius: 15px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    padding: 0.5rem;
}
.card-header .dropdown-item {
    border-radius: 10px;
    padding: 0.5rem 1rem;
    transition: all 0.2s;
}
.card-header .dropdown-item:hover {
    background: linear-gradient(135deg, #f3e9ff, #ffffff);
    color: #5a2d8c;
    transform: translateX(5px);
}

/* Responsive para botones */
@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .card-header > div {
        margin-top: 10px;
        width: 100%;
    }
    .card-header .btn-group,
    .card-header .btn {
        margin: 2px;
    }
}

/* ============================================ */
/* ESTILOS PARA EL MODAL DEL QR */
/* ============================================ */
.qr-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}
.qr-modal-content {
    background: white;
    padding: 30px;
    border-radius: 20px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}
.qr-modal-content h4 {
    color: #5a2d8c;
    margin-bottom: 20px;
}
#qrcode-container {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fc;
    border-radius: 15px;
    min-height: 300px;
}
#qrcode-container canvas {
    margin: 0 auto;
    max-width: 100%;
    height: auto;
}
.qr-modal-content .btn-close {
    background: #dc3545;
    color: white;
    border: none;
    padding: 10px 30px;
    border-radius: 30px;
    font-weight: 600;
    margin-top: 15px;
    cursor: pointer;
    display: block;
    margin-left: auto;
    margin-right: auto;
    width: fit-content;
    text-align: center;
}
.qr-modal-content .btn-close:hover {
    background: #c82333;
}

/* ============================================ */
/* RESPONSIVE */
/* ============================================ */
@media (max-width: 768px) {
    .container-fluid {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
    .table-bordered {
        font-size: 14px !important;
    }
    .table-bordered th,
    .table-bordered td {
        padding: 8px !important;
    }
    .table-bordered th {
        width: 35% !important;
    }
    .col-md-6 .card.bg-light {
        margin-top: 15px !important;
    }
    .col-md-6 .card-body {
        padding: 15px !important;
    }
    .col-md-6 h3 {
        font-size: 2rem !important;
    }
    .btn-success {
        font-size: 1rem !important;
        padding: 12px !important;
    }
    .table-responsive {
        border: none !important;
    }
    .table {
        min-width: auto !important;
    }
    .table thead {
        display: none !important;
    }
    .table tbody tr {
        display: block !important;
        margin-bottom: 20px !important;
        border: 1px solid #e0e0e0 !important;
        border-radius: 15px !important;
        padding: 15px !important;
        background: white !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
    }
    .table tbody td {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 10px 8px !important;
        border: none !important;
        border-bottom: 1px dashed #eee !important;
        font-size: 14px !important;
    }
    .table tbody td:last-child {
        border-bottom: none !important;
    }
    .table tbody td:before {
        content: attr(data-label) !important;
        font-weight: 700 !important;
        color: #5a2d8c !important;
        margin-right: 10px !important;
        min-width: 100px !important;
        font-size: 13px !important;
    }
    .table tbody td .btn-sm {
        padding: 5px 10px !important;
        font-size: 12px !important;
    }
    .list-group-item {
        padding: 12px !important;
        font-size: 13px !important;
    }
    .list-group-item .d-flex {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 5px !important;
    }
    .list-group-item .badge {
        font-size: 11px !important;
        padding: 3px 6px !important;
    }
    .list-group-item small {
        font-size: 11px !important;
    }
}

@media (max-width: 480px) {
    .card-header h4 {
        font-size: 1.2rem !important;
    }
    .card-header .btn {
        font-size: 0.75rem !important;
        padding: 6px 8px !important;
    }
    .table-bordered {
        font-size: 12px !important;
    }
    .table-bordered th,
    .table-bordered td {
        padding: 6px !important;
    }
    .table tbody td {
        font-size: 12px !important;
        padding: 8px 5px !important;
    }
    .table tbody td:before {
        min-width: 80px !important;
        font-size: 11px !important;
    }
    .btn-sm {
        padding: 4px 8px !important;
        font-size: 11px !important;
    }
    .col-md-6 h3 {
        font-size: 1.8rem !important;
    }
}
</style>

<!-- MODAL PARA MOSTRAR QR -->
<div id="qrModal" class="qr-modal">
    <div class="qr-modal-content">
        <h4><i class="fas fa-qrcode me-2"></i>Código QR de <?php echo $persona['nombres']; ?></h4>
        <div id="qrcode-container" style="display: flex; justify-content: center; align-items: center; min-height: 250px;"></div>
        <p class="text-muted">Escanea este código para ver los equipos de la persona</p>
        <button class="btn-close" onclick="cerrarModalQR()">Cerrar</button>
    </div>
</div>


<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <!-- HEADER CON BOTONES MEJORADOS -->
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <h4 class="mb-0"><i class="fas fa-user me-2"></i>Detalle de Persona</h4>
                    <div class="d-flex flex-wrap gap-2">
                      <!-- Grupo Actas - VERSIÓN SIMPLE Y FUNCIONAL -->
<div class="btn-group" role="group">
    <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="btnActasPersona">
        <i class="fas fa-file-pdf me-1"></i>Actas
    </button>
    <ul class="dropdown-menu dropdown-menu-end" id="menuActasPersona" style="max-height: 400px; overflow-y: auto;">
        <?php
        // Opción 1: Cargar estático (seguro)
        $id_actual = $id;
        ?>
        <li><h6 class="dropdown-header">📋 ACTAS DE PERSONA</h6></li>
        <li><a class="dropdown-item" href="/inventario_ti/api/generar_acta_entrega.php?persona_id=<?php echo $id_actual; ?>" target="_blank">
            <i class="fas fa-hand-holding me-2 text-success"></i>Acta Entrega
        </a></li>
        <li><a class="dropdown-item" href="/inventario_ti/api/generar_acta_devolucion.php?persona_id=<?php echo $id_actual; ?>" target="_blank">
            <i class="fas fa-undo-alt me-2 text-warning"></i>Acta Devolución
        </a></li>
        <li><a class="dropdown-item" href="/inventario_ti/api/generar_descargo.php?persona_id=<?php echo $id_actual; ?>" target="_blank">
            <i class="fas fa-file-signature me-2 text-info"></i>Descargo
        </a></li>
        
        <?php
        // Verificar si tiene equipos asignados
        $check_equipos = $conn->query("SELECT COUNT(*) as total FROM asignaciones WHERE persona_id = $id_actual AND fecha_devolucion IS NULL");
        $total_equipos_asignados = $check_equipos->fetch_assoc()['total'];
        
        if ($total_equipos_asignados > 0): 
        ?>
        <li><hr class="dropdown-divider"></li>
        <li><h6 class="dropdown-header">📦 ACTAS DE EQUIPOS</h6></li>
        <li><a class="dropdown-item" href="#" onclick="alert('Seleccione un equipo específico para generar esta acta'); return false;">
            <i class="fas fa-box-open me-2 text-primary"></i>Acta de Ingreso (elegir equipo)
        </a></li>
        <li><a class="dropdown-item" href="#" onclick="alert('Seleccione un equipo específico para generar esta acta'); return false;">
            <i class="fas fa-trash-alt me-2 text-danger"></i>Acta de Baja (elegir equipo)
        </a></li>
        <?php endif; ?>
    </ul>
</div>

<!-- El resto de tus botones (QR, Historial, etc.) siguen igual -->
                        
                        <!-- Grupo QR -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-qrcode me-1"></i>QR
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="generarQR(<?php echo $id; ?>); return false;">Ver QR</a></li>
                                <li><a class="dropdown-item" href="/inventario_ti/api/generar_qr_persona.php?id=<?php echo $id; ?>" download="qr_persona_<?php echo $id; ?>.png">Descargar QR</a></li>
                            </ul>
                        </div>
                        
                        <!-- Botones individuales -->
                        <a href="historial.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-history me-1"></i>Historial
                        </a>
                        
                        <?php if ($es_admin): ?>
                        <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit me-1"></i>Editar
                        </a>
                        <?php endif; ?>
                        
                        <a href="listar.php" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                    </div>
                </div>
                <!-- FIN HEADER -->
                
                <div class="card-body">
                    
                    <!-- mostrar mensajes -->
                    <?php if (isset($_GET['mensaje'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo htmlspecialchars($_GET['mensaje']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php elseif (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Datos de la persona -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <?php if ($es_admin): ?>
                                <tr><th width="30%">Cédula</th><td><?php echo $persona['cedula']; ?></td></tr>
                                <?php endif; ?>
                                <tr><th>Nombres</th><td><?php echo $persona['nombres']; ?></td></tr>
                                <tr><th>Cargo</th><td><?php echo $persona['cargo']; ?></td></tr>
                                <tr><th>Correo</th><td><?php echo $persona['correo'] ?: 'No registrado'; ?></td></tr>
                                <?php if ($es_admin): ?>
                                <tr><th>Teléfono</th><td><?php echo $persona['telefono'] ?: 'No registrado'; ?></td></tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body text-center">
                                    <h3><?php echo $total_equipos; ?></h3>
                                    <p>Equipos asignados actualmente</p>
                                    <?php if ($es_admin): ?>
                                    <a href="../asignaciones/cargar_equipos.php?persona_id=<?php echo $id; ?>" class="btn btn-success btn-lg w-100">
                                        <i class="fas fa-plus-circle me-2"></i>Asignar nuevo equipo
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-secondary btn-lg w-100" disabled>
                                        <i class="fas fa-ban me-2"></i>Asignar (solo admin)
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                          <!-- tarjeta componentes asignados - VERSIÓN SIMPLE -->
<div class="card bg-light">
    <div class="card-body text-center">
        <h3><?php echo $total_componentes; ?></h3>
        <p>Componentes asignados actualmente</p>
        
        <?php if ($es_admin && $total_componentes_disponibles > 0): ?>
            <!-- Enlace directo a una página de asignación -->
            <a href="asignar_componente_page.php?persona_id=<?php echo $id; ?>" class="btn btn-success btn-lg w-100">
                <i class="fas fa-plus-circle me-2"></i>Asignar componente
            </a>
        <?php else: ?>
            <button class="btn btn-secondary btn-lg w-100" disabled>
                <i class="fas fa-ban me-2"></i>No hay componentes disponibles
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL ASIGNAR COMPONENTE - VERSIÓN FINAL -->
<?php if ($es_admin): ?>
<style>
/* Estilos forzados para el modal */
.modal {
    z-index: 999999 !important;
}
.modal-backdrop {
    z-index: 999998 !important;
}
.modal-dialog {
    margin: 1.75rem auto !important;
    max-width: 500px !important;
}
.modal-content {
    background: white !important;
    border-radius: 15px !important;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2) !important;
}
</style>

<div class="modal fade" id="assignComponentModal" tabindex="-1" aria-labelledby="assignComponentModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="assignComponentModalLabel">
                    <i class="fas fa-microchip me-2"></i>Asignar Componente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" action="asignar_componente.php">
                <div class="modal-body">
                    <input type="hidden" name="persona_id" value="<?php echo (int)$id; ?>">
                    
                    <?php if ($total_componentes_disponibles > 0): ?>
                        <div class="mb-3">
                            <label for="componente_id" class="form-label fw-bold">Componente disponible</label>
                            <select class="form-select" id="componente_id" name="componente_id" required>
                                <option value="">-- Seleccione un componente --</option>
                                <?php foreach ($componentes_disponibles as $c): ?>
                                    <option value="<?php echo $c['id']; ?>">
                                        <?php echo htmlspecialchars($c['tipo'] . ' - ' . $c['nombre_componente'] . ' (' . $c['marca'] . ' ' . $c['modelo'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted"><?php echo $total_componentes_disponibles; ?> componente(s) disponible(s)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observaciones" class="form-label fw-bold">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Notas adicionales sobre la asignación..."></textarea>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No hay componentes disponibles para asignar.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <?php if ($total_componentes_disponibles > 0): ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Asignar Componente
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Botón simple que abre el modal -->
<button class="btn btn-success btn-lg w-100" onclick="document.getElementById('miModal').style.display='block'">
    <i class="fas fa-plus-circle me-2"></i>Asignar componente
</button>

<!-- Modal simple sin Bootstrap -->
<div id="miModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
    <div style="background-color:white; margin:15% auto; padding:20px; width:80%; max-width:500px; border-radius:10px;">
        <h4>Asignar Componente</h4>
        <form method="POST" action="asignar_componente.php">
            <input type="hidden" name="persona_id" value="<?php echo (int)$id; ?>">
            
            <div class="mb-3">
                <label>Componente disponible</label>
                <select class="form-control" name="componente_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($componentes_disponibles as $c): ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo $c['tipo'] . ' - ' . $c['nombre_componente']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label>Observaciones</label>
                <textarea class="form-control" name="observaciones" rows="2"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Asignar</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('miModal').style.display='none'">Cancelar</button>
        </form>
    </div>
</div>

<?php endif; ?>
<?php include '../../includes/footer.php'; ?>