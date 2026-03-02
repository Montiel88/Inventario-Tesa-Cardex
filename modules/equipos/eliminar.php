<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin(); // Solo admin puede eliminar equipos

require_once '../../config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// Verificar si el equipo tiene asignaciones activas (préstamos sin devolver)
$sql_check = "SELECT COUNT(*) as total FROM asignaciones WHERE equipo_id = $id AND fecha_devolucion IS NULL";
$result = $conn->query($sql_check);
$row = $result->fetch_assoc();

if ($row['total'] > 0) {
    header('Location: listar.php?error=No se puede eliminar un equipo que está actualmente prestado');
    exit();
}

// Verificar si tiene movimientos en el historial
$sql_movimientos = "SELECT COUNT(*) as total FROM movimientos WHERE equipo_id = $id";
$result_mov = $conn->query($sql_movimientos);
$row_mov = $result_mov->fetch_assoc();

if ($row_mov['total'] > 0) {
    // Tiene movimientos, no se puede eliminar físicamente, se marca como "Dado de baja"
    $sql_update = "UPDATE equipos SET estado = 'Dado de baja' WHERE id = $id";
    if ($conn->query($sql_update)) {
        header('Location: listar.php?mensaje=Equipo marcado como dado de baja (conserva historial)');
    } else {
        header('Location: listar.php?error=Error al actualizar estado: ' . $conn->error);
    }
} else {
    // No tiene movimientos, se puede eliminar completamente
    $sql_delete = "DELETE FROM equipos WHERE id = $id";
    if ($conn->query($sql_delete)) {
        header('Location: listar.php?mensaje=Equipo eliminado correctamente');
    } else {
        header('Location: listar.php?error=Error al eliminar: ' . $conn->error);
    }
}
exit();
?>