<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

session_start();

define('BASE_PATH', 'C:/xampp/htdocs/inventario_ti/');
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'config/permisos.php';
require_once BASE_PATH . 'config/actas_config.php';

if (!isset($_SESSION["user_id"]) && php_sapi_name() !== 'cli') {
    header("Location: /inventario_ti/login.php");
    exit();
}

require_once BASE_PATH . 'vendor/autoload.php';
use Mpdf\Mpdf;

if (php_sapi_name() === 'cli') {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

$equipo_id = intval($_GET["equipo_id"] ?? 0);
if (!$equipo_id) die("ID de equipo no válido");

$config = cargarConfiguracion();

// Obtener datos del equipo
$sql_equipo = "SELECT e.*, u.nombre as ubicacion_nombre, u.codigo_ubicacion 
               FROM equipos e
               LEFT JOIN ubicaciones u ON e.ubicacion_id = u.id
               WHERE e.id = $equipo_id";
$equipo = $conn->query($sql_equipo)->fetch_assoc();
if (!$equipo) die("Equipo no encontrado");

// Obtener datos del ADMIN
$user_id = $_SESSION["user_id"] ?? 1;
$sql_admin = "SELECT * FROM usuarios WHERE id = $user_id";
$admin_db = $conn->query($sql_admin)->fetch_assoc();
$admin_nombre = $admin_db["nombre"] ?? $_SESSION["user_name"] ?? "Administrador";
$admin_email = $admin_db["email"] ?? $_SESSION["user_email"] ?? "";

$codigo_acta = generarCodigoActa('baja');

$meses = array(
    "January" => "ENERO", "February" => "FEBRERO", "March" => "MARZO",
    "April" => "ABRIL", "May" => "MAYO", "June" => "JUNIO",
    "July" => "JULIO", "August" => "AGOSTO", "September" => "SEPTIEMBRE",
    "October" => "OCTUBRE", "November" => "NOVIEMBRE", "December" => "DICIEMBRE"
);
$mes_actual = $meses[date("F")];

// Guardar en BD
$check_table = $conn->query("SHOW TABLES LIKE 'actas'");
if ($check_table && $check_table->num_rows > 0) {
    $sql_insert = "INSERT INTO actas (codigo_acta, tipo_acta, equipo_id, usuario_id, fecha_generacion) 
                   VALUES ('$codigo_acta', 'baja', $equipo_id, $user_id, NOW())";
    $conn->query($sql_insert);
}

// ============================================
// HTML - ACTA DE BAJA
// ============================================
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Acta de Baja</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 1.5cm 1.5cm 1.5cm 1.5cm;
            font-size: 10pt;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header img {
            max-width: 70px;
            height: auto;
        }
        h1 {
            font-size: 16pt;
            font-weight: bold;
            color: #5a2d8c;
            margin: 5px 0;
        }
        h2 {
            font-size: 14pt;
            font-weight: bold;
            color: #f3b229;
            margin: 5px 0 10px 0;
        }
        .codigo {
            font-size: 9pt;
            color: #666;
            margin-bottom: 15px;
            font-family: monospace;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .info-table td {
            border: 1px solid #000;
            padding: 6px;
        }
        .label {
            font-weight: bold;
            background-color: #f0f0f0;
            width: 25%;
        }
        .observaciones {
            margin: 15px 0;
            border: 1px solid #000;
            padding: 8px;
            background-color: #f9f9f9;
            font-size: 9pt;
        }
        .firmas {
            margin-top: 30px;
            width: 100%;
            overflow: hidden;
        }
        .firma-left, .firma-right {
            float: left;
            width: 48%;
            text-align: center;
        }
        .firma-left {
            margin-right: 4%;
        }
        .linea-firma {
            border-top: 1px solid #000;
            width: 80%;
            margin: 20px auto 5px auto;
        }
        .cargo {
            font-size: 8pt;
            color: #666;
        }
        .footer {
            text-align: center;
            font-size: 7pt;
            color: #999;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class=\"header\">
        <img src=\"" . $config['logo_url'] . "\" alt=\"Logo TESA\">
        <h1>" . $config['institucion_nombre'] . "</h1>
        <h2>ACTA DE BAJA DE EQUIPO</h2>
        <div class=\"codigo\">Código: <strong>$codigo_acta</strong></div>
    </div>

    <p style=\"text-align: justify;\">
        Por medio de la presente, se deja constancia de la <strong>baja definitiva</strong> del equipo detallado a continuación, 
        por las razones expuestas. El bien deja de formar parte del inventario institucional, pero su registro histórico 
        se mantiene para efectos de trazabilidad.
    </p>

    <table class=\"info-table\">
        <tr>
            <td class=\"label\">EQUIPO:</td>
            <td><strong>" . strtoupper($equipo["tipo_equipo"]) . " " . $equipo["marca"] . " " . $equipo["modelo"] . "</strong></td>
        </tr>
        <tr>
            <td class=\"label\">CÓDIGO DE BARRAS:</td>
            <td>" . $equipo["codigo_barras"] . "</td>
        </tr>
        <tr>
            <td class=\"label\">NÚMERO DE SERIE:</td>
            <td>" . ($equipo["numero_serie"] ?: "N/A") . "</td>
        </tr>
        <tr>
            <td class=\"label\">UBICACIÓN ACTUAL:</td>
            <td>" . ($equipo["ubicacion_nombre"] ? $equipo["ubicacion_codigo"] . " - " . $equipo["ubicacion_nombre"] : "Sin ubicación") . "</td>
        </tr>
        <tr>
            <td class=\"label\">FECHA DE BAJA:</td>
            <td>" . $config['ciudad'] . ", " . date("d") . " de " . $mes_actual . " de " . date("Y") . "</td>
        </tr>
    </table>

    <div class=\"observaciones\">
        <strong>MOTIVO DE LA BAJA:</strong> 
        " . ($_GET["motivo"] ?? "No especificado") . "
    </div>

    <div class=\"observaciones\">
        <strong>OBSERVACIONES:</strong> 
        " . ($_GET["observaciones"] ?? "Ninguna") . "
    </div>

    <div class=\"firmas\">
        <div class=\"firma-left\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper($admin_nombre) . "</strong>
            <div class=\"cargo\">RESPONSABLE DE BAJA - " . $config['departamento_entrega'] . "</div>
            <div style=\"font-size:7pt;\">" . $admin_email . "</div>
        </div>
        <div class=\"firma-right\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper($config['aprobador_nombre']) . "</strong>
            <div class=\"cargo\">AUTORIZÓ - " . $config['aprobador_cargo'] . "</div>
        </div>
    </div>

    <div class=\"footer\">
        Documento generado electrónicamente - Sistema de Inventario TESA
    </div>
</body>
</html>";

try {
    $mpdf = new Mpdf([
        "format" => "A4",
        "margin_top" => 10,
        "margin_bottom" => 10,
        "margin_left" => 15,
        "margin_right" => 15,
        "default_font_size" => 10
    ]);
    
    $mpdf->WriteHTML($html);
    $mpdf->Output("Acta_Baja_" . $equipo["codigo_barras"] . ".pdf", "I");
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>