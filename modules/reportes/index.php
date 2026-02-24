<?php
session_start();
include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-chart-bar me-2"></i>Generador de Reportes</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-boxes fa-4x text-primary mb-3"></i>
                                    <h5>Inventario General</h5>
                                    <p>Reporte completo de todos los equipos con su estado actual</p>
                                    <div class="btn-group w-100">
                                        <a href="generar.php?reporte=inventario&tipo=excel" class="btn btn-success">
                                            <i class="fas fa-file-excel me-2"></i>Excel
                                        </a>
                                        <a href="generar.php?reporte=inventario&tipo=pdf" class="btn btn-danger">
                                            <i class="fas fa-file-pdf me-2"></i>PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-hand-holding fa-4x text-warning mb-3"></i>
                                    <h5>Préstamos Activos</h5>
                                    <p>Equipos actualmente prestados y a quién</p>
                                    <div class="btn-group w-100">
                                        <a href="generar.php?reporte=prestamos&tipo=excel" class="btn btn-success">
                                            <i class="fas fa-file-excel me-2"></i>Excel
                                        </a>
                                        <a href="generar.php?reporte=prestamos&tipo=pdf" class="btn btn-danger">
                                            <i class="fas fa-file-pdf me-2"></i>PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-4x text-info mb-3"></i>
                                    <h5>Personas y Equipos</h5>
                                    <p>Listado de personas con los equipos que tienen asignados</p>
                                    <div class="btn-group w-100">
                                        <a href="generar.php?reporte=personas&tipo=excel" class="btn btn-success">
                                            <i class="fas fa-file-excel me-2"></i>Excel
                                        </a>
                                        <a href="generar.php?reporte=personas&tipo=pdf" class="btn btn-danger">
                                            <i class="fas fa-file-pdf me-2"></i>PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Los reportes se generan con los datos actualizados al momento de la descarga.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>