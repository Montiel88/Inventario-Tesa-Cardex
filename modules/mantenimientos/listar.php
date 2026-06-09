<?php
session_start();
require_once '../../config/permisos.php';
require_once '../../config/database.php';
verificarSesion();

$es_admin = ($_SESSION['user_rol'] == 1);
include '../../includes/header.php';

// Obtener lista de mantenimientos
$sql = "SELECT m.*, e.tipo_equipo, e.codigo_barras, e.marca, e.modelo 
        FROM mantenimientos m
        JOIN equipos e ON m.equipo_id = e.id
        ORDER BY m.fecha_ingreso DESC";
$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-tools me-2"></i>Mantenimientos de Equipos</h4>
            <?php if ($es_admin): ?>
            <a href="agregar.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Nuevo Mantenimiento
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Equipo</th>
                                <th>F. Ingreso</th>
                                <th>F. Salida</th>
                                <th>Tipo</th>
                                <th>Técnico</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <?php echo $row['tipo_equipo'] . ' - ' . $row['marca'] . ' ' . $row['modelo']; ?>
                                    <br><small><?php echo $row['codigo_barras']; ?></small>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_ingreso'])); ?></td>
                                <td><?php echo $row['fecha_salida'] ? date('d/m/Y H:i', strtotime($row['fecha_salida'])) : '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $row['tipo_mantenimiento'] == 'preventivo' ? 'info' : 'warning'; ?>">
                                        <?php echo strtoupper($row['tipo_mantenimiento']); ?>
                                    </span>
                                </td>
                                <td><?php echo $row['tecnico'] ?: '-'; ?></td>
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
                                <td>
                                    <a href="ver.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($es_admin && $row['estado'] == 'en_proceso'): ?>
                                    <a href="finalizar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success" title="Finalizar mantenimiento">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4">No hay registros de mantenimiento</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>