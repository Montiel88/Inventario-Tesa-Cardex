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

$sql_persona = "SELECT * FROM personas WHERE id = $persona_id";
$persona = $conn->query($sql_persona)->fetch_assoc();
if (!$persona) die("Persona no encontrada");

$codigo_acta = generarCodigoActa('descargo');

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
    $sql_insert = "INSERT INTO actas (codigo_acta, tipo_acta, persona_id, usuario_id, fecha_generacion) 
                   VALUES ('$codigo_acta', 'descargo', $persona_id, " . ($_SESSION["user_id"] ?? 1) . ", NOW())";
    $conn->query($sql_insert);
}

$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Descargo de Responsabilidad</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 1.5cm 1.5cm 1.5cm 1.5cm;
            font-size: 11pt;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header img {
            max-width: 80px;
            height: auto;
        }
        h1 {
            font-size: 18pt;
            font-weight: bold;
            color: #5a2d8c;
            margin: 5px 0;
        }
        h2 {
            font-size: 16pt;
            font-weight: bold;
            color: #f3b229;
            margin: 5px 0 10px 0;
        }
        .codigo {
            text-align: center;
            font-size: 10pt;
            color: #666;
            margin-bottom: 10px;
            font-family: monospace;
        }
        .fecha {
            text-align: right;
            margin: 10px 0 15px 0;
            font-style: italic;
        }
        .content {
            text-align: justify;
        }
        .content p {
            margin: 8px 0;
        }
        .content ol {
            margin: 8px 0 15px 25px;
        }
        .content li {
            margin: 5px 0;
        }
        .firma-empleado {
            margin: 20px 0 20px 0;
        }
        .firmas-final {
            margin-top: 20px;
            overflow: hidden;
            border-top: 2px solid #5a2d8c;
            padding-top: 15px;
        }
        .firma-left {
            float: left;
            width: 48%;
            text-align: center;
        }
        .firma-right {
            float: right;
            width: 48%;
            text-align: center;
        }
        .linea-firma {
            border-top: 2px solid #000;
            width: 80%;
            margin: 10px auto 5px auto;
        }
        .nombre-firma {
            font-weight: bold;
            font-size: 11pt;
        }
        .cargo-firma {
            font-size: 10pt;
            color: #666;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>
    <div class=\"header\">
        <img src=\"" . $config['logo_url'] . "\" alt=\"Logo TESA\">
        <h1>" . $config['institucion_nombre'] . "</h1>
        <h2>DESCARGO DE RESPONSABILIDAD</h2>
        <div class=\"codigo\"><strong>Código: $codigo_acta</strong></div>
    </div>

    <div class=\"fecha\">
        Quito, " . date("d") . " de " . $mes_actual . " de " . date("Y") . "
    </div>

    <div class=\"content\">
        <p>Este documento establece los términos y condiciones relacionados con la devolución de los equipos informáticos proporcionados a los empleados durante su empleo en <strong>" . $config['institucion_nombre'] . "</strong>. Al aceptar y firmar este descargo de responsabilidad, el empleado reconoce y acepta las siguientes condiciones:</p>

        <ol>
            <li><strong>Propiedad de la Empresa:</strong> Todos los equipos informáticos proporcionados por la empresa, incluyendo, pero no limitándose a computadoras portátiles, dispositivos móviles, accesorios y cualquier otro dispositivo electrónico, son propiedad exclusiva de " . $config['institucion_nombre'] . ".</li>

            <li><strong>Uso Exclusivo para Fines Laborales:</strong> Los equipos informáticos proporcionados están destinados exclusivamente para el desempeño de las funciones laborales asignadas al empleado durante su empleo en " . $config['institucion_nombre'] . ". El uso de estos equipos para fines personales no está permitido sin autorización expresa.</li>

            <li><strong>Devolución al Cese de Empleo:</strong> En caso de renuncia, despido u otra terminación del empleo, el empleado se compromete a devolver todos los equipos informáticos, cables, adaptadores y cualquier otro accesorio proporcionado por la empresa en un plazo máximo de 2 días hábiles a partir de la fecha de cese de empleo.</li>

            <li><strong>Devolución en Buen Estado:</strong> El empleado acepta devolver los equipos en el mismo estado en que fueron proporcionados, sujeto a un desgaste razonable por el uso normal. Cualquier daño o pérdida no atribuible al desgaste normal será responsabilidad del empleado.</li>

            <li><strong>Acceso y Eliminación de Datos:</strong> El empleado reconoce que es su responsabilidad respaldar y transferir cualquier dato personal almacenado en los equipos proporcionados antes de la devolución. La empresa no se hace responsable de la pérdida de datos personales del empleado.</li>

            <li><strong>Consecuencias por Incumplimiento:</strong> El incumplimiento de las condiciones establecidas en este descargo de responsabilidad puede resultar en acciones disciplinarias, incluyendo, pero no limitándose a la retención de beneficios pendientes, deducciones salariales y otras medidas aplicables según la política de la empresa.</li>

            <li><strong>Firma del Empleado:</strong> Al firmar este descargo de responsabilidad, el empleado reconoce haber leído y comprendido todas las condiciones establecidas y acepta cumplir con los términos aquí detallados.</li>
        </ol>

        <div class=\"firma-empleado\">
            <p><strong>Firma del Empleado:</strong> _________________________________________</p>
            <p><strong>Fecha:</strong> ____________________</p>
            <p><strong>Nombre del Empleado:</strong> " . strtoupper($persona["nombres"]) . "</p>
            <p><strong>C.I.:</strong> " . $persona["cedula"] . "</p>
        </div>
    </div>

    <div class=\"firmas-final\">
        <div class=\"firma-left\">
            <div class=\"linea-firma\"></div>
            <div class=\"nombre-firma\">" . strtoupper($persona["nombres"]) . "</div>
            <div class=\"cargo-firma\">EL EMPLEADO</div>
        </div>
        <div class=\"firma-right\">
            <div class=\"linea-firma\"></div>
            <div class=\"nombre-firma\">" . $config['institucion_nombre'] . "</div>
            <div class=\"cargo-firma\">POR LA INSTITUCIÓN</div>
        </div>
        <div class=\"clear\"></div>
    </div>
</body>
</html>";

try {
    $mpdf = new Mpdf([
        "format" => "A4",
        "margin_top" => 15,
        "margin_bottom" => 15,
        "margin_left" => 15,
        "margin_right" => 15
    ]);
    
    $mpdf->WriteHTML($html);
    $mpdf->Output("Descargo_" . $persona["cedula"] . ".pdf", "I");
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>