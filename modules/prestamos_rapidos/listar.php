<?php
session_start();
require_once '../../config/permisos.php';
require_once '../../config/database.php';
verificarSesion();

include '../../includes/header.php';

$es_admin = ($_SESSION['user_rol'] == 1);

// Obtener préstamos activos
$sql = "SELECT p.*, 
               e.codigo_barras, e.tipo_equipo, e.marca, e.modelo,
               per.nombres as persona_nombre, per.cedula,
               u.nombre as usuario_registro
        FROM prestamos_rapidos p
        JOIN equipos e ON p.equipo_id = e.id
        JOIN personas per ON p.persona_id = per.id
        LEFT JOIN usuarios u ON p.created_by = u.id
        ORDER BY 
            CASE WHEN p.estado = 'activo' THEN 1 
                 WHEN p.estado = 'vencido' THEN 2 
                 ELSE 3 END,
            p.fecha_estimada_devolucion ASC";
$result = $conn->query($sql);

// Contar vencidos para alerta
$vencidos = $conn->query("SELECT COUNT(*) as total FROM prestamos_rapidos 
                          WHERE estado = 'activo' AND fecha_estimada_devolucion < CURDATE()");
$total_vencidos = $vencidos->fetch_assoc()['total'];
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            
            <?php if ($total_vencidos > 0): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>¡Atención!</strong> Hay <?php echo $total_vencidos; ?> préstamo(s) vencido(s).
                <a href="vencidos.php" class="alert-link">Ver detalles</a>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-hand-holding me-2"></i>Préstamos Rápidos</h4>
                    <div>
                        <a href="registrar.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus-circle me-1"></i>Nuevo Préstamo
                        </a>
                        <a href="vencidos.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-clock me-1"></i>Vencidos
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Persona</th>
                                        <th>Equipo</th>
                                        <th>Fecha Préstamo</th>
                                        <th>Fecha Estimada</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): 
                                        $hoy = date('Y-m-d');
                                        $estado = $row['estado'];
                                        $clase_estado = 'success';
                                        $texto_estado = 'Devuelto';
                                        
                                        if ($estado == 'activo') {
                                            if ($row['fecha_estimada_devolucion'] < $hoy) {
                                                $clase_estado = 'danger';
                                                $texto_estado = 'VENCIDO';
                                            } else {
                                                $clase_estado = 'warning';
                                                $texto_estado = 'Activo';
                                            }
                                        } elseif ($estado == 'vencido') {
                                            $clase_estado = 'danger';
                                            $texto_estado = 'Vencido';
                                        }
                                    ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td>
                                            <?php echo $row['persona_nombre']; ?>
                                            <br><small><?php echo $row['cedula']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo $row['tipo_equipo'] . ' ' . $row['marca'] . ' ' . $row['modelo']; ?>
                                            <br><small><?php echo $row['codigo_barras']; ?></small>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_prestamo'])); ?></td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($row['fecha_estimada_devolucion'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $clase_estado; ?>">
                                                <?php echo $texto_estado; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['estado'] == 'activo'): ?>
                                                <a href="devolver.php?id=<?php echo $row['id']; ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-undo-alt"></i> Devolver
                                                </a>
                                            <?php endif; ?>
                                            <a href="#" class="btn btn-sm btn-info" 
                                               onclick="verDetalle(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No hay préstamos rápidos registrados</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalle -->
<div class="modal fade" id="detalleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleContent">
            </div>
        </div>
    </div>
</div>

<script>
function verDetalle(data) {
    let html = `
        <table class="table table-bordered">
            <tr><th>ID</th><td>#${data.id}</td></tr>
            <tr><th>Persona</th><td>${data.persona_nombre}<br><small>${data.cedula}</small></td></tr>
            <tr><th>Equipo</th><td>${data.tipo_equipo} ${data.marca} ${data.modelo}<br><small>${data.codigo_barras}</small></td></tr>
            <tr><th>Fecha préstamo</th><td>${new Date(data.fecha_prestamo).toLocaleString()}</td></tr>
            <tr><th>Fecha estimada</th><td>${new Date(data.fecha_estimada_devolucion).toLocaleDateString()}</td></tr>
            <tr><th>Observaciones</th><td>${data.observaciones || '-'}</td></tr>
            <tr><th>Registrado por</th><td>${data.usuario_registro || 'Sistema'}</td></tr>
        </table>
    `;
    document.getElementById('detalleContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('detalleModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>