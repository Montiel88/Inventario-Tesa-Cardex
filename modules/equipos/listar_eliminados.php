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

// Compatibilidad: la columna eliminado_por puede no existir en algunas instalaciones
$check_fecha = $conn->query("SHOW COLUMNS FROM equipos LIKE 'fecha_eliminacion'");
if (!$check_fecha || $check_fecha->num_rows == 0) {
    die("Error: La columna fecha_eliminacion no existe en la tabla equipos.");
}

$check_eliminado_por = $conn->query("SHOW COLUMNS FROM equipos LIKE 'eliminado_por'");
$tiene_eliminado_por = $check_eliminado_por && $check_eliminado_por->num_rows > 0;

if ($tiene_eliminado_por) {
    $sql = "SELECT e.*, u.nombre as eliminado_por_nombre 
            FROM equipos e
            LEFT JOIN usuarios u ON e.eliminado_por = u.id
            WHERE e.fecha_eliminacion IS NOT NULL
            ORDER BY e.fecha_eliminacion DESC";
} else {
    $sql = "SELECT e.*, NULL as eliminado_por_nombre
            FROM equipos e
            WHERE e.fecha_eliminacion IS NOT NULL
            ORDER BY e.fecha_eliminacion DESC";
}
$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-trash-restore me-2"></i>Equipos Eliminados</h4>
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
                                        <th>Código</th>
                                        <th>Tipo</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Serie</th>
                                        <th>Fecha eliminación</th>
                                        <th>Eliminado por</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['codigo_barras']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tipo_equipo']); ?></td>
                                        <td><?php echo htmlspecialchars($row['marca']); ?></td>
                                        <td><?php echo htmlspecialchars($row['modelo']); ?></td>
                                        <td><?php echo htmlspecialchars($row['numero_serie'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_eliminacion'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['eliminado_por_nombre'] ?? 'Desconocido'); ?></td>
                                        <td>
                                            <a href="detalle_eliminado.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <!-- Botón para restaurar (puede abrir un modal) -->
                                            <button class="btn btn-sm btn-success" onclick="restaurarEquipo(<?php echo $row['id']; ?>)" title="Restaurar">
                                                <i class="fas fa-undo-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No hay equipos eliminados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para restaurar (simple, con destino a bodega) -->
<div class="modal fade" id="restaurarModal" tabindex="-1" aria-labelledby="restaurarModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="restaurarModalLabel">Restaurar equipo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="restaurar.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="equipo_id" id="modalEquipoId" value="">
                    <p>¿Está seguro de restaurar este equipo? Se colocará como disponible en bodega.</p>
                    <div class="mb-3">
                        <label class="form-label">Observaciones (opcional)</label>
                        <textarea name="observaciones" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Restaurar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function restaurarEquipo(id) {
    document.getElementById('modalEquipoId').value = id;
    var modal = new bootstrap.Modal(document.getElementById('restaurarModal'));
    modal.show();
}
</script>

<?php include '../../includes/footer.php'; ?>
