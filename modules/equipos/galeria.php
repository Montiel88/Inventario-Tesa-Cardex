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

$sql = "SELECT e.* FROM equipos e WHERE e.id = $id";
$result = $conn->query($sql);
$equipo = $result->fetch_assoc();

if (!$equipo) {
    header('Location: listar.php');
    exit();
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-images me-2"></i>Galería de Fotos - <?php echo $equipo['codigo_barras']; ?></h4>
                    <div>
                        <a href="detalle.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Equipo:</strong> <?php echo $equipo['tipo_equipo'] . ' ' . $equipo['marca'] . ' ' . $equipo['modelo']; ?>
                    </div>
                    
                    <?php if ($es_admin): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-camera me-2"></i>Subir Nueva Foto</h5>
                        </div>
                        <div class="card-body">
                            <form id="formSubirFoto" enctype="multipart/form-data">
                                <input type="hidden" name="equipo_id" value="<?php echo $id; ?>">
                                <div class="row">
                                    <div class="col-md-8">
                                        <input type="file" name="foto" class="form-control" accept="image/*" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-upload me-1"></i> Subir Foto
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div id="galeriaFotos" class="row">
                        <div class="col-12 text-center text-muted py-5">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Cargando fotos...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver imagen grande -->
<div class="modal fade" id="modalImagen" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista previa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="imagenGrande" src="" class="img-fluid" style="max-height: 70vh;">
            </div>
        </div>
    </div>
</div>

<script>
const equipoId = <?php echo $id; ?>;
const esAdmin = <?php echo $es_admin ? 'true' : 'false'; ?>;

function cargarFotos() {
    fetch('/inventario_ti/api/equipos_fotos.php?action=listar&equipo_id=' + equipoId)
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('galeriaFotos');
            
            if (!data || data.length === 0) {
                container.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="fas fa-images fa-3x mb-3"></i><p>No hay fotos registradas</p></div>';
                return;
            }
            
            let html = '';
            data.forEach(foto => {
                html += `
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card h-100 ${foto.es_principal ? 'border-primary' : ''}">
                        <div class="position-relative">
                            <img src="${foto.url}" class="card-img-top" style="height: 200px; object-fit: cover; cursor: pointer;" 
                                 onclick="verImagen('${foto.url}')" alt="Foto equipo">
                            ${foto.es_principal ? '<span class="badge bg-primary position-absolute top-0 start-0 m-2">Principal</span>' : ''}
                        </div>
                        <div class="card-body text-center">
                            <small class="text-muted">${foto.fecha}</small>
                        </div>
                        ${esAdmin ? `
                        <div class="card-footer d-flex justify-content-between">
                            ${!foto.es_principal ? `<button class="btn btn-sm btn-outline-primary" onclick="setPrincipal(${foto.id})">
                                <i class="fas fa-star"></i> Principal
                            </button>` : '<span></span>'}
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarFoto(${foto.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        ` : ''}
                    </div>
                </div>
                `;
            });
            
            container.innerHTML = html;
        });
}

function verImagen(url) {
    document.getElementById('imagenGrande').src = url;
    new bootstrap.Modal(document.getElementById('modalImagen')).show();
}

<?php if ($es_admin): ?>
document.getElementById('formSubirFoto').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/inventario_ti/api/equipos_fotos.php?action=subir', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            this.reset();
            cargarFotos();
        } else {
            alert('Error: ' + data.error);
        }
    });
});

function eliminarFoto(id) {
    if (!confirm('¿Está seguro de eliminar esta foto?')) return;
    
    const formData = new FormData();
    formData.append('action', 'eliminar');
    formData.append('id', id);
    
    fetch('/inventario_ti/api/equipos_fotos.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cargarFotos();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function setPrincipal(id) {
    const formData = new FormData();
    formData.append('action', 'set_principal');
    formData.append('id', id);
    
    fetch('/inventario_ti/api/equipos_fotos.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cargarFotos();
        } else {
            alert('Error: ' + data.error);
        }
    });
}
<?php endif; ?>

cargarFotos();
</script>

<?php include '../../includes/footer.php'; ?>
