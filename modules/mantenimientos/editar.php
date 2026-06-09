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
$sql = "SELECT * FROM mantenimientos WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}

$mantenimiento = $result->fetch_assoc();

// Obtener lista de equipos
$equipos = $conn->query("SELECT id, codigo_barras, tipo_equipo, marca, modelo 
                         FROM equipos 
                         ORDER BY tipo_equipo, marca");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipo_id = intval($_POST['equipo_id']);
    $tipo = $conn->real_escape_string($_POST['tipo_mantenimiento']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $tecnico = $conn->real_escape_string($_POST['tecnico'] ?? '');
    $proveedor = $conn->real_escape_string($_POST['proveedor'] ?? '');
    $costo = !empty($_POST['costo']) ? floatval($_POST['costo']) : 'NULL';
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    
    $sql_update = "UPDATE mantenimientos SET 
                  equipo_id = $equipo_id,
                  tipo_mantenimiento = '$tipo',
                  descripcion = '$descripcion',
                  tecnico = '$tecnico',
                  proveedor = '$proveedor',
                  costo = $costo,
                  observaciones = '$observaciones'
                  WHERE id = $id";
    
    if ($conn->query($sql_update)) {
        $success = "✅ Mantenimiento actualizado correctamente";
        // Recargar datos
        $result = $conn->query("SELECT * FROM mantenimientos WHERE id = $id");
        $mantenimiento = $result->fetch_assoc();
    } else {
        $error = "❌ Error: " . $conn->error;
    }
}

include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Mantenimiento</h4>
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
                            <label class="form-label">Equipo *</label>
                            <select name="equipo_id" class="form-control" required>
                                <option value="">-- Seleccione un equipo --</option>
                                <?php while($eq = $equipos->fetch_assoc()): ?>
                                    <option value="<?php echo $eq['id']; ?>" 
                                        <?php echo ($mantenimiento['equipo_id'] == $eq['id']) ? 'selected' : ''; ?>>
                                        <?php echo $eq['codigo_barras'] . ' - ' . $eq['tipo_equipo'] . ' ' . $eq['marca'] . ' ' . $eq['modelo']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Mantenimiento *</label>
                                <select name="tipo_mantenimiento" class="form-control" required>
                                    <option value="">-- Seleccione --</option>
                                    <option value="preventivo" <?php echo ($mantenimiento['tipo_mantenimiento'] == 'preventivo') ? 'selected' : ''; ?>>🔧 Preventivo</option>
                                    <option value="correctivo" <?php echo ($mantenimiento['tipo_mantenimiento'] == 'correctivo') ? 'selected' : ''; ?>>⚡ Correctivo</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción del problema/trabajo *</label>
                            <textarea name="descripcion" class="form-control" rows="3" required><?php echo $mantenimiento['descripcion']; ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Técnico responsable</label>
                                <input type="text" name="tecnico" class="form-control" 
                                       value="<?php echo $mantenimiento['tecnico']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Proveedor/Taller</label>
                                <input type="text" name="proveedor" class="form-control" 
                                       value="<?php echo $mantenimiento['proveedor']; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Costo</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" name="costo" class="form-control" 
                                           value="<?php echo $mantenimiento['costo']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"><?php echo $mantenimiento['observaciones']; ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Actualizar Mantenimiento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>