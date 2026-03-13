<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin(); // Solo admin puede editar equipos

require_once '../../config/database.php';
include '../../includes/header.php';

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos del equipo
$sql = "SELECT * FROM equipos WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}

$equipo = $result->fetch_assoc();
$error = '';
$success = '';

// Obtener lista de ubicaciones para el selector
$ubicaciones = $conn->query("SELECT id, codigo_ubicacion, nombre FROM ubicaciones ORDER BY nombre");

// Procesar el formulario al enviar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $codigo_barras = $conn->real_escape_string($_POST['codigo_barras']);
    $tipo_equipo = $conn->real_escape_string($_POST['tipo_equipo']);
    $marca = $conn->real_escape_string($_POST['marca'] ?? '');
    $modelo = $conn->real_escape_string($_POST['modelo'] ?? '');
    $numero_serie = $conn->real_escape_string($_POST['numero_serie'] ?? '');
    $especificaciones = $conn->real_escape_string($_POST['especificaciones'] ?? '');
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    $estado = $conn->real_escape_string($_POST['estado'] ?? 'Disponible');
    $ubicacion_id = !empty($_POST['ubicacion_id']) ? intval($_POST['ubicacion_id']) : 'NULL';
    
    // Procesar foto
    $foto_update_sql = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $carpeta_fotos = '../../uploads/equipos/';
            if (!file_exists($carpeta_fotos)) {
                mkdir($carpeta_fotos, 0777, true);
            }
            
            // Eliminar foto anterior si existe
            if (!empty($equipo['foto']) && file_exists('../../' . $equipo['foto'])) {
                unlink('../../' . $equipo['foto']);
            }

            $nuevo_nombre = 'equipo_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $destino = $carpeta_fotos . $nuevo_nombre;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                $foto_ruta = 'uploads/equipos/' . $nuevo_nombre;
                $foto_update_sql = ", foto = '$foto_ruta'";
            }
        }
    }

    if (empty($tipo_equipo)) {
        $error = "❌ El tipo de equipo es obligatorio";
    } else {
        $sql = "UPDATE equipos SET 
                codigo_barras = '$codigo_barras',
                tipo_equipo = '$tipo_equipo',
                marca = '$marca',
                modelo = '$modelo',
                numero_serie = '$numero_serie',
                especificaciones = '$especificaciones',
                observaciones = '$observaciones',
                estado = '$estado',
                ubicacion_id = $ubicacion_id
                $foto_update_sql
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            $success = "✅ Equipo actualizado correctamente";
            // Recargar datos actualizados
            $result = $conn->query("SELECT * FROM equipos WHERE id = $id");
            $equipo = $result->fetch_assoc();
        } else {
            $error = "❌ Error al actualizar: " . $conn->error;
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Equipo</h4>
                    <span class="badge bg-warning text-dark">ID: #<?php echo $equipo['id']; ?></span>
                </div>
                <div class="card-body">
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código de Barras</label>
                                <input type="text" name="codigo_barras" class="form-control" 
                                       value="<?php echo htmlspecialchars($equipo['codigo_barras']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Equipo *</label>
                                <select name="tipo_equipo" class="form-control" required>
                                    <option value="">-- Seleccione --</option>
                                    <option value="Laptop" <?php echo $equipo['tipo_equipo'] == 'Laptop' ? 'selected' : ''; ?>>💻 Laptop</option>
                                    <option value="Mouse" <?php echo $equipo['tipo_equipo'] == 'Mouse' ? 'selected' : ''; ?>>🖱️ Mouse</option>
                                    <option value="Teclado" <?php echo $equipo['tipo_equipo'] == 'Teclado' ? 'selected' : ''; ?>>⌨️ Teclado</option>
                                    <option value="Monitor" <?php echo $equipo['tipo_equipo'] == 'Monitor' ? 'selected' : ''; ?>>🖥️ Monitor</option>
                                    <option value="Impresora" <?php echo $equipo['tipo_equipo'] == 'Impresora' ? 'selected' : ''; ?>>🖨️ Impresora</option>
                                    <option value="Proyector" <?php echo $equipo['tipo_equipo'] == 'Proyector' ? 'selected' : ''; ?>>📽️ Proyector</option>
                                    <option value="Tablet" <?php echo $equipo['tipo_equipo'] == 'Tablet' ? 'selected' : ''; ?>>📱 Tablet</option>
                                    <option value="Parlantes" <?php echo $equipo['tipo_equipo'] == 'Parlantes' ? 'selected' : ''; ?>>🔊 Parlantes</option>
                                    <option value="Cámara" <?php echo $equipo['tipo_equipo'] == 'Cámara' ? 'selected' : ''; ?>>📷 Cámara</option>
                                    <option value="Otro" <?php echo $equipo['tipo_equipo'] == 'Otro' ? 'selected' : ''; ?>>🔧 Otro</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-control" 
                                       value="<?php echo htmlspecialchars($equipo['marca'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" class="form-control" 
                                       value="<?php echo htmlspecialchars($equipo['modelo'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Número de Serie</label>
                                <input type="text" name="numero_serie" class="form-control" 
                                       value="<?php echo htmlspecialchars($equipo['numero_serie'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="Disponible" <?php echo $equipo['estado'] == 'Disponible' ? 'selected' : ''; ?>>Disponible</option>
                                    <option value="Asignado" <?php echo $equipo['estado'] == 'Asignado' ? 'selected' : ''; ?>>Asignado</option>
                                    <option value="Mantenimiento" <?php echo $equipo['estado'] == 'Mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
                                    <option value="Baja" <?php echo $equipo['estado'] == 'Baja' ? 'selected' : ''; ?>>Baja</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ubicación</label>
                                <select name="ubicacion_id" class="form-control">
                                    <option value="">-- Sin ubicación --</option>
                                    <?php while($ub = $ubicaciones->fetch_assoc()): ?>
                                        <option value="<?php echo $ub['id']; ?>" 
                                            <?php echo ($equipo['ubicacion_id'] ?? '') == $ub['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ub['codigo_ubicacion'] . ' - ' . $ub['nombre']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cambiar Foto (Opcional)</label>
                                <input type="file" name="foto" class="form-control" accept="image/*">
                            </div>

                            <?php if (!empty($equipo['foto'])): ?>
                                <div class="col-12 mb-3">
                                    <label class="form-label d-block">Foto Actual:</label>
                                    <img src="../../<?php echo $equipo['foto']; ?>" alt="Equipo" class="img-thumbnail" style="max-height: 200px;">
                                </div>
                            <?php endif; ?>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Especificaciones</label>
                                <textarea name="especificaciones" class="form-control" rows="3"><?php echo htmlspecialchars($equipo['especificaciones'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2"><?php echo htmlspecialchars($equipo['observaciones'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-3">
                            <a href="listar.php" class="btn btn-secondary btn-lg px-5">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Actualizar Equipo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>