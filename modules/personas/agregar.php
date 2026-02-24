<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// SOLO ADMIN PUEDE AGREGAR PERSONAS
if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=No tienes permisos para agregar personas');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';

// ============================================
// PROCESAR EL FORMULARIO CUANDO SE ENVÍA
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
    
    // Si no hay errores, proceder a insertar
    if (empty($errores)) {
        
        // Verificar si la cédula ya existe
        $check_sql = "SELECT id FROM personas WHERE cedula = '$cedula'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $error = "❌ La cédula $cedula ya está registrada en el sistema. No se puede duplicar.";
        } else {
            // Insertar nueva persona
            $sql = "INSERT INTO personas (cedula, nombres, correo, cargo, telefono, observaciones) 
                    VALUES ('$cedula', '$nombres', '$correo', '$cargo', '$telefono', '$observaciones')";
            
            if ($conn->query($sql)) {
                $mensaje = "✅ Persona registrada exitosamente";
                // Redirigir después de 2 segundos
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Persona registrada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'listar.php';
                    });
                </script>";
            } else {
                $error = "❌ Error al guardar: " . $conn->error;
            }
        }
    } else {
        // Mostrar errores de validación
        $error = implode("<br>", $errores);
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Agregar Nueva Persona</h4>
                </div>
                <div class="card-body">
                    
                    <!-- MENSAJES DE ÉXITO O ERROR -->
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
                    
                    <!-- AVISO PARA ADMIN -->
                    <div class="alert alert-info mb-4">
                        <div class="d-flex">
                            <i class="fas fa-shield-alt fa-2x me-3"></i>
                            <div>
                                <strong>Modo Administrador</strong>
                                <p class="mb-0">Estás agregando una nueva persona al sistema. Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- FORMULARIO -->
                    <form method="POST" action="" id="formPersona">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cédula <span class="text-danger">*</span></label>
                                <input type="text" name="cedula" class="form-control" 
                                       value="<?php echo isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : ''; ?>" 
                                       placeholder="10 dígitos sin puntos ni guiones" 
                                       maxlength="10" 
                                       pattern="[0-9]{10}"
                                       required>
                                <small class="text-muted">Ej: 1802984326</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombres Completos <span class="text-danger">*</span></label>
                                <input type="text" name="nombres" class="form-control" 
                                       value="<?php echo isset($_POST['nombres']) ? htmlspecialchars($_POST['nombres']) : ''; ?>" 
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" name="correo" class="form-control" 
                                       value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>"
                                       placeholder="ejemplo@tesa.edu.ec">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cargo <span class="text-danger">*</span></label>
                                <input type="text" name="cargo" class="form-control" 
                                       value="<?php echo isset($_POST['cargo']) ? htmlspecialchars($_POST['cargo']) : ''; ?>" 
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" 
                                       value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>"
                                       maxlength="10"
                                       pattern="[0-9]{7,10}">
                                <small class="text-muted">Ej: 0987654321</small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="3"><?php echo isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="listar.php" class="btn btn-secondary btn-lg px-5">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Guardar Persona
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
    
    // Validar cédula
    if (cedula && !/^\d{10}$/.test(cedula)) {
        e.preventDefault();
        alert('La cédula debe tener exactamente 10 dígitos numéricos');
        return false;
    }
    
    // Validar teléfono si se ingresó
    if (telefono && !/^\d{7,10}$/.test(telefono)) {
        e.preventDefault();
        alert('El teléfono debe tener entre 7 y 10 dígitos numéricos');
        return false;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>