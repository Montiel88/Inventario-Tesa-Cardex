<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
    
// Verificar rol (1 = admin, 2 = lector)
$es_admin = ($_SESSION['user_rol'] == 1);

require_once '../../config/database.php';
include '../../includes/header.php';

// Verificar conexión
if (!$conn) {
    die("Error de conexión a la base de datos");
}

// ============================================
// CONSULTA DE EQUIPOS SIN UBICACIÓN
// ============================================
$sql = "SELECT e.*, u.nombre as ubicacion_nombre 
        FROM equipos e
        LEFT JOIN ubicaciones u ON e.ubicacion_id = u.id
        WHERE (e.ubicacion_id IS NULL OR e.ubicacion_id = 0)
          AND e.fecha_eliminacion IS NULL
        ORDER BY e.tipo_equipo, e.marca, e.modelo";

$result = $conn->query($sql);
$total_sin_ubicacion = $result->num_rows;
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            
            <!-- TÍTULO Y ACCIONES -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-map-marker-alt text-warning me-2"></i>Equipos sin Ubicación</h2>
                    <p class="text-muted mb-0">Equipos que requieren asignación de ubicación</p>
                </div>
                <div>
                    <a href="listar.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Equipos
                    </a>
                </div>
            </div>
            
            <!-- TARJETA DE ESTADÍSTICAS -->
            <div class="card mb-4 border-warning" style="border-left: 4px solid #f39c12;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="card-title mb-2">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                Equipos por Ubicar
                            </h5>
                            <p class="card-text text-muted mb-0">
                                Estos equipos no tienen una ubicación asignada en el sistema. 
                                Es importante registrar su ubicación para mantener un control adecuado del inventario.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="display-4 text-warning"><?php echo $total_sin_ubicacion; ?></div>
                            <small class="text-muted">Total de equipos</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- AVISO PARA LECTORES -->
            <?php if (!$es_admin): ?>
            <div class="alert alert-info d-flex align-items-center mb-4" style="border-left: 4px solid #28a745;">
                <i class="fas fa-info-circle fa-2x me-3 text-success"></i>
                <div>
                    <strong>Modo solo lectura activo</strong>
                    <p class="mb-0">Puedes ver los equipos sin ubicación, pero no puedes editarlos.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- TABLA DE EQUIPOS -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado de Equipos</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($total_sin_ubicacion > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th>Tipo</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Código Barras</th>
                                    <th>Serial</th>
                                    <th>Estado</th>
                                    <th style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="<?php echo $row['estado'] == 'Disponible' ? 'table-success' : ''; ?>">
                                    <td><strong><?php echo $row['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['tipo_equipo']); ?></td>
                                    <td><?php echo htmlspecialchars($row['marca'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['modelo'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($row['codigo_barras']): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($row['codigo_barras']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin código</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['serial'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $badge_color = 'secondary';
                                        if ($row['estado'] == 'Disponible') $badge_color = 'success';
                                        elseif ($row['estado'] == 'Asignado') $badge_color = 'primary';
                                        elseif ($row['estado'] == 'Mantenimiento') $badge_color = 'warning';
                                        elseif ($row['estado'] == 'Dañado') $badge_color = 'danger';
                                        ?>
                                        <span class="badge bg-<?php echo $badge_color; ?>">
                                            <?php echo htmlspecialchars($row['estado'] ?? 'Sin estado'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($es_admin): ?>
                                        <a href="editar.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-warning" 
                                           title="Editar y asignar ubicación">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <?php else: ?>
                                        <a href="detalle.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-info" 
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                        <h5 class="text-muted">¡Todos los equipos tienen ubicación!</h5>
                        <p class="text-muted">No hay equipos pendientes de asignar ubicación.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- AYUDA -->
            <div class="card mt-4 bg-light">
                <div class="card-body">
                    <h6><i class="fas fa-lightbulb text-warning me-2"></i>¿Cómo asignar una ubicación?</h6>
                    <ol class="mb-0">
                        <li>Haz clic en el botón <strong>"Editar"</strong> del equipo que deseas ubicar.</li>
                        <li>En el formulario, busca el campo <strong>"Ubicación"</strong>.</li>
                        <li>Selecciona la ubicación correspondiente de la lista desplegable.</li>
                        <li>Si la ubicación no existe, puedes crearla en <strong>Equipos → Ubicaciones</strong>.</li>
                        <li>Guarda los cambios haciendo clic en <strong>"Guardar"</strong>.</li>
                    </ol>
                </div>
            </div>
            
        </div>
    </div>
</div>

<style>
.table-responsive {
    max-height: 600px;
    overflow-y: auto;
}

.table thead th {
    position: sticky;
    top: 0;
    background: #f8f9fa;
    z-index: 1;
    font-weight: 600;
    color: #5a2d8c;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background: rgba(90, 45, 140, 0.05);
    transform: translateX(2px);
}
</style>

<?php include '../../includes/footer.php'; ?>
