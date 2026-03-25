<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

require_once '../config/database.php';

// Datos de equipos por estado
$sql_estados = "SELECT estado, COUNT(*) as total FROM equipos GROUP BY estado";
$result_estados = $conn->query($sql_estados);
$estados_labels = [];
$estados_values = [];
while ($row = $result_estados->fetch_assoc()) {
    $estados_labels[] = $row['estado'];
    $estados_values[] = $row['total'];
}

// Datos de equipos por tipo (top 5)
$sql_tipos = "SELECT tipo_equipo, COUNT(*) as total FROM equipos GROUP BY tipo_equipo ORDER BY total DESC LIMIT 5";
$result_tipos = $conn->query($sql_tipos);
$tipos_labels = [];
$tipos_values = [];
while ($row = $result_tipos->fetch_assoc()) {
    $tipos_labels[] = $row['tipo_equipo'];
    $tipos_values[] = $row['total'];
}

// Datos de movimientos por mes (últimos 6 meses)
$meses = [];
$asignaciones = [];
$devoluciones = [];

for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $nombre_mes = date('M Y', strtotime("-$i months"));
    $meses[] = $nombre_mes;
    
    $sql_asig = "SELECT COUNT(*) as total FROM movimientos 
                 WHERE tipo_movimiento IN ('ASIGNACION', 'PRESTAMO_RAPIDO') 
                 AND DATE_FORMAT(fecha_movimiento, '%Y-%m') = '$mes'";
    $result_asig = $conn->query($sql_asig);
    $asignaciones[] = $result_asig ? ($result_asig->fetch_assoc()['total'] ?? 0) : 0;
    
    $sql_dev = "SELECT COUNT(*) as total FROM movimientos 
                WHERE tipo_movimiento IN ('DEVOLUCION', 'DEVOLUCION_RAPIDA') 
                AND DATE_FORMAT(fecha_movimiento, '%Y-%m') = '$mes'";
    $result_dev = $conn->query($sql_dev);
    $devoluciones[] = $result_dev ? ($result_dev->fetch_assoc()['total'] ?? 0) : 0;
}

echo json_encode([
    'estados' => ['labels' => $estados_labels, 'values' => $estados_values],
    'tipos' => ['labels' => $tipos_labels, 'values' => $tipos_values],
    'movimientos' => [
        'labels' => $meses,
        'asignaciones' => $asignaciones,
        'devoluciones' => $devoluciones
    ]
]);
?>