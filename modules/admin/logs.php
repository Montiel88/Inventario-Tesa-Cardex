<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin();

require_once '../../config/database.php';
include '../../includes/header.php';

// Obtener logs si existe la tabla
$logs = [];
$tabla_existe = $conn->query("SHOW TABLES LIKE 'logs'")->num_rows > 0;

if ($tabla_existe) {
    $sql = "SELECT l.*, u.nombre as usuario_nombre 
            FROM logs l 
            LEFT JOIN usuarios u ON l.usuario_id = u.id 
            ORDER BY l.fecha DESC LIMIT 100";
    $result = $conn->query($sql);
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-history me-2"></i>Logs del Sistema</h4>
        </div>
        <div class="card-body">
            
            <?php if (!$tabla_existe): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                La tabla de logs no existe. Puedes crearla ejecutando:
                <pre class="mt-2 p-2 bg-light">
CREATE TABLE IF NOT EXISTS `logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT,
    `accion` VARCHAR(100),
    `detalle` TEXT,
    `ip` VARCHAR(45),
    `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);</pre>
            </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Detalle</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($log['fecha'])); ?></td>
                                <td><?php echo $log['usuario_nombre'] ?? 'Sistema'; ?></td>
                                <td><?php echo $log['accion']; ?></td>
                                <td><?php echo $log['detalle']; ?></td>
                                <td><?php echo $log['ip'] ?? '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                                    No hay registros de actividad
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>