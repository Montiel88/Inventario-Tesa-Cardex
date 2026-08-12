<?php
session_start();

// ====================================================
// 🔒 CONFIGURACIÓN DE EMAIL TEMPORALMENTE DESHABILITADA
// Para volver a habilitarla: comenta el bloque de redirect
// y el item del menú Admin > Config. de Email en includes/header.php
// ====================================================
header('Location: /inventario_ti/modules/dashboard.php');
exit();

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 1) {
    header('Location: /inventario_ti/login.php');
    exit();
}

require_once '../../config/database.php';
require_once '../../config/validaciones.php';
require_once '../../config/NotificadorEmail.php';

$notificador = new NotificadorEmail($conn);
$mensaje = '';
$error = '';

// Guardar configuración
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_config'])) {
    $smtp_host = trim((string)($_POST['smtp_host'] ?? ''));
    $smtp_port = intval($_POST['smtp_port'] ?? 0);
    $smtp_username = trim((string)($_POST['smtp_username'] ?? ''));
    $smtp_password = (string)($_POST['smtp_password'] ?? '');
    $smtp_encryption = trim((string)($_POST['smtp_encryption'] ?? 'tls'));
    $email_from = trim((string)($_POST['email_from'] ?? ''));
    $email_from_nombre = trim((string)($_POST['email_from_nombre'] ?? 'Sistema de Inventario TESA'));
    $notificar_asignacion = isset($_POST['notificar_asignacion']) ? 1 : 0;
    $notificar_devolucion = isset($_POST['notificar_devolucion']) ? 1 : 0;
    $notificar_vencimiento = isset($_POST['notificar_vencimiento']) ? 1 : 0;
    $dias_antes_vencimiento = intval($_POST['dias_antes_vencimiento'] ?? 3);
    $smtp_modo_fallback = isset($_POST['smtp_modo_fallback']) ? 1 : 0;

    $errores_cfg = [];

    if ($smtp_modo_fallback !== 1) {
        if ($smtp_host === '' || $smtp_port <= 0) {
            $errores_cfg[] = 'Servidor SMTP (Host) y Puerto son obligatorios cuando no está activado el Modo bandeja local.';
        }
        if ($smtp_password === '' && empty($config['smtp_password'])) {
            $errores_cfg[] = 'Contraseña SMTP es obligatoria sin Modo bandeja local (o activa el Modo bandeja local para pruebas).';
        }
    }

    if (!filter_var($smtp_username, FILTER_VALIDATE_EMAIL)) {
        $errores_cfg[] = 'El Usuario SMTP debe ser un correo válido.';
    } elseif (!validarDominioEmailTESA($smtp_username)) {
        $errores_cfg[] = 'Usuario SMTP no permitido. ' . tesa_mensaje_dominios_email();
    }

    if ($email_from !== '' && !filter_var($email_from, FILTER_VALIDATE_EMAIL)) {
        $errores_cfg[] = 'El Email Remitente debe ser un correo válido.';
    } elseif ($email_from !== '' && !validarDominioEmailTESA($email_from)) {
        $errores_cfg[] = 'Email Remitente no permitido. ' . tesa_mensaje_dominios_email();
    }

    if ($dias_antes_vencimiento < 1 || $dias_antes_vencimiento > 30) {
        $errores_cfg[] = 'Los días antes de vencimiento deben estar entre 1 y 30.';
    }

    if (!empty($errores_cfg)) {
        $error = '❌ ' . implode('<br>', $errores_cfg);
    } else {
        $datos = [
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_username' => $smtp_username,
            'smtp_password' => $smtp_password,
            'smtp_encryption' => $smtp_encryption,
            'email_from' => $email_from,
            'email_from_nombre' => $email_from_nombre,
            'notificar_asignacion' => $notificar_asignacion,
            'notificar_devolucion' => $notificar_devolucion,
            'notificar_vencimiento' => $notificar_vencimiento,
            'dias_antes_vencimiento' => $dias_antes_vencimiento,
            'smtp_modo_fallback' => $smtp_modo_fallback,
        ];

        $res = $notificador->guardarConfiguracion($datos);
        if (is_array($res) && !empty($res['ok'])) {
            $mensaje = '✅ Configuración guardada correctamente';
            $notificador = new NotificadorEmail($conn);
        } else {
            $error = '❌ ' . ($res['error'] ?? 'Error al guardar la configuración');
        }
    }
}

// Probar conexión
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['probar_email'])) {
    $test_email = trim((string)($_POST['test_email'] ?? ''));

    if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $error = '❌ Ingresa un correo de destino válido.';
    } else {
        $result = $notificador->enviarEmail(
            $test_email,
            'Prueba de configuración - Sistema TESA',
            '<html><head><meta charset="UTF-8"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>'
                . '<p>Esta es una prueba del sistema de notificaciones del Sistema de Inventario TESA.</p>'
                . '<p>Si recibes este mensaje, la configuración está funcionando correctamente.</p>'
                . '</body></html>',
            'prueba'
        );

        if (!empty($result['success'])) {
            $mensaje = "✅ Email de prueba enviado a $test_email";
            if (!empty($result['local'])) {
                $url = !empty($result['local_url']) ? $result['local_url'] : '';
                $note = !empty($result['note']) ? $result['note'] : '';
                $mensaje .= "<br>
                    <div class='alert alert-info mt-2 mb-0 text-start'>
                        <i class='fas fa-circle-info me-2'></i>{$note}
                        <div class='mt-2'>
                            <a class='btn btn-sm btn-outline-info' target='_blank' href='{$url}'>
                                <i class='fas fa-envelope-open-text me-1'></i>Abrir correo de prueba (local)
                            </a>
                        </div>
                    </div>";
            }
        } else {
            $error = "❌ Error al enviar: " . ($result['error'] ?? 'Desconocido');
        }
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

                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Notas de funcionamiento:</strong>
                        <ul class="mb-0 mt-2">
                            <li>El <b>Usuario SMTP</b> y el <b>Email Remitente</b> deben ser correos institucionales de TESA (<code>@tesa.edu.ec</code> o <code>@estud.tesa.edu.ec</code>).</li>
                            <li>Los <b>destinatarios</b> (préstamos, recuperación, pruebas) pueden ser <b>cualquier correo</b> (Gmail, Hotmail, Outlook, institucional).</li>
                            <li>Si el SMTP institucional no responde (ej: pruebas fuera de la LAN sin VPN), el sistema usa automáticamente un canal de respaldo para que los correos lleguen.</li>
                        </ul>
                    </div>

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
                                                   value="<?php echo htmlspecialchars($config['smtp_host'] ?? 'mail.tesa.edu.ec', ENT_QUOTES, 'UTF-8'); ?>"
                                                   placeholder="mail.tesa.edu.ec o el host SMTP institucional">
                                            <div class="form-text" style="color:rgba(255,255,255,0.6);">Host del instituto. Diagnóstico previo mostró que <b>mail.tesa.edu.ec</b> tiene abiertos los puertos 587/465.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Puerto SMTP</label>
                                            <input type="number" name="smtp_port" class="form-control" 
                                                   value="<?php echo intval($config['smtp_port'] ?? 587); ?>"
                                                   placeholder="Ej: 587 (TLS) o 465 (SSL)">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Usuario SMTP</label>
                                            <input type="text" name="smtp_username" class="form-control" 
                                                   value="<?php echo htmlspecialchars($config['smtp_username'] ?? 'cmontiel@estud.tesa.edu.ec', ENT_QUOTES, 'UTF-8'); ?>"
                                                   placeholder="cmontiel@estud.tesa.edu.ec">
                                            <div class="form-text" style="color:rgba(255,255,255,0.6);">Debe ser un correo institucional (@tesa.edu.ec o @estud.tesa.edu.ec).</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Contraseña SMTP</label>
                                            <input type="password" name="smtp_password" class="form-control" 
                                                   placeholder="<?php echo empty($config['smtp_password']) ? 'Contraseña del correo institucional' : '•••••••• (deja vacía para conservar)'; ?>">
                                            <div class="form-text" style="color:rgba(255,255,255,0.6);">
                                                Se guarda cifrada en la base de datos.
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Encriptación</label>
                                            <select name="smtp_encryption" class="form-select">
                                                <option value="tls" <?php echo (($config['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''); ?>>TLS</option>
                                                <option value="ssl" <?php echo (($config['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''); ?>>SSL</option>
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
                                                   value="<?php echo htmlspecialchars($config['email_from'] ?? 'cmontiel@estud.tesa.edu.ec', ENT_QUOTES, 'UTF-8'); ?>"
                                                   placeholder="cmontiel@estud.tesa.edu.ec">
                                            <div class="form-text" style="color:rgba(255,255,255,0.6);">
                                                Si lo dejas vacío, se usará el Usuario SMTP como remitente. Debe ser institucional.
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Nombre Remitente</label>
                                            <input type="text" name="email_from_nombre" class="form-control" 
                                                   value="<?php echo htmlspecialchars($config['email_from_nombre'] ?? 'Sistema de Inventario TESA', ENT_QUOTES, 'UTF-8'); ?>">
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

                                <div class="card mb-4 border-secondary">
                                    <div class="card-header" data-bs-toggle="collapse" href="#avanzadoCollapse" role="button" aria-expanded="false" aria-controls="avanzadoCollapse" style="cursor:pointer;">
                                        <h5 class="mb-0"><i class="fas fa-gears me-2"></i>Opciones avanzadas <span class="float-end"><i class="fas fa-chevron-down small"></i></span></h5>
                                    </div>
                                    <div class="collapse" id="avanzadoCollapse">
                                        <div class="card-body">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="smtp_modo_fallback" name="smtp_modo_fallback" value="1"
                                                    <?php echo (!empty($config['smtp_modo_fallback']) ? 'checked' : ''); ?>>
                                                <label class="form-check-label ms-2" for="smtp_modo_fallback">
                                                    <b>Forzar Modo bandeja local</b> (no envía correos reales; los guarda como HTML local).
                                                </label>
                                            </div>
                                            <div class="form-text mt-2" style="color:rgba(255,255,255,0.65);">
                                                Útil solo en entornos sin internet. Normalmente <b>no es necesario activarlo</b>: el sistema ya intenta enviar por SMTP institucional y, si falla, usa un canal de respaldo automático.
                                            </div>
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
                            <form method="POST" class="row g-3 align-items-end">
                                <div class="col">
                                    <label class="form-label" for="test_email">Destino de prueba</label>
                                    <input type="email" id="test_email" name="test_email" class="form-control" 
                                           placeholder="tucorreo@gmail.com / cmontiel@estud.tesa.edu.ec" required>
                                    <div class="form-text" style="color:rgba(255,255,255,0.6);">Puedes usar cualquier correo (Gmail, Outlook, Hotmail, institucional).</div>
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
