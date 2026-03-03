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
            
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código Acta</th>
                        <th>Tipo</th>
                        <th>Persona</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                        <th>Secuencia</th>
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
                        <td><?php echo $row['secuencia']; ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="7" class="text-center">No hay actas generadas</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>