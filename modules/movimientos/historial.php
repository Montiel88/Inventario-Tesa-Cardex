<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
require_once '../../config/database.php';
include '../../includes/header.php';

$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';
$persona_id = isset($_GET['persona_id']) ? intval($_GET['persona_id']) : 0;

// ============================================
// CONSULTA PARA PRÉSTAMOS ACTIVOS
// ============================================
if ($filtro == 'activos') {
    $sql = "SELECT 
                a.id as asignacion_id,
                a.fecha_asignacion,
                a.observaciones as obs_asignacion,
                e.id as equipo_id,
                e.codigo_barras,
                e.tipo_equipo,
                e.marca,
                e.modelo,
                e.numero_serie,
                p.id as persona_id,
                p.nombres as persona_nombre,
                p.cedula,
                p.cargo
            FROM asignaciones a
            INNER JOIN equipos e ON a.equipo_id = e.id
            INNER JOIN personas p ON a.persona_id = p.id
            WHERE a.fecha_devolucion IS NULL
            ORDER BY a.fecha_asignacion DESC";
    
    $titulo = "Préstamos Activos";
    
} elseif ($persona_id > 0) {
    // Historial por persona específica
    $sql = "SELECT 
                m.*,
                e.tipo_equipo,
                e.codigo_barras,
                e.marca,
                e.modelo
            FROM movimientos m
            INNER JOIN equipos e ON m.equipo_id = e.id
            WHERE m.persona_id = $persona_id
            ORDER BY m.fecha_movimiento DESC";
    
    $titulo = "Historial de la Persona";
    
} else {
    // Todos los movimientos
    $sql = "SELECT 
                m.*,
                e.tipo_equipo,
                e.codigo_barras,
                p.nombres as persona_nombre
            FROM movimientos m
            LEFT JOIN equipos e ON m.equipo_id = e.id
            LEFT JOIN personas p ON m.persona_id = p.id
            ORDER BY m.fecha_movimiento DESC
            LIMIT 100";
    
    $titulo = "Historial de Movimientos";
}

$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-history me-2"></i><?php echo $titulo; ?></h4>
                </div>
                <div class="card-body">
                    
                    <!-- Botones de filtro -->
                    <div class="mb-3">
                        <a href="historial.php" class="btn btn-sm btn-secondary">Todos</a>
                        <a href="historial.php?filtro=activos" class="btn btn-sm btn-warning">Préstamos Activos</a>
                    </div>
                    
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <?php if ($filtro == 'activos'): ?>
                                            <th>Fecha</th>
                                            <th>Persona</th>
                                            <th>Equipo</th>
                                            <th>Código</th>
                                            <th>Marca/Modelo</th>
                                            <th>Acciones</th>
                                        <?php else: ?>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Equipo</th>
                                            <th>Persona</th>
                                            <th>Observaciones</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($filtro == 'activos'): ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td data-label="FECHA"><?php echo date('d/m/Y H:i', strtotime($row['fecha_asignacion'])); ?></td>
                                            <td data-label="PERSONA">
                                                <strong><?php echo htmlspecialchars($row['persona_nombre']); ?></strong><br>
                                                <small><?php echo $row['cedula']; ?></small>
                                            </td>
                                            <td data-label="EQUIPO"><?php echo $row['tipo_equipo']; ?></td>
                                            <td data-label="CÓDIGO"><?php echo $row['codigo_barras']; ?></td>
                                            <td data-label="MARCA/MODELO"><?php echo $row['marca'] . ' ' . $row['modelo']; ?></td>
                                            <td data-label="ACCIONES">
                                                <a href="../movimientos/devolucion.php?equipo_id=<?php echo $row['equipo_id']; ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-undo-alt"></i> Devolver
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td data-label="FECHA"><?php echo date('d/m/Y H:i', strtotime($row['fecha_movimiento'])); ?></td>
                                            <td data-label="TIPO">
                                                <span class="badge bg-<?php echo $row['tipo_movimiento'] == 'ASIGNACION' ? 'success' : 'warning'; ?>">
                                                    <?php echo $row['tipo_movimiento']; ?>
                                                </span>
                                            </td>
                                            <td data-label="EQUIPO"><?php echo $row['tipo_equipo'] . ' - ' . $row['codigo_barras']; ?></td>
                                            <td data-label="PERSONA"><?php echo $row['persona_nombre'] ?? 'N/A'; ?></td>
                                            <td data-label="OBSERVACIONES"><?php echo $row['observaciones'] ?? ''; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center py-4">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <h5>No hay <?php echo strtolower($titulo); ?></h5>
                            <?php if ($filtro == 'activos'): ?>
                                <p>Todos los equipos están disponibles o no hay préstamos registrados.</p>
                                <a href="../asignaciones/cargar_equipos.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus-circle me-2"></i>Registrar nuevo préstamo
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
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
    }
    
    .table tbody td {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 8px 5px !important;
        border: none !important;
        border-bottom: 1px dashed #eee !important;
        font-size: 13px !important;
    }
    
    .table tbody td:last-child {
        border-bottom: none !important;
    }
    
    .table tbody td:before {
        content: attr(data-label) !important;
        font-weight: 700 !important;
        color: #5a2d8c !important;
        margin-right: 10px !important;
        min-width: 80px !important;
        font-size: 12px !important;
    }
    
    .btn-sm {
        padding: 4px 8px !important;
        font-size: 11px !important;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>