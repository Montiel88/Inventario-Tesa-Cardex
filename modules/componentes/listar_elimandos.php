<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=Acceso denegado');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

$sql = "SELECT c.*, u.nombre as eliminado_por_nombre 
        FROM componentes c
        LEFT JOIN usuarios u ON c.eliminado_por = u.id
        WHERE c.fecha_eliminacion IS NOT NULL
        ORDER BY c.fecha_eliminacion DESC";
$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-trash-restore me-2"></i>Componentes Eliminados</h4>
                    <a href="listar.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Volver al listado activo
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Nombre</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Serie</th>
                                        <th>Fecha eliminación</th>
                                        <th>Eliminado por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nombre_componente']); ?></td>
                                        <td><?php echo htmlspecialchars($row['marca']); ?></td>
                                        <td><?php echo htmlspecialchars($row['modelo']); ?></td>
                                        <td><?php echo htmlspecialchars($row['numero_serie'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_eliminacion'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['eliminado_por_nombre'] ?? 'Desconocido'); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No hay componentes eliminados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>