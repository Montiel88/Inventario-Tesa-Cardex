<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

if ($_SESSION['user_rol'] != 1) {
    header('Location: /inventario_ti/modules/dashboard.php?error=No tienes permisos');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';
$exitos = 0;
$errores = [];

// Obtener todas las personas para el selector de nueva persona
$personas = $conn->query("SELECT id, nombres, cedula, cargo FROM personas ORDER BY nombres");

// Procesar el traspaso múltiple
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['realizar_traspaso_multiple'])) {
    $asignacion_ids = $_POST['asignacion_ids'] ?? [];
    $nueva_persona_id = intval($_POST['nueva_persona_id']);
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    
    if (empty($asignacion_ids)) {
        $error = "❌ Debe seleccionar al menos un equipo para traspasar";
    } elseif (!$nueva_persona_id) {
        $error = "❌ Debe seleccionar la nueva persona";
    } else {
        $conn->begin_transaction();
        
        try {
            foreach ($asignacion_ids as $asignacion_id) {
                $asignacion_id = intval($asignacion_id);
                
                // Obtener datos de la asignación actual
                $sql_actual = "SELECT * FROM asignaciones WHERE id = $asignacion_id";
                $actual = $conn->query($sql_actual)->fetch_assoc();
                
                if (!$actual) {
                    $errores[] = "Asignación ID $asignacion_id no encontrada";
                    continue;
                }
                
                $equipo_id = $actual['equipo_id'];
                $persona_anterior_id = $actual['persona_id'];
                
                // Obtener código del equipo
                $eq = $conn->query("SELECT codigo_barras, tipo_equipo, marca, modelo FROM equipos WHERE id = $equipo_id")->fetch_assoc();
                $codigo = $eq['codigo_barras'] ?? 'N/A';
                
                // 1. Cerrar la asignación actual
                $sql_cerrar = "UPDATE asignaciones SET fecha_devolucion = NOW(), observaciones = CONCAT(IFNULL(observaciones,''), ' | Traspasado a persona ID: $nueva_persona_id') 
                              WHERE id = $asignacion_id";
                if (!$conn->query($sql_cerrar)) {
                    $errores[] = "Error al cerrar asignación $asignacion_id";
                    continue;
                }
                
                // 2. Crear nueva asignación
                $sql_nueva = "INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion, observaciones) 
                             VALUES ($equipo_id, $nueva_persona_id, NOW(), 'Traspaso múltiple. $observaciones')";
                if (!$conn->query($sql_nueva)) {
                    $errores[] = "Error al crear nueva asignación para equipo $codigo";
                    continue;
                }
                
                // 3. Registrar movimiento de devolución
                $sql_mov1 = "INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) 
                            VALUES ($equipo_id, $persona_anterior_id, 'DEVOLUCION', 'Devolución por traspaso múltiple')";
                $conn->query($sql_mov1);
                
                // 4. Registrar movimiento de nueva asignación
                $sql_mov2 = "INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) 
                            VALUES ($equipo_id, $nueva_persona_id, 'ASIGNACION', 'Asignación por traspaso múltiple')";
                $conn->query($sql_mov2);
                
                $exitos++;
            }
            
            if ($exitos > 0) {
                $conn->commit();
                $mensaje = "✅ Se traspasaron $exitos equipos correctamente";
                
                // Guardar para generar acta
                $_SESSION['ultimo_traspaso_multiple'] = [
                    'asignacion_ids' => $asignacion_ids,
                    'nueva_persona_id' => $nueva_persona_id,
                    'cantidad' => $exitos
                ];
            } else {
                $conn->rollback();
                $error = "❌ No se pudo completar ningún traspaso";
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "❌ Error: " . $e->getMessage();
        }
    }
}

// Obtener equipos asignados para el selector
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
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning">
                    <h4 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Traspaso Múltiple de Equipos</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        
                        <?php if (isset($_SESSION['ultimo_traspaso_multiple'])): 
                            $ids = $_SESSION['ultimo_traspaso_multiple'];
                        ?>
                        <div class="alert alert-info mt-3">
                            <strong>¿Generar acta de traspaso múltiple?</strong>
                            <p class="mb-2">Se traspasaron <?php echo $ids['cantidad']; ?> equipos.</p>
                            <div>
                                <a href="/inventario_ti/api/generar_acta_traspaso.php?multiple=1&nueva_persona_id=<?php echo $ids['nueva_persona_id']; ?>" 
                                   target="_blank" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-pdf me-1"></i> Generar Acta de Traspaso
                                </a>
                                <a href="traspaso_multiple.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-plus me-1"></i> Nuevo Traspaso
                                </a>
                            </div>
                        </div>
                        <?php unset($_SESSION['ultimo_traspaso_multiple']); ?>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-warning">
                            <strong>Errores parciales:</strong>
                            <ul class="mb-0">
                                <?php foreach ($errores as $err): ?>
                                    <li><?php echo $err; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="formTraspasoMultiple">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nueva persona (nuevo custodio) *</label>
                                <select name="nueva_persona_id" id="nueva_persona_id" class="form-control select2" required>
                                    <option value="">-- Seleccione la nueva persona --</option>
                                    <?php while($p = $personas->fetch_assoc()): ?>
                                        <option value="<?php echo $p['id']; ?>">
                                            <?php echo $p['nombres'] . ' - ' . $p['cedula'] . ' (' . $p['cargo'] . ')'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Observaciones del traspaso</label>
                                <textarea name="observaciones" class="form-control" rows="1" placeholder="Motivo del traspaso, condiciones, etc."></textarea>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <input type="checkbox" id="selectAllEquipos"> 
                                <strong>Seleccionar todos los equipos</strong>
                            </label>
                            <p class="text-muted small">Marque los equipos que desea traspasar a la nueva persona</p>
                        </div>
                        
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-bordered table-hover table-sm" id="tablaEquipos">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="checkAll" checked>
                                        </th>
                                        <th>Custodio Actual</th>
                                        <th>Equipo</th>
                                        <th>Código</th>
                                        <th>Serie</th>
                                        <th>Fecha Asignación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $asignaciones2 = $conn->query($sql_asignaciones);
                                    while($a = $asignaciones2->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="asignacion_ids[]" 
                                                   value="<?php echo $a['asignacion_id']; ?>" 
                                                   class="equipo-checkbox" checked
                                                   data-equipo="<?php echo htmlspecialchars($a['tipo_equipo'] . ' ' . $a['marca'] . ' ' . $a['modelo']); ?>"
                                                   data-codigo="<?php echo $a['codigo_barras']; ?>">
                                        </td>
                                        <td>
                                            <strong><?php echo $a['persona_actual_nombre']; ?></strong><br>
                                            <small class="text-muted"><?php echo $a['persona_actual_cedula']; ?></small>
                                        </td>
                                        <td><?php echo $a['tipo_equipo'] . ' ' . $a['marca'] . ' ' . $a['modelo']; ?></td>
                                        <td><span class="badge bg-primary"><?php echo $a['codigo_barras']; ?></span></td>
                                        <td><?php echo $a['numero_serie'] ?: 'N/A'; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($a['fecha_asignacion'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Equipos seleccionados:</strong> <span id="countSelected">0</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="/inventario_ti/modules/movimientos/historial.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" name="realizar_traspaso_multiple" class="btn btn-warning" id="btnTraspasar" disabled>
                                <i class="fas fa-exchange-alt me-2"></i>Realizar Traspaso Múltiple
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    const selectAllEquipos = document.getElementById('selectAllEquipos');
    const checkboxes = document.querySelectorAll('.equipo-checkbox');
    const btnTraspasar = document.getElementById('btnTraspasar');
    const countSelected = document.getElementById('countSelected');
    
    function updateCount() {
        const checked = document.querySelectorAll('.equipo-checkbox:checked').length;
        countSelected.textContent = checked;
        btnTraspasar.disabled = checked === 0;
    }
    
    checkAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateCount();
    });
    
    selectAllEquipos.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateCount();
    });
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            const someChecked = Array.from(checkboxes).some(c => c.checked);
            checkAll.checked = allChecked;
            updateCount();
        });
    });
    
    updateCount();
});
</script>

<?php include '../../includes/footer.php'; ?>
