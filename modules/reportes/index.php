<?php
session_start();
include '../../includes/header.php';

$es_admin = ($_SESSION['user_rol'] == 1);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-filter me-2"></i>Reportes por Rango de Fechas</h4>
                </div>
                <div class="card-body">
                    <form id="formReporteFechas" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tipo de Reporte</label>
                            <select name="tipo_reporte" class="form-select" required>
                                <option value="movimientos">Movimientos de Equipos</option>
                                <option value="asignaciones">Asignaciones Realizadas</option>
                                <option value="mantenimientos">Mantenimientos Realizados</option>
                                <option value="bajas">Equipos Dados de Baja</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?php echo date('Y-m-01'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Formato</label>
                            <select name="formato" class="form-select">
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-download me-1"></i> Generar Reporte
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-chart-bar me-2"></i>Reportes Estándar</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Reporte 1: Inventario General -->
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
                        <!-- Reporte 4: Componentes por Equipo -->
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

                        <!-- Reporte 5: Componentes en Mal Estado -->
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

                        <!-- Reporte 6: Historial de Componentes -->
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

                    <div class="row mt-4">
                        <!-- Reporte 7: Equipos sin Asignar -->
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-warehouse fa-4x text-success mb-3"></i>
                                    <h5>Equipos sin Asignar</h5>
                                    <p>Equipos disponibles en inventario que no están asignados</p>
                                    <div class="btn-group w-100">
                                        <a href="generar.php?reporte=equipos_sin_asignar&tipo=excel" class="btn btn-success">
                                            <i class="fas fa-file-excel me-2"></i>Excel
                                        </a>
                                        <a href="generar.php?reporte=equipos_sin_asignar&tipo=pdf" class="btn btn-danger">
                                            <i class="fas fa-file-pdf me-2"></i>PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reporte 8: Equipos en Mantenimiento -->
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-danger">
                                <div class="card-body text-center">
                                    <i class="fas fa-tools fa-4x text-danger mb-3"></i>
                                    <h5>Equipos en Mantenimiento</h5>
                                    <p>Equipos actualmente en mantenimiento</p>
                                    <div class="btn-group w-100">
                                        <a href="generar.php?reporte=equipos_en_mantenimiento&tipo=excel" class="btn btn-success">
                                            <i class="fas fa-file-excel me-2"></i>Excel
                                        </a>
                                        <a href="generar.php?reporte=equipos_en_mantenimiento&tipo=pdf" class="btn btn-danger">
                                            <i class="fas fa-file-pdf me-2"></i>PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reporte 9: Personas sin Equipos -->
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-secondary">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-times fa-4x text-secondary mb-3"></i>
                                    <h5>Personas sin Equipos</h5>
                                    <p>Personas registradas que no tienen equipos asignados</p>
                                    <div class="btn-group w-100">
                                        <a href="generar.php?reporte=personas_sin_equipos&tipo=excel" class="btn btn-success">
                                            <i class="fas fa-file-excel me-2"></i>Excel
                                        </a>
                                        <a href="generar.php?reporte=personas_sin_equipos&tipo=pdf" class="btn btn-danger">
                                            <i class="fas fa-file-pdf me-2"></i>PDF
                                        </a>
                                    </div>
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
document.getElementById('formReporteFechas').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const params = new URLSearchParams(formData);
    window.location.href = 'generar.php?' + params.toString();
});
</script>

<?php include '../../includes/footer.php'; ?>
