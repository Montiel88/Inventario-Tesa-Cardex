<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

$es_admin = ($_SESSION['user_rol'] == 1);

require_once '../../config/database.php';
include '../../includes/header.php';

// Filtros
$persona_id = isset($_GET['persona_id']) ? intval($_GET['persona_id']) : 0;
$equipo_id = isset($_GET['equipo_id']) ? intval($_GET['equipo_id']) : 0;
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'activas';

// Construir WHERE
$where = "a.fecha_devolucion IS NULL";
if ($filtro_estado == 'todas') {
    $where = "1=1";
}
if ($persona_id > 0) {
    $where .= " AND a.persona_id = $persona_id";
}
if ($equipo_id > 0) {
    $where .= " AND a.equipo_id = $equipo_id";
}

// Obtener asignaciones
$sql = "SELECT a.*, 
               e.codigo_barras, e.tipo_equipo, e.marca, e.modelo, e.numero_serie,
               p.nombres as persona_nombre, p.cedula, p.cargo,
               DATEDIFF(NOW(), a.fecha_asignacion) as dias_asignado
        FROM asignaciones a
        JOIN equipos e ON a.equipo_id = e.id
        JOIN personas p ON a.persona_id = p.id
        WHERE $where
        ORDER BY a.fecha_asignacion DESC";

$result = $conn->query($sql);
$total_activas = $conn->query("SELECT COUNT(*) as total FROM asignaciones WHERE fecha_devolucion IS NULL")->fetch_assoc()['total'];
$total_todas = $conn->query("SELECT COUNT(*) as total FROM asignaciones")->fetch_assoc()['total'];

// Obtener listas para filtros
$personas = $conn->query("SELECT id, nombres FROM personas ORDER BY nombres");
$equipos = $conn->query("SELECT id, codigo_barras, tipo_equipo FROM equipos ORDER BY tipo_equipo");
?>

<style>
.asignacion-card {
    transition: all 0.2s ease;
}
.asignacion-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(90,45,140,0.1);
}
.badge-dias {
    font-size: 0.75rem;
    padding: 3px 8px;
}
.dias-vencido {
    background: #dc3545;
    color: white;
}
.dias-por-vencer {
    background: #ffc107;
    color: #212529;
}
.dias-normal {
    background: #28a745;
    color: white;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            
            <!-- AVISO PARA LECTORES -->
            <?php if (!$es_admin): ?>
            <div class="alert alert-info d-flex align-items-center mb-4">
                <i class="fas fa-info-circle fa-2x me-3 text-success"></i>
                <div>
                    <strong>Modo solo lectura activo</strong>
                    <p class="mb-0">Puedes ver las asignaciones pero no puedes editarlas o eliminarlas.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="mb-0"><i class="fas fa-hand-holding me-2"></i>Gestión de Asignaciones</h4>
                    <?php if ($es_admin): ?>
                    <a href="cargar_equipos.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Nueva Asignación
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="card-body">
                    
                    <!-- Mensajes de éxito/error -->
                    <?php if (isset($_GET['mensaje'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['mensaje']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="card bg-primary text-white text-center p-3">
                                <h3 class="mb-0"><?php echo $total_activas; ?></h3>
                                <small>Asignaciones Activas</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="card bg-secondary text-white text-center p-3">
                                <h3 class="mb-0"><?php echo $total_todas - $total_activas; ?></h3>
                                <small>Devoluciones Realizadas</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="card bg-info text-white text-center p-3">
                                <h3 class="mb-0"><?php echo $total_todas; ?></h3>
                                <small>Total Asignaciones</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select" id="filtroEstado" onchange="window.location.href='?estado='+this.value">
                                        <option value="activas" <?php echo $filtro_estado == 'activas' ? 'selected' : ''; ?>>Solo Activas</option>
                                        <option value="todas" <?php echo $filtro_estado == 'todas' ? 'selected' : ''; ?>>Todas (incluye devueltas)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Persona</label>
                                    <select class="form-select" id="filtroPersona" onchange="window.location.href='?persona_id='+this.value+'&estado=<?php echo $filtro_estado; ?>'">
                                        <option value="0">-- Todas --</option>
                                        <?php while($p = $personas->fetch_assoc()): ?>
                                            <option value="<?php echo $p['id']; ?>" <?php echo $persona_id == $p['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($p['nombres']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Equipo</label>
                                    <select class="form-select" id="filtroEquipo" onchange="window.location.href='?equipo_id='+this.value+'&estado=<?php echo $filtro_estado; ?>'">
                                        <option value="0">-- Todos --</option>
                                        <?php while($e = $equipos->fetch_assoc()): ?>
                                            <option value="<?php echo $e['id']; ?>" <?php echo $equipo_id == $e['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($e['codigo_barras'] . ' - ' . $e['tipo_equipo']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <a href="listar.php" class="btn btn-outline-secondary w-100">Limpiar Filtros</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabla de asignaciones -->
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Persona</th>
                                        <th>Equipo</th>
                                        <th>Código</th>
                                        <th>Fecha Asignación</th>
                                        <th>Días</th>
                                        <th>Estado</th>
                                        <th class="text-center">Acciones</th>
                                     </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): 
                                        $dias = $row['dias_asignado'];
                                        $clase_dias = 'dias-normal';
                                        if ($dias >= 30) $clase_dias = 'dias-vencido';
                                        elseif ($dias >= 25) $clase_dias = 'dias-por-vencer';
                                        $estado_texto = $row['fecha_devolucion'] ? 'Devuelto' : 'Activo';
                                        $estado_clase = $row['fecha_devolucion'] ? 'success' : 'warning';
                                    ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['persona_nombre']); ?></strong>
                                            <br><small class="text-muted"><?php echo $row['cedula']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo $row['tipo_equipo'] . ' ' . $row['marca'] . ' ' . $row['modelo']; ?>
                                            <br><small><?php echo $row['numero_serie'] ?: 'Sin serie'; ?></small>
                                        </td>
                                        <td><span class="badge bg-secondary"><?php echo $row['codigo_barras']; ?></span></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_asignacion'])); ?></td>
                                        <td>
                                            <?php if (!$row['fecha_devolucion']): ?>
                                                <span class="badge <?php echo $clase_dias; ?> badge-dias">
                                                    <?php echo $dias; ?> días
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $estado_clase; ?>">
                                                <?php echo $estado_texto; ?>
                                            </span>
                                            <?php if ($row['fecha_devolucion']): ?>
                                                <br><small><?php echo date('d/m/Y', strtotime($row['fecha_devolucion'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="detalle.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Ver detalle">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($es_admin): ?>
                                                    <?php if (!$row['fecha_devolucion']): ?>
                                                        <a href="../movimientos/devolucion.php?equipo_id=<?php echo $row['equipo_id']; ?>" class="btn btn-sm btn-success" title="Registrar devolución">
                                                            <i class="fas fa-undo-alt"></i>
                                                        </a>
                                                        <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="eliminar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar esta asignación?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Devuelto</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                     </tr>
                                    <?php endwhile; ?>
                                </tbody>
                             </table>
                        </div>
                        
                        <div class="mt-3 text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Total de registros: <strong><?php echo $result->num_rows; ?></strong>
                        </div>
                        
                    <?php else: ?>
                        <div class="alert alert-info text-center py-4">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <h5>No hay asignaciones registradas</h5>
                            <p>No se encontraron asignaciones con los filtros seleccionados.</p>
                            <?php if ($es_admin): ?>
                                <a href="cargar_equipos.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus-circle me-2"></i>Crear nueva asignación
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>