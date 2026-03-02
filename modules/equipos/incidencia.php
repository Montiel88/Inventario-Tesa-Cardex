<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin(); // Solo admin puede registrar incidencias

require_once '../../config/database.php';
include '../../includes/header.php';

$equipo_id = isset($_GET['equipo_id']) ? intval($_GET['equipo_id']) : 0;
$equipo = null;
if ($equipo_id > 0) {
    $result = $conn->query("SELECT id, codigo_barras, tipo_equipo FROM equipos WHERE id = $equipo_id");
    if ($result->num_rows > 0) {
        $equipo = $result->fetch_assoc();
    }
}

$personas = $conn->query("SELECT id, nombres FROM personas ORDER BY nombres");
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipo_id = intval($_POST['equipo_id']);
    $tipo = $conn->real_escape_string($_POST['tipo_incidencia']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $persona_id = !empty($_POST['persona_id']) ? intval($_POST['persona_id']) : 'NULL';
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');

    if (empty($tipo) || empty($descripcion)) {
        $error = "❌ El tipo y la descripción son obligatorios.";
    } else {
        $sql = "INSERT INTO incidencias (equipo_id, persona_id, tipo_incidencia, descripcion, observaciones, usuario_registro)
                VALUES ($equipo_id, $persona_id, '$tipo', '$descripcion', '$observaciones', {$_SESSION['user_id']})";
        if ($conn->query($sql)) {
            $success = "✅ Incidencia registrada correctamente.";
            echo "<script>setTimeout(() => window.location.href = 'historial.php?id=$equipo_id', 1500);</script>";
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
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Registrar Incidencia</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <?php if (!$equipo): ?>
                        <div class="alert alert-warning">No se ha especificado un equipo válido.</div>
                    <?php else: ?>
                        <p><strong>Equipo:</strong> <?php echo $equipo['codigo_barras'] . ' - ' . $equipo['tipo_equipo']; ?></p>
                        <form method="POST">
                            <input type="hidden" name="equipo_id" value="<?php echo $equipo_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Tipo de incidencia *</label>
                                <select name="tipo_incidencia" class="form-control" required>
                                    <option value="">-- Seleccione --</option>
                                    <option value="daño">Daño</option>
                                    <option value="reparación">Reparación</option>
                                    <option value="mantenimiento">Mantenimiento</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción *</label>
                                <textarea name="descripcion" class="form-control" rows="3" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Persona relacionada (opcional)</label>
                                <select name="persona_id" class="form-control">
                                    <option value="">-- Ninguna --</option>
                                    <?php while($p = $personas->fetch_assoc()): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo $p['nombres']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Observaciones adicionales</label>
                                <textarea name="observaciones" class="form-control" rows="2"></textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="historial.php?id=<?php echo $equipo_id; ?>" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-danger">Registrar incidencia</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>