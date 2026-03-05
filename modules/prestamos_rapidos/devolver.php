<?php
session_start();
require_once '../../config/permisos.php';
require_once '../../config/database.php';
verificarSesion();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);
$error = '';
$success = '';

// Obtener datos del préstamo
$sql = "SELECT p.*, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo, e.id as equipo_id,
               per.nombres as persona_nombre
        FROM prestamos_rapidos p
        JOIN equipos e ON p.equipo_id = e.id
        JOIN personas per ON p.persona_id = per.id
        WHERE p.id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}

$prestamo = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    
    $conn->begin_transaction();
    try {
        // Actualizar préstamo
        $conn->query("UPDATE prestamos_rapidos SET 
                     fecha_devolucion_real = NOW(), 
                     estado = 'devuelto',
                     observaciones = CONCAT(observaciones, ' | Devolución: ', '$observaciones')
                     WHERE id = $id");
        
        // Cambiar estado del equipo a disponible
        $conn->query("UPDATE equipos SET estado = 'Disponible' WHERE id = " . $prestamo['equipo_id']);
        
        // Registrar en movimientos
        $sql_mov = "INSERT INTO movimientos 
                   (equipo_id, persona_id, tipo_movimiento, observaciones) 
                   VALUES 
                   ({$prestamo['equipo_id']}, {$prestamo['persona_id']}, 'DEVOLUCION_RAPIDA', 'Devolución de préstamo rápido: $observaciones')";
        $conn->query($sql_mov);
        
        $conn->commit();
        $success = "✅ Devolución registrada correctamente";
        
        echo "<script>
            setTimeout(() => window.location.href = 'listar.php', 2000);
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
                    <h4 class="mb-0"><i class="fas fa-undo-alt me-2"></i>Devolver Préstamo Rápido</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <p><strong>Persona:</strong> <?php echo $prestamo['persona_nombre']; ?></p>
                        <p><strong>Equipo:</strong> <?php echo $prestamo['tipo_equipo'] . ' ' . $prestamo['marca'] . ' ' . $prestamo['modelo']; ?></p>
                        <p><strong>Código:</strong> <?php echo $prestamo['codigo_barras']; ?></p>
                        <p><strong>Fecha préstamo:</strong> <?php echo date('d/m/Y H:i', strtotime($prestamo['fecha_prestamo'])); ?></p>
                        <p><strong>Fecha estimada:</strong> <?php echo date('d/m/Y', strtotime($prestamo['fecha_estimada_devolucion'])); ?></p>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Observaciones de la devolución</label>
                            <textarea name="observaciones" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="listar.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Registrar Devolución
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>