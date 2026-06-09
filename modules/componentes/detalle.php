<?php
session_start();
require_once '../../config/database.php';
include '../../includes/header.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) die('ID no válido');

$comp = $conn->query("SELECT * FROM componentes WHERE id = $id")->fetch_assoc();
if (!$comp) die('Componente no encontrado');

$historial = $conn->query("SELECT mc.*, p.nombres as persona_nombre FROM movimientos_componentes mc LEFT JOIN personas p ON mc.persona_id = p.id WHERE mc.componente_id = $id ORDER BY mc.fecha_movimiento DESC");
?>
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4>Detalle de Componente: <?php echo $comp['nombre_componente']; ?></h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tr><th>Tipo</th><td><?php echo $comp['tipo']; ?></td></tr>
                <tr><th>Marca</th><td><?php echo $comp['marca']; ?></td></tr>
                <tr><th>Modelo</th><td><?php echo $comp['modelo']; ?></td></tr>
                <tr><th>Serie</th><td><?php echo $comp['numero_serie']; ?></td></tr>
                <tr><th>Especificaciones</th><td><?php echo nl2br($comp['especificaciones']); ?></td></tr>
            </table>
            <h5>Historial de Movimientos</h5>
            <table class="table table-sm">
                <thead>
                    <tr><th>Fecha</th><th>Tipo</th><th>Persona</th><th>Observaciones</th></tr>
                </thead>
                <tbody>
                    <?php while($h = $historial->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $h['fecha_movimiento']; ?></td>
                        <td><?php echo $h['tipo_movimiento']; ?></td>
                        <td><?php echo $h['persona_nombre'] ?? '-'; ?></td>
                        <td><?php echo $h['observaciones']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>