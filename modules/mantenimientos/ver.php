<?php
session_start();
require_once '../../config/permisos.php';
require_once '../../config/database.php';
verificarSesion();

include '../../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);
$es_admin = ($_SESSION['user_rol'] == 1);

// Obtener datos del mantenimiento
$sql = "SELECT m.*, 
               e.tipo_equipo, e.marca, e.modelo, e.numero_serie, e.codigo_barras, e.estado as estado_equipo,
               u.nombre as usuario_registro
        FROM mantenimientos m
        JOIN equipos e ON m.equipo_id = e.id
        LEFT JOIN usuarios u ON m.created_by = u.id
        WHERE m.id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}

$row = $result->fetch_assoc();
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Detalle de Mantenimiento #<?php echo $row['id']; ?></h4>
                    <div>
                        <a href="listar.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                        <?php if ($es_admin && $row['estado'] == 'en_proceso'): ?>
                            <a href="finalizar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-check me-1"></i>Finalizar
                            </a>
                            <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit me-1"></i>Editar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 40%;">ID Mantenimiento</th>
                                    <td>#<?php echo $row['id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Fecha ingreso</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_ingreso'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Fecha salida</th>
                                    <td>
                                        <?php echo $row['fecha_salida'] ? date('d/m/Y H:i', strtotime($row['fecha_salida'])) : '<span class="badge bg-warning">En proceso</span>'; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tipo</th>
                                    <td>
                                        <span class="badge bg-<?php echo $row['tipo_mantenimiento'] == 'preventivo' ? 'info' : 'warning'; ?>">
                                            <?php echo strtoupper($row['tipo_mantenimiento']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Estado</th>
                                    <td>
                                        <?php
                                        $badge = match($row['estado']) {
                                            'en_proceso' => 'warning',
                                            'finalizado' => 'success',
                                            'cancelado' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $badge; ?>">
                                            <?php echo str_replace('_', ' ', $row['estado']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Registrado por</th>
                                    <td><?php echo $row['usuario_registro'] ?? 'Sistema'; ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 40%;">Equipo</th>
                                    <td>
                                        <strong><?php echo $row['tipo_equipo'] . ' ' . $row['marca'] . ' ' . $row['modelo']; ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Código</th>
                                    <td><?php echo $row['codigo_barras']; ?></td>
                                </tr>
                                <tr>
                                    <th>N° Serie</th>
                                    <td><?php echo $row['numero_serie'] ?: 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <th>Estado actual</th>
                                    <td>
                                        <span class="badge bg-<?php echo $row['estado_equipo'] == 'Disponible' ? 'success' : 'warning'; ?>">
                                            <?php echo $row['estado_equipo']; ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5>Descripción del mantenimiento</h5>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br($row['descripcion']); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($row['tecnico']) || !empty($row['proveedor']) || !empty($row['costo'])): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5>Información adicional</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php if (!empty($row['tecnico'])): ?>
                                <div class="col-md-4">
                                    <strong>Técnico:</strong> <?php echo $row['tecnico']; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($row['proveedor'])): ?>
                                <div class="col-md-4">
                                    <strong>Proveedor:</strong> <?php echo $row['proveedor']; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($row['costo'])): ?>
                                <div class="col-md-4">
                                    <strong>Costo:</strong> $<?php echo number_format($row['costo'], 2); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($row['observaciones'])): ?>
                            <div class="mt-3">
                                <strong>Observaciones:</strong>
                                <p><?php echo nl2br($row['observaciones']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>