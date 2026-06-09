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

// ===== NUEVO: Cargar logo en Base64 =====
$ruta_logo_fisica = BASE_PATH . 'assets/img/logo-tesa.png';
$logo_base64 = '';
if (file_exists($ruta_logo_fisica)) {
    $imageData = base64_encode(file_get_contents($ruta_logo_fisica));
    $logo_base64 = 'data:image/png;base64,' . $imageData;
} else {
    $ruta_alternativa = __DIR__ . '/../assets/img/logo-tesa.png';
    if (file_exists($ruta_alternativa)) {
        $imageData = base64_encode(file_get_contents($ruta_alternativa));
        $logo_base64 = 'data:image/png;base64,' . $imageData;
    }
}
// ===== FIN NUEVO =====

// Obtener datos de la persona
$sql_persona = "SELECT * FROM personas WHERE id = $persona_id";
$persona = $conn->query($sql_persona)->fetch_assoc();
if (!$persona) die("Persona no encontrada");

// Consulta corregida: usando e.estado y m.observaciones
$sql_equipos = "SELECT e.*, e.estado AS estado_equipo, m.observaciones AS condiciones, m.fecha_movimiento 
                FROM movimientos m
                JOIN equipos e ON m.equipo_id = e.id
                WHERE m.persona_id = $persona_id AND m.tipo_movimiento = 'DEVOLUCION'
                ORDER BY m.fecha_movimiento DESC";
$equipos = $conn->query($sql_equipos);

$codigo_acta = generarCodigoActa('devolucion');

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
                   VALUES ('$codigo_acta', 'devolucion', $persona_id, " . ($_SESSION["user_id"] ?? 1) . ", NOW(), '$equipos_ids_string')";
    $conn->query($sql_insert);
}
$equipos->data_seek(0);

// Construir tabla de equipos
$tabla_equipos = '';
if ($equipos->num_rows > 0) {
    $contador = 1;
    while($eq = $equipos->fetch_assoc()) {
        $estado = $eq["estado_equipo"] ?? "BUENO";
        $color_estado = "#28a745"; // verde
        if ($estado == "REGULAR") $color_estado = "#ffc107";
        if ($estado == "MALO" || $estado == "DAÑADO") $color_estado = "#dc3545";
        
        $tabla_equipos .= "
        <tr>
            <td style='text-align: center; width: 8%;'>$contador</td>
            <td style='width: 42%;'>{$eq["tipo_equipo"]} {$eq["marca"]} {$eq["modelo"]}</td>
            <td style='width: 20%;'>" . ($eq["numero_serie"] ?: "N/A") . "</td>
            <td style='width: 15%; text-align: center;'><span style='color: $color_estado; font-weight: bold;'>$estado</span></td>
            <td style='width: 10%; text-align: center;'>1</td>
        </tr>";
        $contador++;
    }
    $total = $contador - 1;
    $tabla_equipos .= "
        <tr style='font-weight: bold; background-color: #f0f0f0;'>
            <td colspan='4' style='text-align: right;'>TOTAL:</td>
            <td style='text-align: center;'>$total</td>
        </tr>";
} else {
    $tabla_equipos = "<tr><td colspan='5' style='text-align: center; padding: 15px;'>No hay devoluciones registradas</td></tr>";
}

// ============================================
// HTML CORREGIDO - ACTA DE DEVOLUCIÓN
// ============================================
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Acta de Devolución</title>
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
        <img src=\"" . $logo_base64 . "\" alt=\"Logo TESA\">
        <h1>" . $config['institucion_nombre'] . "</h1>
        <h2>ACTA DE DEVOLUCIÓN DE MATERIALES</h2>
        <div class=\"codigo\">Código: <strong>$codigo_acta</strong></div>
    </div>

    <table class=\"info-table\">
        <tr>
            <td class=\"label\">RESPONSABLE:</td>
            <td><strong>" . strtoupper($persona["nombres"]) . "</strong></td>
        </tr>
        <tr>
            <td class=\"label\">UNIDAD ADMINISTRATIVA:</td>
            <td>" . $persona["cargo"] . "</td>
        </tr>
        <tr>
            <td class=\"label\">FECHA DEVOLUCIÓN:</td>
            <td>" . $config['ciudad'] . ", " . date("d") . " de " . $mes_actual . " de " . date("Y") . "</td>
        </tr>
    </table>

    <table class=\"items-table\">
        <thead>
            <tr>
                <th width=\"8%\">NO.</th>
                <th width=\"42%\">ARTÍCULO</th>
                <th width=\"20%\">SERIE</th>
                <th width=\"15%\">ESTADO</th>
                <th width=\"10%\">CANT.</th>
            </tr>
        </thead>
        <tbody>
            $tabla_equipos
        </tbody>
    </table>

    <div class=\"observaciones\">
        <strong>CONDICIONES DE DEVOLUCIÓN:</strong> Los equipos fueron devueltos en el estado indicado. El custodio queda liberado de responsabilidad sobre los bienes devueltos.
    </div>

    <!-- ============================================ -->
    <!-- FIRMAS PRINCIPALES (SIEMPRE VISIBLES) -->
    <!-- ============================================ -->
    <div class=\"firmas\">
        <!-- FIRMA DE QUIEN RECIBE LA DEVOLUCIÓN (DESDE CONFIGURACIÓN) -->
        <div class=\"firma-left\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper($config['aprobador_nombre']) . "</strong>
            <div class=\"cargo\">RECIBÍ CONFORME - " . $config['aprobador_cargo'] . "</div>
            <div style=\"font-size:7pt;\">" . ($config['email_entrega'] ?? '') . "</div>
        </div>
        
        <!-- FIRMA DE QUIEN DEVUELVE (DESDE BD) -->
        <div class=\"firma-right\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper($persona["nombres"]) . "</strong>
            <div class=\"cargo\">ENTREGÓ - " . ($persona["cargo"] ?? '') . "</div>
            <div style=\"font-size:7pt;\">C.I. " . ($persona["cedula"] ?? '') . "</div>
        </div>
    </div>";

// Verificar si debe mostrar la firma del aprobador
if (isset($config['mostrar_aprobado']) && $config['mostrar_aprobado'] == '1') {
    $html .= "
    <div class=\"aprobado\">
        <strong>APROBADO POR:</strong>
        <div class=\"aprobado-linea\"></div>
        <strong>" . ($config['aprobador_aprueba_nombre'] ?? '') . "</strong>
        <div class=\"cargo\">" . ($config['aprobador_aprueba_cargo'] ?? '') . "</div>
    </div>";
}

// Cerrar HTML
$html .= "
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
    $mpdf->Output("Acta_Devolucion_" . $persona["cedula"] . ".pdf", "I");
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>