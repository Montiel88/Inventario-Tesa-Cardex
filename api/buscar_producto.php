<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_GET['codigo'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Código no proporcionado']);
    exit;
}

$codigo = $conn->real_escape_string($_GET['codigo']);

// Buscar el equipo por código de barras
$sql = "SELECT e.*, 
               a.persona_id, 
               p.nombres as persona_nombre,
               a.fecha_asignacion,
               a.fecha_devolucion
        FROM equipos e
        LEFT JOIN asignaciones a ON e.id = a.equipo_id AND a.fecha_devolucion IS NULL
        LEFT JOIN personas p ON a.persona_id = p.id
        WHERE e.codigo_barras = '$codigo'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $equipo = $result->fetch_assoc();
    
    // Determinar el estado del equipo
    if ($equipo['persona_id']) {
        $equipo['estado_actual'] = 'PRESTADO';
        $equipo['mensaje'] = "Este equipo está prestado a: " . $equipo['persona_nombre'];
    } else {
        $equipo['estado_actual'] = 'DISPONIBLE';
        $equipo['mensaje'] = "Equipo disponible en bodega";
    }
    
    echo json_encode([
        'success' => true,
        'equipo' => $equipo
    ]);
} else {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Equipo no encontrado en la base de datos'
    ]);
}
?>