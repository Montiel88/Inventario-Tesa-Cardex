<?php
session_start();
require_once '../../config/permisos.php';
require_once '../../config/database.php';
verificarSesion();
requiereAdmin(); // Solo admin puede crear mantenimientos

include '../../includes/header.php';

$mensaje = '';
$error = '';

// Obtener lista de equipos (solo disponibles y asignados, no en mantenimiento)
$equipos = $conn->query("SELECT id, codigo_barras, tipo_equipo, marca, modelo 
                         FROM equipos 
                         WHERE estado != 'En mantenimiento' AND estado != 'Baja'
                         ORDER BY tipo_equipo, marca");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipo_id = intval($_POST['equipo_id']);
    $tipo = $conn->real_escape_string($_POST['tipo_mantenimiento']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $tecnico = $conn->real_escape_string($_POST['tecnico'] ?? '');
    $proveedor = $conn->real_escape_string($_POST['proveedor'] ?? '');
    $costo = !empty($_POST['costo']) ? floatval($_POST['costo']) : 'NULL';
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    $usuario_id = $_SESSION['user_id'];

    if (empty($equipo_id) || empty($tipo) || empty($descripcion)) {
        $error = "❌ El equipo, tipo y descripción son obligatorios";
    } else {
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Insertar mantenimiento
            $sql = "INSERT INTO mantenimientos 
                    (equipo_id, fecha_ingreso, tipo_mantenimiento, descripcion, tecnico, proveedor, costo, observaciones, created_by) 
                    VALUES 
                    ($equipo_id, NOW(), '$tipo', '$descripcion', '$tecnico', '$proveedor', $costo, '$observaciones', $usuario_id)";
            
            if ($conn->query($sql)) {
                // Actualizar estado del equipo a "En mantenimiento"
                $conn->query("UPDATE equipos SET estado = 'En mantenimiento' WHERE id = $equipo_id");
                
                $conn->commit();
                $mensaje = "✅ Mantenimiento registrado correctamente";
                
                // Redirigir después de 2 segundos
                echo "<script>
                    setTimeout(() => window.location.href = 'listar.php', 2000);
                </script>";
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "❌ Error: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-tools me-2"></i>Registrar Mantenimiento</h4>
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
                            <label class="form-label">Equipo *</label>
                            <select name="equipo_id" class="form-control" required>
                                <option value="">-- Seleccione un equipo --</option>
                                <?php while($eq = $equipos->fetch_assoc()): ?>
                                    <option value="<?php echo $eq['id']; ?>">
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
                                    <option value="preventivo">🔧 Preventivo</option>
                                    <option value="correctivo">⚡ Correctivo</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción del problema/trabajo *</label>
                            <textarea name="descripcion" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Técnico responsable</label>
                                <input type="text" name="tecnico" class="form-control" placeholder="Nombre del técnico">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Proveedor/Taller</label>
                                <input type="text" name="proveedor" class="form-control" placeholder="Nombre del proveedor o taller">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Costo estimado</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" name="costo" class="form-control" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones adicionales</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="listar.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Registrar Mantenimiento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>