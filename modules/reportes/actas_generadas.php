<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
require_once '../../config/database.php';
include '../../includes/header.php';

$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_persona = isset($_GET['persona_id']) ? intval($_GET['persona_id']) : 0;

$sql = "SELECT a.*, p.nombres as persona_nombre, p.cedula, u.nombre as usuario_nombre
        FROM actas a
        LEFT JOIN personas p ON a.persona_id = p.id
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE 1=1";

if ($filtro_tipo) {
    $sql .= " AND a.tipo_acta = '$filtro_tipo'";
}

if ($filtro_persona > 0) {
    $sql .= " AND a.persona_id = $filtro_persona";
}

$sql .= " ORDER BY a.fecha_generacion DESC";
$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-file-pdf me-2"></i>Actas Generadas</h4>
        </div>
        <div class="card-body">
            
            <!-- Filtros -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <select class="form-control" onchange="window.location.href='?tipo='+this.value">
                        <option value="">Todos los tipos</option>
                        <option value="entrega" <?php echo $filtro_tipo == 'entrega' ? 'selected' : ''; ?>>Acta Entrega</option>
                        <option value="devolucion" <?php echo $filtro_tipo == 'devolucion' ? 'selected' : ''; ?>>Acta Devolución</option>
                        <option value="descargo" <?php echo $filtro_tipo == 'descargo' ? 'selected' : ''; ?>>Descargo</option>
                    </select>
                </div>
            </div>
            
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código Acta</th>
                        <th>Tipo</th>
                        <th>Persona</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                        <th>Firmado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><strong><?php echo $row['codigo_acta']; ?></strong></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $row['tipo_acta'] == 'entrega' ? 'success' : 
                                    ($row['tipo_acta'] == 'devolucion' ? 'warning' : 'info'); 
                            ?>">
                                <?php echo $row['tipo_acta']; ?>
                            </span>
                        </td>
                        <td><?php echo $row['persona_nombre'] . ' (' . $row['cedula'] . ')'; ?></td>
                        <td><?php echo $row['usuario_nombre']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_generacion'])); ?></td>
                        <td class="text-center">
                            <?php if (!empty($row['archivo_firmado'])): ?>
                                <a href="/inventario_ti/<?php echo $row['archivo_firmado']; ?>" target="_blank" class="btn btn-sm btn-success mb-1">
                                    <i class="fas fa-file-signature me-1"></i>Ver Firmado
                                </a>
                                <br>
                                <button class="btn btn-sm btn-outline-primary btn-upload" data-id="<?php echo $row['id']; ?>" data-codigo="<?php echo $row['codigo_acta']; ?>">
                                    <i class="fas fa-sync me-1"></i>Reemplazar
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-danger btn-upload" data-id="<?php echo $row['id']; ?>" data-codigo="<?php echo $row['codigo_acta']; ?>">
                                    <i class="fas fa-upload me-1"></i>Subir PDF
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="7" class="text-center">No hay actas generadas</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Subida -->
<div class="modal fade" id="modalSubida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Subir Acta Firmada</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formSubidaActa" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="acta_id" id="upload_acta_id">
                    <div class="mb-3">
                        <label class="form-label">Acta: <strong id="upload_acta_codigo"></strong></label>
                        <input type="file" name="archivo_firmado" class="form-control" accept=".pdf" required>
                        <small class="text-muted">Solo se permiten archivos en formato PDF.</small>
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
    const modalSubida = new bootstrap.Modal(document.getElementById('modalSubida'));
    const formSubida = document.getElementById('formSubidaActa');
    
    document.querySelectorAll('.btn-upload').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('upload_acta_id').value = this.dataset.id;
            document.getElementById('upload_acta_codigo').innerText = this.dataset.codigo;
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