<?php
session_start();
require_once '../../config/database.php';
include '../../includes/header.php';

$equipo_id = intval($_GET['id'] ?? 0);

// Datos del equipo
$sql_equipo = "SELECT * FROM equipos WHERE id = $equipo_id";
$equipo = $conn->query($sql_equipo)->fetch_assoc();

// Historial completo del equipo
$sql_historial = "SELECT m.*, p.nombres as persona_nombre, p.cedula
                  FROM movimientos m
                  LEFT JOIN personas p ON m.persona_id = p.id
                  WHERE m.equipo_id = $equipo_id
                  ORDER BY m.fecha_movimiento DESC";
$historial = $conn->query($sql_historial);

// Estado actual
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
            <p><strong><?php echo $equipo['tipo_equipo'] . ' - ' . $equipo['codigo_barras']; ?></strong></p>
        </div>
        <div class="card-body">
            
            <!-- Estado actual -->
            <div class="alert alert-info">
                <strong>UBICACIÓN ACTUAL:</strong> 
                <?php if($actual): ?>
                    En poder de: <?php echo $actual['nombres']; ?> (desde <?php echo date('d/m/Y', strtotime($actual['fecha_asignacion'])); ?>)
                <?php else: ?>
                    En BODEGA PRINCIPAL (Disponible)
                <?php endif; ?>
            </div>
            
            <!-- Historial -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Movimiento</th>
                        <th>Persona</th>
                        <th>Cédula</th>
                        <th>Estado Equipo</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($h = $historial->fetch_assoc()): 
                        $clase = $h['tipo_movimiento'] == 'ASIGNACION' ? 'warning' : 'success';
                    ?>
                    <tr class="table-<?php echo $clase; ?>">
                        <td><?php echo date('d/m/Y H:i', strtotime($h['fecha_movimiento'])); ?></td>
                        <td><?php echo $h['tipo_movimiento']; ?></td>
                        <td><?php echo $h['persona_nombre'] ?? 'SISTEMA'; ?></td>
                        <td><?php echo $h['cedula'] ?? '-'; ?></td>
                        <td>
                            <?php 
                            if($h['estado_equipo']) {
                                $badge = match($h['estado_equipo']) {
                                    'BUENO' => 'success',
                                    'REGULAR' => 'warning',
                                    'MALO', 'DAÑADO' => 'danger',
                                    default => 'secondary'
                                };
                                echo "<span class='badge bg-$badge'>" . $h['estado_equipo'] . "</span>";
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?php echo $h['observaciones'] ?? ($h['condiciones'] ?? '-'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>