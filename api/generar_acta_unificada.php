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
require_once __DIR__ . '/actas_pdf_common.php';

if (!isset($_SESSION["user_id"]) && php_sapi_name() !== 'cli') {
    header("Location: /inventario_ti/login.php");
    exit();
}
if (!file_exists(BASE_PATH . 'vendor/autoload.php')) {
    die("Error: Composer autoload file not found. Please run 'composer install' in the project root.");
}
require_once BASE_PATH . 'vendor/autoload.php';
use Mpdf\Mpdf;

if (php_sapi_name() === 'cli') {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

$acta_id = intval($_GET["acta_id"] ?? 0);
$guardar = intval($_GET["guardar"] ?? 0);
if (!$acta_id) die("ID de acta no válido");

$acta = actas_cargar_desde_id($conn, $acta_id);
if (!$acta) die("Acta no encontrada.");

$tipo = $acta['tipo_acta'];
$codigo_acta = $acta['codigo_acta'];
$equipos = $acta['equipos_list'] ?? [];
$logo_base64 = actas_cargar_logo();
$meses = $GLOBALS['actas_meses'];
$mes_actual = $meses[date("F", strtotime($acta['fecha_generacion']))];

$tipos_metadata = [
    'ingreso' => [
        'h2' => 'ACTA DE INGRESO DE INVENTARIO',
        'titulo_pdf' => 'Acta de Ingreso',
        'label_custodio' => 'ENTREGADO POR (PROVEEDOR / REMITENTE):',
        'label_unidad' => 'DOCUMENTO / FACTURA DE COMPRA:',
        'label_entrega_firma' => 'ENTREGÓ - Remitente / Proveedor',
        'label_recibe_firma' => 'RECIBIÓ - Responsable de Bodega',
        'observaciones_default' => 'Los equipos y materiales detallados ingresan al inventario institucional en las condiciones descritas. Se deja constancia de su recepción física.'
    ],
    'entrega' => [
        'h2' => 'ACTA ENTREGA-RECEPCIÓN DE MATERIALES',
        'titulo_pdf' => 'Acta de Entrega',
        'label_custodio' => 'CUSTODIO / RESPONSABLE:',
        'label_unidad' => 'UNIDAD ADMINISTRATIVA:',
        'label_entrega_firma' => 'ENTREGÓ - Responsable de Inventario',
        'label_recibe_firma' => 'RECIBIÓ - Custodio',
        'observaciones_default' => 'Los equipos detallados se entregan en buen estado para el desarrollo de actividades laborales. El custodio se compromete a dar buen uso y cuidado a los bienes institucionales.'
    ],
    'devolucion' => [
        'h2' => 'ACTA DE DEVOLUCIÓN DE MATERIALES',
        'titulo_pdf' => 'Acta de Devolución',
        'label_custodio' => 'CUSTODIO QUE DEVUELVE:',
        'label_unidad' => 'UNIDAD ADMINISTRATIVA:',
        'label_entrega_firma' => 'ENTREGÓ - Custodio',
        'label_recibe_firma' => 'RECIBIÓ - Responsable de Bodega',
        'observaciones_default' => 'Los equipos detallados son devueltos por el custodio al almacén institucional. Se deja constancia de su recepción en las condiciones aquí descritas.'
    ],
    'traspaso' => [
        'h2' => 'ACTA DE TRASPASO DE CUSTODIO',
        'titulo_pdf' => 'Acta de Traspaso',
        'label_custodio' => 'NUEVO CUSTODIO:',
        'label_unidad' => 'UNIDAD ADMINISTRATIVA DESTINO:',
        'label_entrega_firma' => 'ENTREGÓ - Custodio Anterior',
        'label_recibe_firma' => 'RECIBIÓ - Nuevo Custodio',
        'observaciones_default' => 'Los equipos y materiales detallados cambian de custodio dentro de la institución. Ambas partes aceptan el estado descrito y se comprometen al cuidado y uso correcto.'
    ],
    'baja' => [
        'h2' => 'ACTA DE BAJA / DESCARGO DE INVENTARIO',
        'titulo_pdf' => 'Acta de Baja',
        'label_custodio' => 'RESPONSABLE DE BAJA:',
        'label_unidad' => 'UNIDAD QUE SOLICITA:',
        'label_entrega_firma' => 'AUTORIZÓ - Responsable Administrativo',
        'label_recibe_firma' => 'REALIZÓ - Responsable de Inventario',
        'observaciones_default' => 'Los equipos y materiales detallados se retiran definitivamente del inventario institucional por la causa justificada descrita a continuación.'
    ]
];
$meta = isset($tipos_metadata[$tipo]) ? $tipos_metadata[$tipo] : $tipos_metadata['entrega'];

$config = function_exists('cargarConfiguracion') ? cargarConfiguracion() : [
    'institucion_nombre' => 'INSTITUTO TECNOLÓGICO SAN ANTONIO',
    'ciudad' => 'Quito',
    'aprobador_nombre' => $acta['usuario_nombre'] ?? 'Administrador',
    'aprobador_cargo' => 'Responsable de Inventario y TI',
    'email_entrega' => $acta['usuario_email'] ?? '',
    'mostrar_aprobado' => '0'
];

if (empty($config['institucion_nombre'])) $config['institucion_nombre'] = 'INSTITUTO TECNOLÓGICO SAN ANTONIO';
if (empty($config['ciudad'])) $config['ciudad'] = 'Quito';
if (empty($config['aprobador_nombre'])) $config['aprobador_nombre'] = $acta['usuario_nombre'] ?? 'Administrador';
if (empty($config['aprobador_cargo'])) $config['aprobador_cargo'] = 'Responsable de Inventario y TI';

$persona_campo_nombre = !empty($acta['persona_id']) && !empty($acta['nombres'])
    ? strtoupper($acta['nombres'])
    : strtoupper($config['aprobador_nombre']);
$persona_campo_unidad = !empty($acta['cargo'])
    ? $acta['cargo']
    : ($config['aprobador_cargo'] ?? 'Unidad Administrativa');

if ($tipo == 'ingreso' && !empty($acta['persona_nombre'])) {
    $persona_campo_nombre = strtoupper($acta['persona_nombre']);
}

$observaciones = !empty($acta['motivo'])
    ? trim($acta['motivo'])
    : $meta['observaciones_default'];
if ($tipo == 'baja' && !empty($acta['motivo'])) {
    $observaciones = '<strong>MOTIVO DE LA BAJA:</strong> ' . nl2br(htmlspecialchars(trim($acta['motivo']))) . '<br><br>' . $meta['observaciones_default'];
} else {
    $observaciones = nl2br(htmlspecialchars($observaciones));
}

$con_componentes = false;
$persona_id = !empty($acta['persona_id']) ? $acta['persona_id'] : null;
$tabla_equipos = actas_obtener_tabla($equipos, $con_componentes, $conn, $persona_id);

$logo_img_html = !empty($logo_base64)
    ? "<img src=\"" . $logo_base64 . "\" alt=\"Logo TESA\">"
    : "<h1 style='color:#5a2d8c'>TECNOLÓGICO SAN ANTONIO</h1>";

$nombre_responsable = strtoupper($config['aprobador_nombre']);
$cargo_responsable = $config['aprobador_cargo'];
$email_responsable = $config['email_entrega'] ?? '';

$firma_izq_nombre = $nombre_responsable;
$firma_izq_cargo = $meta['label_entrega_firma'];
$firma_der_nombre = $persona_campo_nombre;
$firma_der_cargo = $meta['label_recibe_firma'];
$cedula_der = !empty($acta['cedula']) ? 'C.I. ' . $acta['cedula'] : '';

if ($tipo == 'entrega' || $tipo == 'traspaso') {
    $firma_izq_nombre = $nombre_responsable;
    $firma_izq_cargo = $meta['label_entrega_firma'] . ' - ' . $cargo_responsable;
    $firma_der_nombre = $persona_campo_nombre;
    $firma_der_cargo = $meta['label_recibe_firma'] . (empty($persona_campo_unidad) || $persona_campo_unidad === $cargo_responsable ? '' : ' - ' . $persona_campo_unidad);
}
if ($tipo == 'devolucion') {
    $firma_izq_nombre = $persona_campo_nombre;
    $firma_izq_cargo = $meta['label_entrega_firma'];
    $firma_der_nombre = $nombre_responsable;
    $firma_der_cargo = $meta['label_recibe_firma'] . ' - ' . $cargo_responsable;
}
if ($tipo == 'ingreso') {
    $firma_izq_nombre = $persona_campo_nombre;
    $firma_izq_cargo = $meta['label_entrega_firma'];
    $firma_der_nombre = $nombre_responsable;
    $firma_der_cargo = $meta['label_recibe_firma'] . ' - ' . $cargo_responsable;
}
if ($tipo == 'baja') {
    $firma_izq_nombre = $nombre_responsable;
    $firma_izq_cargo = $meta['label_entrega_firma'] . ' - ' . $cargo_responsable;
    $firma_der_nombre = $persona_campo_nombre;
    $firma_der_cargo = $meta['label_recibe_firma'] . (empty($persona_campo_unidad) ? '' : ' - ' . $persona_campo_unidad);
}

$fecha_texto = $config['ciudad'] . ", " . date("d", strtotime($acta['fecha_generacion'])) . " de " . $mes_actual . " de " . date("Y", strtotime($acta['fecha_generacion']));

$aprobado_html = '';
if (isset($config['mostrar_aprobado']) && $config['mostrar_aprobado'] == '1' && !empty($config['aprobador_aprueba_nombre'])) {
    $aprobado_html = "
    <div class='aprobado'>
        <strong>APROBADO POR:</strong>
        <div class='aprobado-linea'></div>
        <strong>" . strtoupper($config['aprobador_aprueba_nombre']) . "</strong>
        <div class='cargo'>" . ($config['aprobador_aprueba_cargo'] ?? '') . "</div>
    </div>";
}

$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>" . htmlspecialchars($meta['titulo_pdf']) . "</title>
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
    <div class='header'>
        $logo_img_html
        <h1>" . htmlspecialchars($config['institucion_nombre']) . "</h1>
        <h2>" . htmlspecialchars($meta['h2']) . "</h2>
        <div class='codigo'>Código: <strong>$codigo_acta</strong></div>
    </div>

    <table class='info-table'>
        <tr>
            <td class='label'>" . htmlspecialchars($meta['label_custodio']) . "</td>
            <td><strong>" . htmlspecialchars($persona_campo_nombre) . "</strong></td>
        </tr>
        <tr>
            <td class='label'>" . htmlspecialchars($meta['label_unidad']) . "</td>
            <td>" . htmlspecialchars($persona_campo_unidad) . "</td>
        </tr>
        <tr>
            <td class='label'>FECHA:</td>
            <td>$fecha_texto</td>
        </tr>
    </table>

    <table class='items-table'>
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

    <div class='observaciones'>
        <strong>OBSERVACIONES:</strong> $observaciones
    </div>

    <div class='firmas'>
        <div class='firma-left'>
            <div class='linea-firma'></div>
            <strong>" . htmlspecialchars($firma_izq_nombre) . "</strong>
            <div class='cargo'>" . htmlspecialchars($firma_izq_cargo) . "</div>
            " . ($firma_izq_cargo == $config['aprobador_cargo'] || $firma_izq_nombre == $nombre_responsable ? "<div style='font-size:7pt;'>" . htmlspecialchars($email_responsable) . "</div>" : '') . "
        </div>
        <div class='firma-right'>
            <div class='linea-firma'></div>
            <strong>" . htmlspecialchars($firma_der_nombre) . "</strong>
            <div class='cargo'>" . htmlspecialchars($firma_der_cargo) . "</div>
            <div style='font-size:7pt;'>" . htmlspecialchars($cedula_der) . "</div>
        </div>
    </div>

    $aprobado_html

    <div class='footer'>
        Documento generado electrónicamente - Sistema de Gestión de Inventario TESA · Código $codigo_acta
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

    if ($guardar === 1) {
        actas_guardar_pdf_y_redirigir($conn, $acta_id, '', $mpdf);
    } else {
        $nombre_descarga = $meta['titulo_pdf'] . '_' . $codigo_acta . '.pdf';
        $mpdf->Output($nombre_descarga, "I");
    }
} catch (Exception $e) {
    echo "Error al generar el PDF: " . $e->getMessage();
}
