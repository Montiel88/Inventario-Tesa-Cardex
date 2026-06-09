<?php
session_start();

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 1) {
    header('Location: /inventario_ti/login.php');
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/logs_functions.php';

// Obtener filtros
$filtro_desde = $_GET['desde'] ?? date('Y-m-01');
$filtro_hasta = $_GET['hasta'] ?? date('Y-m-d');

// Obtener logs
$sql = "SELECT l.*, u.nombre_usuario, u.email 
        FROM logs l 
        LEFT JOIN usuarios u ON l.usuario_id = u.id 
        WHERE l.fecha BETWEEN ? AND ?
        ORDER BY l.fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $filtro_desde, $filtro_hasta);
$stmt->execute();
$result = $stmt->get_result();

// Generar CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="logs_' . $filtro_desde . '_al_' . $filtro_hasta . '.csv"');

$output = fopen('php://output', 'w');

// BOM para UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados
fputcsv($output, ['ID', 'Fecha', 'Hora', 'Usuario', 'Email', 'Acción', 'Detalle', 'IP']);

// Datos
while ($log = $result->fetch_assoc()) {
    $fecha = strtotime($log['fecha']);
    fputcsv($output, [
        $log['id'],
        date('Y-m-d', $fecha),
        date('H:i:s', $fecha),
        $log['nombre_usuario'] ?? 'Sistema',
        $log['email'] ?? '',
        $log['accion'],
        $log['detalle'] ?? '',
        $log['ip']
    ]);
}

fclose($output);
$conn->close();
exit;
?>
