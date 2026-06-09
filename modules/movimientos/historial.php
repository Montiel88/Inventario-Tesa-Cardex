<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar rol (1 = admin, 2 = lector)
$es_admin = ($_SESSION['user_rol'] == 1);

require_once '../../config/database.php';
include '../../includes/header.php';

$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';
$persona_id = isset($_GET['persona_id']) ? intval($_GET['persona_id']) : 0;

// ============================================
// CONSULTA PARA PRÉSTAMOS ACTIVOS
// ============================================
if ($filtro == 'activos') {
    $sql = "SELECT 
                a.id as asignacion_id,
                a.fecha_asignacion,
                a.observaciones as obs_asignacion,
                e.id as equipo_id,
                e.codigo_barras,
                e.tipo_equipo,
                e.marca,
                e.modelo,
                e.numero_serie,
                p.id as persona_id,
                p.nombres as persona_nombre,
                p.cedula,
                p.cargo
            FROM asignaciones a
            INNER JOIN equipos e ON a.equipo_id = e.id
            INNER JOIN personas p ON a.persona_id = p.id
            WHERE a.fecha_devolucion IS NULL
            ORDER BY a.fecha_asignacion DESC";
    
    $titulo = "Préstamos Activos";
    
} elseif ($persona_id > 0) {
    // Historial por persona específica
    $sql = "SELECT 
                m.*,
                e.tipo_equipo,
                e.codigo_barras,
                e.marca,
                e.modelo
            FROM movimientos m
            INNER JOIN equipos e ON m.equipo_id = e.id
            WHERE m.persona_id = $persona_id
            ORDER BY m.fecha_movimiento DESC";
    
    $titulo = "Historial de la Persona";
    
} else {
    // Todos los movimientos
    $sql = "SELECT 
                m.*,
                e.tipo_equipo,
                e.codigo_barras,
                p.nombres as persona_nombre
            FROM movimientos m
            LEFT JOIN equipos e ON m.equipo_id = e.id
            LEFT JOIN personas p ON m.persona_id = p.id
            ORDER BY m.fecha_movimiento DESC
            LIMIT 100";
    
    $titulo = "Historial de Movimientos";
}

$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            
            <!-- AVISO PARA LECTORES -->
            <?php if (!$es_admin): ?>
            <div class="alert alert-info d-flex align-items-center mb-4" style="border-left: 4px solid #28a745;">
                <i class="fas fa-info-circle fa-2x me-3 text-success"></i>
                <div>
                    <strong>Modo solo lectura activo</strong>
                    <p class="mb-0">Puedes ver la información pero no puedes realizar acciones como devoluciones.</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" style="background: rgba(139, 92, 246, 0.2) !important; border-bottom: 2px solid var(--c-gold) !important;">
                    <h5 class="mb-0 text-white"><i class="fas fa-history me-2 text-warning"></i><?php echo $titulo; ?></h5>
                </div>
                <div class="card-body p-0">
                    
                    <!-- Botones de filtro -->
                    <div class="mb-3">
                        <a href="historial.php" class="btn btn-sm btn-secondary">Todos</a>
                        <a href="historial.php?filtro=activos" class="btn btn-sm btn-warning">Préstamos Activos</a>
                    </div>
                    
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tablaHistorial">
                                <thead class="table-light" style="background: rgba(255,255,255,0.05) !important;">
                                    <tr>
                                        <?php if ($filtro == 'activos'): ?>
                                            <th>Fecha</th>
                                            <th>Persona</th>
                                            <?php if ($es_admin): ?>
                                                <th>Cédula</th>
                                            <?php endif; ?>
                                            <th>Equipo</th>
                                            <th>Código</th>
                                            <th>Marca/Modelo</th>
                                            <th>Acciones</th>
                                        <?php else: ?>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Equipo</th>
                                            <th>Persona</th>
                                            <th>Observaciones</th>
                                            <th>Doc. Firmado</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($filtro == 'activos'): ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td data-label="FECHA"><?php echo date('d/m/Y H:i', strtotime($row['fecha_asignacion'])); ?></td>
                                            <td data-label="PERSONA"><?php echo htmlspecialchars($row['persona_nombre']); ?></td>
                                            <?php if ($es_admin): ?>
                                                <td data-label="CÉDULA"><?php echo $row['cedula']; ?></td>
                                            <?php endif; ?>
                                            <td data-label="EQUIPO"><?php echo $row['tipo_equipo']; ?></td>
                                            <td data-label="CÓDIGO"><?php echo $row['codigo_barras']; ?></td>
                                            <td data-label="MARCA/MODELO"><?php echo $row['marca'] . ' ' . $row['modelo']; ?></td>
                                            <td data-label="ACCIONES">
                                                <?php if ($es_admin): ?>
                                                    <a href="../movimientos/devolucion.php?equipo_id=<?php echo $row['equipo_id']; ?>" 
                                                       class="btn btn-sm btn-success">
                                                        <i class="fas fa-undo-alt"></i> Devolver
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Solo admin</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td data-label="FECHA"><?php echo date('d/m/Y H:i', strtotime($row['fecha_movimiento'])); ?></td>
                                            <td data-label="TIPO">
                                                <span class="badge bg-<?php 
                                                    echo $row['tipo_movimiento'] == 'ENTRADA' ? 'success' : 
                                                        ($row['tipo_movimiento'] == 'ASIGNACION' ? 'primary' : 
                                                        ($row['tipo_movimiento'] == 'DEVOLUCION' ? 'warning' : 'danger')); 
                                                ?>">
                                                    <?php echo $row['tipo_movimiento']; ?>
                                                </span>
                                            </td>
                                            <td data-label="EQUIPO"><?php echo $row['tipo_equipo'] . ' - ' . $row['codigo_barras']; ?></td>
                                            <td data-label="PERSONA"><?php echo $row['persona_nombre'] ?? 'N/A'; ?></td>
                                            <td data-label="OBSERVACIONES"><?php echo $row['observaciones'] ?? ''; ?></td>
                                            <td data-label="DOC. FIRMADO" class="text-center">
                                                <?php if (!empty($row['acta_firmada'])): ?>
                                                    <a href="/inventario_ti/<?php echo $row['acta_firmada']; ?>" target="_blank" class="btn btn-sm btn-success">
                                                        <i class="fas fa-file-pdf"></i> Ver
                                                    </a>
                                                <?php elseif ($row['tipo_movimiento'] == 'ASIGNACION' || $row['tipo_movimiento'] == 'DEVOLUCION'): ?>
                                                    <button class="btn btn-sm btn-outline-danger btn-upload-mov" data-id="<?php echo $row['id']; ?>" data-tipo="<?php echo $row['tipo_movimiento']; ?>">
                                                        <i class="fas fa-upload"></i> Subir
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center py-4">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <h5>No hay <?php echo strtolower($titulo); ?></h5>
                            <?php if ($filtro == 'activos'): ?>
                                <p>Todos los equipos están disponibles o no hay préstamos registrados.</p>
                                <?php if ($es_admin): ?>
                                    <a href="../asignaciones/cargar_equipos.php" class="btn btn-primary mt-2">
                                        <i class="fas fa-plus-circle me-2"></i>Registrar nuevo préstamo
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary mt-2" disabled>
                                        <i class="fas fa-ban me-2"></i>Registrar (solo admin)
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .table thead {
        display: none !important;
    }
    
    .table tbody tr {
        display: block !important;
        margin-bottom: 20px !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 15px !important;
        padding: 15px !important;
        background: rgba(255, 255, 255, 0.05) !important;
    }
    
    .table tbody td {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 8px 5px !important;
        border: none !important;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.1) !important;
        font-size: 13px !important;
        color: #fff !important;
    }
    
    .table tbody td:last-child {
        border-bottom: none !important;
    }
    
    .table tbody td:before {
        content: attr(data-label) !important;
        font-weight: 700 !important;
        color: var(--c-gold) !important;
        margin-right: 10px !important;
        min-width: 80px !important;
        font-size: 12px !important;
    }
    
    .btn-sm {
        padding: 4px 8px !important;
        font-size: 11px !important;
    }
}
</style>

<!-- Modal de Subida -->
<div class="modal fade" id="modalSubidaMov" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Subir Acta Firmada</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formSubidaActaMov" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="movimiento_id" id="upload_mov_id">
                    <div class="mb-3">
                        <label class="form-label">Tipo de Movimiento: <strong id="upload_mov_tipo"></strong></label>
                        <input type="file" name="archivo_firmado" class="form-control" accept=".pdf" required>
                        <small class="text-muted">Adjunta el acta escaneada en formato PDF.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Subir Archivo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalSubida = new bootstrap.Modal(document.getElementById('modalSubidaMov'));
    const formSubida = document.getElementById('formSubidaActaMov');
    
    document.querySelectorAll('.btn-upload-mov').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('upload_mov_id').value = this.dataset.id;
            document.getElementById('upload_mov_tipo').innerText = this.dataset.tipo;
            modalSubida.show();
        });
    });

    formSubida.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        Swal.fire({
            title: 'Subiendo archivo...',
            didOpen: () => { Swal.showLoading(); },
            allowOutsideClick: false
        });

        fetch('../../api/subir_acta_firmada.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire('¡Éxito!', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Hubo un problema con la conexión', 'error');
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>