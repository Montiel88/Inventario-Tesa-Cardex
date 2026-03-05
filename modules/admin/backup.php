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
                Esta herramienta te permite generar respaldos de la base de datos.
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-save fa-3x text-primary mb-3"></i>
                            <h5>Crear Nuevo Backup</h5>
                            <p>Genera un respaldo completo de la base de datos</p>
                            <button class="btn btn-primary" onclick="alert('Función en desarrollo - Próximamente disponible')">
                                <i class="fas fa-download me-2"></i>Generar Backup
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-history fa-3x text-success mb-3"></i>
                            <h5>Backups Existentes</h5>
                            <p>Listado de respaldos disponibles</p>
                            <div class="alert alert-secondary">
                                No hay backups disponibles
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
