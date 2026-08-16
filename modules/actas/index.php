<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();

require_once '../../config/database.php';
include '../../includes/header.php';

// Filtros
 = ['tipo'] ?? '';
 = ['persona_id'] ?? 0;

 = "SELECT a.*, p.nombres as persona_nombre, p.cedula, u.nombre as usuario_nombre
        FROM actas a
        LEFT JOIN personas p ON a.persona_id = p.id
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE 1=1";

if () {
     .= " AND a.tipo_acta = ''";
}
if ( > 0) {
     .= " AND a.persona_id = ";
}
 .= " ORDER BY a.fecha_generacion DESC";
 = ->query();
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-file-pdf me-2"></i>Gestión Centralizada de Actas</h4>
            <a href="generar.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Nueva Acta
            </a>
        </div>
        <div class="card-body">
            <!-- Filtros -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <select class="form-control" onchange="window.location.href='?tipo='+this.value">
                        <option value="">Todos los tipos</option>
                        <option value="ingreso" <?php echo  == 'ingreso' ? 'selected' : ''; ?>>Acta de Ingreso</option>
                        <option value="entrega" <?php echo  == 'entrega' ? 'selected' : ''; ?>>Acta de Entrega</option>
                        <option value="devolucion" <?php echo  == 'devolucion' ? 'selected' : ''; ?>>Acta de Devolución</option>
                        <option value="traspaso" <?php echo  == 'traspaso' ? 'selected' : ''; ?>>Acta de Traspaso</option>
                        <option value="baja" <?php echo  == 'baja' ? 'selected' : ''; ?>>Acta de Baja</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Responsable</th>
                            <th>Persona/Equipo</th>
                            <th>Fecha</th>
                            <th>Firmado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (->num_rows > 0): while( = ->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo ['codigo_acta']; ?></strong></td>
                            <td><span class="badge bg-primary"><?php echo ['tipo_acta']; ?></span></td>
                            <td><?php echo ['usuario_nombre']; ?></td>
                            <td><?php echo ['persona_nombre'] ?: 'Equipo(s)'; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime(['fecha_generacion'])); ?></td>
                            <td><?php echo !empty(['archivo_firmado']) ? '<i class="fas fa-check-circle text-success"></i> Sí' : '<span class="text-muted">No</span>'; ?></td>
                            <td>
                                <a href="detalle.php?id=<?php echo ['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-center">No hay actas registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
