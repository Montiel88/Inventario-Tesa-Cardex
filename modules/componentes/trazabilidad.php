<?php
session_start();
require_once '../../config/database.php';
include '../../includes/header.php';

$id = intval($_GET['id'] ?? 0);

// Datos del componente
$sql_comp = "SELECT c.*, e.tipo_equipo, e.codigo_barras 
             FROM componentes c
             JOIN equipos e ON c.equipo_id = e.id
             WHERE c.id = $id";
$comp = $conn->query($sql_comp)->fetch_assoc();
if (!$comp) {
    echo "<div class='alert alert-danger'>Componente no encontrado</div>";
    include '../../includes/footer.php';
    exit();
}

// Historial de movimientos
$sql_mov = "SELECT m.*, u.nombre as usuario
            FROM movimientos_componentes m
            LEFT JOIN usuarios u ON m.persona_id = u.id
            WHERE m.componente_id = $id
            ORDER BY m.fecha_movimiento DESC";
$movimientos = $conn->query($sql_mov);
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4>Trazabilidad del Componente: <?php echo $comp['nombre_componente']; ?></h4>
            <p>Equipo: <?php echo $comp['tipo_equipo'] . ' - ' . $comp['codigo_barras']; ?></p>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Movimiento</th>
                        <th>Estado anterior</th>
                        <th>Estado nuevo</th>
                        <th>Usuario</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($m = $movimientos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($m['fecha_movimiento'])); ?></td>
                        <td><?php echo $m['tipo_movimiento']; ?></td>
                        <td><?php echo $m['estado_anterior'] ?? '-'; ?></td>
                        <td><?php echo $m['estado_nuevo'] ?? '-'; ?></td>
                        <td><?php echo $m['usuario'] ?? 'Sistema'; ?></td>
                        <td><?php echo $m['observaciones'] ?? '-'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>