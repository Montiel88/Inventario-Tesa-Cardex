<?php
session_start();
include '../../includes/header.php';

// Verificar rol (opcional, pero lo dejamos)
$es_admin = ($_SESSION['user_rol'] == 1);
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
                        <!-- Reporte 1: Inventario General (equipos) -->
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
                        
                        <!-- Reporte 2: Préstamos Activos -->
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
                        
                        <!-- Reporte 3: Personas y Equipos -->
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

                    <div class="row mt-4">
                        <!-- Reporte 4: Componentes por Equipo (NUEVO) -->
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-microchip fa-4x text-info mb-3"></i>
                                    <h5>Componentes por Equipo</h5>
                                    <p>Lista de todos los componentes agrupados por equipo</p>
                                    <div class="btn-group w-100">
                                        <a href="generar.php?reporte=componentes_por_equipo&tipo=excel" class="btn btn-success">
                                            <i class="fas fa-file-excel me-2"></i>Excel
                                        </a>
                                        <a href="generar.php?reporte=componentes_por_equipo&tipo=pdf" class="btn btn-danger">
                                            <i class="fas fa-file-pdf me-2"></i>PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reporte 5: Componentes en Mal Estado (NUEVO) -->
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
                                    <h5>Componentes en Mal Estado</h5>
                                    <p>Componentes con estado "Malo", "Regular" o "Por reemplazar"</p>
                                    <div class="btn-group w-100">
                                        <a href="generar.php?reporte=componentes_mal_estado&tipo=excel" class="btn btn-success">
                                            <i class="fas fa-file-excel me-2"></i>Excel
                                        </a>
                                        <a href="generar.php?reporte=componentes_mal_estado&tipo=pdf" class="btn btn-danger">
                                            <i class="fas fa-file-pdf me-2"></i>PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reporte 6: Historial de Componentes por Equipo (NUEVO) -->
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-secondary">
                                <div class="card-body text-center">
                                    <i class="fas fa-history fa-4x text-secondary mb-3"></i>
                                    <h5>Historial de Componentes</h5>
                                    <p>Movimientos de componentes (instalaciones, retiros, reemplazos)</p>
                                    <div class="btn-group w-100">
                                        <a href="generar.php?reporte=historial_componentes&tipo=excel" class="btn btn-success">
                                            <i class="fas fa-file-excel me-2"></i>Excel
                                        </a>
                                        <a href="generar.php?reporte=historial_componentes&tipo=pdf" class="btn btn-danger">
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
                        <?php if (!$es_admin): ?>
                        <br><span class="text-warning">Algunos reportes pueden estar limitados para usuarios con rol de lectura.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>