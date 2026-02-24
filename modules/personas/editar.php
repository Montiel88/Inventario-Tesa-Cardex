<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// SOLO ADMIN PUEDE EDITAR PERSONAS
if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=No tienes permisos para editar personas');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

// Verificar si viene el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos actuales
$sql = "SELECT * FROM personas WHERE id = $id";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}

$persona = $result->fetch_assoc();
$error = '';
$success = '';

// ============================================
// PROCESAR EL FORMULARIO
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Obtener y limpiar datos
    $cedula = trim($conn->real_escape_string($_POST['cedula']));
    $nombres = trim($conn->real_escape_string($_POST['nombres']));
    $correo = trim($conn->real_escape_string($_POST['correo'] ?? ''));
    $cargo = trim($conn->real_escape_string($_POST['cargo']));
    $telefono = trim($conn->real_escape_string($_POST['telefono'] ?? ''));
    $observaciones = trim($conn->real_escape_string($_POST['observaciones'] ?? ''));
    
    // Validar campos obligatorios
    $errores = [];
    
    if (empty($cedula)) {
        $errores[] = "La cédula es obligatoria";
    } elseif (!preg_match('/^\d{10}$/', $cedula)) {
        $errores[] = "La cédula debe tener 10 dígitos numéricos";
    }
    
    if (empty($nombres)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (empty($cargo)) {
        $errores[] = "El cargo es obligatorio";
    }
    
    // Validar correo si se proporcionó
    if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    }
    
    // Si no hay errores, proceder a actualizar
    if (empty($errores)) {
        
        // Verificar si la cédula ya existe (excepto la actual)
        $check_sql = "SELECT id FROM personas WHERE cedula = '$cedula' AND id != $id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $error = "❌ La cédula $cedula ya está registrada en otra persona. No se puede duplicar.";
        } else {
            // Actualizar persona
            $sql = "UPDATE personas SET 
                    cedula = '$cedula',
                    nombres = '$nombres',
                    correo = '$correo',
                    cargo = '$cargo',
                    telefono = '$telefono',
                    observaciones = '$observaciones'
                    WHERE id = $id";
            
            if ($conn->query($sql)) {
                $success = "Persona actualizada correctamente";
                // Recargar datos actualizados
                $result = $conn->query("SELECT * FROM personas WHERE id = $id");
                $persona = $result->fetch_assoc();
                
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: 'Persona actualizada correctamente',
                        timer: 2000,
                        showConfirmButton: true,
                        confirmButtonColor: '#5a2d8c'
                    });
                </script>";
            } else {
                $error = "❌ Error al actualizar: " . $conn->error;
            }
        }
    } else {
        $error = implode("<br>", $errores);
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Persona</h4>
                    <span class="badge bg-warning text-dark">ID: #<?php echo $persona['id']; ?></span>
                </div>
                
                <div class="card-body">
                    
                    <!-- Mensajes de error -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Información para el admin -->
                    <div class="alert alert-warning mb-4">
                        <div class="d-flex">
                            <i class="fas fa-edit fa-2x me-3" style="color: #f3b229;"></i>
                            <div>
                                <strong>Modo Edición</strong>
                                <p class="mb-0">Estás editando los datos de <strong><?php echo htmlspecialchars($persona['nombres']); ?></strong>. Los campos con <span class="text-danger">*</span> son obligatorios.</p>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" id="formPersona">
                        <div class="row">
                            <!-- Cédula -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cédula <span class="text-danger">*</span></label>
                                <input type="text" name="cedula" class="form-control" 
                                       value="<?php echo htmlspecialchars($persona['cedula']); ?>" 
                                       required 
                                       maxlength="10"
                                       pattern="[0-9]{10}"
                                       title="Ingrese 10 dígitos numéricos">
                                <small class="text-muted">10 dígitos sin guiones</small>
                            </div>
                            
                            <!-- Nombres -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombres Completos <span class="text-danger">*</span></label>
                                <input type="text" name="nombres" class="form-control" 
                                       value="<?php echo htmlspecialchars($persona['nombres']); ?>" 
                                       required>
                            </div>
                            
                            <!-- Correo -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" name="correo" class="form-control" 
                                       value="<?php echo htmlspecialchars($persona['correo'] ?? ''); ?>"
                                       placeholder="ejemplo@tesa.edu.ec">
                            </div>
                            
                            <!-- Cargo -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cargo <span class="text-danger">*</span></label>
                                <input type="text" name="cargo" class="form-control" 
                                       value="<?php echo htmlspecialchars($persona['cargo']); ?>" 
                                       required>
                            </div>
                            
                            <!-- Teléfono -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" 
                                       value="<?php echo htmlspecialchars($persona['telefono'] ?? ''); ?>"
                                       maxlength="10"
                                       pattern="[0-9]{7,10}"
                                       title="Ingrese solo números (7-10 dígitos)">
                                <small class="text-muted">Ej: 0987654321</small>
                            </div>
                            
                            <!-- Observaciones -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="3"><?php echo htmlspecialchars($persona['observaciones'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="listar.php" class="btn btn-secondary btn-lg px-4">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-save me-2"></i>Actualizar Datos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script de validación adicional -->
<script>
document.getElementById('formPersona')?.addEventListener('submit', function(e) {
    const cedula = document.querySelector('input[name="cedula"]').value;
    const telefono = document.querySelector('input[name="telefono"]').value;
    const cedulaOriginal = '<?php echo $persona['cedula']; ?>';
    
    // Validar cédula
    if (cedula && !/^\d{10}$/.test(cedula)) {
        e.preventDefault();
        alert('La cédula debe tener exactamente 10 dígitos numéricos');
        return false;
    }
    
    // Validar teléfono
    if (telefono && !/^\d{7,10}$/.test(telefono)) {
        e.preventDefault();
        alert('El teléfono debe tener entre 7 y 10 dígitos numéricos');
        return false;
    }
    
    // Confirmar si cambió la cédula
    if (cedula !== cedulaOriginal) {
        if (!confirm('¿La cédula cambiará de ' + cedulaOriginal + ' a ' + cedula + '. ¿Estás seguro?')) {
            e.preventDefault();
            return false;
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>