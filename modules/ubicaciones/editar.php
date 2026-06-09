<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin(); // Solo admin puede editar

require_once '../../config/database.php';
include '../../includes/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM ubicaciones WHERE id = $id");
if ($result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}
$ubicacion = $result->fetch_assoc();

$personas = $conn->query("SELECT id, nombres FROM personas ORDER BY nombres");
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo_ubicacion = $conn->real_escape_string($_POST['codigo_ubicacion'] ?? '');
    $nombre = $conn->real_escape_string($_POST['nombre'] ?? '');
    $tipo = $conn->real_escape_string($_POST['tipo'] ?? '');
    $responsable_id = !empty($_POST['responsable_id']) ? intval($_POST['responsable_id']) : 'NULL';
    $descripcion = $conn->real_escape_string($_POST['descripcion'] ?? '');

    if (empty($codigo_ubicacion) || empty($nombre) || empty($tipo)) {
        $error = "❌ Los campos Código, Nombre y Tipo son obligatorios.";
    } else {
        $sql = "UPDATE ubicaciones SET 
                codigo_ubicacion = '$codigo_ubicacion',
                nombre = '$nombre',
                tipo = '$tipo',
                responsable_id = $responsable_id,
                descripcion = '$descripcion'
                WHERE id = $id";

        if ($conn->query($sql)) {
            $success = "✅ Ubicación actualizada correctamente.";
            // Recargar datos
            $result = $conn->query("SELECT * FROM ubicaciones WHERE id = $id");
            $ubicacion = $result->fetch_assoc();
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
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Ubicación</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código de ubicación *</label>
                                <input type="text" name="codigo_ubicacion" class="form-control" 
                                       value="<?php echo htmlspecialchars($ubicacion['codigo_ubicacion'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" class="form-control" 
                                       value="<?php echo htmlspecialchars($ubicacion['nombre'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo *</label>
                            <select name="tipo" class="form-control" required>
                                <option value="">-- Seleccione --</option>
                                <option value="salon" <?php echo ($ubicacion['tipo'] ?? '') == 'salon' ? 'selected' : ''; ?>>Salón</option>
                                <option value="laboratorio" <?php echo ($ubicacion['tipo'] ?? '') == 'laboratorio' ? 'selected' : ''; ?>>Laboratorio</option>
                                <option value="biblioteca" <?php echo ($ubicacion['tipo'] ?? '') == 'biblioteca' ? 'selected' : ''; ?>>Biblioteca</option>
                                <option value="oficina" <?php echo ($ubicacion['tipo'] ?? '') == 'oficina' ? 'selected' : ''; ?>>Oficina</option>
                                <option value="bodega" <?php echo ($ubicacion['tipo'] ?? '') == 'bodega' ? 'selected' : ''; ?>>Bodega</option>
                                <option value="otro" <?php echo ($ubicacion['tipo'] ?? '') == 'otro' ? 'selected' : ''; ?>>Otro</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Responsable</label>
                            <select name="responsable_id" class="form-control">
                                <option value="">-- Sin asignar --</option>
                                <?php while($p = $personas->fetch_assoc()): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo ($ubicacion['responsable_id'] ?? '') == $p['id'] ? 'selected' : ''; ?>>
                                        <?php echo $p['nombres']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"><?php echo htmlspecialchars($ubicacion['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="listar.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Actualizar Ubicación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>