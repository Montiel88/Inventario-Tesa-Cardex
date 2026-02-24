<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
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

// Obtener equipos asignados a esta persona
$sql_equipos = "SELECT e.*, a.fecha_asignacion, a.observaciones as obs_asignacion
                FROM equipos e
                JOIN asignaciones a ON e.id = a.equipo_id
                WHERE a.persona_id = $id AND a.fecha_devolucion IS NULL
                ORDER BY a.fecha_asignacion DESC";
$equipos_asignados = $conn->query($sql_equipos);

// Obtener historial de movimientos de esta persona
$sql_historial = "SELECT m.*, e.tipo_equipo, e.codigo_barras
                  FROM movimientos m
                  JOIN equipos e ON m.equipo_id = e.id
                  WHERE m.persona_id = $id
                  ORDER BY m.fecha_movimiento DESC
                  LIMIT 20";
$historial = $conn->query($sql_historial);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-user me-2"></i>Detalle de Persona</h4>
                    <div>
                        <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Editar Persona
                        </a>
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Datos de la persona -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Cédula</th>
                                    <td><?php echo $persona['cedula']; ?></td>
                                </tr>
                                <tr>
                                    <th>Nombres</th>
                                    <td><?php echo $persona['nombres']; ?></td>
                                </tr>
                                <tr>
                                    <th>Cargo</th>
                                    <td><?php echo $persona['cargo']; ?></td>
                                </tr>
                                <tr>
                                    <th>Correo</th>
                                    <td><?php echo $persona['correo'] ?: 'No registrado'; ?></td>
                                </tr>
                                <tr>
                                    <th>Teléfono</th>
                                    <td><?php echo $persona['telefono'] ?: 'No registrado'; ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3><?php echo $equipos_asignados->num_rows; ?></h3>
                                    <p>Equipos asignados actualmente</p>
                                    <a href="../asignaciones/cargar_equipos.php?persona_id=<?php echo $id; ?>" class="btn btn-success">
                                        <i class="fas fa-plus-circle me-2"></i>Asignar nuevo equipo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Equipos actuales -->
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-laptop me-2"></i>Equipos Asignados Actualmente</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($equipos_asignados->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Tipo</th>
                                                <th>Marca/Modelo</th>
                                                <th>Serie</th>
                                                <th>Fecha Asignación</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($eq = $equipos_asignados->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $eq['codigo_barras']; ?></td>
                                                <td><?php echo $eq['tipo_equipo']; ?></td>
                                                <td><?php echo $eq['marca'] . ' ' . $eq['modelo']; ?></td>
                                                <td><?php echo $eq['numero_serie'] ?: 'N/A'; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($eq['fecha_asignacion'])); ?></td>
                                                <td>
                                                    <a href="../movimientos/devolucion.php?equipo_id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-warning" title="Registrar devolución">
                                                        <i class="fas fa-undo-alt"></i>
                                                    </a>
                                                    <a href="../equipos/detalle.php?id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-info" title="Ver equipo">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-4">
                                    <i class="fas fa-info-circle fa-2x mb-3"></i><br>
                                    Esta persona no tiene equipos asignados actualmente.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Historial de movimientos -->
                    <div class="card mt-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Movimientos</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($historial->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while($h = $historial->fetch_assoc()): 
                                        $clase = $h['tipo_movimiento'] == 'ASIGNACION' ? 'success' : 
                                                ($h['tipo_movimiento'] == 'DEVOLUCION' ? 'warning' : 'info');
                                    ?>
                                        <div class="list-group-item list-group-item-<?php echo $clase; ?>">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong><?php echo $h['tipo_equipo']; ?></strong>
                                                    <span class="badge bg-<?php echo $clase; ?> ms-2"><?php echo $h['tipo_movimiento']; ?></span>
                                                    <small class="text-muted ms-2"><?php echo $h['codigo_barras']; ?></small>
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
                                <p class="text-muted">No hay historial de movimientos</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>