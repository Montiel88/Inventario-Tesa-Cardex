<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin(); // Solo admin puede agregar

require_once '../../config/database.php';
include '../../includes/header.php';

$error = '';
$success = '';

// Obtener lista de personas para responsable
$personas = $conn->query("SELECT id, nombres FROM personas ORDER BY nombres");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo_ubicacion = $conn->real_escape_string($_POST['codigo_ubicacion'] ?? '');
    $nombre = $conn->real_escape_string($_POST['nombre'] ?? '');
    $tipo = $conn->real_escape_string($_POST['tipo'] ?? '');
    $responsable_id = !empty($_POST['responsable_id']) ? intval($_POST['responsable_id']) : 'NULL';
    $descripcion = $conn->real_escape_string($_POST['descripcion'] ?? '');

    if (empty($codigo_ubicacion) || empty($nombre) || empty($tipo)) {
        $error = "❌ Los campos Código, Nombre y Tipo son obligatorios.";
    } else {
        $sql = "INSERT INTO ubicaciones (codigo_ubicacion, nombre, tipo, responsable_id, descripcion) 
                VALUES ('$codigo_ubicacion', '$nombre', '$tipo', $responsable_id, '$descripcion')";

        if ($conn->query($sql)) {
            $success = "✅ Ubicación agregada correctamente.";
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Ubicación guardada',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => window.location.href = 'listar.php');
            </script>";
        } else {
            $error = "❌ Error al guardar: " . $conn->error;
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Nueva Ubicación</h4>
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
                                <input type="text" name="codigo_ubicacion" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo *</label>
                            <select name="tipo" class="form-control" required>
                                <option value="">-- Seleccione --</option>
                                <option value="salon">Salón</option>
                                <option value="laboratorio">Laboratorio</option>
                                <option value="biblioteca">Biblioteca</option>
                                <option value="oficina">Oficina</option>
                                <option value="bodega">Bodega</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Responsable</label>
                            <select name="responsable_id" class="form-control">
                                <option value="">-- Sin asignar --</option>
                                <?php while($p = $personas->fetch_assoc()): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo $p['nombres']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="listar.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Ubicación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>