<?php
session_start();
require_once '../../config/database.php';
include '../../includes/header.php';

$sql = "SELECT c.*, e.tipo_equipo, e.codigo_barras 
        FROM componentes c
        JOIN equipos e ON c.equipo_id = e.id
        WHERE c.estado IN ('Malo', 'Por reemplazar')
        ORDER BY e.tipo_equipo, c.tipo";
$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4>Componentes en Mal Estado o por Reemplazar</h4>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th>Componente</th>
                        <th>Tipo</th>
                        <th>Marca/Modelo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['tipo_equipo'] . '<br><small>' . $row['codigo_barras'] . '</small>'; ?></td>
                        <td><?php echo $row['nombre_componente']; ?></td>
                        <td><?php echo $row['tipo']; ?></td>
                        <td><?php echo $row['marca'] . ' ' . $row['modelo']; ?></td>
                        <td>
                            <span class="badge bg-danger"><?php echo $row['estado']; ?></span>
                        </td>
                        <td>
                            <a href="../componentes/editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="../componentes/trazabilidad.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Historial</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>