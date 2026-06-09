<?php
session_start();
require_once '../../config/permisos.php';
require_once '../../config/database.php';
verificarSesion();
requiereAdmin();

include '../../includes/header.php';

$mensaje = '';
$error = '';

// ============================================
// PROCESAR ACCIONES DEL FORMULARIO
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    
    // CREAR NUEVO USUARIO
    if ($_POST['accion'] == 'crear') {
        $nombre = $conn->real_escape_string($_POST['nombre']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $rol = $_POST['rol'] == 'admin' ? 1 : 2;
        
        // Verificar si el email ya existe
        $check = $conn->query("SELECT id FROM usuarios WHERE email = '$email'");
        if ($check && $check->num_rows > 0) {
            $error = "❌ El email ya está registrado";
        } else {
            $sql = "INSERT INTO usuarios (nombre, email, password, rol, ultimo_acceso, created_at) 
                    VALUES ('$nombre', '$email', '$password', '$rol', NOW(), NOW())";
            if ($conn->query($sql)) {
                $mensaje = "✅ Usuario creado correctamente";
            } else {
                $error = "❌ Error: " . $conn->error;
            }
        }
    }
    
    // CAMBIAR ROL (ADMIN/LECTOR)
    if ($_POST['accion'] == 'cambiar_rol' && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $nuevo_rol = $_POST['nuevo_rol'] == 'admin' ? 1 : 2;
        
        // No permitir cambiarse a sí mismo
        if ($user_id == $_SESSION['user_id']) {
            $error = "❌ No puedes cambiar tu propio rol";
        } else {
            if ($conn->query("UPDATE usuarios SET rol = '$nuevo_rol' WHERE id = $user_id")) {
                $mensaje = "✅ Rol actualizado correctamente";
            } else {
                $error = "❌ Error: " . $conn->error;
            }
        }
    }
    
    // ELIMINAR USUARIO
    if ($_POST['accion'] == 'eliminar' && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        
        // No permitir eliminarse a sí mismo
        if ($user_id == $_SESSION['user_id']) {
            $error = "❌ No puedes eliminar tu propio usuario";
        } else {
            if ($conn->query("DELETE FROM usuarios WHERE id = $user_id")) {
                $mensaje = "✅ Usuario eliminado permanentemente";
            } else {
                $error = "❌ Error: " . $conn->error;
            }
        }
    }
    
    // RESETEAR CONTRASEÑA
    if ($_POST['accion'] == 'reset_password' && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $nueva_password = password_hash('Tesa2024!', PASSWORD_DEFAULT);
        if ($conn->query("UPDATE usuarios SET password = '$nueva_password' WHERE id = $user_id")) {
            $mensaje = "✅ Contraseña reseteada a: <strong>Tesa2024!</strong>";
        } else {
            $error = "❌ Error: " . $conn->error;
        }
    }
}

// ============================================
// OBTENER LISTA DE USUARIOS
// ============================================
$sql = "SELECT id, nombre, email, rol, ultimo_acceso, created_at FROM usuarios ORDER BY id";
$result = $conn->query($sql);

// Verificar si la consulta falló
if (!$result) {
    die("Error en la base de datos: " . $conn->error . "<br>SQL: " . $sql);
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-users-cog me-2"></i>Gestión de Usuarios del Sistema</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                        <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
                    </button>
                </div>
                <div class="card-body">
                    
                    <!-- Mensajes de éxito/error -->
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
                    
                    <!-- Tabla de usuarios -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Último Acceso</th>
                                    <th>Fecha Creación</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($user = $result->fetch_assoc()): 
                                        $es_admin = ($user['rol'] == 1 || $user['rol'] == '1' || $user['rol'] == 'admin');
                                        $es_mismo = ($user['id'] == $_SESSION['user_id']);
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo $user['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php if ($es_admin): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-crown me-1"></i> ADMIN
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-eye me-1"></i> LECTOR
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($user['ultimo_acceso']) && $user['ultimo_acceso'] != '0000-00-00 00:00:00') {
                                                echo date('d/m/Y H:i', strtotime($user['ultimo_acceso']));
                                            } else {
                                                echo '<span class="text-muted">Nunca</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($user['created_at'])) {
                                                echo date('d/m/Y', strtotime($user['created_at']));
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                
                                                <!-- Botón Cambiar Rol -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="accion" value="cambiar_rol">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="nuevo_rol" value="<?php echo $es_admin ? 'lector' : 'admin'; ?>">
                                                    <button type="submit" class="btn btn-sm btn-warning" 
                                                            <?php echo $es_mismo ? 'disabled' : ''; ?>
                                                            title="<?php echo $es_mismo ? 'No puedes cambiarte a ti mismo' : 'Cambiar a ' . ($es_admin ? 'LECTOR' : 'ADMIN'); ?>">
                                                        <i class="fas fa-sync-alt"></i>
                                                        <span class="d-none d-md-inline ms-1">Cambiar Rol</span>
                                                    </button>
                                                </form>
                                                
                                                <!-- Botón Resetear Contraseña -->
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('⚠️ ¿Resetear contraseña para este usuario?\n\nLa nueva contraseña será: Tesa2024!')">
                                                    <input type="hidden" name="accion" value="reset_password">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-info" title="Resetear contraseña">
                                                        <i class="fas fa-key"></i>
                                                        <span class="d-none d-md-inline ms-1">Resetear</span>
                                                    </button>
                                                </form>
                                                
                                                <!-- Botón Eliminar -->
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('⚠️ ¿ELIMINAR USUARIO?\n\nUsuario: <?php echo addslashes($user['nombre']); ?>\nEmail: <?php echo addslashes($user['email']); ?>\n\nEsta acción no se puede deshacer.')">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            <?php echo $es_mismo ? 'disabled' : ''; ?>
                                                            title="<?php echo $es_mismo ? 'No puedes eliminarte a ti mismo' : 'Eliminar usuario'; ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                        <span class="d-none d-md-inline ms-1">Eliminar</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                            <h5 class="text-muted">No hay usuarios registrados</h5>
                                            <p class="text-muted">Crea el primer usuario haciendo clic en "Nuevo Usuario"</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Información de roles -->
                    <div class="alert alert-info mt-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle fa-2x me-3"></i>
                            <div>
                                <strong>Roles del sistema:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>
                                        <span class="badge bg-warning text-dark me-2">👑 ADMIN</span> 
                                        Acceso completo: puede crear, editar, eliminar y gestionar usuarios.
                                    </li>
                                    <li class="mt-2">
                                        <span class="badge bg-success me-2">👁️ LECTOR</span> 
                                        Solo lectura: puede ver información pero no modificar nada.
                                    </li>
                                </ul>
                                <p class="mt-3 mb-0 text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    No puedes eliminar o cambiar tu propio rol por seguridad.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL PARA CREAR NUEVO USUARIO -->
<!-- ============================================ -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required 
                               placeholder="Ej: Juan Pérez">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email institucional <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               placeholder="ejemplo@tesa.edu.ec">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required 
                               value="Tesa2024!">
                        <small class="text-muted">Contraseña por defecto: <strong>Tesa2024!</strong></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-control" id="rol" name="rol" required>
                            <option value="lector">👁️ Lector (solo lectura)</option>
                            <option value="admin">👑 Administrador (acceso total)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script para Bootstrap 5 -->
<script>
    // Inicializar todos los tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>

<?php include '../../includes/footer.php'; ?>