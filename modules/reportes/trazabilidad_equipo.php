<?php
session_start();
require_once '../../config/database.php';
include '../../includes/header.php';

// Verificar rol (1 = admin, 2 = lector)
$es_admin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1;

// Obtener ID del equipo
$equipo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($equipo_id <= 0) {
    echo "<div class='alert alert-danger'>ID de equipo no válido</div>";
    include '../../includes/footer.php';
    exit();
}

// Datos del equipo
$sql_equipo = "SELECT * FROM equipos WHERE id = $equipo_id";
$result_equipo = $conn->query($sql_equipo);
if ($result_equipo && $result_equipo->num_rows > 0) {
    $equipo = $result_equipo->fetch_assoc();
} else {
    echo "<div class='alert alert-danger'>Equipo no encontrado</div>";
    include '../../includes/footer.php';
    exit();
}

// Historial completo del equipo
$sql_historial = "SELECT m.*, p.nombres as persona_nombre, p.cedula
                  FROM movimientos m
                  LEFT JOIN personas p ON m.persona_id = p.id
                  WHERE m.equipo_id = $equipo_id
                  ORDER BY m.fecha_movimiento DESC";
$historial = $conn->query($sql_historial);

// Estado actual (préstamo activo)
$sql_actual = "SELECT p.nombres, a.fecha_asignacion 
               FROM asignaciones a
               JOIN personas p ON a.persona_id = p.id
               WHERE a.equipo_id = $equipo_id AND a.fecha_devolucion IS NULL";
$actual = $conn->query($sql_actual)->fetch_assoc();
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-history me-2"></i>Trazabilidad del Equipo</h4>
            <p><strong><?php echo htmlspecialchars($equipo['tipo_equipo'] . ' - ' . $equipo['codigo_barras']); ?></strong></p>
        </div>
        <div class="card-body">
            
            <!-- Estado actual -->
            <div class="alert alert-info">
                <strong>UBICACIÓN ACTUAL:</strong> 
                <?php if($actual): ?>
                    En poder de: <?php echo htmlspecialchars($actual['nombres']); ?> 
                    (desde <?php echo date('d/m/Y', strtotime($actual['fecha_asignacion'])); ?>)
                <?php else: ?>
                    En BODEGA PRINCIPAL (Disponible)
                <?php endif; ?>
            </div>
            
            <!-- Historial de movimientos -->
            <h5 class="mt-4">Historial de movimientos</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Movimiento</th>
                        <th>Persona</th>
                        <?php if ($es_admin): ?>
                        <th>Cédula</th>
                        <?php endif; ?>
                        <th>Estado Equipo</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($historial && $historial->num_rows > 0): ?>
                        <?php while($h = $historial->fetch_assoc()): 
                            $clase = $h['tipo_movimiento'] == 'ASIGNACION' ? 'warning' : 'success';
                        ?>
                        <tr class="table-<?php echo $clase; ?>">
                            <td><?php echo date('d/m/Y H:i', strtotime($h['fecha_movimiento'])); ?></td>
                            <td><?php echo htmlspecialchars($h['tipo_movimiento']); ?></td>
                            <td><?php echo htmlspecialchars($h['persona_nombre'] ?? 'SISTEMA'); ?></td>
                            <?php if ($es_admin): ?>
                            <td><?php echo htmlspecialchars($h['cedula'] ?? '-'); ?></td>
                            <?php endif; ?>
                            <td>
                                <?php 
                                if (!empty($h['estado_equipo'])) {
                                    $badge = match($h['estado_equipo']) {
                                        'BUENO' => 'success',
                                        'REGULAR' => 'warning',
                                        'MALO', 'DAÑADO' => 'danger',
                                        default => 'secondary'
                                    };
                                    echo "<span class='badge bg-$badge'>" . htmlspecialchars($h['estado_equipo']) . "</span>";
                                } elseif (!empty($h['condiciones'])) {
                                    echo htmlspecialchars($h['condiciones']);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($h['observaciones'] ?? ($h['condiciones'] ?? '-')); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $es_admin ? 6 : 5; ?>" class="text-center">No hay movimientos registrados para este equipo</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>