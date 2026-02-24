<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar roles si es necesario
$es_admin = ($_SESSION['user_rol'] == 'admin');

// Solo admin puede acceder a ciertas funciones
if (!$es_admin && strpos($_SERVER['PHP_SELF'], 'eliminar.php') !== false) {
    header('Location: dashboard.php?error=No tienes permisos');
    exit();
}
?>
<?php
require_once '../../config/database.php';
include '../../includes/header.php';

$sql = "SELECT p.id, p.nombres, p.cargo, 
               COUNT(e.id) as total_equipos,
               GROUP_CONCAT(CONCAT(e.tipo_equipo, ' (', e.marca, ')') SEPARATOR ', ') as equipos
        FROM personas p
        LEFT JOIN asignaciones a ON p.id = a.persona_id AND a.fecha_devolucion IS NULL
        LEFT JOIN equipos e ON a.equipo_id = e.id
        GROUP BY p.id
        ORDER BY p.nombres";

$result = $conn->query($sql);
?>

<h1>Equipos Asignados por Persona</h1>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Persona</th>
            <th>Cargo</th>
            <th>Total Equipos</th>
            <th>Equipos Asignados</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['nombres']; ?></td>
            <td><?php echo $row['cargo']; ?></td>
            <td><?php echo $row['total_equipos']; ?></td>
            <td><?php echo $row['equipos'] ?? 'Ninguno'; ?></td>
            <td>
                <a href="detalle_persona.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Ver detalle</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include '../../includes/footer.php'; ?>