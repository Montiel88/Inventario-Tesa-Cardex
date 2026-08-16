<?php
if (getenv('APP_DEBUG') === '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id']) || !in_array(intval($_SESSION['user_rol'] ?? 0), [1])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado', 'asignaciones' => []]);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$persona_id = intval($_GET['persona_id'] ?? 0);
if ($persona_id <= 0) {
    echo json_encode(['success' => true, 'asignaciones' => [], 'total' => 0]);
    exit();
}

$sql = "SELECT a.id as asignacion_id,
               a.fecha_asignacion,
               a.observaciones as asignacion_obs,
               e.id as equipo_id,
               e.codigo_barras,
               e.tipo_equipo,
               e.marca,
               e.modelo,
               e.numero_serie,
               e.estado as equipo_estado,
               p.id as persona_id,
               p.nombres as persona_nombre,
               p.cedula,
               p.cargo
        FROM asignaciones a
        JOIN equipos e ON a.equipo_id = e.id
        JOIN personas p ON a.persona_id = p.id
        WHERE a.fecha_devolucion IS NULL
          AND a.persona_id = ?
        ORDER BY e.tipo_equipo, e.marca, e.modelo";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Query error', 'asignaciones' => []]);
    exit();
}
$stmt->bind_param('i', $persona_id);
$stmt->execute();
$res = $stmt->get_result();

$asignaciones = [];
while ($r = $res->fetch_assoc()) {
    $articulo = trim(($r['tipo_equipo'] ?? '') . ' ' . ($r['marca'] ?? '') . ' ' . ($r['modelo'] ?? ''));
    if ($articulo === '' || $articulo === ' ') $articulo = 'Equipo';
    $r['articulo'] = $articulo;
    $r['serie'] = !empty($r['numero_serie']) ? $r['numero_serie'] : 'N/A';
    $r['fecha_asignacion_fmt'] = $r['fecha_asignacion'] ? date('d/m/Y', strtotime($r['fecha_asignacion'])) : '-';
    $asignaciones[] = $r;
}

echo json_encode([
    'success' => true,
    'asignaciones' => $asignaciones,
    'total' => count($asignaciones),
    'persona_id' => $persona_id
]);
