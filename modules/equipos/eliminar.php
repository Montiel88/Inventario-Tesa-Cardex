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

// ============================================
// ELIMINACIÓN TOTAL DEL EQUIPO (CON HISTORIAL)
// ============================================

// Iniciar transacción para asegurar que todo se elimine correctamente
$conn->begin_transaction();

try {
    // 1. Verificar si el equipo existe
    $sql_check = "SELECT id, codigo_barras, tipo_equipo FROM equipos WHERE id = $id";
    $result = $conn->query($sql_check);
    
    if ($result->num_rows == 0) {
        throw new Exception("El equipo no existe");
    }
    
    $equipo = $result->fetch_assoc();
    $codigo = $equipo['codigo_barras'];
    $tipo = $equipo['tipo_equipo'];
    
    // 2. Verificar si está prestado actualmente (NO PERMITIR ELIMINAR)
    $sql_prestado = "SELECT COUNT(*) as total FROM asignaciones WHERE equipo_id = $id AND fecha_devolucion IS NULL";
    $result_prestado = $conn->query($sql_prestado);
    $row_prestado = $result_prestado->fetch_assoc();
    
    if ($row_prestado['total'] > 0) {
        throw new Exception("No se puede eliminar un equipo que está actualmente prestado. Primero registre la devolución.");
    }
    
    // 3. ELIMINAR TODOS LOS REGISTROS RELACIONADOS
    
    // Eliminar movimientos del equipo
    $conn->query("DELETE FROM movimientos WHERE equipo_id = $id");
    
    // Eliminar asignaciones del equipo (historial de préstamos)
    $conn->query("DELETE FROM asignaciones WHERE equipo_id = $id");
    
    // 4. FINALMENTE ELIMINAR EL EQUIPO
    $conn->query("DELETE FROM equipos WHERE id = $id");
    
    // Confirmar todos los cambios
    $conn->commit();
    
    // Redirigir con mensaje de éxito
    header('Location: listar.php?mensaje=' . urlencode("✅ Equipo eliminado permanentemente: $tipo ($codigo)"));
    
} catch (Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollback();
    
    // Redirigir con mensaje de error
    header('Location: listar.php?error=' . urlencode("❌ " . $e->getMessage()));
}

exit();
?>