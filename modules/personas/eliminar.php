<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// SOLO ADMIN PUEDE ELIMINAR PERSONAS
if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=No tienes permisos para eliminar');
    exit();
}

require_once '../../config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// ============================================
// VERIFICAR QUE LA PERSONA EXISTE
// ============================================
$sql_check = "SELECT id, nombres FROM personas WHERE id = $id";
$result_check = $conn->query($sql_check);

if (!$result_check || $result_check->num_rows == 0) {
    header('Location: listar.php?error=La persona no existe');
    exit();
}

$persona = $result_check->fetch_assoc();
$nombre_persona = $persona['nombres'];

// ============================================
// VERIFICAR SI TIENE MOVIMIENTOS
// ============================================
$sql_movimientos = "SELECT COUNT(*) as total FROM movimientos WHERE persona_id = $id";
$result_movimientos = $conn->query($sql_movimientos);
$row_movimientos = $result_movimientos->fetch_assoc();

$sql_asignaciones = "SELECT COUNT(*) as total FROM asignaciones WHERE persona_id = $id";
$result_asignaciones = $conn->query($sql_asignaciones);
$row_asignaciones = $result_asignaciones->fetch_assoc();

$tiene_movimientos = $row_movimientos['total'] > 0;
$tiene_asignaciones = $row_asignaciones['total'] > 0;

if ($tiene_movimientos || $tiene_asignaciones) {
    // Tiene movimientos o asignaciones, redirigir a eliminación total
    header('Location: eliminar_total.php?id=' . $id . '&confirm=1');
    exit();
}

// ============================================
// ELIMINACIÓN LÓGICA (SOFT DELETE)
// ============================================
$usuario_actual = $_SESSION['user_id'];
$sql = "UPDATE personas SET fecha_eliminacion = NOW(), eliminado_por = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $usuario_actual, $id);

if ($stmt->execute()) {
    header('Location: listar.php?mensaje=' . urlencode("✅ Persona eliminada (lógicamente): $nombre_persona"));
} else {
    header('Location: listar.php?error=' . urlencode("❌ Error al eliminar: " . $conn->error));
}
$stmt->close();
exit();
?>