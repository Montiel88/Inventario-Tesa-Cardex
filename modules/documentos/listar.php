<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

$es_admin = ($_SESSION['user_rol'] == 1);

require_once '../../config/database.php';
include '../../includes/header.php';

$id = intval($_GET['id']);
$tipo = $_GET['tipo'] ?? 'equipo'; // equipo o persona

if ($tipo == 'equipo') {
    $sql = "SELECT e.*, u.nombre as ubicacion_nombre 
            FROM equipos e 
            LEFT JOIN ubicaciones u ON e.ubicacion_id = u.id 
            WHERE e.id = $id";
    $result = $conn->query($sql);
    $item = $result->fetch_assoc();
    $titulo = $item['tipo_equipo'] . ' ' . $item['marca'] . ' ' . $item['modelo'];
} else {
    $sql = "SELECT * FROM personas WHERE id = $id";
    $result = $conn->query($sql);
    $item = $result->fetch_assoc();
    $titulo = $item['nombres'];
}

if (!$item) {
    header('Location: listar.php');
    exit();
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-folder-open me-2"></i>Documentos Adjuntos</h4>
                    <div>
                        <a href="<?php echo $tipo == 'equipo' ? 'detalle.php?id=' . $id : '../personas/detalle.php?id=' . $id; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Documentos de:</strong> <?php echo $titulo; ?> 
                        (Código: <?php echo $tipo == 'equipo' ? $item['codigo_barras'] : $item['cedula']; ?>)
                    </div>
                    
                    <?php if ($es_admin): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Subir Nuevo Documento</h5>
                        </div>
                        <div class="card-body">
                            <form id="formSubirDocumento" enctype="multipart/form-data">
                                <input type="hidden" name="equipo_id" value="<?php echo $tipo == 'equipo' ? $id : ''; ?>">
                                <input type="hidden" name="persona_id" value="<?php echo $tipo == 'persona' ? $id : ''; ?>">
                                <input type="hidden" name="action" value="subir">
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Tipo de Documento *</label>
                                        <select name="tipo_documento" class="form-control" required>
                                            <option value="factura">Factura</option>
                                            <option value="garantia">Garantía</option>
                                            <option value="manual">Manual/Guía</option>
                                            <option value="certificado">Certificado</option>
                                            <option value="mantenimiento">Orden de Mantenimiento</option>
                                            <option value="otro">Otro</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Archivo *</label>
                                        <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx" required>
                                        <small class="text-muted">Máx. 10MB (PDF, imágenes, Word, Excel)</small>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Descripción</label>
                                        <input type="text" name="descripcion" class="form-control" placeholder="Descripción opcional">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i> Subir Documento
                                </button>
                            </form>
                            
                            <div id="uploadProgress" class="mt-3" style="display: none;">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%">0%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Documentos Registrados</h5>
                        </div>
                        <div class="card-body">
                            <div id="listaDocumentos" class="table-responsive">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p>Cargando documentos...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const equipoId = <?php echo $tipo == 'equipo' ? $id : 'null'; ?>;
const personaId = <?php echo $tipo == 'persona' ? $id : 'null'; ?>;

function cargarDocumentos() {
    let url = '/inventario_ti/api/documentos_adjuntos.php?action=listar';
    if (equipoId) url += '&equipo_id=' + equipoId;
    if (personaId) url += '&persona_id=' + personaId;
    
    fetch(url)
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('listaDocumentos');
            
            if (!data || data.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-folder-open fa-3x mb-3"></i><p>No hay documentos adjuntos</p></div>';
                return;
            }
            
            let html = '<table class="table table-hover table-sm"><thead><tr>';
            html += '<th>Tipo</th><th>Nombre</th><th>Tamaño</th><th>Fecha</th><th>Usuario</th><th>Acciones</th>';
            html += '</tr></thead><tbody>';
            
            data.forEach(doc => {
                const icon = getIconForType(doc.tipo_documento);
                html += '<tr>';
                html += '<td><span class="badge bg-secondary">' + doc.tipo_documento + '</span></td>';
                html += '<td><i class="' + icon + ' me-1"></i>' + doc.nombre_original + '</td>';
                html += '<td>' + doc.tamano_format + '</td>';
                html += '<td>' + doc.fecha + '</td>';
                html += '<td>' + (doc.usuario_nombre || 'Sistema') + '</td>';
                html += '<td>';
                html += '<a href="/inventario_ti/api/documentos_adjuntos.php?action=descargar&id=' + doc.id + '" class="btn btn-sm btn-primary" title="Descargar"><i class="fas fa-download"></i></a>';
                <?php if ($es_admin): ?>
                html += ' <button class="btn btn-sm btn-danger" onclick="eliminarDocumento(' + doc.id + ')" title="Eliminar"><i class="fas fa-trash"></i></button>';
                <?php endif; ?>
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
        })
        .catch(err => {
            document.getElementById('listaDocumentos').innerHTML = '<div class="alert alert-danger">Error al cargar documentos</div>';
        });
}

function getIconForType(tipo) {
    const icons = {
        'factura': 'fas fa-file-invoice',
        'garantia': 'fas fa-shield-alt',
        'manual': 'fas fa-book',
        'certificado': 'fas fa-certificate',
        'mantenimiento': 'fas fa-tools',
        'otro': 'fas fa-file'
    };
    return icons[tipo] || 'fas fa-file';
}

<?php if ($es_admin): ?>
document.getElementById('formSubirDocumento').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const progress = document.getElementById('uploadProgress');
    const progressBar = progress.querySelector('.progress-bar');
    
    progress.style.display = 'block';
    progressBar.style.width = '50%';
    progressBar.textContent = 'Subiendo...';
    
    fetch('/inventario_ti/api/documentos_adjuntos.php?action=subir', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        progressBar.style.width = '100%';
        if (data.success) {
            progressBar.classList.add('bg-success');
            progressBar.textContent = 'Completado!';
            this.reset();
            cargarDocumentos();
            setTimeout(() => { progress.style.display = 'none'; progressBar.classList.remove('bg-success'); }, 2000);
        } else {
            progressBar.classList.add('bg-danger');
            progressBar.textContent = 'Error: ' + data.error;
        }
    })
    .catch(err => {
        progressBar.classList.add('bg-danger');
        progressBar.textContent = 'Error de conexión';
    });
});

function eliminarDocumento(id) {
    if (!confirm('¿Está seguro de eliminar este documento?')) return;
    
    const formData = new FormData();
    formData.append('action', 'eliminar');
    formData.append('id', id);
    
    fetch('/inventario_ti/api/documentos_adjuntos.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cargarDocumentos();
        } else {
            alert('Error: ' + data.error);
        }
    });
}
<?php endif; ?>

cargarDocumentos();
</script>

<?php include '../../includes/footer.php'; ?>
