<?php
session_start();
require_once '../../config/permisos.php';
require_once '../../config/database.php';
verificarSesion();

include '../../includes/header.php';

$mensaje = '';
$error = '';

// Obtener lista de personas y equipos disponibles
$personas = $conn->query("SELECT id, nombres, cedula FROM personas ORDER BY nombres");
$equipos = $conn->query("SELECT id, codigo_barras, tipo_equipo, marca, modelo 
                         FROM equipos 
                         WHERE estado = 'Disponible' 
                         ORDER BY tipo_equipo, marca");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipo_id = intval($_POST['equipo_id']);
    $persona_id = intval($_POST['persona_id']);
    $fecha_estimada = $conn->real_escape_string($_POST['fecha_estimada']);
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    
    if (empty($equipo_id) || empty($persona_id) || empty($fecha_estimada)) {
        $error = "❌ Todos los campos son obligatorios";
    } else {
        // Verificar que el equipo siga disponible
        $check = $conn->query("SELECT estado FROM equipos WHERE id = $equipo_id");
        if ($check->fetch_assoc()['estado'] != 'Disponible') {
            $error = "❌ El equipo ya no está disponible";
        } else {
            $conn->begin_transaction();
            try {
                // Insertar préstamo rápido
                $sql = "INSERT INTO prestamos_rapidos 
                        (equipo_id, persona_id, fecha_prestamo, fecha_estimada_devolucion, observaciones, created_by) 
                        VALUES 
                        ($equipo_id, $persona_id, NOW(), '$fecha_estimada', '$observaciones', {$_SESSION['user_id']})";
                $conn->query($sql);
                
                // Cambiar estado del equipo a "Prestado"
                $conn->query("UPDATE equipos SET estado = 'Prestado' WHERE id = $equipo_id");
                
                // Registrar en movimientos (trazabilidad)
                $sql_mov = "INSERT INTO movimientos 
                           (equipo_id, persona_id, tipo_movimiento, observaciones) 
                           VALUES 
                           ($equipo_id, $persona_id, 'PRESTAMO_RAPIDO', 'Préstamo rápido sin acta. Fecha estimada: $fecha_estimada')";
                $conn->query($sql_mov);
                
                $conn->commit();
                $mensaje = "✅ Préstamo rápido registrado correctamente";
                
                echo "<script>
                    setTimeout(() => window.location.href = 'listar.php', 2000);
                </script>";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "❌ Error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-hand-holding me-2"></i>Registrar Préstamo Rápido</h4>
                    <p class="text-muted mb-0">Sin acta, solo para salidas temporales de bodega</p>
                </div>
                <div class="card-body">
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success"><?php echo $mensaje; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Persona que recibe *</label>
                            <select name="persona_id" class="form-control" required>
                                <option value="">-- Seleccione --</option>
                                <?php while($p = $personas->fetch_assoc()): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo $p['nombres'] . ' - ' . $p['cedula']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Equipo a prestar *</label>
                            <select name="equipo_id" class="form-control" required>
                                <option value="">-- Seleccione --</option>
                                <?php while($e = $equipos->fetch_assoc()): ?>
                                    <option value="<?php echo $e['id']; ?>">
                                        <?php echo $e['codigo_barras'] . ' - ' . $e['tipo_equipo'] . ' ' . $e['marca'] . ' ' . $e['modelo']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fecha estimada de devolución *</label>
                            <input type="date" name="fecha_estimada" class="form-control" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones (opcional)</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="listar.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Registrar Préstamo Rápido
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>