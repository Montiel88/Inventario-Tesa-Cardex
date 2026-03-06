<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Solo admin puede hacer traspasos
if ($_SESSION['user_rol'] != 1) {
    header('Location: /inventario_ti/modules/dashboard.php?error=No tienes permisos');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';

// Obtener asignaciones activas (equipos prestados actualmente)
$sql_asignaciones = "SELECT a.id as asignacion_id, 
                            a.fecha_asignacion,
                            e.id as equipo_id,
                            e.codigo_barras,
                            e.tipo_equipo,
                            e.marca,
                            e.modelo,
                            e.numero_serie,
                            p.id as persona_actual_id,
                            p.nombres as persona_actual_nombre,
                            p.cedula as persona_actual_cedula,
                            p.cargo as persona_actual_cargo
                     FROM asignaciones a
                     JOIN equipos e ON a.equipo_id = e.id
                     JOIN personas p ON a.persona_id = p.id
                     WHERE a.fecha_devolucion IS NULL
                     ORDER BY p.nombres, e.tipo_equipo";

$asignaciones = $conn->query($sql_asignaciones);

// Obtener todas las personas para el selector de nueva persona
$personas = $conn->query("SELECT id, nombres, cedula, cargo FROM personas ORDER BY nombres");

// Procesar el traspaso
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['realizar_traspaso'])) {
    $asignacion_id = intval($_POST['asignacion_id']);
    $nueva_persona_id = intval($_POST['nueva_persona_id']);
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    
    if (!$asignacion_id || !$nueva_persona_id) {
        $error = "❌ Debe seleccionar una asignación y una nueva persona";
    } else {
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Obtener datos de la asignación actual
            $sql_actual = "SELECT * FROM asignaciones WHERE id = $asignacion_id";
            $actual = $conn->query($sql_actual)->fetch_assoc();
            
            if (!$actual) {
                throw new Exception("Asignación no encontrada");
            }
            
            $equipo_id = $actual['equipo_id'];
            $persona_anterior_id = $actual['persona_id'];
            
            // 1. Cerrar la asignación actual (poner fecha de devolución)
            $sql_cerrar = "UPDATE asignaciones SET fecha_devolucion = NOW(), observaciones = CONCAT(observaciones, ' | Traspasado a persona ID: $nueva_persona_id') 
                          WHERE id = $asignacion_id";
            if (!$conn->query($sql_cerrar)) {
                throw new Exception("Error al cerrar asignación actual");
            }
            
            // 2. Crear nueva asignación para la nueva persona
            $sql_nueva = "INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion, observaciones) 
                         VALUES ($equipo_id, $nueva_persona_id, NOW(), 'Traspaso desde asignación ID: $asignacion_id. $observaciones')";
            if (!$conn->query($sql_nueva)) {
                throw new Exception("Error al crear nueva asignación");
            }
            
            // 3. Registrar movimiento de devolución del anterior
            $sql_mov1 = "INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) 
                        VALUES ($equipo_id, $persona_anterior_id, 'DEVOLUCION', 'Devolución por traspaso a nueva persona')";
            $conn->query($sql_mov1);
            
            // 4. Registrar movimiento de nueva asignación
            $sql_mov2 = "INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) 
                        VALUES ($equipo_id, $nueva_persona_id, 'ASIGNACION', 'Asignación por traspaso')";
            $conn->query($sql_mov2);
            
            $conn->commit();
            
            // Guardar los IDs en la sesión para usarlos después
            $_SESSION['ultimo_traspaso'] = [
                'asignacion_id' => $asignacion_id,
                'nueva_persona_id' => $nueva_persona_id
            ];
            
            $mensaje = "✅ Traspaso realizado correctamente";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "❌ Error al realizar traspaso: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning">
                    <h4 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Traspaso de Equipos (Cambio de Custodio)</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        
                        <?php if (isset($_SESSION['ultimo_traspaso'])): 
                            $ids = $_SESSION['ultimo_traspaso'];
                            $url_acta = "/inventario_ti/api/generar_acta_traspaso.php?asignacion_id={$ids['asignacion_id']}&nueva_persona_id={$ids['nueva_persona_id']}";
                        ?>
                        <div class="alert alert-info mt-3">
                            <strong>¿Generar acta de traspaso?</strong>
                            <div class="mt-2">
                                <a href="<?php echo $url_acta; ?>" target="_blank" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-pdf me-1"></i> Generar Acta Ahora
                                </a>
                                <a href="traspaso.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i> Cerrar
                                </a>
                            </div>
                        </div>
                        <?php unset($_SESSION['ultimo_traspaso']); ?>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" id="formTraspaso">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Equipo a traspasar (actualmente asignado) *</label>
                                <select name="asignacion_id" id="asignacion_id" class="form-control" required>
                                    <option value="">-- Seleccione un equipo asignado --</option>
                                    <?php while($a = $asignaciones->fetch_assoc()): ?>
                                        <option value="<?php echo $a['asignacion_id']; ?>" 
                                                data-equipo="<?php echo htmlspecialchars($a['tipo_equipo'] . ' ' . $a['marca'] . ' ' . $a['modelo']); ?>"
                                                data-persona="<?php echo htmlspecialchars($a['persona_actual_nombre']); ?>"
                                                data-codigo="<?php echo $a['codigo_barras']; ?>">
                                            <?php echo $a['persona_actual_nombre'] . ' → ' . $a['tipo_equipo'] . ' ' . $a['marca'] . ' (' . $a['codigo_barras'] . ')'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nueva persona (nuevo custodio) *</label>
                                <select name="nueva_persona_id" id="nueva_persona_id" class="form-control" required>
                                    <option value="">-- Seleccione la nueva persona --</option>
                                    <?php while($p = $personas->fetch_assoc()): ?>
                                        <option value="<?php echo $p['id']; ?>">
                                            <?php echo $p['nombres'] . ' - ' . $p['cedula'] . ' (' . $p['cargo'] . ')'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones del traspaso</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Motivo del traspaso, condiciones, etc."></textarea>
                        </div>
                        
                        <div id="infoAsignacion" class="alert alert-info" style="display: none;">
                            <h6><i class="fas fa-info-circle me-2"></i>Detalle del equipo seleccionado:</h6>
                            <p id="infoEquipo"></p>
                            <p id="infoPersonaActual"></p>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="/inventario_ti/modules/movimientos/historial.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" name="realizar_traspaso" class="btn btn-warning">
                                <i class="fas fa-exchange-alt me-2"></i>Realizar Traspaso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('asignacion_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const infoDiv = document.getElementById('infoAsignacion');
    const infoEquipo = document.getElementById('infoEquipo');
    const infoPersona = document.getElementById('infoPersonaActual');
    
    if (this.value) {
        const equipo = selected.getAttribute('data-equipo');
        const persona = selected.getAttribute('data-persona');
        const codigo = selected.getAttribute('data-codigo');
        
        infoEquipo.innerHTML = '<strong>Equipo:</strong> ' + equipo + ' (Código: ' + codigo + ')';
        infoPersona.innerHTML = '<strong>Custodio actual:</strong> ' + persona;
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
});
</script>

<?php include '../../includes/footer.php'; ?>