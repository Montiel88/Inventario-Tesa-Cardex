<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 1) {
    header('Location: /inventario_ti/login.php');
    exit();
}

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: listar.php');
    exit();
}

$persona_id = intval($_POST['persona_id'] ?? 0);
$componente_id = intval($_POST['componente_id'] ?? 0);
$observaciones = trim($_POST['observaciones'] ?? '');

if (!$persona_id || !$componente_id) {
    header("Location: detalle.php?id=$persona_id&error=Debe seleccionar un componente");
    exit();
}

// Verificar que el componente esté disponible
$check = $conn->query("SELECT COUNT(*) as total FROM movimientos_componentes mc 
                       WHERE mc.componente_id = $componente_id 
                         AND mc.tipo_movimiento = 'ASIGNACION'
                         AND NOT EXISTS (
                             SELECT 1 FROM movimientos_componentes mc2
                             WHERE mc2.componente_id = mc.componente_id
                               AND mc2.tipo_movimiento = 'DEVOLUCION'
                               AND mc2.fecha_movimiento > mc.fecha_movimiento
                         )");
$row = $check->fetch_assoc();
if ($row['total'] > 0) {
    header("Location: detalle.php?id=$persona_id&error=El componente ya está asignado");
    exit();
}

// Insertar asignación
$sql = "INSERT INTO movimientos_componentes (componente_id, persona_id, tipo_movimiento, observaciones)
        VALUES (?, ?, 'ASIGNACION', ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $componente_id, $persona_id, $observaciones);
if ($stmt->execute()) {
    header("Location: detalle.php?id=$persona_id&mensaje=Componente asignado correctamente");
} else {
    header("Location: detalle.php?id=$persona_id&error=Error al asignar componente");
}
$stmt->close();
?>