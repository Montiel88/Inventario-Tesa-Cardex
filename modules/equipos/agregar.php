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
require_once '../../config/listas.php'; // ← AGREGADO: Lista completa de equipos
include '../../includes/header.php';

// Asegurar que SweetAlert2 esté disponible
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

// Obtener lista de ubicaciones para el selector
$ubicaciones = $conn->query("SELECT id, codigo_ubicacion, nombre FROM ubicaciones ORDER BY nombre");

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo_barras = trim($conn->real_escape_string($_POST['codigo_barras'] ?? ''));
    $tipo_equipo = $conn->real_escape_string($_POST['tipo_equipo'] ?? '');
    $marca = $conn->real_escape_string($_POST['marca'] ?? '');
    $modelo = $conn->real_escape_string($_POST['modelo'] ?? '');
    $numero_serie = $conn->real_escape_string($_POST['numero_serie'] ?? '');
    $especificaciones = $conn->real_escape_string($_POST['especificaciones'] ?? '');
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    $ubicacion_id = !empty($_POST['ubicacion_id']) ? intval($_POST['ubicacion_id']) : 'NULL';
    $estado = 'Disponible';

    // Procesar foto
    $foto_ruta = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $carpeta_fotos = '../../uploads/equipos/';
            if (!file_exists($carpeta_fotos)) {
                mkdir($carpeta_fotos, 0777, true);
            }
            
            $nuevo_nombre = 'equipo_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $destino = $carpeta_fotos . $nuevo_nombre;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                $foto_ruta = 'uploads/equipos/' . $nuevo_nombre;
            }
        }
    }

    if (empty($tipo_equipo)) {
        $error = "❌ El tipo de equipo es obligatorio";
    } else {
        // 👉 CASO 1: El usuario ingresó un código manualmente
        if (!empty($codigo_barras)) {
            // Verificar que el código no exista
            $check = $conn->query("SELECT id FROM equipos WHERE codigo_barras = '$codigo_barras'");
            if ($check->num_rows > 0) {
                $error = "❌ El código de barras '$codigo_barras' ya existe. Usa otro o déjalo vacío para generar uno automático.";
            }
        } 
        // 👉 CASO 2: El usuario dejó vacío → generar código interno
        else {
            $result = $conn->query("SELECT MAX(id) as max_id FROM equipos");
            $row = $result->fetch_assoc();
            $next_id = ($row['max_id'] ?? 0) + 1;
            $codigo_barras = 'PRO-' . str_pad($next_id, 6, '0', STR_PAD_LEFT);
        }

        // Si no hay error, proceder a insertar
        if (empty($error)) {
            $sql = "INSERT INTO equipos (codigo_barras, tipo_equipo, marca, modelo, numero_serie, especificaciones, observaciones, ubicacion_id, estado, foto) 
                    VALUES ('$codigo_barras', '$tipo_equipo', '$marca', '$modelo', '$numero_serie', '$especificaciones', '$observaciones', $ubicacion_id, '$estado', '$foto_ruta')";

            if ($conn->query($sql)) {
                $equipo_id = $conn->insert_id;
                $mensaje = "✅ Equipo registrado exitosamente. Código: $codigo_barras";
                
                // Script para preguntar si generar acta de ingreso
                echo "<script>
                    Swal.fire({
                        title: '¿Generar acta de ingreso?',
                        text: 'Equipo guardado correctamente',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, generar acta',
                        cancelButtonText: 'No, solo guardar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open('/inventario_ti/api/generar_acta_ingreso.php?equipo_id=$equipo_id', '_blank');
                        }
                        window.location.href = 'listar.php?mensaje=Equipo agregado correctamente';
                    });
                </script>";
                
            } else {
                $error = "❌ Error al guardar: " . $conn->error;
            }
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

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código de Barras</label>
                                <input type="text" name="codigo_barras" class="form-control" 
                                       placeholder="Ingrese código del equipo o deje vacío">
                                <small class="text-muted">
                                    ✅ Si el equipo tiene su propio código, ingrésalo aquí.<br>
                                    ✅ Si no tiene o no se puede leer, déjalo vacío y el sistema generará uno interno (ej: PRO-000123).
                                </small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Equipo *</label>
                                <select name="tipo_equipo" class="form-control" required>
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach($tipos_equipos as $valor => $etiqueta): ?>
                                        <option value="<?php echo $valor; ?>"><?php echo $etiqueta; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">📋 <?php echo count($tipos_equipos); ?> tipos disponibles</small>
                            </div>
                        </div>
                        
                        <div class="row">
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
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
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
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Foto del Equipo (Opcional)</label>
                                <input type="file" name="foto" class="form-control" accept="image/*">
                                <small class="text-muted">Formatos permitidos: JPG, PNG, GIF</small>
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