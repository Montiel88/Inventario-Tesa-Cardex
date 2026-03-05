<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Solo administradores pueden ver eliminados
if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=Acceso denegado');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

// Consultar personas eliminadas
$sql = "SELECT p.*, u.nombre as eliminado_por_nombre 
        FROM personas p
        LEFT JOIN usuarios u ON p.eliminado_por = u.id
        WHERE p.fecha_eliminacion IS NOT NULL
        ORDER BY p.fecha_eliminacion DESC";
$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-trash-restore me-2"></i>Personas Eliminadas</h4>
                    <a href="listar.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Volver al listado activo
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['mensaje'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['mensaje']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <?php endif; ?>

                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cédula</th>
                                        <th>Nombres</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Fecha eliminación</th>
                                        <th>Eliminado por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['cedula']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nombres']); ?></td>
                                        <td><?php echo htmlspecialchars($row['correo']); ?></td>
                                        <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_eliminacion'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['eliminado_por_nombre'] ?? 'Desconocido'); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No hay personas eliminadas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>