<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin(); // Solo admin puede agregar equipos

require_once '../../config/database.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $codigo_barras = $conn->real_escape_string($_POST['codigo_barras'] ?? '');
    $tipo_equipo = $conn->real_escape_string($_POST['tipo_equipo'] ?? '');
    $marca = $conn->real_escape_string($_POST['marca'] ?? '');
    $modelo = $conn->real_escape_string($_POST['modelo'] ?? '');
    $numero_serie = $conn->real_escape_string($_POST['numero_serie'] ?? '');
    $especificaciones = $conn->real_escape_string($_POST['especificaciones'] ?? '');
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    
    if (empty($tipo_equipo)) {
        $error = "❌ El tipo de equipo es obligatorio";
    } else {
        
        if (empty($codigo_barras)) {
            $result = $conn->query("SELECT MAX(id) as max_id FROM equipos");
            $row = $result->fetch_assoc();
            $next_id = ($row['max_id'] ?? 0) + 1;
            $codigo_barras = 'PRO-' . str_pad($next_id, 6, '0', STR_PAD_LEFT);
        }
        
        $sql = "INSERT INTO equipos (codigo_barras, tipo_equipo, marca, modelo, numero_serie, especificaciones, observaciones, estado) 
                VALUES ('$codigo_barras', '$tipo_equipo', '$marca', '$modelo', '$numero_serie', '$especificaciones', '$observaciones', 'Disponible')";
        
        if ($conn->query($sql)) {
            $mensaje = "✅ Equipo registrado exitosamente. Código: $codigo_barras";
        } else {
            $error = "❌ Error al guardar: " . $conn->error;
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Agregar Nuevo Equipo</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código de Barras</label>
                                <input type="text" name="codigo_barras" class="form-control" placeholder="Dejar vacío para generar automático">
                                <small class="text-muted">Si deja vacío, se generará automáticamente (ej: PRO-000001)</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Equipo *</label>
                                <select name="tipo_equipo" class="form-control" required>
                                    <option value="">-- Seleccione --</option>
                                    <option value="Laptop">💻 Laptop</option>
                                    <option value="Mouse">🖱️ Mouse</option>
                                    <option value="Teclado">⌨️ Teclado</option>
                                    <option value="Monitor">🖥️ Monitor</option>
                                    <option value="Impresora">🖨️ Impresora</option>
                                    <option value="Proyector">📽️ Proyector</option>
                                    <option value="Tablet">📱 Tablet</option>
                                    <option value="Parlantes">🔊 Parlantes</option>
                                    <option value="Cámara">📷 Cámara</option>
                                    <option value="Otro">🔧 Otro</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-control" placeholder="Ej: HP, Dell, Logitech">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" class="form-control" placeholder="Ej: Pavilion, Latitude">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Número de Serie</label>
                                <input type="text" name="numero_serie" class="form-control" placeholder="Serie del fabricante">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Especificaciones</label>
                                <textarea name="especificaciones" class="form-control" rows="3" placeholder="RAM, procesador, disco duro, etc."></textarea>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales"></textarea>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Guardar Equipo
                            </button>
                            <a href="listar.php" class="btn btn-secondary btn-lg px-5">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>