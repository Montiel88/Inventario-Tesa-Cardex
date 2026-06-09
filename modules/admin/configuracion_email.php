<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 1) {
    header('Location: /inventario_ti/login.php');
    exit();
}

require_once '../../config/database.php';
require_once '../../config/NotificadorEmail.php';

$notificador = new NotificadorEmail($conn);
$mensaje = '';
$error = '';

// Guardar configuración
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_config'])) {
    $datos = [
        'smtp_host' => $_POST['smtp_host'],
        'smtp_port' => intval($_POST['smtp_port']),
        'smtp_username' => $_POST['smtp_username'],
        'smtp_password' => $_POST['smtp_password'],
        'smtp_encryption' => $_POST['smtp_encryption'],
        'email_from' => $_POST['email_from'],
        'email_from_nombre' => $_POST['email_from_nombre'],
        'notificar_asignacion' => isset($_POST['notificar_asignacion']) ? 1 : 0,
        'notificar_devolucion' => isset($_POST['notificar_devolucion']) ? 1 : 0,
        'notificar_vencimiento' => isset($_POST['notificar_vencimiento']) ? 1 : 0,
        'dias_antes_vencimiento' => intval($_POST['dias_antes_vencimiento'])
    ];
    
    if ($notificador->guardarConfiguracion($datos)) {
        $mensaje = '✅ Configuración guardada correctamente';
        // Recargar configuración
        $notificador = new NotificadorEmail($conn);
    } else {
        $error = '❌ Error al guardar la configuración';
    }
}

// Probar conexión
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['probar_email'])) {
    $test_email = $_POST['test_email'];
    
    $result = $notificador->enviarEmail(
        $test_email,
        'Prueba de configuración - Sistema TESA',
        '<p>Esta es una prueba del sistema de notificaciones del Sistema de Inventario TESA.</p><p>Si recibes este mensaje, la configuración está funcionando correctamente.</p>',
        'prueba'
    );
    
    if ($result['success']) {
        $mensaje = "✅ Email de prueba enviado a $test_email";
    } else {
        $error = "❌ Error al enviar: " . ($result['error'] ?? 'Desconocido');
    }
}

$config = $notificador->getConfig();

include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Configuración de Notificaciones por Email</h4>
                </div>
                <div class="card-body">
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$notificador->estaActivo()): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Configuración no activa:</strong> No hay configuración de email guardada o está desactivada.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Estado:</strong> Configuración activa y lista para enviar notificaciones.
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Configuración del Servidor SMTP</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Servidor SMTP (Host)</label>
                                            <input type="text" name="smtp_host" class="form-control" 
                                                   value="<?php echo $config['smtp_host'] ?? 'smtp.gmail.com'; ?>"
                                                   placeholder="smtp.gmail.com">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Puerto SMTP</label>
                                            <input type="number" name="smtp_port" class="form-control" 
                                                   value="<?php echo $config['smtp_port'] ?? 587; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Usuario SMTP</label>
                                            <input type="text" name="smtp_username" class="form-control" 
                                                   value="<?php echo $config['smtp_username'] ?? ''; ?>"
                                                   placeholder="tu@email.com">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Contraseña SMTP</label>
                                            <input type="password" name="smtp_password" class="form-control" 
                                                   value="<?php echo $config['smtp_password'] ?? ''; ?>"
                                                   placeholder="••••••••">
                                            <small class="text-muted">Ingresa la contraseña de aplicación si usas Gmail</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Encriptación</label>
                                            <select name="smtp_encryption" class="form-select">
                                                <option value="tls" <?php echo ($config['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                <option value="ssl" <?php echo ($config['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Configuración del Remitente</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Email Remitente</label>
                                            <input type="email" name="email_from" class="form-control" 
                                                   value="<?php echo $config['email_from'] ?? ''; ?>"
                                                   placeholder="inventario@tesa.edu">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Nombre Remitente</label>
                                            <input type="text" name="email_from_nombre" class="form-control" 
                                                   value="<?php echo $config['email_from_nombre'] ?? 'Sistema de Inventario TESA'; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Notificaciones</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="notificar_asignacion" 
                                                   id="notificar_asignacion" 
                                                   <?php echo ($config['notificar_asignacion'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="notificar_asignacion">
                                                Notificar nuevas asignaciones
                                            </label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="notificar_devolucion" 
                                                   id="notificar_devolucion" 
                                                   <?php echo ($config['notificar_devolucion'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="notificar_devolucion">
                                                Notificar devoluciones
                                            </label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="notificar_vencimiento" 
                                                   id="notificar_vencimiento" 
                                                   <?php echo ($config['notificar_vencimiento'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="notificar_vencimiento">
                                                Notificar préstamos próximos a vencer
                                            </label>
                                        </div>
                                        <div class="mb-3 mt-3">
                                            <label class="form-label">Días antes de vencimiento para alertar</label>
                                            <input type="number" name="dias_antes_vencimiento" class="form-control" 
                                                   value="<?php echo $config['dias_antes_vencimiento'] ?? 3; ?>"
                                                   min="1" max="30">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="guardar_config" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Prueba de Configuración</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <div class="col-auto">
                                    <input type="email" name="test_email" class="form-control" 
                                           placeholder="email@ejemplo.com" required>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" name="probar_email" class="btn btn-info">
                                        <i class="fas fa-paper-plane me-1"></i> Enviar Email de Prueba
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
