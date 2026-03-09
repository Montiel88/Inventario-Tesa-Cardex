<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

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

// Obtener parámetros
$asignacion_id = intval($_GET["asignacion_id"] ?? 0);
$nueva_persona_id = intval($_GET["nueva_persona_id"] ?? 0);

if (!$asignacion_id || !$nueva_persona_id) {
    die("Parámetros incompletos");
}

$config = cargarConfiguracion();

// ===== Logo en Base64 =====
$ruta_logo_fisica = BASE_PATH . 'assets/img/logo-tesa.png';
$logo_base64 = '';
if (file_exists($ruta_logo_fisica)) {
    $imageData = base64_encode(file_get_contents($ruta_logo_fisica));
    $logo_base64 = 'data:image/png;base64,' . $imageData;
}

// Obtener datos de la asignación actual
$sql_asignacion = "SELECT a.*, 
                          e.codigo_barras, e.tipo_equipo, e.marca, e.modelo, e.numero_serie,
                          p_anterior.nombres as persona_anterior_nombre, 
                          p_anterior.cedula as persona_anterior_cedula,
                          p_anterior.cargo as persona_anterior_cargo
                   FROM asignaciones a
                   JOIN equipos e ON a.equipo_id = e.id
                   JOIN personas p_anterior ON a.persona_id = p_anterior.id
                   WHERE a.id = $asignacion_id";
$asignacion = $conn->query($sql_asignacion)->fetch_assoc();
if (!$asignacion) die("Asignación no encontrada");

// Obtener datos de la nueva persona
$sql_nueva = "SELECT * FROM personas WHERE id = $nueva_persona_id";
$nueva_persona = $conn->query($sql_nueva)->fetch_assoc();
if (!$nueva_persona) die("Persona nueva no encontrada");

// Obtener datos del usuario que registra el traspaso
$user_id = $_SESSION["user_id"] ?? 1;
$sql_admin = "SELECT nombre FROM usuarios WHERE id = $user_id";
$admin = $conn->query($sql_admin)->fetch_assoc();
$registrador = $admin["nombre"] ?? $_SESSION["user_name"] ?? "Administrador";

// Generar código de acta
$codigo_acta = generarCodigoActa('traspaso');

// Meses en español
$meses = array(
    "January" => "ENERO", "February" => "FEBRERO", "March" => "MARZO",
    "April" => "ABRIL", "May" => "MAYO", "June" => "JUNIO",
    "July" => "JULIO", "August" => "AGOSTO", "September" => "SEPTIEMBRE",
    "October" => "OCTUBRE", "November" => "NOVIEMBRE", "December" => "DICIEMBRE"
);
$mes_actual = $meses[date("F")];

// ============================================
// HTML - ACTA DE TRASPASO
// ============================================
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Acta de Traspaso</title>
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
        .firmas {
            margin-top: 40px;
            width: 100%;
            overflow: hidden;
        }
        .firma-left, .firma-center, .firma-right {
            float: left;
            width: 31%;
            text-align: center;
            margin-right: 2%;
        }
        .firma-right {
            margin-right: 0;
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
        <img src=\"" . $logo_base64 . "\" alt=\"Logo TESA\">
        <h1>" . $config['institucion_nombre'] . "</h1>
        <h2>ACTA DE TRASPASO DE CUSTODIO</h2>
        <div class=\"codigo\">Código: <strong>$codigo_acta</strong></div>
    </div>

    <p style=\"text-align: justify;\">
        Por medio de la presente, se deja constancia del traspaso de custodia del equipo detallado a continuación, 
        transfiriéndose la responsabilidad del mismo de un custodio a otro, según las políticas institucionales.
    </p>

    <table class=\"info-table\">
        <tr>
            <td class=\"label\">EQUIPO:</td>
            <td><strong>" . strtoupper($asignacion["tipo_equipo"] . " " . $asignacion["marca"] . " " . $asignacion["modelo"]) . "</strong></td>
        </tr>
        <tr>
            <td class=\"label\">CÓDIGO DE BARRAS:</td>
            <td>" . $asignacion["codigo_barras"] . "</td>
        </tr>
        <tr>
            <td class=\"label\">NÚMERO DE SERIE:</td>
            <td>" . ($asignacion["numero_serie"] ?: "N/A") . "</td>
        </tr>
    </table>

    <h3>DATOS DEL TRASPASO</h3>
    
    <table class=\"info-table\">
        <tr>
            <td class=\"label\" style=\"width: 20%;\">CUSTODIO ANTERIOR:</td>
            <td><strong>" . strtoupper($asignacion["persona_anterior_nombre"]) . "</strong><br>
                C.I. " . $asignacion["persona_anterior_cedula"] . "<br>
                Cargo: " . $asignacion["persona_anterior_cargo"] . "
            </td>
        </tr>
        <tr>
            <td class=\"label\">NUEVO CUSTODIO:</td>
            <td><strong>" . strtoupper($nueva_persona["nombres"]) . "</strong><br>
                C.I. " . $nueva_persona["cedula"] . "<br>
                Cargo: " . $nueva_persona["cargo"] . "
            </td>
        </tr>
        <tr>
            <td class=\"label\">FECHA:</td>
            <td>" . $config['ciudad'] . ", " . date("d") . " de " . $mes_actual . " de " . date("Y") . "</td>
        </tr>
    </table>

    <div class=\"firmas\">
        <div class=\"firma-left\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper($asignacion["persona_anterior_nombre"]) . "</strong>
            <div class=\"cargo\">ENTREGÓ - CUSTODIO ANTERIOR</div>
        </div>
        <div class=\"firma-center\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper($nueva_persona["nombres"]) . "</strong>
            <div class=\"cargo\">RECIBIÓ - NUEVO CUSTODIO</div>
        </div>
        <div class=\"firma-right\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper($registrador) . "</strong>
            <div class=\"cargo\">AUTORIZÓ - " . $config['departamento_entrega'] . "</div>
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
    $mpdf->Output("Acta_Traspaso_" . $asignacion["codigo_barras"] . ".pdf", "I");
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>