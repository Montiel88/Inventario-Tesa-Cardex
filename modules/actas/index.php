<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();

require_once '../../config/database.php';

function autoInstalarTablaActas(&$conn) {
    @$conn->query("CREATE TABLE IF NOT EXISTS `actas` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `codigo_acta` VARCHAR(120) NOT NULL UNIQUE,
        `tipo_acta` ENUM('ingreso','entrega','devolucion','traspaso','baja') NOT NULL,
        `persona_id` INT UNSIGNED NULL,
        `usuario_id` INT UNSIGNED NOT NULL,
        `equipos_ids` TEXT NULL,
        `motivo` TEXT NULL,
        `fecha_generacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `archivo_pdf` VARCHAR(500) NULL,
        `archivo_firmado` VARCHAR(500) NULL,
        `fecha_firmado` DATETIME NULL,
        `firmado_por` INT UNSIGNED NULL,
        `movimiento_id` INT UNSIGNED NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_tipo` (`tipo_acta`),
        INDEX `idx_persona` (`persona_id`),
        INDEX `idx_usuario` (`usuario_id`),
        INDEX `idx_fecha` (`fecha_generacion`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $cols = [];
    $rc = @$conn->query("SHOW COLUMNS FROM `actas`");
    if ($rc) while ($r = $rc->fetch_assoc()) $cols[$r['Field']] = $r['Type'];

    if (empty($cols['tipo_acta']) || (strpos($cols['tipo_acta'], 'ingreso') === false) || (strpos($cols['tipo_acta'], 'traspaso') === false) || (strpos($cols['tipo_acta'], 'baja') === false)) {
        @$conn->query("ALTER TABLE `actas` MODIFY COLUMN `tipo_acta` ENUM('ingreso','entrega','devolucion','traspaso','baja') NOT NULL");
    }

    $addCols = [
        'archivo_pdf' => "ALTER TABLE `actas` ADD `archivo_pdf` VARCHAR(500) NULL",
        'archivo_firmado' => "ALTER TABLE `actas` ADD `archivo_firmado` VARCHAR(500) NULL",
        'fecha_firmado' => "ALTER TABLE `actas` ADD `fecha_firmado` DATETIME NULL",
        'firmado_por' => "ALTER TABLE `actas` ADD `firmado_por` INT UNSIGNED NULL",
        'movimiento_id' => "ALTER TABLE `actas` ADD `movimiento_id` INT UNSIGNED NULL",
        'motivo' => "ALTER TABLE `actas` ADD `motivo` TEXT NULL",
        'equipos_ids' => "ALTER TABLE `actas` ADD `equipos_ids` TEXT NULL",
        'created_at' => "ALTER TABLE `actas` ADD `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE `actas` ADD `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        'codigo_acta' => "ALTER TABLE `actas` ADD `codigo_acta` VARCHAR(120) NULL UNIQUE",
        'persona_id' => "ALTER TABLE `actas` ADD `persona_id` INT UNSIGNED NULL",
        'usuario_id' => "ALTER TABLE `actas` ADD `usuario_id` INT UNSIGNED NOT NULL"
    ];
    foreach ($addCols as $col => $sql) {
        if (empty($cols[$col])) @$conn->query($sql);
    }

    if (!empty($cols['codigo_acta']) && strpos($cols['codigo_acta'], '120') === false) {
        @$conn->query("ALTER TABLE `actas` MODIFY COLUMN `codigo_acta` VARCHAR(120) NOT NULL");
    }
}
autoInstalarTablaActas($conn);

include '../../includes/header.php';

$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_persona = isset($_GET['persona_id']) ? intval($_GET['persona_id']) : 0;
$filtro_fecha_ini = isset($_GET['fecha_ini']) ? trim($_GET['fecha_ini']) : '';
$filtro_fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
$filtro_equipo = isset($_GET['equipo_id']) ? intval($_GET['equipo_id']) : 0;

$sql = "SELECT a.*, p.nombres as persona_nombre, p.cedula, u.nombre as usuario_nombre
        FROM actas a
        LEFT JOIN personas p ON a.persona_id = p.id
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE 1=1";
$params = [];
$types = '';

if (!empty($filtro_tipo)) {
    $sql .= " AND a.tipo_acta = ?";
    $params[] = $filtro_tipo;
    $types .= 's';
}
if ($filtro_persona > 0) {
    $sql .= " AND a.persona_id = ?";
    $params[] = $filtro_persona;
    $types .= 'i';
}
if (!empty($filtro_fecha_ini)) {
    $sql .= " AND DATE(a.fecha_generacion) >= ?";
    $params[] = $filtro_fecha_ini;
    $types .= 's';
}
if (!empty($filtro_fecha_fin)) {
    $sql .= " AND DATE(a.fecha_generacion) <= ?";
    $params[] = $filtro_fecha_fin;
    $types .= 's';
}
if ($filtro_equipo > 0) {
    $sql .= " AND (a.equipos_ids LIKE ? OR a.equipos_ids LIKE ? OR a.equipos_ids LIKE ? OR a.equipos_ids = ?)";
    $params[] = "$filtro_equipo,%";
    $params[] = "%,$filtro_equipo,%";
    $params[] = "%,$filtro_equipo";
    $params[] = (string)$filtro_equipo;
    $types .= 'ssss';
}
$sql .= " ORDER BY a.fecha_generacion DESC";

if (!empty($params) && function_exists('mysqli_prepare')) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (function_exists('mysqli_stmt_bind_param') && method_exists($stmt, 'bind_param')) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
} else {
    $result = $conn->query($sql);
}

$personas = $conn->query("SELECT id, nombres FROM personas ORDER BY nombres");
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4><i class="fas fa-file-pdf me-2"></i>Gestión Centralizada de Actas</h4>
            <?php if (esAdmin()): ?>
            <a href="generar.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Nueva Acta
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-4">
                <div class="col-md-3">
                    <select name="tipo" class="form-control" onchange="this.form.submit()">
                        <option value="">Todos los tipos</option>
                        <option value="ingreso" <?php echo $filtro_tipo == 'ingreso' ? 'selected' : ''; ?>>Acta de Ingreso</option>
                        <option value="entrega" <?php echo $filtro_tipo == 'entrega' ? 'selected' : ''; ?>>Acta de Entrega</option>
                        <option value="devolucion" <?php echo $filtro_tipo == 'devolucion' ? 'selected' : ''; ?>>Acta de Devolución</option>
                        <option value="traspaso" <?php echo $filtro_tipo == 'traspaso' ? 'selected' : ''; ?>>Acta de Traspaso</option>
                        <option value="baja" <?php echo $filtro_tipo == 'baja' ? 'selected' : ''; ?>>Acta de Baja</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="persona_id" class="form-control" onchange="this.form.submit()">
                        <option value="0">Todas las personas</option>
                        <?php if ($personas && $personas->num_rows > 0): while($pr = $personas->fetch_assoc()): ?>
                            <option value="<?php echo $pr['id']; ?>" <?php echo $filtro_persona == $pr['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pr['nombres']); ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="fecha_ini" class="form-control" value="<?php echo htmlspecialchars($filtro_fecha_ini); ?>" placeholder="Desde">
                </div>
                <div class="col-md-2">
                    <input type="date" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($filtro_fecha_fin); ?>" placeholder="Hasta">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100"><i class="fas fa-filter me-1"></i>Filtrar</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Responsable</th>
                            <th>Persona</th>
                            <th>Equipos</th>
                            <th>Fecha</th>
                            <th>Firmado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['codigo_acta']); ?></strong></td>
                            <td>
                                <?php
                                    $tipos_badge = [
                                        'ingreso' => 'bg-success',
                                        'entrega' => 'bg-primary',
                                        'devolucion' => 'bg-info',
                                        'traspaso' => 'bg-warning',
                                        'baja' => 'bg-danger'
                                    ];
                                    $badge = isset($tipos_badge[$row['tipo_acta']]) ? $tipos_badge[$row['tipo_acta']] : 'bg-secondary';
                                    $labels = [
                                        'ingreso' => 'Ingreso',
                                        'entrega' => 'Entrega',
                                        'devolucion' => 'Devolución',
                                        'traspaso' => 'Traspaso',
                                        'baja' => 'Baja'
                                    ];
                                    $label = isset($labels[$row['tipo_acta']]) ? $labels[$row['tipo_acta']] : $row['tipo_acta'];
                                ?>
                                <span class="badge <?php echo $badge; ?>"><?php echo $label; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($row['usuario_nombre'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['persona_nombre'] ?: '-'); ?></td>
                            <td>
                                <?php
                                    $ids = array_filter(array_map('intval', explode(',', $row['equipos_ids'] ?? '')));
                                    echo !empty($ids) ? count($ids) . ' equipo(s)' : '-';
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_generacion'])); ?></td>
                            <td><?php echo !empty($row['archivo_firmado']) ? '<i class="fas fa-check-circle text-success"></i> Sí' : '<span class="text-muted">No</span>'; ?></td>
                            <td>
                                <a href="detalle.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8" class="text-center">No hay actas registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
