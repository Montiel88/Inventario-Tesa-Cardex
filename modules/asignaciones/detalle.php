<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

$es_admin = ($_SESSION['user_rol'] == 1);

require_once '../../config/database.php';
include '../../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos de la asignación
$sql = "SELECT a.*, 
               e.codigo_barras, e.tipo_equipo, e.marca, e.modelo, e.numero_serie, e.especificaciones,
               p.id as persona_id, p.nombres as persona_nombre, p.cedula, p.cargo, p.correo, p.telefono,
               DATEDIFF(NOW(), a.fecha_asignacion) as dias_asignado
        FROM asignaciones a
        JOIN equipos e ON a.equipo_id = e.id
        JOIN personas p ON a.persona_id = p.id
        WHERE a.id = $id";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: listar.php?error=Asignación no encontrada');
    exit();
}

$asignacion = $result->fetch_assoc();
$es_activa = is_null($asignacion['fecha_devolucion']);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-hand-holding me-2"></i>Detalle de Asignación #<?php echo $id; ?></h4>
                    <div>
                        <a href="listar.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                        <?php if ($es_admin && $es_activa): ?>
                            <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit me-1"></i>Editar
                            </a>
                            <a href="../movimientos/devolucion.php?equipo_id=<?php echo $asignacion['equipo_id']; ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-undo-alt me-1"></i>Registrar Devolución
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-body">
                    
                    <!-- Información de la asignación -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Persona Asignada</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr><th>Nombre:</th><td><strong><?php echo htmlspecialchars($asignacion['persona_nombre']); ?></strong></td></tr>
                                        <tr><th>Cédula:</th><td><?php echo $asignacion['cedula']; ?></td></tr>
                                        <tr><th>Cargo:</th><td><?php echo $asignacion['cargo']; ?></td></tr>
                                        <tr><th>Correo:</th><td><?php echo $asignacion['correo'] ?: 'No registrado'; ?></td></tr>
                                        <tr><th>Teléfono:</th><td><?php echo $asignacion['telefono'] ?: 'No registrado'; ?></td></tr>
                                    </table>
                                    <a href="/inventario_ti/modules/personas/detalle.php?id=<?php echo $asignacion['persona_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-user me-1"></i>Ver Perfil Completo
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-laptop me-2"></i>Equipo Asignado</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr><th>Código:</th><td><span class="badge bg-secondary"><?php echo $asignacion['codigo_barras']; ?></span></td></tr>
                                        <tr><th>Tipo:</th><td><?php echo $asignacion['tipo_equipo']; ?></td></tr>
                                        <tr><th>Marca/Modelo:</th><td><?php echo $asignacion['marca'] . ' ' . $asignacion['modelo']; ?></td></tr>
                                        <tr><th>N° Serie:</th><td><?php echo $asignacion['numero_serie'] ?: 'N/A'; ?></td></tr>
                                        <tr><th>Especificaciones:</th><td><?php echo nl2br($asignacion['especificaciones'] ?: 'No especificadas'); ?></td></tr>
                                    </table>
                                    <a href="/inventario_ti/modules/equipos/detalle.php?id=<?php echo $asignacion['equipo_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-laptop me-1"></i>Ver Detalle del Equipo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Datos de la asignación -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Datos de la Asignación</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr><th>Fecha de Asignación:</th><td><?php echo date('d/m/Y H:i', strtotime($asignacion['fecha_asignacion'])); ?></td></tr>
                                        <tr><th>Días Asignado:</th>
                                            <td>
                                                <?php if ($es_activa): ?>
                                                    <span class="badge <?php echo $asignacion['dias_asignado'] >= 30 ? 'bg-danger' : ($asignacion['dias_asignado'] >= 25 ? 'bg-warning' : 'bg-success'); ?>">
                                                        <?php echo $asignacion['dias_asignado']; ?> días
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Devuelto</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if (!$es_activa): ?>
                                        <tr><th>Fecha Devolución:</th><td><?php echo date('d/m/Y H:i', strtotime($asignacion['fecha_devolucion'])); ?></td></tr>
                                        <?php endif; ?>
                                        <tr><th>Observaciones:</th><td><?php echo nl2br($asignacion['observaciones'] ?: 'Ninguna'); ?></td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-file-pdf me-2"></i>Acciones Rápidas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="/inventario_ti/api/generar_acta_entrega.php?persona_id=<?php echo $asignacion['persona_id']; ?>" target="_blank" class="btn btn-outline-primary">
                                            <i class="fas fa-file-pdf me-2"></i>Acta de Entrega
                                        </a>
                                        <a href="/inventario_ti/api/generar_acta_devolucion.php?persona_id=<?php echo $asignacion['persona_id']; ?>" target="_blank" class="btn btn-outline-warning">
                                            <i class="fas fa-file-pdf me-2"></i>Acta de Devolución
                                        </a>
                                        <a href="/inventario_ti/api/generar_descargo.php?persona_id=<?php echo $asignacion['persona_id']; ?>" target="_blank" class="btn btn-outline-info">
                                            <i class="fas fa-file-signature me-2"></i>Descargo de Responsabilidad
                                        </a>
                                        <?php if ($es_activa && $es_admin): ?>
                                        <hr>
                                        <a href="../movimientos/devolucion.php?equipo_id=<?php echo $asignacion['equipo_id']; ?>" class="btn btn-success">
                                            <i class="fas fa-undo-alt me-2"></i>Registrar Devolución
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>