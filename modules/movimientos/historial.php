<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /Inventario-Tesa-Cardex/login.php');
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
                e.modelo,
                (SELECT a.id FROM actas a 
                    WHERE a.persona_id = m.persona_id 
                      AND FIND_IN_SET(m.equipo_id, a.equipos_ids)
                      AND a.tipo_acta = LOWER(m.tipo_movimiento)
                      AND ABS(TIMESTAMPDIFF(HOUR, a.fecha_generacion, m.fecha_movimiento)) <= 48
                 ORDER BY a.id DESC LIMIT 1) AS acta_id
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
                p.nombres as persona_nombre,
                (SELECT a.id FROM actas a 
                    WHERE a.persona_id = m.persona_id 
                      AND FIND_IN_SET(m.equipo_id, a.equipos_ids)
                      AND a.tipo_acta = LOWER(m.tipo_movimiento)
                      AND ABS(TIMESTAMPDIFF(HOUR, a.fecha_generacion, m.fecha_movimiento)) <= 48
                 ORDER BY a.id DESC LIMIT 1) AS acta_id
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
                        <div class="table-responsive" style="max-height: 550px; overflow-y: auto !important; scrollbar-width: thin; scrollbar-color: var(--c-gold) transparent; border-radius: 15px;">
                            <table class="table table-hover mb-0" id="tablaHistorial">
                                <thead class="sticky-top" style="z-index: 1; background: var(--c-deep);">
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
                                                    <a href="/Inventario-Tesa-Cardex/<?php echo $row['acta_firmada']; ?>" target="_blank" class="btn btn-sm btn-success">
                                                        <i class="fas fa-file-pdf"></i> Ver
                                                    </a>
                                                <?php elseif ($row['tipo_movimiento'] == 'ASIGNACION' || $row['tipo_movimiento'] == 'DEVOLUCION'): ?>
                                                    <button class="btn btn-sm btn-outline-danger btn-upload-mov" 
                                                            data-id="<?php echo $row['id']; ?>" 
                                                            data-movimiento-id="<?php echo $row['id']; ?>" 
                                                            data-acta-id="<?php echo intval($row['acta_id'] ?? 0); ?>" 
                                                            data-tipo="<?php echo $row['tipo_movimiento']; ?>">
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
            <form id="formSubidaActaMov" 
                  method="POST" 
                  action="../../api/subir_acta_firmada.php" 
                  enctype="multipart/form-data" 
                  class="needs-ajax-upload" 
                  data-ajax="1" 
                  data-ux-no-auto="1">
                <div class="modal-body">
                    <input type="hidden" name="movimiento_id" id="upload_mov_id" value="0">
                    <input type="hidden" name="acta_id" id="upload_acta_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Tipo de Movimiento: <strong id="upload_mov_tipo"></strong></label>
                        <input type="file" name="archivo_firmado" class="form-control" accept=".pdf" required>
                        <small class="text-muted">Adjunta el acta escaneada en formato PDF (máx 15 MB).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-subir-acta">
                        <i class="fas fa-cloud-upload-alt me-1"></i> Subir Archivo
                    </button>
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
            const movId = intvalOrZero(this.dataset.movimientoId || this.dataset.id || '0');
            const actId = intvalOrZero(this.dataset.actaId || '0');
            const tipo = this.dataset.tipo || '';
            document.getElementById('upload_mov_id').value = movId;
            document.getElementById('upload_acta_id').value = actId;
            document.getElementById('upload_mov_tipo').innerText = tipo;
            // Reset file input & restore button loading state
            const fileInput = formSubida.querySelector('input[type="file"]');
            if (fileInput) fileInput.value = '';
            const submitBtn = formSubida.querySelector('button[type="submit"]');
            if (submitBtn && window.UXLoading && window.UXLoading.btnRestaurar) {
                window.UXLoading.btnRestaurar(submitBtn);
            }
            modalSubida.show();
        });
    });

    function intvalOrZero(v) {
        const n = parseInt(String(v || '0'), 10);
        return Number.isFinite(n) && n > 0 ? n : 0;
    }

    formSubida.addEventListener('submit', function(e) {
        e.preventDefault();

        const movId = intvalOrZero(document.getElementById('upload_mov_id').value);
        const actId = intvalOrZero(document.getElementById('upload_acta_id').value);

        if (movId <= 0 && actId <= 0) {
            modalSubida.hide();
            Swal.fire({
                icon: 'error',
                title: 'Error de configuración',
                text: 'No se pudo identificar el movimiento. Por favor recarga la página e intenta nuevamente. Si el problema persiste contacta a soporte.',
                confirmButtonColor: '#b91c1c',
                confirmButtonText: 'OK'
            });
            return;
        }

        const fileInput = formSubida.querySelector('input[name="archivo_firmado"]');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Falta archivo',
                text: 'Selecciona un PDF antes de subir.',
                confirmButtonColor: '#f3b229',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        const formData = new FormData(this);
        
        Swal.fire({
            title: 'Subiendo archivo firmado...',
            didOpen: () => { Swal.showLoading(); },
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });

        fetch(formSubida.getAttribute('action') || '../../api/subir_acta_firmada.php', {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('El servidor no devolvió una respuesta JSON. Respuesta: ' + (text.length > 300 ? text.substring(0, 300) + '…' : text));
            }
            if (!response.ok && data && !data.message) {
                data.message = 'Código HTTP ' + response.status;
            }
            return data;
        })
        .then(data => {
            Swal.close();
            modalSubida.hide();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message || 'Archivo subido correctamente',
                    confirmButtonColor: '#5a2d8c',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al subir',
                    text: data.message || 'Error desconocido',
                    confirmButtonColor: '#b91c1c',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            try { Swal.close(); } catch(e) {}
            try { modalSubida.hide(); } catch(e) {}
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'Hubo un problema con la conexión: ' + (error.message || ''),
                confirmButtonColor: '#b91c1c',
                confirmButtonText: 'OK'
            });
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
