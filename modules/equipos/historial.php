<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();

require_once '../../config/database.php';
include '../../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);
$es_admin = ($_SESSION['user_rol'] == 1);

// Obtener datos del equipo
$sql_equipo = "SELECT e.*, u.nombre as ubicacion_nombre, u.codigo_ubicacion 
               FROM equipos e
               LEFT JOIN ubicaciones u ON e.ubicacion_id = u.id
               WHERE e.id = $id";
$result_equipo = $conn->query($sql_equipo);
if ($result_equipo->num_rows == 0) {
    header('Location: listar.php');
    exit();
}
$equipo = $result_equipo->fetch_assoc();

// Obtener historial de movimientos
$sql_movimientos = "SELECT m.*, p.nombres as persona_nombre, u.nombre as usuario_nombre
                    FROM movimientos m
                    LEFT JOIN personas p ON m.persona_id = p.id
                    LEFT JOIN usuarios u ON m.usuario_registro = u.id
                    WHERE m.equipo_id = $id
                    ORDER BY m.fecha_movimiento DESC";
$movimientos = $conn->query($sql_movimientos);

// Obtener historial de incidencias
$sql_incidencias = "SELECT i.*, p.nombres as persona_nombre, u.nombre as usuario_nombre
                    FROM incidencias i
                    LEFT JOIN personas p ON i.persona_id = p.id
                    LEFT JOIN usuarios u ON i.usuario_registro = u.id
                    WHERE i.equipo_id = $id
                    ORDER BY i.fecha_reporte DESC";
$incidencias = $conn->query($sql_incidencias);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-history me-2"></i>Historial del Equipo</h4>
                    <div>
                        <a href="detalle.php?id=<?php echo $id; ?>" class="btn btn-info">
                            <i class="fas fa-eye"></i> Ver Detalle
                        </a>
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Datos del equipo -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr><th>Código</th><td><?php echo $equipo['codigo_barras']; ?></td></tr>
                                <tr><th>Tipo</th><td><?php echo $equipo['tipo_equipo']; ?></td></tr>
                                <tr><th>Marca</th><td><?php echo $equipo['marca'] ?: 'N/A'; ?></td></tr>
                                <tr><th>Modelo</th><td><?php echo $equipo['modelo'] ?: 'N/A'; ?></td></tr>
                                <tr><th>N° Serie</th><td><?php echo $equipo['numero_serie'] ?: 'N/A'; ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr><th>Estado actual</th>
                                    <td>
                                        <span class="badge bg-<?php echo $equipo['estado'] == 'Disponible' ? 'success' : ($equipo['estado'] == 'Asignado' ? 'warning' : 'secondary'); ?>">
                                            <?php echo $equipo['estado']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr><th>Ubicación</th><td><?php echo $equipo['ubicacion_nombre'] ? $equipo['ubicacion_codigo'] . ' - ' . $equipo['ubicacion_nombre'] : 'Sin ubicación'; ?></td></tr>
                                <tr><th>Fecha de ingreso</th><td><?php echo date('d/m/Y H:i', strtotime($equipo['fecha_ingreso'] ?? $equipo['created_at'] ?? 'now')); ?></td></tr>
                            </table>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- MOVIMIENTOS -->
                    <!-- ============================================ -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <h5><i class="fas fa-list me-2"></i>Movimientos registrados</h5>
                        <?php if ($es_admin): ?>
                        <a href="../movimientos/registrar.php?equipo_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus-circle"></i> Nuevo movimiento
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php if ($movimientos && $movimientos->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Persona</th>
                                        <th>Observaciones</th>
                                        <th>Descripción</th>
                                        <th>Registrado por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($mov = $movimientos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $mov['tipo_movimiento'] == 'ASIGNACION' ? 'warning' : 
                                                    ($mov['tipo_movimiento'] == 'DEVOLUCION' ? 'success' : 
                                                    ($mov['tipo_movimiento'] == 'REPARACION' ? 'info' : 'secondary')); 
                                            ?>">
                                                <?php echo $mov['tipo_movimiento']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $mov['persona_nombre'] ?? '-'; ?></td>
                                        <td><?php echo nl2br($mov['observaciones'] ?? ''); ?></td>
                                        <td><?php echo nl2br($mov['descripcion'] ?? ''); ?></td>
                                        <td><?php echo $mov['usuario_nombre'] ?? 'Sistema'; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No hay movimientos registrados para este equipo.</p>
                    <?php endif; ?>

                    <!-- ============================================ -->
                    <!-- INCIDENCIAS -->
                    <!-- ============================================ -->
                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Incidencias reportadas</h5>
                        <?php if ($es_admin): ?>
                        <a href="incidencia.php?equipo_id=<?php echo $id; ?>" class="btn btn-sm btn-danger">
                            <i class="fas fa-plus-circle"></i> Nueva incidencia
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php if ($incidencias && $incidencias->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha reporte</th>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Persona</th>
                                        <th>Fecha resolución</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($inc = $incidencias->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($inc['fecha_reporte'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $inc['tipo_incidencia'] == 'daño' ? 'danger' : 
                                                    ($inc['tipo_incidencia'] == 'reparación' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo strtoupper($inc['tipo_incidencia']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo nl2br($inc['descripcion']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $inc['estado'] == 'pendiente' ? 'secondary' : 
                                                    ($inc['estado'] == 'en proceso' ? 'primary' : 'success'); 
                                            ?>">
                                                <?php echo $inc['estado']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $inc['persona_nombre'] ?? '-'; ?></td>
                                        <td><?php echo $inc['fecha_resolucion'] ? date('d/m/Y H:i', strtotime($inc['fecha_resolucion'])) : '-'; ?></td>
                                        <td><?php echo nl2br($inc['observaciones'] ?? ''); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No hay incidencias reportadas para este equipo.</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>