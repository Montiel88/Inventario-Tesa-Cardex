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

// Verificar si ya está eliminado
$check_eliminado = $conn->query("SELECT fecha_eliminacion FROM componentes WHERE id = $id");
$row_eliminado = $check_eliminado->fetch_assoc();
if ($row_eliminado && $row_eliminado['fecha_eliminacion'] !== null) {
    header('Location: listar.php?error=El componente ya ha sido eliminado anteriormente');
    exit();
}

// Eliminación lógica (soft delete)
$usuario_actual = $_SESSION['user_id'];
$sql = "UPDATE componentes SET fecha_eliminacion = NOW(), eliminado_por = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $usuario_actual, $id);

if ($stmt->execute()) {
    header('Location: listar.php?mensaje=Componente eliminado correctamente (marcado como eliminado)');
} else {
    header('Location: listar.php?error=' . urlencode("Error al eliminar: " . $conn->error));
}
$stmt->close();
exit();
?>