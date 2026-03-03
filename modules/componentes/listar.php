<?php
session_start();
require_once '../../config/database.php';
include '../../includes/header.php';

// Verificar rol (1 = admin, 2 = lector)
$es_admin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1;

// Consulta para listar componentes con información del equipo
$sql = "SELECT c.*, e.tipo_equipo, e.codigo_barras, e.id as equipo_id
        FROM componentes c
        JOIN equipos e ON c.equipo_id = e.id
        ORDER BY e.tipo_equipo, c.tipo, c.nombre_componente";
$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-microchip me-2"></i>Listado de Componentes</h4>
                    <?php if ($es_admin): ?>
                    <a href="agregar.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Componente
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
                                        <th>Tipo</th>
                                        <th>Nombre</th>
                                        <th>Marca/Modelo</th>
                                        <th>N° Serie</th>
                                        <th>Estado</th>
                                        <th>Instalación</th>
                                        <?php if ($es_admin): ?>
                                        <th>Acciones</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="ID"><?php echo $row['id']; ?></td>
                                        <td data-label="Equipo">
                                            <a href="../equipos/detalle.php?id=<?php echo $row['equipo_id']; ?>">
                                                <?php echo htmlspecialchars($row['tipo_equipo']); ?>
                                                <br><small><?php echo htmlspecialchars($row['codigo_barras']); ?></small>
                                            </a>
                                        </td>
                                        <td data-label="Tipo"><?php echo htmlspecialchars($row['tipo']); ?></td>
                                        <td data-label="Nombre"><?php echo htmlspecialchars($row['nombre_componente']); ?></td>
                                        <td data-label="Marca/Modelo"><?php echo htmlspecialchars($row['marca'] . ' ' . $row['modelo']); ?></td>
                                        <td data-label="N° Serie"><?php echo htmlspecialchars($row['numero_serie'] ?? 'N/A'); ?></td>
                                        <td data-label="Estado">
                                            <?php
                                            $badge = match($row['estado']) {
                                                'Bueno' => 'success',
                                                'Regular' => 'warning',
                                                'Malo', 'Por reemplazar' => 'danger',
                                                default => 'secondary'
                                            };
                                            echo "<span class='badge bg-$badge'>" . htmlspecialchars($row['estado']) . "</span>";
                                            ?>
                                        </td>
                                        <td data-label="Instalación"><?php echo $row['fecha_instalacion'] ? date('d/m/Y', strtotime($row['fecha_instalacion'])) : '-'; ?></td>
                                        <?php if ($es_admin): ?>
                                        <td data-label="Acciones" class="text-center">
                                            <a href="trazabilidad_componente.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Ver historial">
                                                <i class="fas fa-history"></i>
                                            </a>
                                            <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="eliminar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este componente? Esta acción no se puede deshacer.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center py-4">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <h5>No hay componentes registrados</h5>
                            <?php if ($es_admin): ?>
                            <a href="agregar.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus-circle me-2"></i>Agregar primer componente
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .table thead {
        display: none !important;
    }
    .table tbody tr {
        display: block !important;
        margin-bottom: 20px !important;
        border: 1px solid #e0e0e0 !important;
        border-radius: 15px !important;
        padding: 15px !important;
        background: white !important;
    }
    .table tbody td {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 8px 5px !important;
        border: none !important;
        border-bottom: 1px dashed #eee !important;
        font-size: 13px !important;
    }
    .table tbody td:last-child {
        border-bottom: none !important;
    }
    .table tbody td:before {
        content: attr(data-label) !important;
        font-weight: 700 !important;
        color: #5a2d8c !important;
        margin-right: 10px !important;
        min-width: 80px !important;
        font-size: 12px !important;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>