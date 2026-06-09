<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=No tienes permisos');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos de la asignación
$sql = "SELECT a.*, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo,
               p.nombres as persona_nombre, p.cedula
        FROM asignaciones a
        JOIN equipos e ON a.equipo_id = e.id
        JOIN personas p ON a.persona_id = p.id
        WHERE a.id = $id";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: listar.php?error=Asignación no encontrada');
    exit();
}

$asignacion = $result->fetch_assoc();

// Verificar si ya fue devuelta
if (!is_null($asignacion['fecha_devolucion'])) {
    header('Location: listar.php?error=No se puede editar una asignación ya devuelta');
    exit();
}

// Obtener personas para selector
$personas = $conn->query("SELECT id, nombres, cedula FROM personas ORDER BY nombres");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $persona_id = intval($_POST['persona_id']);
    $fecha_asignacion = $conn->real_escape_string($_POST['fecha_asignacion']);
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    
    if ($persona_id <= 0) {
        $error = "❌ Debe seleccionar una persona";
    } elseif (empty($fecha_asignacion)) {
        $error = "❌ La fecha de asignación es obligatoria";
    } else {
        $sql_update = "UPDATE asignaciones SET 
                        persona_id = $persona_id,
                        fecha_asignacion = '$fecha_asignacion',
                        observaciones = '$observaciones'
                       WHERE id = $id";
        
        if ($conn->query($sql_update)) {
            $success = "✅ Asignación actualizada correctamente";
            // Recargar datos
            $result = $conn->query("SELECT a.*, e.codigo_barras, e.tipo_equipo, p.nombres as persona_nombre
                                   FROM asignaciones a
                                   JOIN equipos e ON a.equipo_id = e.id
                                   JOIN personas p ON a.persona_id = p.id
                                   WHERE a.id = $id");
            $asignacion = $result->fetch_assoc();
        } else {
            $error = "❌ Error al actualizar: " . $conn->error;
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Asignación #<?php echo $id; ?></h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info mb-4">
                        <strong>Equipo asignado:</strong> <?php echo $asignacion['tipo_equipo'] . ' ' . $asignacion['marca'] . ' ' . $asignacion['modelo']; ?>
                        <br><strong>Código:</strong> <?php echo $asignacion['codigo_barras']; ?>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Persona Asignada *</label>
                            <select name="persona_id" class="form-control" required>
                                <option value="">-- Seleccione una persona --</option>
                                <?php while($p = $personas->fetch_assoc()): ?>
                                    <option value="<?php echo $p['id']; ?>" 
                                        <?php echo ($asignacion['persona_id'] == $p['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['nombres'] . ' - ' . $p['cedula']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fecha de Asignación *</label>
                            <input type="datetime-local" name="fecha_asignacion" class="form-control" 
                                   value="<?php echo date('Y-m-d\TH:i', strtotime($asignacion['fecha_asignacion'])); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3"><?php echo htmlspecialchars($asignacion['observaciones'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="detalle.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>