<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /Inventario-Tesa-Cardex/login.php');
    exit();
}

$es_admin = ($_SESSION['user_rol'] == 1);

require_once '../../config/database.php';
include '../../includes/header.php';

if (!$conn) {
    die("Error de conexión a la base de datos");
}

$sql = "SELECT u.*, p.nombres as responsable_nombre
        FROM ubicaciones u
        LEFT JOIN personas p ON u.responsable_id = p.id
        ORDER BY u.tipo, u.nombre";
$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-door-open me-2"></i>Listado de Salones
                    </h4>

                    <?php if ($es_admin): ?>
                    <a href="agregar.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Salón
                    </a>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Responsable</th>
                                        <th>Descripción</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['codigo_ubicacion'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['nombre'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                            $tipo = $row['tipo'] ?? '';
                                            $badgeClass = match ($tipo) {
                                                'salon' => 'primary',
                                                'laboratorio' => 'success',
                                                'biblioteca' => 'info',
                                                'oficina' => 'warning',
                                                'bodega' => 'secondary',
                                                default => 'dark'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                                <?php echo htmlspecialchars(ucfirst($tipo)); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['responsable_nombre'] ?? 'Sin asignar'); ?></td>
                                        <td><?php echo htmlspecialchars($row['descripcion'] ?? ''); ?></td>
                                        <td class="text-center">
                                            <a href="editar.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-warning" title="Editar salón">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>No hay salones registrados.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
