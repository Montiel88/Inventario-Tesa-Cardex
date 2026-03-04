<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=No tienes permisos');
    exit();
}

require_once '../../config/database.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: listar.php?error=ID no válido');
    exit();
}

// Verificar si el componente está asignado actualmente
$check = $conn->query("SELECT COUNT(*) as total FROM movimientos_componentes 
                       WHERE componente_id = $id AND tipo_movimiento = 'ASIGNACION'
                       AND NOT EXISTS (
                           SELECT 1 FROM movimientos_componentes mc2 
                           WHERE mc2.componente_id = movimientos_componentes.componente_id 
                             AND mc2.tipo_movimiento = 'DEVOLUCION'
                             AND mc2.fecha_movimiento > movimientos_componentes.fecha_movimiento
                       )");
$row = $check->fetch_assoc();
if ($row['total'] > 0) {
    header('Location: listar.php?error=No se puede eliminar un componente asignado');
    exit();
}

// Eliminar (los movimientos relacionados se borrarán por ON DELETE CASCADE si la FK está configurada)
$conn->query("DELETE FROM componentes WHERE id = $id");

header('Location: listar.php?mensaje=Componente eliminado correctamente');
exit();
?>