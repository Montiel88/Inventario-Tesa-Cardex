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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// Verificar que la asignación existe
$sql_check = "SELECT a.*, e.codigo_barras, e.tipo_equipo, p.nombres as persona_nombre
              FROM asignaciones a
              JOIN equipos e ON a.equipo_id = e.id
              JOIN personas p ON a.persona_id = p.id
              WHERE a.id = $id";

$result = $conn->query($sql_check);

if ($result->num_rows == 0) {
    header('Location: listar.php?error=Asignación no encontrada');
    exit();
}

$asignacion = $result->fetch_assoc();

// Verificar si ya fue devuelta
if (!is_null($asignacion['fecha_devolucion'])) {
    header('Location: listar.php?error=No se puede eliminar una asignación ya devuelta');
    exit();
}

// Verificar si el equipo está en uso (préstamo activo)
// La asignación es el préstamo en sí, así que podemos eliminarla

$conn->begin_transaction();

try {
    // Registrar movimiento de eliminación (opcional)
    $sql_mov = "INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) 
                VALUES ({$asignacion['equipo_id']}, {$asignacion['persona_id']}, 'ELIMINACION', 
                        'Asignación eliminada administrativamente')";
    $conn->query($sql_mov);
    
    // Cambiar estado del equipo a Disponible
    $conn->query("UPDATE equipos SET estado = 'Disponible' WHERE id = {$asignacion['equipo_id']}");
    
    // Eliminar la asignación
    $sql_delete = "DELETE FROM asignaciones WHERE id = $id";
    $conn->query($sql_delete);
    
    $conn->commit();
    
    header('Location: listar.php?mensaje=' . urlencode("✅ Asignación eliminada correctamente. Equipo {$asignacion['codigo_barras']} disponible."));
    
} catch (Exception $e) {
    $conn->rollback();
    header('Location: listar.php?error=' . urlencode("❌ Error al eliminar: " . $e->getMessage()));
}

exit();
?>