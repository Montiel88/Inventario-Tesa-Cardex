<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

session_start();

define('BASE_PATH', 'C:/xampp/htdocs/inventario_ti/');
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'config/permisos.php';
require_once BASE_PATH . 'config/actas_config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: /inventario_ti/login.php");
    exit();
}

require_once BASE_PATH . 'vendor/autoload.php';
use Mpdf\Mpdf;

$config = cargarConfiguracion();

// Obtener IDs de sesión
$ids_string = $_SESSION['baja_masiva_ids'] ?? '';
$motivo = $_SESSION['baja_masiva_motivo'] ?? 'No especificado';
$observaciones = $_SESSION['baja_masiva_observaciones'] ?? '';

if (empty($ids_string)) {
    die("No hay equipos seleccionados");
}

// Obtener datos de los equipos
$sql = "SELECT e.*, u.nombre as ubicacion_nombre 
        FROM equipos e
        LEFT JOIN ubicaciones u ON e.ubicacion_id = u.id
        WHERE e.id IN ($ids_string)";
$equipos = $conn->query($sql);

$user_id = $_SESSION["user_id"];
$sql_admin = "SELECT * FROM usuarios WHERE id = $user_id";
$admin_db = $conn->query($sql_admin)->fetch_assoc();
$admin_nombre = $admin_db["nombre"] ?? $_SESSION["user_name"] ?? "Administrador";

$codigo_acta = generarCodigoActa('baja_masiva');

$meses = array(
    "January" => "ENERO", "February" => "FEBRERO", "March" => "MARZO",
    "April" => "ABRIL", "May" => "MAYO", "June" => "JUNIO",
    "July" => "JULIO", "August" => "AGOSTO", "September" => "SEPTIEMBRE",
    "October" => "OCTUBRE", "November" => "NOVIEMBRE", "December" => "DICIEMBRE"
);
$mes_actual = $meses[date("F")];

// Construir tabla de equipos
$tabla_equipos = '';
$contador = 1;
while($eq = $equipos->fetch_assoc()) {
    $tabla_equipos .= "
    <tr>
        <td style='text-align: center; width: 8%;'>$contador</td>
        <td style='width: 30%;'>{$eq['codigo_barras']}</td>
        <td style='width: 32%;'>{$eq['tipo_equipo']} {$eq['marca']} {$eq['modelo']}</td>
        <td style='width: 20%;'>" . ($eq['numero_serie'] ?: 'N/A') . "</td>
        <td style='width: 10%; text-align: center;'>1</td>
    </tr>";
    $contador++;
}
$total = $contador - 1;

$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Acta de Baja Masiva</title>
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
        <h2>ACTA DE BAJA MASIVA DE EQUIPOS</h2>
        <div class=\"codigo\">Código: <strong>$codigo_acta</strong></div>
    </div>

    <p style=\"text-align: justify;\">
        Por medio de la presente, se deja constancia de la <strong>baja definitiva</strong> de los equipos detallados a continuación, 
        por las razones expuestas. Los bienes dejan de formar parte del inventario institucional, pero sus registros históricos 
        se mantienen para efectos de trazabilidad.
    </p>

    <table class=\"info-table\">
        <tr>
            <td class=\"label\">FECHA DE BAJA:</td>
            <td>" . $config['ciudad'] . ", " . date("d") . " de " . $mes_actual . " de " . date("Y") . "</td>
        </tr>
        <tr>
            <td class=\"label\">MOTIVO:</td>
            <td>$motivo</td>
        </tr>
    </table>

    <table class=\"items-table\">
        <thead>
            <tr>
                <th width=\"8%\">NO.</th>
                <th width=\"30%\">CÓDIGO</th>
                <th width=\"32%\">EQUIPO</th>
                <th width=\"20%\">SERIE</th>
                <th width=\"10%\">CANT.</th>
            </tr>
        </thead>
        <tbody>
            $tabla_equipos
            <tr style='font-weight: bold; background-color: #f0f0f0;'>
                <td colspan='4' style='text-align: right;'>TOTAL:</td>
                <td style='text-align: center;'>$total</td>
            </tr>
        </tbody>
    </table>

    <div class=\"observaciones\">
        <strong>OBSERVACIONES:</strong> $observaciones
    </div>

    <div class=\"firmas\">
        <div class=\"firma-left\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper($admin_nombre) . "</strong>
            <div class=\"cargo\">RESPONSABLE DE BAJA - " . $config['departamento_entrega'] . "</div>
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
    $mpdf = new Mpdf(["format" => "A4", "margin_top" => 10, "margin_bottom" => 10, "margin_left" => 15, "margin_right" => 15]);
    $mpdf->WriteHTML($html);
    $mpdf->Output("Acta_Baja_Masiva_" . date('Ymd_His') . ".pdf", "I");
    
    // Limpiar sesión
    unset($_SESSION['baja_masiva_ids']);
    unset($_SESSION['baja_masiva_motivo']);
    unset($_SESSION['baja_masiva_observaciones']);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>