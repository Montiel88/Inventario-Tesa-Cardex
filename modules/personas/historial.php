<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();

require_once '../../config/database.php';
include '../../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos de la persona
$sql_persona = "SELECT * FROM personas WHERE id = $id";
$result_persona = $conn->query($sql_persona);
if ($result_persona->num_rows == 0) {
    header('Location: listar.php');
    exit();
}
$persona = $result_persona->fetch_assoc();

// Obtener todas las asignaciones de equipos a esta persona (incluyendo devueltas)
$sql_asignaciones = "SELECT a.*, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo,
                            IF(a.fecha_devolucion IS NULL, 'Activo', 'Devuelto') as estado_asignacion
                     FROM asignaciones a
                     JOIN equipos e ON a.equipo_id = e.id
                     WHERE a.persona_id = $id
                     ORDER BY a.fecha_asignacion DESC";
$asignaciones = $conn->query($sql_asignaciones);

// Obtener movimientos de la persona
$sql_movimientos = "SELECT m.*, e.codigo_barras, e.tipo_equipo
                    FROM movimientos m
                    JOIN equipos e ON m.equipo_id = e.id
                    WHERE m.persona_id = $id
                    ORDER BY m.fecha_movimiento DESC";
$movimientos = $conn->query($sql_movimientos);

// Obtener incidencias relacionadas con la persona
$sql_incidencias = "SELECT i.*, e.codigo_barras, e.tipo_equipo
                    FROM incidencias i
                    JOIN equipos e ON i.equipo_id = e.id
                    WHERE i.persona_id = $id
                    ORDER BY i.fecha_reporte DESC";
$incidencias = $conn->query($sql_incidencias);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-history me-2"></i>Historial de la Persona</h4>
                    <div>
                        <a href="detalle.php?id=<?php echo $id; ?>" class="btn btn-info">
                            <i class="fas fa-eye"></i> Ver Detalle
                        </a>
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Datos de la persona -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr><th>Cédula</th><td><?php echo $persona['cedula']; ?></td></tr>
                                <tr><th>Nombre</th><td><?php echo $persona['nombres']; ?></td></tr>
                                <tr><th>Cargo</th><td><?php echo $persona['cargo']; ?></td></tr>
                                <tr><th>Correo</th><td><?php echo $persona['correo'] ?: 'No registrado'; ?></td></tr>
                                <tr><th>Teléfono</th><td><?php echo $persona['telefono'] ?: 'No registrado'; ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3><?php echo $asignaciones->num_rows; ?></h3>
                                    <p>Total de asignaciones</p>
                                    <?php
                                    // Contar equipos actualmente asignados (no devueltos)
                                    $activos = 0;
                                    if ($asignaciones->num_rows > 0) {
                                        $asignaciones->data_seek(0); // Reset pointer
                                        while($row = $asignaciones->fetch_assoc()) {
                                            if ($row['estado_asignacion'] == 'Activo') $activos++;
                                        }
                                        $asignaciones->data_seek(0); // Reset again for later use
                                    }
                                    ?>
                                    <p><span class="badge bg-warning"><?php echo $activos; ?></span> equipos actualmente asignados</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- ASIGNACIONES (EQUIPOS) -->
                    <!-- ============================================ -->
                    <h5 class="mt-4"><i class="fas fa-laptop me-2"></i>Equipos asignados</h5>
                    <?php if ($asignaciones && $asignaciones->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha asignación</th>
                                        <th>Equipo</th>
                                        <th>Código</th>
                                        <th>Fecha devolución</th>
                                        <th>Estado</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($a = $asignaciones->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($a['fecha_asignacion'])); ?></td>
                                        <td><?php echo $a['tipo_equipo'] . ' ' . $a['marca'] . ' ' . $a['modelo']; ?></td>
                                        <td><?php echo $a['codigo_barras']; ?></td>
                                        <td><?php echo $a['fecha_devolucion'] ? date('d/m/Y H:i', strtotime($a['fecha_devolucion'])) : '—'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $a['estado_asignacion'] == 'Activo' ? 'warning' : 'success'; ?>">
                                                <?php echo $a['estado_asignacion']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo nl2br($a['observaciones'] ?? ''); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No hay asignaciones registradas para esta persona.</p>
                    <?php endif; ?>

                    <!-- ============================================ -->
                    <!-- MOVIMIENTOS -->
                    <!-- ============================================ -->
                    <h5 class="mt-5"><i class="fas fa-exchange-alt me-2"></i>Movimientos registrados</h5>
                    <?php if ($movimientos && $movimientos->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Equipo</th>
                                        <th>Código</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($m = $movimientos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($m['fecha_movimiento'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $m['tipo_movimiento'] == 'ASIGNACION' ? 'warning' : 'success'; ?>">
                                                <?php echo $m['tipo_movimiento']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $m['tipo_equipo']; ?></td>
                                        <td><?php echo $m['codigo_barras']; ?></td>
                                        <td><?php echo nl2br($m['observaciones'] ?? ''); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No hay movimientos registrados para esta persona.</p>
                    <?php endif; ?>

                    <!-- ============================================ -->
                    <!-- INCIDENCIAS RELACIONADAS -->
                    <!-- ============================================ -->
                    <?php if ($incidencias && $incidencias->num_rows > 0): ?>
                    <h5 class="mt-5"><i class="fas fa-exclamation-triangle me-2"></i>Incidencias relacionadas</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha reporte</th>
                                    <th>Tipo</th>
                                    <th>Equipo</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($i = $incidencias->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($i['fecha_reporte'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $i['tipo_incidencia'] == 'daño' ? 'danger' : ($i['tipo_incidencia'] == 'reparación' ? 'warning' : 'info'); ?>">
                                            <?php echo strtoupper($i['tipo_incidencia']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $i['tipo_equipo'] . ' (' . $i['codigo_barras'] . ')'; ?></td>
                                    <td><?php echo nl2br($i['descripcion']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $i['estado'] == 'pendiente' ? 'secondary' : ($i['estado'] == 'en proceso' ? 'primary' : 'success'); ?>">
                                            <?php echo $i['estado']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>