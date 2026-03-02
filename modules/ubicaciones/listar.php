<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
require_once '../../config/database.php';
include '../../includes/header.php';

// Consulta con JOIN para obtener el nombre del responsable
$sql = "SELECT u.*, p.nombres as responsable_nombre 
        FROM ubicaciones u
        LEFT JOIN personas p ON u.responsable_id = p.id
        ORDER BY u.tipo, u.nombre";
$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-building me-2"></i>Gestión de Salones y Ubicaciones</h4>
        </div>
        <div class="card-body">
            <a href="agregar.php" class="btn btn-primary mb-3">
                <i class="fas fa-plus-circle me-2"></i>Nueva Ubicación
            </a>

            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Responsable</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['codigo_ubicacion'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['nombre'] ?? ''); ?></td>
                                <td>
                                    <?php 
                                    $tipo = $row['tipo'] ?? '';
                                    $badgeClass = match($tipo) {
                                        'salon' => 'primary',
                                        'laboratorio' => 'success',
                                        'biblioteca' => 'info',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($tipo); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['responsable_nombre'] ?? 'Sin asignar'); ?></td>
                                <td><?php echo htmlspecialchars($row['descripcion'] ?? ''); ?></td>
                                <td>
                                    <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Editar ubicación">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../equipos/listar.php?ubicacion_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Ver equipos en esta ubicación">
                                        <i class="fas fa-laptop"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No hay ubicaciones registradas</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>