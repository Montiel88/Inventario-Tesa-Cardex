<?php
session_start();
require_once '../../config/permisos.php';
require_once '../../config/database.php';
verificarSesion();
requiereAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);
$error = '';
$success = '';

// Obtener datos del mantenimiento
$sql = "SELECT m.*, e.estado as estado_equipo 
        FROM mantenimientos m
        JOIN equipos e ON m.equipo_id = e.id
        WHERE m.id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}

$mantenimiento = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    $costo_final = !empty($_POST['costo_final']) ? floatval($_POST['costo_final']) : $mantenimiento['costo'];
    $nuevo_estado_equipo = $conn->real_escape_string($_POST['nuevo_estado'] ?? 'Disponible');
    
    $conn->begin_transaction();
    
    try {
        // Actualizar mantenimiento
        $sql_update = "UPDATE mantenimientos SET 
                      fecha_salida = NOW(),
                      estado = 'finalizado',
                      observaciones = CONCAT(observaciones, '\n\nFinalizado: ', '$observaciones'),
                      costo = $costo_final
                      WHERE id = $id";
        $conn->query($sql_update);
        
        // Actualizar estado del equipo
        $conn->query("UPDATE equipos SET estado = '$nuevo_estado_equipo' WHERE id = " . $mantenimiento['equipo_id']);
        
        $conn->commit();
        $success = "✅ Mantenimiento finalizado correctamente";
        
        echo "<script>
            setTimeout(() => window.location.href = 'ver.php?id=$id', 2000);
        </script>";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "❌ Error: " . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-check-circle me-2"></i>Finalizar Mantenimiento</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Observaciones de la reparación</label>
                            <textarea name="observaciones" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Costo final</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="costo_final" class="form-control" 
                                       value="<?php echo $mantenimiento['costo']; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nuevo estado del equipo</label>
                            <select name="nuevo_estado" class="form-control" required>
                                <option value="Disponible">✅ Disponible</option>
                                <option value="Asignado">📦 Asignado</option>
                                <option value="Baja">❌ Dar de baja</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Finalizar Mantenimiento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>