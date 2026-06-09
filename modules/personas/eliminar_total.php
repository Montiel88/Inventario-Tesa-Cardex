<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar que sea admin (rol = 1)
if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=No tienes permisos para eliminar');
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/logs_functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// ============================================
// VERIFICAR QUE LA PERSONA EXISTE
// ============================================
$sql_check = "SELECT id, nombres, cedula FROM personas WHERE id = $id";
$result_check = $conn->query($sql_check);

if (!$result_check || $result_check->num_rows == 0) {
    header('Location: listar.php?error=La persona no existe');
    exit();
}

$persona = $result_check->fetch_assoc();
$nombre_persona = $persona['nombres'];
$cedula_persona = $persona['cedula'];

// ============================================
// VERIFICAR SI REALMENTE TIENE MOVIMIENTOS
// ============================================
$sql_movimientos = "SELECT COUNT(*) as total FROM movimientos WHERE persona_id = $id";
$result_movimientos = $conn->query($sql_movimientos);
$row_movimientos = $result_movimientos->fetch_assoc();

$sql_asignaciones = "SELECT COUNT(*) as total FROM asignaciones WHERE persona_id = $id";
$result_asignaciones = $conn->query($sql_asignaciones);
$row_asignaciones = $result_asignaciones->fetch_assoc();

$tiene_movimientos = $row_movimientos['total'] > 0;
$tiene_asignaciones = $row_asignaciones['total'] > 0;

// Si no tiene movimientos, redirigir a eliminación simple
if (!$tiene_movimientos && !$tiene_asignaciones) {
    header('Location: eliminar.php?id=' . $id);
    exit();
}

// ============================================
// ELIMINACIÓN LÓGICA (SOFT DELETE) - NO SE ELIMINA FÍSICAMENTE
// ============================================
// Según requerimiento: "Ningún artículo deberá eliminarse físicamente de la base de datos"
// Solo marcamos como eliminado, conservando el historial

$sql = "UPDATE personas SET fecha_eliminacion = NOW() WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // ============================================
    // REGISTRAR LOG DE ELIMINACIÓN DE PERSONA (CON HISTORIAL)
    // ============================================
    registrarLog($conn, 'Eliminar persona (con historial)', "Persona ID: {$id} - {$nombre_persona} (Cédula: {$cedula_persona}) - Tenía {$row_movimientos['total']} movimientos y {$row_asignaciones['total']} asignaciones", $_SESSION['user_id']);
    // ============================================

    header('Location: listar.php?mensaje=' . urlencode("✅ Persona marcada como eliminada: $nombre_persona (conservando su historial)"));
} else {
    header('Location: listar.php?error=' . urlencode("❌ Error al eliminar: " . $conn->error));
}
$stmt->close();
exit();
?>