<?php
session_start();
require_once '../config/database.php';

// Solo admin puede hacer backups
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$mensaje = '';
$error = '';

// Crear carpeta de backups si no existe
$backup_dir = '../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

if (isset($_POST['crear_backup'])) {
    $fecha = date('Y-m-d_H-i-s');
    $filename = "backup_{$fecha}.sql";
    $filepath = $backup_dir . $filename;
    
    // Comando mysqldump (ajusta según tu configuración)
    $command = "C:\\xampp\\mysql\\bin\\mysqldump --user=root --host=localhost inventario_ti > \"$filepath\" 2>&1";
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    
    if ($return_var === 0) {
        $mensaje = "✅ Backup creado exitosamente: $filename";
        
        // Registrar en log
        $log = "Backup realizado por {$_SESSION['user_name']} el " . date('Y-m-d H:i:s');
        file_put_contents($backup_dir . 'backup_log.txt', $log . PHP_EOL, FILE_APPEND);
    } else {
        $error = "❌ Error al crear backup: " . implode("\n", $output);
    }
}

// Listar backups existentes
$backups = glob($backup_dir . '*.sql');
rsort($backups); // Ordenar por fecha descendente

// Restaurar backup
if (isset($_POST['restaurar']) && isset($_POST['archivo'])) {
    $archivo = basename($_POST['archivo']);
    $filepath = $backup_dir . $archivo;
    
    if (file_exists($filepath)) {
        $command = "C:\\xampp\\mysql\\bin\\mysql --user=root --host=localhost inventario_ti < \"$filepath\" 2>&1";
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            $mensaje = "✅ Base de datos restaurada correctamente desde: $archivo";
        } else {
            $error = "❌ Error al restaurar: " . implode("\n", $output);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Backup - Inventario TESA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-database me-2"></i>Respaldo y Restauración</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($mensaje): ?>
                            <div class="alert alert-success"><?php echo $mensaje; ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <i class="fas fa-save fa-3x text-primary mb-3"></i>
                                        <h5>Crear Nuevo Backup</h5>
                                        <p>Genera una copia de seguridad completa de la base de datos</p>
                                        <form method="POST">
                                            <button type="submit" name="crear_backup" class="btn btn-primary">
                                                <i class="fas fa-download me-2"></i>Crear Backup Ahora
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5><i class="fas fa-history me-2"></i>Backups Disponibles</h5>
                                        <?php if (count($backups) > 0): ?>
                                            <div class="list-group mt-3">
                                                <?php foreach ($backups as $backup): 
                                                    $nombre = basename($backup);
                                                    $tamano = round(filesize($backup) / 1024, 2);
                                                    $fecha = date('d/m/Y H:i:s', filemtime($backup));
                                                ?>
                                                    <div class="list-group-item">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <strong><?php echo $nombre; ?></strong>
                                                                <br>
                                                                <small><?php echo $fecha; ?> - <?php echo $tamano; ?> KB</small>
                                                            </div>
                                                            <div>
                                                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Restaurar este backup? Se perderán los datos actuales')">
                                                                    <input type="hidden" name="archivo" value="<?php echo $nombre; ?>">
                                                                    <button type="submit" name="restaurar" class="btn btn-sm btn-warning">
                                                                        <i class="fas fa-undo"></i> Restaurar
                                                                    </button>
                                                                </form>
                                                                <a href="<?php echo '../backups/' . $nombre; ?>" download class="btn btn-sm btn-success">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted mt-3">No hay backups disponibles</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Recomendación:</strong> Realiza backups diarios automáticos. Puedes configurar una tarea programada en Windows para ejecutar este script automáticamente.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>