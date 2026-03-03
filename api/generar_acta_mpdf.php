<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/database.php';
require_once '../config/permisos.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

require_once '../vendor/autoload.php';
use Mpdf\Mpdf;

$tipo = $_GET['tipo'] ?? '';
$persona_id = intval($_GET['persona_id'] ?? 0);

if (!$persona_id) die('ID de persona no válido');

// ============================================
// OBTENER DATOS DE LA PERSONA QUE RECIBE
// ============================================
$sql_persona = "SELECT * FROM personas WHERE id = $persona_id";
$persona = $conn->query($sql_persona)->fetch_assoc();
if (!$persona) die('Persona no encontrada');

// ============================================
// OBTENER DATOS DEL ADMIN (DESDE SESIÓN Y TABLA)
// ============================================
$user_id = $_SESSION['user_id'];

// Intentar obtener de la tabla usuarios
$sql_admin = "SELECT * FROM usuarios WHERE id = $user_id";
$admin_db = $conn->query($sql_admin)->fetch_assoc();

// Construir array de admin con datos disponibles
$admin = [
    'nombre' => 'Administrador',
    'email' => 'admin@tesa.edu.ec',
    'rol' => 'admin'
];

if ($admin_db) {
    // Si hay datos de la tabla, usarlos
    $admin['nombre'] = $admin_db['nombre'] ?? $admin['nombre'];
    $admin['email'] = $admin_db['email'] ?? $admin['email'];
    $admin['rol'] = $admin_db['rol'] ?? $admin['rol'];
} else {
    // Si no hay tabla, usar datos de sesión
    $admin['nombre'] = $_SESSION['user_name'] ?? $admin['nombre'];
    $admin['email'] = $_SESSION['user_email'] ?? $admin['email'];
}

// ============================================
// OBTENER EQUIPOS
// ============================================
if ($tipo == 'ingreso') {
    $sql_equipos = "SELECT e.* FROM equipos e
                    JOIN asignaciones a ON e.id = a.equipo_id
                    WHERE a.persona_id = $persona_id AND a.fecha_devolucion IS NULL";
    $titulo = 'ACTA DE ENTREGA DE EQUIPOS';
} else {
    $sql_equipos = "SELECT e.* FROM equipos e
                    JOIN movimientos m ON e.id = m.equipo_id
                    WHERE m.persona_id = $persona_id AND m.tipo_movimiento = 'DEVOLUCION'";
    $titulo = 'ACTA DE DEVOLUCIÓN DE EQUIPOS';
}
$equipos = $conn->query($sql_equipos);

$base_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/inventario_ti';

// HTML CON ESPACIOS REDUCIDOS
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $titulo . '</title>
    <style>
        body { font-family: Arial; margin: 10px; font-size: 10px; }
        .header { text-align: center; border-bottom: 2px solid #5a2d8c; padding-bottom: 5px; margin-bottom: 10px; }
        .header img { max-width: 60px; }
        h1 { color: #5a2d8c; font-size: 16px; margin: 2px 0; }
        h2 { color: #f3b229; font-size: 14px; margin: 2px 0; }
        .seccion { margin: 10px 0; }
        .seccion h3 { color: #5a2d8c; font-size: 12px; margin: 5px 0; border-bottom: 1px solid #f3b229; padding-bottom: 2px; }
        .info-box { background: #f8f9fc; padding: 8px; border-left: 4px solid #5a2d8c; margin: 5px 0; }
        .info-box-admin { background: #fff3cd; padding: 8px; border-left: 4px solid #f3b229; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 5px 0; font-size: 9px; }
        th { background: #5a2d8c; color: white; padding: 4px; text-align: left; }
        td { padding: 3px; border-bottom: 1px solid #ddd; }
        .firmas { margin-top: 15px; overflow: hidden; }
        .firma-left, .firma-right { width: 45%; text-align: center; }
        .firma-left { float: left; }
        .firma-right { float: right; }
        .linea { border-top: 1px solid #333; width: 80%; margin: 5px auto; }
        .footer { text-align: center; font-size: 7px; color: #999; margin-top: 10px; padding-top: 3px; border-top: 1px solid #ddd; }
        .clear { clear: both; }
        .small { font-size: 8px; color: #666; }
        .dato { margin: 2px 0; }
        .dato strong { display: inline-block; width: 70px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="' . $base_url . '/assets/img/logo-tesa.png">
        <h1>TECNOLÓGICO SAN ANTONIO - TESA</h1>
        <h2>' . $titulo . '</h2>
        <p style="font-size: 8px;"><strong>Fecha:</strong> ' . date('d/m/Y H:i') . '</p>
    </div>

    <div class="seccion">
        <h3>👤 ENTREGÓ</h3>
        <div class="info-box-admin">
            <div class="dato"><strong>Nombre:</strong> ' . strtoupper($admin['nombre']) . '</div>
            <div class="dato"><strong>Email:</strong> ' . $admin['email'] . '</div>
            <div class="dato"><strong>Rol:</strong> ' . ucfirst($admin['rol']) . '</div>
        </div>
    </div>

    <div class="seccion">
        <h3>📦 RECIBIÓ</h3>
        <div class="info-box">
            <div class="dato"><strong>Cédula:</strong> ' . $persona['cedula'] . '</div>
            <div class="dato"><strong>Nombres:</strong> ' . strtoupper($persona['nombres']) . '</div>
            <div class="dato"><strong>Cargo:</strong> ' . $persona['cargo'] . '</div>
            <div class="dato"><strong>Correo:</strong> ' . ($persona['correo'] ?: 'N/R') . '</div>
            <div class="dato"><strong>Teléfono:</strong> ' . ($persona['telefono'] ?: 'N/R') . '</div>
        </div>
    </div>

    <div class="seccion">
        <h3>🔧 EQUIPOS</h3>';

if ($equipos->num_rows > 0) {
    $html .= '<table>
        <tr><th>CÓDIGO</th><th>TIPO</th><th>MARCA</th><th>MODELO</th><th>SERIE</th><th>ESTADO</th></tr>';
    
    $total_equipos = 0;
    while($eq = $equipos->fetch_assoc()) {
        $total_equipos++;
        $html .= '<tr>
            <td>' . $eq['codigo_barras'] . '</td>
            <td>' . $eq['tipo_equipo'] . '</td>
            <td>' . ($eq['marca'] ?: 'N/A') . '</td>
            <td>' . ($eq['modelo'] ?: 'N/A') . '</td>
            <td>' . ($eq['numero_serie'] ?: 'N/A') . '</td>
            <td>' . ($eq['estado'] ?? 'N/A') . '</td>
        </tr>';
    }
    
    $html .= '<tr><td colspan="5" style="text-align:right;"><strong>TOTAL:</strong></td><td><strong>' . $total_equipos . '</strong></td></tr>
    </table>';
} else {
    $html .= '<p style="text-align:center;">No hay equipos</p>';
}

$html .= '
    </div>

    <div class="firmas">
        <div class="firma-left">
            <div class="linea"></div>
            <p style="margin:2px;"><strong>ENTREGÓ</strong><br>' . strtoupper($admin['nombre']) . '</p>
        </div>
        <div class="firma-right">
            <div class="linea"></div>
            <p style="margin:2px;"><strong>RECIBIÓ</strong><br>' . strtoupper($persona['nombres']) . '</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="footer">
        Generado por: ' . $admin['nombre'] . ' · ' . date('d/m/Y H:i:s') . '
    </div>
</body>
</html>';

try {
    $mpdf = new Mpdf([
        'format' => 'A4',
        'margin_top' => 10,
        'margin_bottom' => 10,
        'margin_left' => 10,
        'margin_right' => 10
    ]);
    
    $mpdf->WriteHTML($html);
    $mpdf->Output('acta_' . $tipo . '_' . $persona['cedula'] . '.pdf', 'I');
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>