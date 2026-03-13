<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$equipo_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$equipo_id) {
    die('ID de equipo no válido');
}

$sql = "SELECT e.*, u.nombre as ubicacion_nombre 
        FROM equipos e 
        LEFT JOIN ubicaciones u ON e.ubicacion_id = u.id 
        WHERE e.id = $equipo_id";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die('Equipo no encontrado');
}

$equipo = $result->fetch_assoc();

// Obtener asignación actual
$sql_asignacion = "SELECT p.nombres, p.cedula, p.cargo, p.email, a.fecha_asignacion
                   FROM asignaciones a
                   JOIN personas p ON a.persona_id = p.id
                   WHERE a.equipo_id = $equipo_id AND a.fecha_devolucion IS NULL";
$asignacion = $conn->query($sql_asignacion)->fetch_assoc();

// Obtener componentes
$sql_componentes = "SELECT * FROM componentes WHERE equipo_id = $equipo_id";
$componentes = $conn->query($sql_componentes);

// Obtener historial
$sql_historial = "SELECT m.*, p.nombres as persona_nombre
                  FROM movimientos m
                  LEFT JOIN personas p ON m.persona_id = p.id
                  WHERE m.equipo_id = $equipo_id
                  ORDER BY m.fecha_movimiento DESC
                  LIMIT 20";
$historial = $conn->query($sql_historial);

// Generar HTML
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Detalle del Equipo - ' . $equipo['codigo_barras'] . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #5a2d8c; padding-bottom: 10px; }
        .header h1 { color: #5a2d8c; margin: 0; }
        .header h2 { color: #f3b229; margin: 5px 0 0 0; }
        .section { margin-bottom: 20px; }
        .section-title { background: #5a2d8c; color: white; padding: 8px 15px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 10px; }
        .badge-disponible { background: #28a745; color: white; }
        .badge-asignado { background: #ffc107; color: black; }
        .badge-mantenimiento { background: #17a2b8; color: white; }
        .badge-baja { background: #dc3545; color: white; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #666; }
        .qr { text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INSTITUTO TECNOLÓGICO SAN ANTONIO</h1>
        <h2>TESA - Sistema de Gestión de Inventario</h2>
    </div>
    
    <div class="section">
        <div class="section-title">DATOS DEL EQUIPO</div>
        <table>
            <tr>
                <th>Código:</th>
                <td><strong>' . $equipo['codigo_barras'] . '</strong></td>
                <th>Estado:</th>
                <td><span class="badge badge-' . strtolower($equipo['estado']) . '">' . $equipo['estado'] . '</span></td>
            </tr>
            <tr>
                <th>Tipo:</th>
                <td>' . $equipo['tipo_equipo'] . '</td>
                <th>Marca:</th>
                <td>' . ($equipo['marca'] ?: 'N/A') . '</td>
            </tr>
            <tr>
                <th>Modelo:</th>
                <td>' . ($equipo['modelo'] ?: 'N/A') . '</td>
                <th>Número de Serie:</th>
                <td>' . ($equipo['numero_serie'] ?: 'N/A') . '</td>
            </tr>
            <tr>
                <th>Activo Fijo:</th>
                <td>' . ($equipo['activo_fijo'] ?: 'N/A') . '</td>
                <th>Ubicación:</th>
                <td>' . ($equipo['ubicacion_nombre'] ?: 'Sin ubicación') . '</td>
            </tr>
            <tr>
                <th>Fecha de Ingreso:</th>
                <td>' . date('d/m/Y', strtotime($equipo['fecha_ingreso'])) . '</td>
                <th>Fecha de Registro:</th>
                <td>' . date('d/m/Y H:i', strtotime($equipo['created_at'])) . '</td>
            </tr>
        </table>
    </div>
';

if ($asignacion) {
    $html .= '
    <div class="section">
        <div class="section-title">EQUIPO ASIGNADO A:</div>
        <table>
            <tr>
                <th>Nombre:</th>
                <td>' . $asignacion['nombres'] . '</td>
                <th>Cédula:</th>
                <td>' . $asignacion['cedula'] . '</td>
            </tr>
            <tr>
                <th>Cargo:</th>
                <td>' . $asignacion['cargo'] . '</td>
                <th>Email:</th>
                <td>' . ($asignacion['email'] ?: 'N/A') . '</td>
            </tr>
            <tr>
                <th>Fecha de Asignación:</th>
                <td colspan="3">' . date('d/m/Y', strtotime($asignacion['fecha_asignacion'])) . '</td>
            </tr>
        </table>
    </div>
    ';
}

if ($componentes->num_rows > 0) {
    $html .= '
    <div class="section">
        <div class="section-title">COMPONENTES ASOCIADOS</div>
        <table>
            <tr>
                <th>Tipo</th>
                <th>Nombre</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Serie</th>
                <th>Estado</th>
            </tr>
    ';
    
    while ($comp = $componentes->fetch_assoc()) {
        $html .= '
            <tr>
                <td>' . $comp['tipo'] . '</td>
                <td>' . $comp['nombre_componente'] . '</td>
                <td>' . ($comp['marca'] ?: 'N/A') . '</td>
                <td>' . ($comp['modelo'] ?: 'N/A') . '</td>
                <td>' . ($comp['serie'] ?: 'N/A') . '</td>
                <td>' . $comp['estado'] . '</td>
            </tr>
        ';
    }
    
    $html .= '</table></div>';
}

$html .= '
    <div class="section">
        <div class="section-title">HISTORIAL DE MOVIMIENTOS</div>
        <table>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Persona</th>
                <th>Observaciones</th>
            </tr>
';

while ($mov = $historial->fetch_assoc()) {
    $html .= '
        <tr>
            <td>' . date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])) . '</td>
            <td>' . $mov['tipo_movimiento'] . '</td>
            <td>' . ($mov['persona_nombre'] ?: 'Sistema') . '</td>
            <td>' . ($mov['observaciones'] ?: '-') . '</td>
        </tr>
    ';
}

$html .= '
        </table>
    </div>
    
    <div class="footer">
        <p>Documento generado el ' . date('d/m/Y H:i:s') . '</p>
        <p>Sistema de Gestión de Inventario TESA</p>
    </div>
</body>
</html>
';

// Generar PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Descargar PDF
$dompdf->stream('equipo_' . $equipo['codigo_barras'] . '.pdf', ['Attachment' => true]);
