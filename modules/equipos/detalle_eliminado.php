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

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: listar_eliminados.php?error=ID no válido');
    exit();
}

// Obtener datos del equipo eliminado (compatible con BD sin eliminado_por)
$check_eliminado_por = $conn->query("SHOW COLUMNS FROM equipos LIKE 'eliminado_por'");
$tiene_eliminado_por = $check_eliminado_por && $check_eliminado_por->num_rows > 0;

if ($tiene_eliminado_por) {
    $sql_equipo = "SELECT e.*, u.nombre as eliminado_por_nombre
                   FROM equipos e
                   LEFT JOIN usuarios u ON e.eliminado_por = u.id
                   WHERE e.id = $id AND e.fecha_eliminacion IS NOT NULL";
} else {
    $sql_equipo = "SELECT e.*, NULL as eliminado_por_nombre
                   FROM equipos e
                   WHERE e.id = $id AND e.fecha_eliminacion IS NOT NULL";
}
$result = $conn->query($sql_equipo);
if (!$result || $result->num_rows == 0) {
    header('Location: listar_eliminados.php?error=Equipo no encontrado o no eliminado');
    exit();
}
$equipo = $result->fetch_assoc();

// Obtener último custodio (asignación activa al momento de eliminación)
$sql_ultimo_custodio = "SELECT p.nombres, p.id as persona_id
                        FROM asignaciones a
                        JOIN personas p ON a.persona_id = p.id
                        WHERE a.equipo_id = $id AND a.fecha_devolucion IS NULL
                        ORDER BY a.fecha_asignacion DESC
                        LIMIT 1";
$ultimo_custodio = $conn->query($sql_ultimo_custodio)->fetch_assoc();

// Obtener historial de asignaciones completo
$sql_asignaciones = "SELECT a.*, p.nombres as persona_nombre
                     FROM asignaciones a
                     JOIN personas p ON a.persona_id = p.id
                     WHERE a.equipo_id = $id
                     ORDER BY a.fecha_asignacion DESC";
$asignaciones = $conn->query($sql_asignaciones);

// Obtener historial de movimientos
$sql_movimientos = "SELECT m.*, p.nombres as persona_nombre
                    FROM movimientos m
                    LEFT JOIN personas p ON m.persona_id = p.id
                    WHERE m.equipo_id = $id
                    ORDER BY m.fecha_movimiento DESC";
$movimientos = $conn->query($sql_movimientos);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-trash-alt me-2 text-danger"></i>Detalle de equipo eliminado</h4>
                    <div>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#restaurarModal">
                            <i class="fas fa-undo-alt me-1"></i>Restaurar equipo
                        </button>
                        <a href="listar_eliminados.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">

                    <!-- Información del equipo -->
                    <h5>Información del equipo</h5>
                    <table class="table table-bordered">
                        <tr><th>Código</th><td><?php echo htmlspecialchars($equipo['codigo_barras']); ?></td></tr>
                        <tr><th>Tipo</th><td><?php echo htmlspecialchars($equipo['tipo_equipo']); ?></td></tr>
                        <tr><th>Marca</th><td><?php echo htmlspecialchars($equipo['marca'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Modelo</th><td><?php echo htmlspecialchars($equipo['modelo'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Nº Serie</th><td><?php echo htmlspecialchars($equipo['numero_serie'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Estado (antes de eliminar)</th><td><?php echo htmlspecialchars($equipo['estado']); ?></td></tr>
                        <tr><th>Ubicación</th><td><?php echo htmlspecialchars($equipo['ubicacion_id'] ?: 'Sin ubicación'); ?></td></tr>
                        <tr><th>Especificaciones</th><td><?php echo nl2br(htmlspecialchars($equipo['especificaciones'] ?? '')); ?></td></tr>
                    </table>

                    <!-- Datos de eliminación -->
                    <h5 class="mt-4">Datos de eliminación</h5>
                    <table class="table table-bordered">
                        <tr><th>Fecha de eliminación</th><td><?php echo date('d/m/Y H:i', strtotime($equipo['fecha_eliminacion'])); ?></td></tr>
                        <tr><th>Eliminado por</th><td><?php echo htmlspecialchars($equipo['eliminado_por_nombre'] ?? 'Desconocido'); ?></td></tr>
                    </table>

                    <!-- Último custodio -->
                    <?php if ($ultimo_custodio): ?>
                    <h5 class="mt-4">Último custodio conocido</h5>
                    <p><?php echo htmlspecialchars($ultimo_custodio['nombres']); ?></p>
                    <?php endif; ?>

                    <!-- Historial de asignaciones -->
                    <h5 class="mt-4">Historial de asignaciones</h5>
                    <?php if ($asignaciones->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha asignación</th>
                                        <th>Persona</th>
                                        <th>Fecha devolución</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($a = $asignaciones->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($a['fecha_asignacion'])); ?></td>
                                        <td><?php echo htmlspecialchars($a['persona_nombre']); ?></td>
                                        <td><?php echo $a['fecha_devolucion'] ? date('d/m/Y H:i', strtotime($a['fecha_devolucion'])) : 'Activo'; ?></td>
                                        <td><?php echo htmlspecialchars($a['observaciones'] ?? ''); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No hay asignaciones registradas.</p>
                    <?php endif; ?>

                    <!-- Historial de movimientos -->
                    <h5 class="mt-4">Historial de movimientos</h5>
                    <?php if ($movimientos->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Movimiento</th>
                                        <th>Persona</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($m = $movimientos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($m['fecha_movimiento'])); ?></td>
                                        <td><?php echo htmlspecialchars($m['tipo_movimiento']); ?></td>
                                        <td><?php echo htmlspecialchars($m['persona_nombre'] ?? 'Sistema'); ?></td>
                                        <td><?php echo htmlspecialchars($m['observaciones'] ?? ''); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No hay movimientos registrados.</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de restauración (copiado de listar_eliminados.php) -->
<div class="modal fade" id="restaurarModal" tabindex="-1" aria-labelledby="restaurarModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="restaurarModalLabel">Restaurar equipo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="restaurar.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="equipo_id" value="<?php echo $id; ?>">
                    <p>Seleccione el destino para el equipo restaurado:</p>
                    <div class="mb-3">
                        <label class="form-label">Destino</label>
                        <select name="destino" id="destinoSelect" class="form-select" required>
                            <option value="">-- Elija una opción --</option>
                            <option value="bodega">A bodega (disponible)</option>
                            <option value="persona">Asignar a una persona</option>
                            <option value="ubicacion">Asignar a una ubicación (salón/laboratorio)</option>
                        </select>
                    </div>

                    <div id="personaField" style="display: none;">
                        <label class="form-label">Persona</label>
                        <select name="persona_id" class="form-select">
                            <option value="">-- Seleccione persona --</option>
                            <?php
                            $personas = $conn->query("SELECT id, nombres FROM personas ORDER BY nombres");
                            while($p = $personas->fetch_assoc()):
                            ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombres']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div id="ubicacionField" style="display: none;">
                        <label class="form-label">Ubicación</label>
                        <select name="ubicacion_id" class="form-select">
                            <option value="">-- Seleccione ubicación --</option>
                            <?php
                            $ubicaciones = $conn->query("SELECT id, codigo_ubicacion, nombre FROM ubicaciones ORDER BY nombre");
                            while($u = $ubicaciones->fetch_assoc()):
                            ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['codigo_ubicacion'] . ' - ' . $u['nombre']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

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
document.getElementById('destinoSelect').addEventListener('change', function() {
    var valor = this.value;
    document.getElementById('personaField').style.display = (valor === 'persona') ? 'block' : 'none';
    document.getElementById('ubicacionField').style.display = (valor === 'ubicacion') ? 'block' : 'none';
});
</script>

<?php include '../../includes/footer.php'; ?>
