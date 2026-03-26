<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

$es_admin = ($_SESSION['user_rol'] == 1);

// ============================================
// CONSULTA PRINCIPAL (excluye eliminados)
// ============================================
$sql = "SELECT c.*,
               (SELECT COUNT(*) FROM movimientos_componentes mc 
                WHERE mc.componente_id = c.id 
                  AND mc.tipo_movimiento = 'ASIGNACION'
                  AND NOT EXISTS (
                      SELECT 1 FROM movimientos_componentes mc2 
                      WHERE mc2.componente_id = mc.componente_id 
                        AND mc2.tipo_movimiento = 'DEVOLUCION'
                        AND mc2.fecha_movimiento > mc.fecha_movimiento
                  )
               ) as asignado_actualmente
        FROM componentes c
        WHERE c.fecha_eliminacion IS NULL
        ORDER BY c.tipo, c.nombre_componente";
$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-microchip me-2"></i>Inventario de Componentes</h4>
            <?php if ($es_admin): ?>
            <a href="agregar.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Agregar Componente
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['mensaje'])): ?>
                <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($_GET['mensaje']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($_GET['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Nombre</th>
                            <th>Marca/Modelo</th>
                            <th>Serie</th>
                            <th>Estado</th>
                            <?php if ($es_admin): ?><th>Acciones</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($c = $result->fetch_assoc()): 
                            $disponible = ($c['asignado_actualmente'] == 0);
                        ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td><?php echo htmlspecialchars($c['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($c['nombre_componente']); ?></td>
                            <td><?php echo htmlspecialchars($c['marca'] . ' ' . $c['modelo']); ?></td>
                            <td><?php echo htmlspecialchars($c['numero_serie'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($disponible): ?>
                                    <span class="badge bg-success">Disponible</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Asignado</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($es_admin): ?>
                            <td>
                                <?php if ($disponible): ?>
                                    <a href="asignar.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-success" title="Asignar a persona">
                                        <i class="fas fa-hand-holding"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="devolver.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-warning" title="Registrar devolución">
                                        <i class="fas fa-undo-alt"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="detalle.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($disponible): ?>
                                    <a href="eliminar.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar componente" onclick="return confirm('¿Estás seguro de eliminar este componente?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="btn btn-sm btn-secondary disabled" title="No se puede eliminar porque está asignado">
                                        <i class="fas fa-trash"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Total de componentes activos: <strong><?php echo $result->num_rows; ?></strong>
                <?php if (!$es_admin): ?>
                    <span class="ms-3 badge bg-success">Modo lectura</span>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <p class="text-center text-muted">No hay componentes activos registrados.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bootstrap JS (para tooltips - YA INCLUIDO EN EL HEADER) -->
<script>
// Inicializar tooltips con la versión de Bootstrap cargada en el header
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>

<?php include '../../includes/footer.php'; ?>