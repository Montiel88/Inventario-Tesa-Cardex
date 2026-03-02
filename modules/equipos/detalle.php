<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);
$es_admin = ($_SESSION['user_rol'] == 1);

// Obtener datos del equipo incluyendo la ubicación
$sql = "SELECT e.*, 
               a.persona_id, 
               p.nombres as persona_nombre,
               p.cedula as persona_cedula,
               a.fecha_asignacion,
               u.id as ubicacion_id,
               u.nombre as ubicacion_nombre,
               u.codigo_ubicacion,
               a.observaciones as obs_asignacion
        FROM equipos e
        LEFT JOIN asignaciones a ON e.id = a.equipo_id AND a.fecha_devolucion IS NULL
        LEFT JOIN personas p ON a.persona_id = p.id
        LEFT JOIN ubicaciones u ON e.ubicacion_id = u.id
        WHERE e.id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}

$equipo = $result->fetch_assoc();

// Obtener historial de movimientos del equipo (últimos 5)
$sql_historial = "SELECT m.*, p.nombres as persona_nombre
                  FROM movimientos m
                  LEFT JOIN personas p ON m.persona_id = p.id
                  WHERE m.equipo_id = $id
                  ORDER BY m.fecha_movimiento DESC
                  LIMIT 5";
$historial = $conn->query($sql_historial);

// Obtener últimas incidencias (últimas 3)
$sql_incidencias = "SELECT i.*, p.nombres as persona_nombre
                    FROM incidencias i
                    LEFT JOIN personas p ON i.persona_id = p.id
                    WHERE i.equipo_id = $id
                    ORDER BY i.fecha_reporte DESC
                    LIMIT 3";
$incidencias = $conn->query($sql_incidencias);

// URL para el QR
$url_qr = '/inventario_ti/api/generar_qr_equipo.php?id=' . $id;
?>

<!-- Estilos y modal (igual que antes, se omiten por brevedad, pero deben mantenerse) -->
<!-- ... -->

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-laptop me-2"></i>Detalle del Equipo</h4>
                    <div>
                        <button onclick="generarQR(<?php echo $id; ?>)" class="btn btn-info">
                            <i class="fas fa-qrcode me-2"></i>Ver QR
                        </button>
                        <a href="historial.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="fas fa-history me-2"></i>Historial
                        </a>
                        <?php if ($es_admin): ?>
                        <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        <?php endif; ?>
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Datos del equipo -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr><th>Código:</th><td><?php echo htmlspecialchars($equipo['codigo_barras']); ?></td></tr>
                                <tr><th>Tipo:</th><td><?php echo htmlspecialchars($equipo['tipo_equipo']); ?></td></tr>
                                <tr><th>Marca:</th><td><?php echo htmlspecialchars($equipo['marca'] ?: 'N/A'); ?></td></tr>
                                <tr><th>Modelo:</th><td><?php echo htmlspecialchars($equipo['modelo'] ?: 'N/A'); ?></td></tr>
                                <tr><th>N° Serie:</th><td><?php echo htmlspecialchars($equipo['numero_serie'] ?: 'N/A'); ?></td></tr>
                                <tr><th>Estado:</th>
                                    <td>
                                        <?php 
                                        $estado = $equipo['persona_id'] ? 'PRESTADO' : 'DISPONIBLE';
                                        $badgeClass = $equipo['persona_id'] ? 'warning' : 'success';
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $estado; ?></span>
                                    </td>
                                </tr>
                                <tr><th>Ubicación:</th>
                                    <td>
                                        <?php if ($equipo['ubicacion_id']): ?>
                                            <a href="../ubicaciones/detalle.php?id=<?php echo $equipo['ubicacion_id']; ?>">
                                                <?php echo htmlspecialchars($equipo['codigo_ubicacion'] . ' - ' . $equipo['ubicacion_nombre']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Sin ubicación</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <?php if ($equipo['persona_id']): ?>
                                        <h3><i class="fas fa-user me-2"></i><?php echo $equipo['persona_nombre']; ?></h3>
                                        <p>Equipo prestado actualmente</p>
                                        <p class="text-muted">Cédula: <?php echo $equipo['persona_cedula']; ?></p>
                                        <p class="text-muted">Desde: <?php echo date('d/m/Y', strtotime($equipo['fecha_asignacion'])); ?></p>
                                        <a href="../movimientos/devolucion.php?equipo_id=<?php echo $id; ?>" class="btn btn-warning btn-lg w-100">
                                            <i class="fas fa-undo-alt me-2"></i>Registrar Devolución
                                        </a>
                                    <?php else: ?>
                                        <h3><i class="fas fa-check-circle text-success"></i> DISPONIBLE</h3>
                                        <p>Equipo disponible</p>
                                        <a href="../movimientos/prestamo.php?equipo_id=<?php echo $id; ?>" class="btn btn-primary btn-lg w-100">
                                            <i class="fas fa-hand-holding me-2"></i>Registrar Préstamo
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Especificaciones y Observaciones -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mt-2">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-microchip me-2"></i>Especificaciones</h5>
                                </div>
                                <div class="card-body">
                                    <?php echo nl2br(htmlspecialchars($equipo['especificaciones'] ?: 'No hay especificaciones registradas')); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mt-2">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Observaciones</h5>
                                </div>
                                <div class="card-body">
                                    <?php echo nl2br(htmlspecialchars($equipo['observaciones'] ?: 'No hay observaciones')); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Últimos movimientos -->
                    <div class="card mt-4">
                        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Últimos Movimientos</h5>
                            <a href="historial.php?id=<?php echo $id; ?>" class="btn btn-sm btn-light">Ver todos</a>
                        </div>
                        <div class="card-body">
                            <?php if ($historial && $historial->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while($h = $historial->fetch_assoc()): 
                                        $clase = $h['tipo_movimiento'] == 'ASIGNACION' ? 'warning' : 
                                                ($h['tipo_movimiento'] == 'DEVOLUCION' ? 'success' : 'info');
                                    ?>
                                        <div class="list-group-item list-group-item-<?php echo $clase; ?>">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <span class="badge bg-<?php echo $clase; ?>"><?php echo $h['tipo_movimiento']; ?></span>
                                                    <?php if ($h['persona_nombre']): ?>
                                                        <small class="ms-2"><i class="fas fa-user me-1"></i><?php echo $h['persona_nombre']; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                <small><?php echo date('d/m/Y H:i', strtotime($h['fecha_movimiento'])); ?></small>
                                            </div>
                                            <?php if ($h['observaciones']): ?>
                                                <p class="mb-0 mt-2"><small><?php echo $h['observaciones']; ?></small></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No hay movimientos registrados</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Últimas incidencias -->
                    <?php if ($incidencias && $incidencias->num_rows > 0): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Incidencias Recientes</h5>
                            <a href="historial.php?id=<?php echo $id; ?>" class="btn btn-sm btn-light">Ver todas</a>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php while($inc = $incidencias->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <span class="badge bg-<?php 
                                                    echo $inc['tipo_incidencia'] == 'daño' ? 'danger' : 
                                                        ($inc['tipo_incidencia'] == 'reparación' ? 'warning' : 'info'); 
                                                ?>">
                                                    <?php echo strtoupper($inc['tipo_incidencia']); ?>
                                                </span>
                                                <span class="badge bg-<?php 
                                                    echo $inc['estado'] == 'pendiente' ? 'secondary' : 
                                                        ($inc['estado'] == 'en proceso' ? 'primary' : 'success'); 
                                                ?> ms-2">
                                                    <?php echo $inc['estado']; ?>
                                                </span>
                                                <small class="ms-2"><?php echo date('d/m/Y', strtotime($inc['fecha_reporte'])); ?></small>
                                            </div>
                                        </div>
                                        <p class="mb-0 mt-2"><?php echo nl2br($inc['descripcion']); ?></p>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts y footer (igual que antes) -->
<!-- ... -->
<?php include '../../includes/footer.php'; ?>