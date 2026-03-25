<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin();

include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-database me-2"></i>Respaldo de Base de Datos</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Esta herramienta te permite generar respaldos completos de la base de datos y restaurarlos.
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-save fa-4x text-primary mb-3"></i>
                            <h5>Crear Nuevo Backup</h5>
                            <p>Genera un respaldo completo de la base de datos en formato SQL</p>
                            <button class="btn btn-primary btn-lg" id="btnGenerarBackup">
                                <i class="fas fa-download me-2"></i>Generar Backup
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-history fa-4x text-success mb-3"></i>
                            <h5>Backups Existentes</h5>
                            <p>Listado de respaldos disponibles</p>
                            <div id="backupList" class="mt-3">
                                <div class="text-center text-muted py-3">Cargando...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Esperar a que jQuery esté disponible (el script se carga en el footer)
(function waitForJQuery() {
    if (window.jQuery) {
        initBackupUI();
    } else {
        setTimeout(waitForJQuery, 30);
    }
})();

function initBackupUI() {
    const $ = window.jQuery;

    function cargarBackups() {
        $('#backupList').html('<div class="text-center text-muted py-3">Cargando...</div>');
        $.getJSON('/inventario_ti/api/backup_listar.php')
            .done(function(data) {
                if (data.success && data.backups.length > 0) {
                    let html = '<div class="list-group">';
                    data.backups.forEach(b => {
                        let size = (b.size / 1024).toFixed(2);
                        html += `
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="text-start">
                                    <i class="fas fa-file-archive me-2 text-secondary"></i>
                                    <strong>${b.name}</strong><br>
                                    <small class="text-muted">${b.date} - ${size} KB</small>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-primary download-backup" data-file="${b.name}">
                                        <i class="fas fa-download"></i> Descargar
                                    </button>
                                    <button class="btn btn-sm btn-warning restore-backup" data-file="${b.name}">
                                        <i class="fas fa-undo-alt"></i> Restaurar
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    $('#backupList').html(html);
                } else {
                    $('#backupList').html('<div class="alert alert-secondary">No hay backups disponibles. Genera uno nuevo.</div>');
                }
            })
            .fail(function() {
                $('#backupList').html('<div class="alert alert-danger">Error al cargar la lista de backups.</div>');
            });
    }

    $('#btnGenerarBackup').on('click', function() {
        Swal.fire({
            title: 'Generar respaldo',
            text: '¿Deseas crear un respaldo completo de la base de datos?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, generar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Generando respaldo...',
                    text: 'Por favor espera, esto puede tomar unos segundos.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.getJSON('/inventario_ti/api/backup_generar.php')
                    .done(function(data) {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Respaldo creado',
                                html: `Archivo: <strong>${data.filename}</strong><br>Se ha generado correctamente.`,
                                confirmButtonText: 'Aceptar'
                            });
                            cargarBackups();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al generar backup',
                                text: data.error || 'Error desconocido'
                            });
                        }
                    })
                    .fail(function() {
                        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                    });
            }
        });
    });

    $(document).on('click', '.download-backup', function() {
        let file = $(this).data('file');
        window.location.href = `/inventario_ti/api/backup_descargar.php?file=${encodeURIComponent(file)}`;
    });

    $(document).on('click', '.restore-backup', function() {
        let file = $(this).data('file');
        Swal.fire({
            title: 'Restaurar respaldo',
            text: `¿Estás seguro de restaurar el archivo ${file}? Esta acción SOBRESCRIBIRÁ todos los datos actuales.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, restaurar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Restaurando...',
                    text: 'Por favor espera, la base de datos está siendo reemplazada.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.post('/inventario_ti/api/backup_restaurar.php', { file: file })
                    .done(function(data) {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Restauración completada',
                                html: data.message,
                                confirmButtonText: 'Aceptar'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al restaurar',
                                text: data.error || 'Error desconocido'
                            });
                        }
                    })
                    .fail(function() {
                        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                    });
            }
        });
    });

    // Cargar lista inicial
    cargarBackups();
}
</script>

<?php include '../../includes/footer.php'; ?>
