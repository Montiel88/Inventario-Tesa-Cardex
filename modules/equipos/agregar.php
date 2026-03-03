<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
$es_admin = ($_SESSION['user_rol'] == 1);
if (!$es_admin) {
    header('Location: listar.php?error=No tienes permisos');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

// Obtener lista de ubicaciones para el selector
$ubicaciones = $conn->query("SELECT id, codigo_ubicacion, nombre FROM ubicaciones ORDER BY nombre");

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
    $ubicacion_id = !empty($_POST['ubicacion_id']) ? intval($_POST['ubicacion_id']) : 'NULL';
    $estado = 'Disponible';

    if (empty($tipo_equipo)) {
        $error = "❌ El tipo de equipo es obligatorio";
    } else {
        if (empty($codigo_barras)) {
            $result = $conn->query("SELECT MAX(id) as max_id FROM equipos");
            $row = $result->fetch_assoc();
            $next_id = ($row['max_id'] ?? 0) + 1;
            $codigo_barras = 'PRO-' . str_pad($next_id, 6, '0', STR_PAD_LEFT);
        }

        $sql = "INSERT INTO equipos (codigo_barras, tipo_equipo, marca, modelo, numero_serie, especificaciones, observaciones, ubicacion_id, estado) 
                VALUES ('$codigo_barras', '$tipo_equipo', '$marca', '$modelo', '$numero_serie', '$especificaciones', '$observaciones', $ubicacion_id, '$estado')";

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
                        <div class="alert alert-success"><?php echo $mensaje; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código de Barras</label>
                                <input type="text" name="codigo_barras" class="form-control" placeholder="Dejar vacío para generar automático">
                                <small class="text-muted">Si deja vacío, se generará automáticamente</small>
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
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-control" placeholder="Ej: HP, Dell">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" class="form-control" placeholder="Ej: Pavilion">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Número de Serie</label>
                                <input type="text" name="numero_serie" class="form-control" placeholder="Serie del fabricante">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Especificaciones</label>
                            <textarea name="especificaciones" class="form-control" rows="3" placeholder="RAM, procesador, disco duro, etc."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ubicación</label>
                            <select name="ubicacion_id" class="form-control">
                                <option value="">-- Sin ubicación --</option>
                                <?php while($ub = $ubicaciones->fetch_assoc()): ?>
                                    <option value="<?php echo $ub['id']; ?>">
                                        <?php echo $ub['codigo_ubicacion'] . ' - ' . $ub['nombre']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Guardar Equipo
                            </button>
                          
                     <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>