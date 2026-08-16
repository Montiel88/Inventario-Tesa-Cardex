<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();

require_once '../../config/database.php';
include '../../includes/header.php';

 = intval(['id'] ?? 0);
if (!) {
    header('Location: index.php');
    exit();
}

 = "SELECT a.*, p.nombres as persona_nombre, u.nombre as usuario_nombre
        FROM actas a
        LEFT JOIN personas p ON a.persona_id = p.id
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.id = ";
 = ->query();

if (->num_rows == 0) {
    header('Location: index.php');
    exit();
}
 = ->fetch_assoc();
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-file-pdf me-2"></i>Detalle del Acta</h4>
            <div>
                <a href="index.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Código:</strong> <?php echo ['codigo_acta']; ?>
                <br><strong>Tipo:</strong> <?php echo ['tipo_acta']; ?>
                <br><strong>Generada por:</strong> <?php echo ['usuario_nombre']; ?>
                <br><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime(['fecha_generacion'])); ?>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <h5>Acta Original (Generada automáticamente)</h5>
                    <?php if (!empty(['archivo_pdf'])): ?>
                        <a href="/inventario_ti/<?php echo ['archivo_pdf']; ?>" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-file-pdf me-1"></i> Ver PDF Original
                        </a>
                    <?php else: ?>
                        <span class="text-muted">No se generó PDF original.</span>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <h5>Acta Firmada</h5>
                    <?php if (!empty(['archivo_firmado'])): ?>
                        <a href="/inventario_ti/<?php echo ['archivo_firmado']; ?>" target="_blank" class="btn btn-success">
                            <i class="fas fa-file-signature me-1"></i> Ver PDF Firmado
                        </a>
                    <?php else: ?>
                        <form action="/inventario_ti/api/subir_acta_firmada.php" method="POST" enctype="multipart/form-data" class="mt-2">
                            <input type="hidden" name="acta_id" value="<?php echo ; ?>">
                            <div class="input-group">
                                <input type="file" name="archivo_firmado" class="form-control" accept=".pdf" required>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-upload me-1"></i> Subir Firmado
                                </button>
                            </div>
                            <small class="text-muted">Solo se permiten archivos PDF.</small>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
