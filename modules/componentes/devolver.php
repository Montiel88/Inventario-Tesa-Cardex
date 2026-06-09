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

// Buscar la asignación activa usando la misma lógica que en listar.php
$sql_asignacion = "SELECT mc.id, mc.componente_id, mc.persona_id 
                   FROM movimientos_componentes mc
                   WHERE mc.componente_id = $id 
                     AND mc.tipo_movimiento = 'ASIGNACION'
                     AND NOT EXISTS (
                         SELECT 1 FROM movimientos_componentes mc2 
                         WHERE mc2.componente_id = mc.componente_id 
                           AND mc2.tipo_movimiento = 'DEVOLUCION'
                           AND mc2.fecha_movimiento > mc.fecha_movimiento
                     )
                   ORDER BY mc.fecha_movimiento DESC LIMIT 1";
$result = $conn->query($sql_asignacion);
if ($result->num_rows == 0) {
    header('Location: listar.php?error=El componente no está asignado actualmente');
    exit();
}
$asignacion = $result->fetch_assoc();

// Insertar devolución (nuevo movimiento)
$sql_insert = "INSERT INTO movimientos_componentes (componente_id, persona_id, tipo_movimiento, observaciones)
               VALUES (?, ?, 'DEVOLUCION', 'Devolución registrada')";
$stmt = $conn->prepare($sql_insert);
$stmt->bind_param("ii", $asignacion['componente_id'], $asignacion['persona_id']);
if ($stmt->execute()) {
    header('Location: listar.php?mensaje=Componente devuelto correctamente');
} else {
    header('Location: listar.php?error=Error al devolver el componente: ' . urlencode($conn->error));
}
$stmt->close();
exit();
?>