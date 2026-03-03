<?php
session_start();
require_once '../../config/database.php';

// Solo admin puede eliminar
if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=No tienes permisos para eliminar');
    exit();
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: listar.php');
    exit();
}

// Obtener el equipo_id para redirigir al detalle del equipo
$sql = "SELECT equipo_id FROM componentes WHERE id = $id";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $equipo_id = $row['equipo_id'];
} else {
    header('Location: listar.php');
    exit();
}

// Eliminar componente
$sql_delete = "DELETE FROM componentes WHERE id = $id";
if ($conn->query($sql_delete)) {
    header("Location: /inventario_ti/modules/equipos/detalle.php?id=$equipo_id&mensaje=Componente eliminado correctamente");
} else {
    header("Location: /inventario_ti/modules/equipos/detalle.php?id=$equipo_id&error=Error al eliminar: " . urlencode($conn->error));
}
exit();
?>