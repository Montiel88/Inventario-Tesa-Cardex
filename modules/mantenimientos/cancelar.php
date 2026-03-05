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

// Solo se puede cancelar si está en proceso
if ($mantenimiento['estado'] != 'en_proceso') {
    header('Location: listar.php?error=No se puede cancelar un mantenimiento finalizado');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $motivo = $conn->real_escape_string($_POST['motivo']);
    
    $conn->begin_transaction();
    
    try {
        // Cambiar estado a cancelado
        $conn->query("UPDATE mantenimientos SET 
                     estado = 'cancelado',
                     observaciones = CONCAT(observaciones, '\n\nCANCELADO: ', '$motivo')
                     WHERE id = $id");
        
        // Restaurar estado del equipo a como estaba antes
        // Por simplicidad, lo dejamos como Disponible
        $conn->query("UPDATE equipos SET estado = 'Disponible' WHERE id = " . $mantenimiento['equipo_id']);
        
        $conn->commit();
        $success = "✅ Mantenimiento cancelado correctamente";
        
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
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-ban me-2"></i>Cancelar Mantenimiento</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>¿Estás seguro de cancelar este mantenimiento?</strong>
                        <p class="mb-0 mt-2">El equipo volverá a estar disponible. Esta acción quedará registrada en el historial.</p>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Motivo de la cancelación *</label>
                            <textarea name="motivo" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-ban me-2"></i>Sí, Cancelar Mantenimiento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>