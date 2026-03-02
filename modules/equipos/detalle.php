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

// Obtener datos del equipo
$sql = "SELECT e.*, 
               a.persona_id, 
               p.nombres as persona_nombre,
               a.fecha_asignacion
        FROM equipos e
        LEFT JOIN asignaciones a ON e.id = a.equipo_id AND a.fecha_devolucion IS NULL
        LEFT JOIN personas p ON a.persona_id = p.id
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
                                    <td><?php echo $equipo['codigo_barras']; ?></td>
                                </tr>
                                <tr>
                                    <th>Tipo:</th>
                                    <td><?php echo $equipo['tipo_equipo']; ?></td>
                                </tr>
                                <tr>
                                    <th>Marca:</th>
                                    <td><?php echo $equipo['marca'] ?: 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <th>Modelo:</th>
                                    <td><?php echo $equipo['modelo'] ?: 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <th>N° Serie:</th>
                                    <td><?php echo $equipo['numero_serie'] ?: 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <th>Estado:</th>
                                    <td>
                                        <span class="badge bg-<?php echo $equipo['persona_id'] ? 'warning' : 'success'; ?>">
                                            <?php echo $equipo['persona_id'] ? 'PRESTADO' : 'DISPONIBLE'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php if ($equipo['persona_id']): ?>
                                <tr>
                                    <th>Asignado a:</th>
                                    <td>
                                        <a href="../personas/detalle.php?id=<?php echo $equipo['persona_id']; ?>">
                                            <?php echo $equipo['persona_nombre']; ?>
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
                                    <p><?php echo nl2br($equipo['especificaciones'] ?: 'Sin especificaciones'); ?></p>
                                    
                                    <h5 class="mt-4">Observaciones</h5>
                                    <p><?php echo nl2br($equipo['observaciones'] ?: 'Sin observaciones'); ?></p>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>