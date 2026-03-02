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

// Obtener datos del equipo incluyendo la ubicación
$sql = "SELECT e.*, 
               a.persona_id, 
               p.nombres as persona_nombre,
               a.fecha_asignacion,
               u.id as ubicacion_id,
               u.nombre as ubicacion_nombre,
               u.codigo_ubicacion
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
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-laptop me-2"></i>Detalle del Equipo</h4>
                    <div>
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Código:</th>
                                    <td><?php echo htmlspecialchars($equipo['codigo_barras']); ?></td>
                                </tr>
                                <tr>
                                    <th>Tipo:</th>
                                    <td><?php echo htmlspecialchars($equipo['tipo_equipo']); ?></td>
                                </tr>
                                <tr>
                                    <th>Marca:</th>
                                    <td><?php echo htmlspecialchars($equipo['marca'] ?: 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Modelo:</th>
                                    <td><?php echo htmlspecialchars($equipo['modelo'] ?: 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>N° Serie:</th>
                                    <td><?php echo htmlspecialchars($equipo['numero_serie'] ?: 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Estado:</th>
                                    <td>
                                        <?php 
                                        $estado = $equipo['persona_id'] ? 'PRESTADO' : 'DISPONIBLE';
                                        $badgeClass = $equipo['persona_id'] ? 'warning' : 'success';
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                            <?php echo $estado; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ubicación:</th>
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
                                <?php if ($equipo['persona_id']): ?>
                                <tr>
                                    <th>Asignado a:</th>
                                    <td>
                                        <a href="../personas/detalle.php?id=<?php echo $equipo['persona_id']; ?>">
                                            <?php echo htmlspecialchars($equipo['persona_nombre']); ?>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Fecha asignación:</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($equipo['fecha_asignacion'])); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5>Especificaciones</h5>
                                    <p><?php echo nl2br(htmlspecialchars($equipo['especificaciones'] ?: 'Sin especificaciones')); ?></p>
                                    
                                    <h5 class="mt-4">Observaciones</h5>
                                    <p><?php echo nl2br(htmlspecialchars($equipo['observaciones'] ?: 'Sin observaciones')); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($equipo['persona_id']): ?>
                    <div class="mt-4 text-center">
                        <a href="../movimientos/devolucion.php?equipo_id=<?php echo $equipo['id']; ?>" class="btn btn-warning btn-lg">
                            <i class="fas fa-undo-alt me-2"></i>Registrar Devolución
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="mt-4 text-center">
                        <a href="../movimientos/prestamo.php?equipo_id=<?php echo $equipo['id']; ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-hand-holding me-2"></i>Registrar Préstamo
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>