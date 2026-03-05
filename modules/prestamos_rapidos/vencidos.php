<?php
session_start();
require_once '../../config/permisos.php';
require_once '../../config/database.php';
verificarSesion();

include '../../includes/header.php';

// Actualizar estado de vencidos automáticamente
$conn->query("UPDATE prestamos_rapidos 
              SET estado = 'vencido' 
              WHERE estado = 'activo' AND fecha_estimada_devolucion < CURDATE()");

// Obtener préstamos vencidos
$sql = "SELECT p.*, 
               e.codigo_barras, e.tipo_equipo, e.marca, e.modelo,
               per.nombres as persona_nombre, per.cedula, per.telefono
        FROM prestamos_rapidos p
        JOIN equipos e ON p.equipo_id = e.id
        JOIN personas per ON p.persona_id = per.id
        WHERE p.estado = 'vencido' OR (p.estado = 'activo' AND p.fecha_estimada_devolucion < CURDATE())
        ORDER BY p.fecha_estimada_devolucion ASC";
$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Préstamos Vencidos</h4>
        </div>
        <div class="card-body">
            
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Persona</th>
                                <th>Equipo</th>
                                <th>Fecha Préstamo</th>
                                <th>Fecha Estimada</th>
                                <th>Días vencido</th>
                                <th>Contacto</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): 
                                $fecha_estimada = new DateTime($row['fecha_estimada_devolucion']);
                                $hoy = new DateTime();
                                $dias_vencido = $hoy->diff($fecha_estimada)->days;
                            ?>
                            <tr>
                                <td><?php echo $row['persona_nombre']; ?></td>
                                <td><?php echo $row['tipo_equipo'] . ' ' . $row['marca'] . ' ' . $row['modelo']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['fecha_prestamo'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['fecha_estimada_devolucion'])); ?></td>
                                <td><span class="badge bg-danger"><?php echo $dias_vencido; ?> días</span></td>
                                <td><?php echo $row['telefono'] ?: 'Sin teléfono'; ?></td>
                                <td>
                                    <a href="devolver.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-undo-alt"></i> Devolver
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4">No hay préstamos vencidos</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>