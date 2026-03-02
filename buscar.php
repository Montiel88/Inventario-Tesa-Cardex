<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config/database.php';
include 'includes/header.php';

$termino = isset($_GET['q']) ? trim($_GET['q']) : '';
$termino_seguro = $conn->real_escape_string($termino);
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-search me-2"></i>Resultados de búsqueda: "<?php echo htmlspecialchars($termino); ?>"</h4>
        </div>
        <div class="card-body">
            <?php if (empty($termino)): ?>
                <p class="text-muted">Ingresa un término de búsqueda.</p>
            <?php else: ?>
                <?php
                // Buscar en personas
                $sql_personas = "SELECT id, nombres, cedula, cargo FROM personas 
                                 WHERE nombres LIKE '%$termino_seguro%' 
                                    OR cedula LIKE '%$termino_seguro%' 
                                    OR cargo LIKE '%$termino_seguro%'";
                $result_personas = $conn->query($sql_personas);
                
                // Buscar en equipos
                $sql_equipos = "SELECT e.*, u.nombre as ubicacion FROM equipos e 
                                LEFT JOIN ubicaciones u ON e.ubicacion_id = u.id
                                WHERE e.codigo_barras LIKE '%$termino_seguro%' 
                                   OR e.tipo_equipo LIKE '%$termino_seguro%'
                                   OR e.marca LIKE '%$termino_seguro%'
                                   OR e.modelo LIKE '%$termino_seguro%'
                                   OR e.numero_serie LIKE '%$termino_seguro%'";
                $result_equipos = $conn->query($sql_equipos);
                
                // Buscar en ubicaciones
                $sql_ubicaciones = "SELECT * FROM ubicaciones 
                                    WHERE nombre LIKE '%$termino_seguro%' 
                                       OR codigo_ubicacion LIKE '%$termino_seguro%'
                                       OR descripcion LIKE '%$termino_seguro%'";
                $result_ubicaciones = $conn->query($sql_ubicaciones);
                ?>

                <h5>Personas encontradas (<?php echo $result_personas->num_rows; ?>)</h5>
                <?php if ($result_personas->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Cédula</th>
                                    <th>Nombre</th>
                                    <th>Cargo</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result_personas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['cedula']; ?></td>
                                    <td><?php echo $row['nombres']; ?></td>
                                    <td><?php echo $row['cargo']; ?></td>
                                    <td>
                                        <a href="modules/personas/detalle.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Ver</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No se encontraron personas.</p>
                <?php endif; ?>

                <hr>

                <h5>Equipos encontrados (<?php echo $result_equipos->num_rows; ?>)</h5>
                <?php if ($result_equipos->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Tipo</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Ubicación</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result_equipos->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['codigo_barras']; ?></td>
                                    <td><?php echo $row['tipo_equipo']; ?></td>
                                    <td><?php echo $row['marca']; ?></td>
                                    <td><?php echo $row['modelo']; ?></td>
                                    <td><?php echo $row['ubicacion'] ?? 'Sin ubicación'; ?></td>
                                    <td>
                                        <a href="modules/equipos/detalle.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Ver</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No se encontraron equipos.</p>
                <?php endif; ?>

                <hr>

                <h5>Ubicaciones encontradas (<?php echo $result_ubicaciones->num_rows; ?>)</h5>
                <?php if ($result_ubicaciones->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Descripción</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result_ubicaciones->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['codigo_ubicacion']; ?></td>
                                    <td><?php echo $row['nombre']; ?></td>
                                    <td><?php echo $row['tipo']; ?></td>
                                    <td><?php echo $row['descripcion']; ?></td>
                                    <td>
                                        <a href="modules/ubicaciones/editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No se encontraron ubicaciones.</p>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>