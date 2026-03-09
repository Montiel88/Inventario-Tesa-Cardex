<?php
session_start();
require_once '../../config/permisos.php';
require_once '../../config/database.php';
verificarSesion();
requiereAdmin();

include '../../includes/header.php';

$error = '';
$mensaje = '';

// Obtener IDs de la URL (pueden venir como string separado por comas)
$ids_input = isset($_GET['ids']) ? $_GET['ids'] : (isset($_POST['equipos']) ? $_POST['equipos'] : '');
$equipos_seleccionados = [];

// Convertir a array si es string
if (is_string($ids_input) && !empty($ids_input)) {
    $equipos_seleccionados = explode(',', $ids_input);
} elseif (is_array($ids_input)) {
    $equipos_seleccionados = $ids_input;
}

// Limpiar y convertir a enteros
$equipos_seleccionados = array_map('intval', array_filter($equipos_seleccionados));

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar'])) {
    $motivo = $conn->real_escape_string($_POST['motivo']);
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    $ids = $_POST['equipos'] ?? [];
    
    if (empty($ids)) {
        $error = "❌ No se seleccionaron equipos";
    } elseif (empty($motivo)) {
        $error = "❌ Debe especificar el motivo de la baja";
    } else {
        // Asegurar que sea array
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        $ids = array_map('intval', array_filter($ids));
        
        if (empty($ids)) {
            $error = "❌ IDs de equipos no válidos";
        } else {
            $conn->begin_transaction();
            try {
                $ids_string = implode(',', $ids);
                
                // Actualizar estado de los equipos
                $conn->query("UPDATE equipos SET estado = 'Baja' WHERE id IN ($ids_string)");
                
                // Registrar movimiento para cada equipo
                foreach ($ids as $equipo_id) {
                    $sql_mov = "INSERT INTO movimientos (equipo_id, tipo_movimiento, observaciones) 
                                VALUES ($equipo_id, 'BAJA', 'Baja - Motivo: $motivo. $observaciones')";
                    $conn->query($sql_mov);
                }
                
                // Guardar en sesión para generar el acta después
                $_SESSION['baja_masiva_ids'] = $ids_string;
                $_SESSION['baja_masiva_motivo'] = $motivo;
                $_SESSION['baja_masiva_observaciones'] = $observaciones;
                
                $conn->commit();
                
                // ✅ ABRIR PDF EN NUEVA PESTAÑA Y REDIRIGIR
                echo "<script>
                    window.open('/inventario_ti/api/generar_acta_baja_masiva.php', '_blank');
                    window.location.href = 'listar.php?mensaje=" . urlencode("✅ Baja procesada correctamente. Se generó el acta en una nueva pestaña.") . "';
                </script>";
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error = "❌ Error al procesar la baja: " . $e->getMessage();
            }
        }
    }
}

// Obtener datos de los equipos seleccionados
$equipos_data = [];
if (!empty($equipos_seleccionados)) {
    $ids_string = implode(',', $equipos_seleccionados);
    $sql = "SELECT e.*, u.nombre as ubicacion_nombre 
            FROM equipos e
            LEFT JOIN ubicaciones u ON e.ubicacion_id = u.id
            WHERE e.id IN ($ids_string)";
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) {
        $equipos_data[] = $row;
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-trash-alt me-2"></i>Baja de Equipos</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (empty($equipos_data)): ?>
                        <div class="alert alert-warning">
                            No hay equipos seleccionados. 
                            <a href="listar.php" class="alert-link">Volver al listado</a>
                        </div>
                    <?php else: ?>
                        
                        <div class="alert alert-info">
                            <strong><?php echo count($equipos_data); ?> equipo(s) seleccionado(s):</strong>
                        </div>
                        
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Tipo</th>
                                        <th>Marca/Modelo</th>
                                        <th>Serie</th>
                                        <th>Ubicación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($equipos_data as $eq): ?>
                                    <tr>
                                        <td><?php echo $eq['codigo_barras']; ?></td>
                                        <td><?php echo $eq['tipo_equipo']; ?></td>
                                        <td><?php echo $eq['marca'] . ' ' . $eq['modelo']; ?></td>
                                        <td><?php echo $eq['numero_serie'] ?: 'N/A'; ?></td>
                                        <td><?php echo $eq['ubicacion_nombre'] ?: 'Sin ubicación'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="equipos" value="<?php echo implode(',', array_column($equipos_data, 'id')); ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Motivo de la baja *</label>
                                <select name="motivo" class="form-control" required>
                                    <option value="">-- Seleccione --</option>
                                    <option value="Obsolescencia">Obsolescencia</option>
                                    <option value="Daño irreparable">Daño irreparable</option>
                                    <option value="Robo/Pérdida">Robo/Pérdida</option>
                                    <option value="Donación">Donación</option>
                                    <option value="Venta">Venta</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="listar.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <button type="submit" name="confirmar" class="btn btn-danger">
                                    <i class="fas fa-check me-2"></i>Confirmar Baja
                                </button>
                            </div>
                        </form>
                        
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>