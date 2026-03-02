<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// No es necesario verificar roles para solo visualización
require_once '../../config/database.php';
include '../../includes/header.php';

$sql = "SELECT p.id, p.nombres, p.cargo, 
               COUNT(e.id) as total_equipos,
               GROUP_CONCAT(CONCAT(e.tipo_equipo, ' (', e.marca, ')') SEPARATOR ', ') as equipos
        FROM personas p
        LEFT JOIN asignaciones a ON p.id = a.persona_id AND a.fecha_devolucion IS NULL
        LEFT JOIN equipos e ON a.equipo_id = e.id
        GROUP BY p.id
        ORDER BY p.nombres";

$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Equipos Asignados por Persona</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Persona</th>
                            <th>Cargo</th>
                            <th>Total Equipos</th>
                            <th>Equipos Asignados</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nombres']); ?></td>
                            <td><?php echo htmlspecialchars($row['cargo']); ?></td>
                            <td><?php echo $row['total_equipos']; ?></td>
                            <td><?php echo $row['equipos'] ?? 'Ninguno'; ?></td>
                            <td>
                                <a href="../personas/detalle.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Ver detalle
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>