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
// ELIMINACIÓN LÓGICA (SOFT DELETE) DEL EQUIPO
// ============================================

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
    
    // 3. Verificar si el equipo ya está eliminado (opcional)
    $sql_eliminado = "SELECT fecha_eliminacion FROM equipos WHERE id = $id";
    $result_eliminado = $conn->query($sql_eliminado);
    $row_eliminado = $result_eliminado->fetch_assoc();
    if ($row_eliminado['fecha_eliminacion'] !== null) {
        throw new Exception("El equipo ya ha sido eliminado anteriormente.");
    }
    
    // 4. Realizar la eliminación lógica
    $usuario_actual = (int)$_SESSION['user_id'];
    $check_eliminado_por = $conn->query("SHOW COLUMNS FROM equipos LIKE 'eliminado_por'");

    if ($check_eliminado_por && $check_eliminado_por->num_rows > 0) {
        $sql_update = "UPDATE equipos SET fecha_eliminacion = NOW(), eliminado_por = ? WHERE id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("ii", $usuario_actual, $id);
    } else {
        $sql_update = "UPDATE equipos SET fecha_eliminacion = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("i", $id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Error al eliminar el equipo: " . $conn->error);
    }
    
    // Redirigir con mensaje de éxito
    header('Location: listar.php?mensaje=' . urlencode("✅ Equipo eliminado (marcado como eliminado): $tipo ($codigo)"));
    
} catch (Exception $e) {
    // Redirigir con mensaje de error
    header('Location: listar.php?error=' . urlencode("❌ " . $e->getMessage()));
}

exit();
?>  
