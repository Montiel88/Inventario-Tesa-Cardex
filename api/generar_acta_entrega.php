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

$persona_id = intval($_GET["persona_id"] ?? 0);
if (!$persona_id) die("ID de persona no válido");

$config = cargarConfiguracion();

// Obtener datos de la persona
$sql_persona = "SELECT * FROM personas WHERE id = $persona_id";
$persona = $conn->query($sql_persona)->fetch_assoc();
if (!$persona) die("Persona no encontrada");

// Obtener datos del ADMIN
$user_id = $_SESSION["user_id"] ?? 1;
$sql_admin = "SELECT * FROM usuarios WHERE id = $user_id";
$admin_db = $conn->query($sql_admin)->fetch_assoc();
$admin_nombre = $admin_db["nombre"] ?? $_SESSION["user_name"] ?? "Administrador";
$admin_email = $admin_db["email"] ?? $_SESSION["user_email"] ?? "";

// Obtener equipos asignados
$sql_equipos = "SELECT e.* FROM equipos e
                JOIN asignaciones a ON e.id = a.equipo_id
                WHERE a.persona_id = $persona_id AND a.fecha_devolucion IS NULL";
$equipos = $conn->query($sql_equipos);

$codigo_acta = generarCodigoActa('entrega');

$meses = array(
    "January" => "ENERO", "February" => "FEBRERO", "March" => "MARZO",
    "April" => "ABRIL", "May" => "MAYO", "June" => "JUNIO",
    "July" => "JULIO", "August" => "AGOSTO", "September" => "SEPTIEMBRE",
    "October" => "OCTUBRE", "November" => "NOVIEMBRE", "December" => "DICIEMBRE"
);
$mes_actual = $meses[date("F")];

// Guardar en BD
$equipos_ids_array = [];
if ($equipos->num_rows > 0) {
    $equipos->data_seek(0);
    while($eq = $equipos->fetch_assoc()) {
        $equipos_ids_array[] = $eq['id'];
    }
}
$equipos_ids_string = implode(',', $equipos_ids_array);
$check_table = $conn->query("SHOW TABLES LIKE 'actas'");
if ($check_table && $check_table->num_rows > 0) {
    $sql_insert = "INSERT INTO actas (codigo_acta, tipo_acta, persona_id, usuario_id, fecha_generacion, equipos_ids) 
                   VALUES ('$codigo_acta', 'entrega', $persona_id, $user_id, NOW(), '$equipos_ids_string')";
    $conn->query($sql_insert);
}
$equipos->data_seek(0);

// Construir tabla de equipos
$tabla_equipos = '';
if ($equipos->num_rows > 0) {
    $contador = 1;
    while($eq = $equipos->fetch_assoc()) {
        $tabla_equipos .= "
        <tr>
            <td style='text-align: center; width: 8%;'>$contador</td>
            <td style='width: 52%;'>{$eq["tipo_equipo"]} {$eq["marca"]} {$eq["modelo"]}</td>
            <td style='width: 30%;'>" . ($eq["numero_serie"] ?: "N/A") . "</td>
            <td style='text-align: center; width: 10%;'>1</td>
        </tr>";
        $contador++;
    }
    $total = $contador - 1;
    $tabla_equipos .= "
        <tr style='font-weight: bold; background-color: #f0f0f0;'>
            <td colspan='3' style='text-align: right;'>TOTAL:</td>
            <td style='text-align: center;'>$total</td>
        </tr>";
} else {
    $tabla_equipos = "<tr><td colspan='4' style='text-align: center; padding: 20px;'>No hay equipos asignados</td></tr>";
}

$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Acta de Entrega</title>
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
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9pt;
        }
        .items-table th {
            background-color: #5a2d8c;
            color: white;
            font-weight: bold;
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }
        .items-table td {
            border: 1px solid #000;
            padding: 5px;
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
        .aprobado {
            text-align: center;
            margin-top: 25px;
            clear: both;
        }
        .aprobado-linea {
            border-top: 1px solid #000;
            width: 30%;
            margin: 10px auto 5px auto;
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
        <h2>ACTA ENTREGA-RECEPCIÓN DE MATERIALES</h2>
        <div class=\"codigo\">Código: <strong>$codigo_acta</strong></div>
    </div>

    <table class=\"info-table\">
        <tr>
            <td class=\"label\">CUSTODIO / RESPONSABLE:</td>
            <td><strong>" . strtoupper($persona["nombres"]) . "</strong></td>
        </tr>
        <tr>
            <td class=\"label\">UNIDAD ADMINISTRATIVA:</td>
            <td>" . $persona["cargo"] . "</td>
        </tr>
        <tr>
            <td class=\"label\">FECHA:</td>
            <td>Quito, " . date("d") . " de " . $mes_actual . " de " . date("Y") . "</td>
        </tr>
    </table>

    <table class=\"items-table\">
        <thead>
            <tr>
                <th width=\"8%\">NO.</th>
                <th width=\"52%\">ARTÍCULO</th>
                <th width=\"30%\">NÚMERO DE SERIE</th>
                <th width=\"10%\">CANT.</th>
            </tr>
        </thead>
        <tbody>
            $tabla_equipos
        </tbody>
    </table>

    <div class=\"observaciones\">
        <strong>OBSERVACIONES:</strong> Los equipos detallados se entregan en buen estado para el desarrollo de actividades laborales. El custodio se compromete a dar buen uso y cuidado a los bienes institucionales.
    </div>

    <div class=\"firmas\">
        <div class=\"firma-left\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper($admin_nombre) . "</strong>
            <div class=\"cargo\">ENTREGÓ - " . $config['departamento_entrega'] . "</div>
            <div style=\"font-size:7pt;\">" . $admin_email . "</div>
        </div>
        <div class=\"firma-right\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper($persona["nombres"]) . "</strong>
            <div class=\"cargo\">RECIBIÓ - " . $persona["cargo"] . "</div>
        </div>
    </div>

    <div class=\"aprobado\">
        <strong>APROBADO POR:</strong>
        <div class=\"aprobado-linea\"></div>
        <strong>" . $config['aprobador_nombre'] . "</strong>
        <div class=\"cargo\">" . $config['aprobador_cargo'] . "</div>
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
    $mpdf->Output("Acta_Entrega_" . $persona["cedula"] . ".pdf", "I");
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>