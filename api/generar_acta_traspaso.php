<?php
if (getenv('APP_DEBUG') === '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

session_start();

define('BASE_PATH', rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..')), '/') . '/');
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'config/permisos.php';
require_once BASE_PATH . 'config/actas_config.php';

if (!isset($_SESSION["user_id"]) && php_sapi_name() !== 'cli') {
    header("Location: /Inventario-Tesa-Cardex/login.php");
    exit();
}

require_once BASE_PATH . 'vendor/autoload.php';
use Mpdf\Mpdf;

$multiple = intval($_GET["multiple"] ?? 0);
$asignacion_id = intval($_GET["asignacion_id"] ?? 0);
$nueva_persona_id = intval($_GET["nueva_persona_id"] ?? 0);

$config = cargarConfiguracion();

$ruta_logo_fisica = BASE_PATH . 'assets/img/logo-tesa.png';
$logo_base64 = '';
if (file_exists($ruta_logo_fisica)) {
    $imageData = base64_encode(file_get_contents($ruta_logo_fisica));
    $logo_base64 = 'data:image/png;base64,' . $imageData;
}

$user_id = $_SESSION["user_id"] ?? 1;
$sql_admin = "SELECT nombre FROM usuarios WHERE id = $user_id";
$admin = $conn->query($sql_admin)->fetch_assoc();
$registrador = $admin["nombre"] ?? $_SESSION["user_name"] ?? "Administrador";

$codigo_acta = generarCodigoActa('traspaso');

$meses = array(
    "January" => "ENERO", "February" => "FEBRERO", "March" => "MARZO",
    "April" => "ABRIL", "May" => "MAYO", "June" => "JUNIO",
    "July" => "JULIO", "August" => "AGOSTO", "September" => "SEPTIEMBRE",
    "October" => "OCTUBRE", "November" => "NOVIEMBRE", "December" => "DICIEMBRE"
);
$mes_actual = $meses[date("F")];

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($multiple === 1) {
    if (!$nueva_persona_id) {
        die("Parámetros incompletos: falta el ID de la nueva persona.");
    }

    $traspaso_data = $_SESSION['ultimo_traspaso_multiple'] ?? null;
    $asignacion_ids = is_array($traspaso_data) ? ($traspaso_data['asignacion_ids'] ?? []) : [];
    $observaciones = is_array($traspaso_data) ? trim((string)($traspaso_data['observaciones'] ?? '')) : '';

    if (!is_array($asignacion_ids) || count($asignacion_ids) === 0) {
        die("Error: No se encontraron datos del traspaso múltiple en la sesión. Realiza el traspaso nuevamente y luego genera el acta.");
    }

    $ids_int = array_values(array_filter(array_map('intval', $asignacion_ids), function ($v) { return $v > 0; }));
    if (count($ids_int) === 0) {
        die("Error: IDs inválidos para traspaso múltiple.");
    }

    $ids_string = implode(',', $ids_int);

    $sql_equipos = "SELECT 
                        a.id as asignacion_id,
                        e.codigo_barras,
                        e.tipo_equipo,
                        e.marca,
                        e.modelo,
                        e.numero_serie,
                        p_anterior.nombres as persona_anterior_nombre,
                        p_anterior.cedula as persona_anterior_cedula,
                        p_anterior.cargo as persona_anterior_cargo
                    FROM asignaciones a
                    JOIN equipos e ON a.equipo_id = e.id
                    JOIN personas p_anterior ON a.persona_id = p_anterior.id
                    WHERE a.id IN ($ids_string)
                    ORDER BY p_anterior.nombres ASC, e.tipo_equipo ASC, e.codigo_barras ASC";
    $result_equipos = $conn->query($sql_equipos);
    if (!$result_equipos || $result_equipos->num_rows === 0) {
        die("Error: No se encontraron los equipos traspasados en la base de datos.");
    }

    $sql_nueva = "SELECT * FROM personas WHERE id = $nueva_persona_id";
    $nueva_persona = $conn->query($sql_nueva)->fetch_assoc();
    if (!$nueva_persona) {
        die("Nueva persona no encontrada");
    }

    $equipos = [];
    $anteriores = [];
    while ($row = $result_equipos->fetch_assoc()) {
        $equipos[] = $row;
        $key = (string)($row['persona_anterior_nombre'] ?? '');
        $anteriores[$key] = [
            'nombres' => $row['persona_anterior_nombre'] ?? '',
            'cedula' => $row['persona_anterior_cedula'] ?? '',
            'cargo' => $row['persona_anterior_cargo'] ?? ''
        ];
    }

    $firmante_anterior = '';
    $n_anteriores = 0;
    foreach ($anteriores as $k => $_v) {
        if (trim((string)$k) !== '') {
            $n_anteriores++;
            if ($firmante_anterior === '') {
                $firmante_anterior = (string)$k;
            }
        }
    }

    if ($n_anteriores === 0) {
        $firmante_anterior = 'CUSTODIO ANTERIOR';
    } elseif ($n_anteriores > 1) {
        $firmante_anterior = 'VARIOS CUSTODIOS';
    }

    $lista_anteriores = '';
    if ($n_anteriores > 0) {
        $lista_anteriores .= "<ul style='margin:6px 0 0 18px; padding:0;'>";
        foreach ($anteriores as $an) {
            $nombre = trim((string)($an['nombres'] ?? ''));
            if ($nombre === '') continue;
            $ced = trim((string)($an['cedula'] ?? ''));
            $car = trim((string)($an['cargo'] ?? ''));
            $extra = [];
            if ($ced !== '') $extra[] = "C.I. " . h($ced);
            if ($car !== '') $extra[] = "Cargo: " . h($car);
            $extra_txt = count($extra) ? " — " . implode(" — ", $extra) : "";
            $lista_anteriores .= "<li><strong>" . strtoupper(h($nombre)) . "</strong>" . $extra_txt . "</li>";
        }
        $lista_anteriores .= "</ul>";
    }

    $tabla_equipos = '';
    $contador = 1;
    foreach ($equipos as $eq) {
        $equipo_txt = trim(($eq['tipo_equipo'] ?? '') . ' ' . ($eq['marca'] ?? '') . ' ' . ($eq['modelo'] ?? ''));
        $custodio_cell = "<strong>" . strtoupper(h($eq['persona_anterior_nombre'] ?? '')) . "</strong>";
        if (!empty($eq['persona_anterior_cedula']) || !empty($eq['persona_anterior_cargo'])) {
            $custodio_cell .= "<br><span style='font-size:8pt; color:#333;'>" .
                h($eq['persona_anterior_cedula'] ?? '') .
                (!empty($eq['persona_anterior_cargo']) ? " — " . h($eq['persona_anterior_cargo']) : "") .
            "</span>";
        }
        $tabla_equipos .= "
            <tr>
                <td style='text-align:center; width:6%;'>{$contador}</td>
                <td style='width:34%;'>" . h($equipo_txt) . "</td>
                <td style='width:18%;'>" . h($eq['codigo_barras'] ?? '') . "</td>
                <td style='width:14%;'>" . h($eq['numero_serie'] ?? 'N/A') . "</td>
                <td style='width:20%;'>" . $custodio_cell . "</td>
                <td style='width:8%; text-align:center;'>1</td>
            </tr>";
        $contador++;
    }
    $total = $contador - 1;

    $html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Acta de Traspaso Múltiple</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 1.5cm; font-size: 10pt; line-height: 1.3; }
        .header { text-align: center; margin-bottom: 15px; }
        .header img { max-width: 70px; height: auto; }
        h1 { font-size: 16pt; font-weight: bold; color: #5a2d8c; margin: 5px 0; }
        h2 { font-size: 14pt; font-weight: bold; color: #f3b229; margin: 5px 0 10px 0; }
        .codigo { font-size: 9pt; color: #666; margin-bottom: 15px; font-family: monospace; }
        .info-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .info-table td { border: 1px solid #000; padding: 6px; }
        .label { font-weight: bold; background-color: #f0f0f0; width: 25%; }
        .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 9pt; }
        .items-table th { background: #5a2d8c; color: #fff; border: 1px solid #000; padding: 6px; text-align: center; }
        .items-table td { border: 1px solid #000; padding: 5px; }
        .firmas { margin-top: 35px; width: 100%; overflow: hidden; }
        .firma-left, .firma-center, .firma-right { float: left; width: 31%; text-align: center; margin-right: 2%; }
        .firma-right { margin-right: 0; }
        .linea-firma { border-top: 1px solid #000; width: 80%; margin: 20px auto 5px auto; }
        .cargo { font-size: 8pt; color: #666; }
        .footer { text-align: center; font-size: 7pt; color: #999; margin-top: 15px; }
    </style>
</head>
<body>
    <div class=\"header\">
        <img src=\"" . $logo_base64 . "\" alt=\"Logo TESA\">
        <h1>" . h($config['institucion_nombre'] ?? '') . "</h1>
        <h2>ACTA DE TRASPASO MÚLTIPLE DE CUSTODIO</h2>
        <div class=\"codigo\">Código: <strong>" . h($codigo_acta) . "</strong></div>
    </div>

    <p style=\"text-align: justify;\">
        Por medio de la presente, se deja constancia del traspaso múltiple de custodia de los equipos detallados a continuación.
    </p>

    <table class=\"info-table\">
        <tr>
            <td class=\"label\">NUEVO CUSTODIO:</td>
            <td><strong>" . strtoupper(h($nueva_persona['nombres'] ?? '')) . "</strong><br>
                C.I. " . h($nueva_persona['cedula'] ?? '') . "<br>
                Cargo: " . h($nueva_persona['cargo'] ?? '') . "
            </td>
        </tr>
        <tr>
            <td class=\"label\">CUSTODIO(S) ANTERIOR(ES):</td>
            <td>" . $lista_anteriores . "</td>
        </tr>
        <tr>
            <td class=\"label\">FECHA:</td>
            <td>" . h($config['ciudad'] ?? '') . ", " . date("d") . " de " . h($mes_actual) . " de " . date("Y") . "</td>
        </tr>
        " . ($observaciones !== '' ? ("
        <tr>
            <td class=\"label\">OBSERVACIONES:</td>
            <td>" . h($observaciones) . "</td>
        </tr>
        ") : "") . "
    </table>

    <table class=\"items-table\">
        <thead>
            <tr>
                <th>NO.</th>
                <th>EQUIPO</th>
                <th>CÓDIGO</th>
                <th>SERIE</th>
                <th>CUSTODIO ANTERIOR</th>
                <th>CANT.</th>
            </tr>
        </thead>
        <tbody>
            $tabla_equipos
            <tr style='font-weight: bold; background-color: #f0f0f0;'>
                <td colspan='5' style='text-align: right;'>TOTAL:</td>
                <td style='text-align: center;'>$total</td>
            </tr>
        </tbody>
    </table>

    <div class=\"firmas\">
        <div class=\"firma-left\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper(h($firmante_anterior)) . "</strong>
            <div class=\"cargo\">ENTREGÓ - CUSTODIO ANTERIOR</div>
        </div>
        <div class=\"firma-center\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper(h($nueva_persona['nombres'] ?? '')) . "</strong>
            <div class=\"cargo\">RECIBIÓ - NUEVO CUSTODIO</div>
        </div>
        <div class=\"firma-right\">
            <div class=\"linea-firma\"></div>
            <strong>" . strtoupper(h($registrador)) . "</strong>
            <div class=\"cargo\">AUTORIZÓ - " . h($config['departamento_entrega'] ?? '') . "</div>
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
        $mpdf->Output("Acta_Traspaso_Multiple_" . date("Ymd_His") . ".pdf", "I");
        unset($_SESSION['ultimo_traspaso_multiple']);
        exit;

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit;
    }
}

if (!$asignacion_id || !$nueva_persona_id) {
    die("Parámetros incompletos");
}

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

$sql_nueva = "SELECT * FROM personas WHERE id = $nueva_persona_id";
$nueva_persona = $conn->query($sql_nueva)->fetch_assoc();
if (!$nueva_persona) die("Persona nueva no encontrada");

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

