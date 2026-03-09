<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar roles si es necesario
$es_admin = ($_SESSION['user_rol'] == 'admin');

// Solo admin puede acceder a ciertas funciones
if (!$es_admin && strpos($_SERVER['PHP_SELF'], 'eliminar.php') !== false) {
    header('Location: dashboard.php?error=No tienes permisos');
    exit();
}
?>
<?php
require_once '../../config/database.php';
include '../../includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos de la persona
$sql = "SELECT * FROM personas WHERE id = $id";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}

$persona = $result->fetch_assoc();

// Obtener equipos asignados a esta persona
$sql_equipos = "SELECT e.*, a.fecha_asignacion 
                FROM equipos e 
                JOIN asignaciones a ON e.id = a.equipo_id 
                WHERE a.persona_id = $id AND a.fecha_devolucion IS NULL";
$equipos = $conn->query($sql_equipos);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-user me-2"></i>Detalle de Persona</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Cédula:</th>
                                    <td><?php echo $persona['cedula']; ?></td>
                                </tr>
                                <tr>
                                    <th>Nombres:</th>
                                    <td><?php echo $persona['nombres']; ?></td>
                                </tr>
                                <tr>
                                    <th>Correo:</th>
                                    <td><?php echo $persona['correo'] ?: 'No registrado'; ?></td>
                                </tr>
                                <tr>
                                    <th>Cargo:</th>
                                    <td><?php echo $persona['cargo']; ?></td>
                                </tr>
                                <tr>
                                    <th>Teléfono:</th>
                                    <td><?php echo $persona['telefono'] ?: 'No registrado'; ?></td>
                                </tr>
                            </table>
                            
                            <div class="mt-3">
                                <a href="editar.php?id=<?php echo $persona['id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-edit me-2"></i>Editar
                                </a>
                                <a href="listar.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Volver
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Equipos asignados actualmente:</h5>
                            <?php if ($equipos && $equipos->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while($eq = $equipos->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between">
                                                <strong><?php echo $eq['tipo_equipo']; ?></strong>
                                                <small><?php echo $eq['codigo_barras']; ?></small>
                                            </div>
                                            <p><?php echo $eq['marca'] . ' ' . $eq['modelo']; ?></p>
                                            <small>Desde: <?php echo $eq['fecha_asignacion']; ?></small>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No tiene equipos asignados actualmente</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>