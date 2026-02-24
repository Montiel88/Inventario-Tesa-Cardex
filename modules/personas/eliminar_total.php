<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar que sea admin (rol = 1) - CORREGIDO
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
// ELIMINACIÓN TOTAL CON TRANSACCIÓN
// ============================================

// Iniciar transacción
$conn->begin_transaction();

try {
    // Registrar en log
    error_log("Eliminando persona: $nombre_persona (Cédula: $cedula_persona)");
    
    // 1. Eliminar movimientos relacionados
    $conn->query("DELETE FROM movimientos WHERE persona_id = $id");
    
    // 2. Eliminar asignaciones relacionadas
    $conn->query("DELETE FROM asignaciones WHERE persona_id = $id");
    
    // 3. Finalmente eliminar la persona
    $conn->query("DELETE FROM personas WHERE id = $id");
    
    // Confirmar todo
    $conn->commit();
    
    // Redirigir con mensaje de éxito
    header('Location: listar.php?mensaje=' . urlencode("✅ Persona eliminada permanentemente: $nombre_persona"));
    
} catch (Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollback();
    
    // Registrar error
    error_log("Error al eliminar persona ID $id: " . $e->getMessage());
    
    // Redirigir con mensaje de error
    header('Location: listar.php?error=' . urlencode("❌ Error al eliminar: " . $e->getMessage()));
}

exit();
?>