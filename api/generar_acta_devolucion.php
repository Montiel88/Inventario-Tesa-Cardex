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
    header("Location: /inventario_ti/login.php");
    exit();
}

require_once BASE_PATH . 'vendor/autoload.php';
use Mpdf\Mpdf;

if (php_sapi_name() === 'cli') {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

// =====================================================================
// NUEVO: SOPORTE PARA ?acta_id=X (LEE DESDE LA TABLA `actas`)
// =====================================================================
$acta_id = intval($_GET['acta_id'] ?? 0);
$acta = null;
$__es_acta_id = ($acta_id > 0);
if ($__es_acta_id) {
    $stmt_a = $conn->prepare("SELECT * FROM actas WHERE id = ? LIMIT 1");
    if ($stmt_a) {
        $stmt_a->bind_param('i', $acta_id);
        $stmt_a->execute();
        $res_a = $stmt_a->get_result();
        if ($res_a && $res_a->num_rows > 0) {
            $acta = $res_a->fetch_assoc();
        }
        $stmt_a->close();
    }
}

$persona_id = intval($_GET["persona_id"] ?? 0);
if (!$persona_id && $acta && !empty($acta['persona_id'])) {
    $persona_id = intval($acta['persona_id']);
}
if (!$persona_id) {
    http_response_code(400);
    die("ID de persona no válido. Utilice ?persona_id=N o ?acta_id=N");
}

$equipos_ids_filtrados = [];
if ($acta && !empty($acta['equipos_ids'])) {
    $tmp_arr = explode(',', $acta['equipos_ids']);
    foreach ($tmp_arr as $t) {
        $ti = intval(trim($t));
        if ($ti > 0) $equipos_ids_filtrados[] = $ti;
    }
}

// Fecha de referencia del acta (no la actual)
$fecha_acta = date('Y-m-d H:i:s');
if ($acta && !empty($acta['fecha_generacion'])) {
    $fecha_acta = $acta['fecha_generacion'];
}
$fecha_acta_inicio = date('Y-m-d 00:00:00', strtotime($fecha_acta . ' - 1 DAY'));
$fecha_acta_fin = date('Y-m-d 23:59:59', strtotime($fecha_acta . ' + 1 DAY'));

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
$stmt_p = $conn->prepare("SELECT * FROM personas WHERE id = ? LIMIT 1");
$stmt_p->bind_param('i', $persona_id);
$stmt_p->execute();
$persona = $stmt_p->get_result()->fetch_assoc();
$stmt_p->close();
if (!$persona) die("Persona no encontrada");

// Tomar devoluciones recientes (desde la última acta de devolución, o desde hoy)
// Pero si venimos con ?acta_id=N usamos el rango de fechas cerca del acta + filtro estricto equipo_ids
$fecha_desde = date('Y-m-d 00:00:00');
$check_table = $conn->query("SHOW TABLES LIKE 'actas'");
if (!$__es_acta_id && $check_table && $check_table->num_rows > 0) {
    $sql_last = "SELECT fecha_generacion
                 FROM actas
                 WHERE persona_id = $persona_id AND tipo_acta = 'devolucion'
                 ORDER BY fecha_generacion DESC
                 LIMIT 1";
    $last = $conn->query($sql_last);
    if ($last && $last->num_rows > 0) {
        $row_last = $last->fetch_assoc();
        if (!empty($row_last['fecha_generacion'])) {
            $fecha_desde = $row_last['fecha_generacion'];
        }
    }
}

if ($__es_acta_id) {
    $fecha_desde = $fecha_acta_inicio;
}

$sql_equipos = "SELECT e.*, e.estado AS estado_equipo, m.observaciones AS condiciones, m.fecha_movimiento
                FROM movimientos m
                JOIN equipos e ON m.equipo_id = e.id
                WHERE m.persona_id = $persona_id
                  AND m.tipo_movimiento IN ('DEVOLUCION','DEVOLUCION_RAPIDA')
                  AND m.fecha_movimiento >= '$fecha_desde'";
if ($__es_acta_id) {
    $sql_equipos .= " AND m.fecha_movimiento BETWEEN '$fecha_acta_inicio' AND '$fecha_acta_fin'";
    if (!empty($equipos_ids_filtrados)) {
        $in_ids = implode(',', $equipos_ids_filtrados);
        $sql_equipos .= " AND m.equipo_id IN ($in_ids)";
    }
}
$sql_equipos .= " ORDER BY m.fecha_movimiento ASC";
$equipos = $conn->query($sql_equipos);

$sql_componentes = "SELECT c.*, c.estado AS estado_comp, mc.fecha_movimiento
                    FROM movimientos_componentes mc
                    JOIN componentes c ON mc.componente_id = c.id
                    WHERE mc.persona_id = $persona_id
                      AND mc.tipo_movimiento = 'DEVOLUCION'
                      AND mc.fecha_movimiento >= '$fecha_desde'";
if ($__es_acta_id) {
    $sql_componentes .= " AND mc.fecha_movimiento BETWEEN '$fecha_acta_inicio' AND '$fecha_acta_fin'";
}
$sql_componentes .= " ORDER BY mc.fecha_movimiento ASC";
$componentes = $conn->query($sql_componentes);

$fecha_hoy_inicio = date('Y-m-d 00:00:00');
$sin_resultados = ((!$equipos || $equipos->num_rows === 0) && (!$componentes || $componentes->num_rows === 0));
if ($sin_resultados && !$__es_acta_id && $fecha_desde !== $fecha_hoy_inicio) {
    $fecha_desde = $fecha_hoy_inicio;
    $sql_equipos = "SELECT e.*, e.estado AS estado_equipo, m.observaciones AS condiciones, m.fecha_movimiento
                    FROM movimientos m
                    JOIN equipos e ON m.equipo_id = e.id
                    WHERE m.persona_id = $persona_id
                      AND m.tipo_movimiento IN ('DEVOLUCION','DEVOLUCION_RAPIDA')
                      AND m.fecha_movimiento >= '$fecha_desde'
                    ORDER BY m.fecha_movimiento ASC";
    $equipos = $conn->query($sql_equipos);

    $sql_componentes = "SELECT c.*, c.estado AS estado_comp, mc.fecha_movimiento
                        FROM movimientos_componentes mc
                        JOIN componentes c ON mc.componente_id = c.id
                        WHERE mc.persona_id = $persona_id
                          AND mc.tipo_movimiento = 'DEVOLUCION'
                          AND mc.fecha_movimiento >= '$fecha_desde'
                        ORDER BY mc.fecha_movimiento ASC";
    $componentes = $conn->query($sql_componentes);
}

// SI VIENE ?acta_id=N y tenemos vacío, armamos 1 fila manual desde la tabla actas
if ($__es_acta_id && ($equipos === false || $equipos->num_rows === 0) && !empty($equipos_ids_filtrados)) {
    $sin_resultados = false;
}

// Código del acta (USAR el de la BD, NO generar uno nuevo)
if ($acta && !empty($acta['codigo_acta'])) {
    $codigo_acta = $acta['codigo_acta'];
} else {
    $codigo_acta = generarCodigoActa('devolucion');
}

$meses = array(
    "January" => "ENERO", "February" => "FEBRERO", "March" => "MARZO",
    "April" => "ABRIL", "May" => "MAYO", "June" => "JUNIO",
    "July" => "JULIO", "August" => "AGOSTO", "September" => "SEPTIEMBRE",
    "October" => "OCTUBRE", "November" => "NOVIEMBRE", "December" => "DICIEMBRE"
);
$ts_acta = strtotime($fecha_acta);
if (!$ts_acta) $ts_acta = time();
$mes_actual = $meses[date("F", $ts_acta)];
$dia_acta = date("d", $ts_acta);
$anio_acta = date("Y", $ts_acta);

// Guardar en BD SOLO SI NO VIENE ?acta_id (evitar duplicados)
if (!$__es_acta_id) {
    $equipos_ids_array = [];
    if ($equipos && $equipos->num_rows > 0) {
        $equipos->data_seek(0);
        while($eq = $equipos->fetch_assoc()) {
            $equipos_ids_array[] = $eq['id'];
        }
    }
    if (empty($equipos_ids_array) && !empty($equipos_ids_filtrados)) {
        $equipos_ids_array = $equipos_ids_filtrados;
    }
    $equipos_ids_string = implode(',', $equipos_ids_array);
    if ($check_table && $check_table->num_rows > 0) {
        $uid_user = intval($_SESSION["user_id"] ?? 1);
        $stmt_ins = $conn->prepare("INSERT INTO actas (codigo_acta, tipo_acta, persona_id, usuario_id, fecha_generacion, equipos_ids) VALUES (?, 'devolucion', ?, ?, NOW(), ?)");
        if ($stmt_ins) {
            $stmt_ins->bind_param('siis', $codigo_acta, $persona_id, $uid_user, $equipos_ids_string);
            @$stmt_ins->execute();
            $stmt_ins->close();
        }
    }
    if ($equipos) @$equipos->data_seek(0);
}

// Si venimos de acta_id y no hay filas en movimientos, armamos un set manual desde equipos
if ($__es_acta_id && !empty($equipos_ids_filtrados) && ($equipos === false || $equipos->num_rows === 0)) {
    $in_ids = implode(',', $equipos_ids_filtrados);
    $sql_backup = "SELECT e.*, e.estado AS estado_equipo,
                          '' AS condiciones,
                          '" . $conn->real_escape_string($fecha_acta) . "' AS fecha_movimiento
                   FROM equipos e
                   WHERE e.id IN ($in_ids)
                   ORDER BY e.id ASC";
    $equipos = $conn->query($sql_backup);
}

// Construir tabla de equipos
$tabla_equipos = '';
$contador = 1;
$total_items = 0;

if ($equipos && $equipos->num_rows > 0) {
    while($eq = $equipos->fetch_assoc()) {
        $estado = $eq["estado_equipo"] ?? "Disponible";
        $color_estado = "#6c757d";
        if ($estado === "Disponible") $color_estado = "#28a745";
        if ($estado === "Asignado") $color_estado = "#0d6efd";
        if ($estado === "Prestado") $color_estado = "#0dcaf0";
        if ($estado === "En mantenimiento") $color_estado = "#ffc107";
        if ($estado === "Baja") $color_estado = "#dc3545";
        
        $tabla_equipos .= "
        <tr>
            <td style='text-align: center; width: 8%;'>$contador</td>
            <td style='width: 42%;'>{$eq["tipo_equipo"]} {$eq["marca"]} {$eq["modelo"]}</td>
            <td style='width: 20%;'>" . ($eq["numero_serie"] ?: "N/A") . "</td>
            <td style='width: 15%; text-align: center;'><span style='color: $color_estado; font-weight: bold;'>$estado</span></td>
            <td style='width: 10%; text-align: center;'>1</td>
        </tr>";
        $contador++;
        $total_items++;
    }
}

if ($componentes && $componentes->num_rows > 0) {
    while($c = $componentes->fetch_assoc()) {
        $estado = $c["estado_comp"] ?? "Disponible";
        $color_estado = "#6c757d";
        if ($estado === "Disponible") $color_estado = "#28a745";
        if ($estado === "Asignado") $color_estado = "#0d6efd";
        if ($estado === "Prestado") $color_estado = "#0dcaf0";
        if ($estado === "En mantenimiento") $color_estado = "#ffc107";
        if ($estado === "Baja") $color_estado = "#dc3545";
        
        $articulo = trim(($c['tipo'] ?? '') . ' ' . ($c['nombre_componente'] ?? '') . ' ' . ($c['marca'] ?? '') . ' ' . ($c['modelo'] ?? ''));
        if ($articulo === '') {
            $articulo = 'Componente';
        }
        
        $tabla_equipos .= "
        <tr>
            <td style='text-align: center; width: 8%;'>$contador</td>
            <td style='width: 42%;'>COMPONENTE - {$articulo}</td>
            <td style='width: 20%;'>" . ($c["numero_serie"] ?: "N/A") . "</td>
            <td style='width: 15%; text-align: center;'><span style='color: $color_estado; font-weight: bold;'>$estado</span></td>
            <td style='width: 10%; text-align: center;'>1</td>
        </tr>";
        $contador++;
        $total_items++;
    }
}

if ($total_items > 0) {
    $tabla_equipos .= "
        <tr style='font-weight: bold; background-color: #f0f0f0;'>
            <td colspan='4' style='text-align: right;'>TOTAL:</td>
            <td style='text-align: center;'>$total_items</td>
        </tr>";
} else {
    $tabla_equipos = "<tr><td colspan='5' style='text-align: center; padding: 15px;'>No hay devoluciones registradas</td></tr>";
}

// Observaciones: usar el motivo del acta si existe
$obs_motivo = '';
if ($acta && !empty($acta['motivo'])) {
    $obs_motivo = '<p style="margin: 0 0 6px 0;"><strong>Observación del registro:</strong> ' . htmlspecialchars($acta['motivo']) . '</p>';
}
$obs_condiciones = '';
if ($acta && !empty($acta['condiciones'])) {
    $obs_condiciones = '<p style="margin: 0 0 6px 0;"><strong>Condiciones registradas:</strong> ' . htmlspecialchars($acta['condiciones']) . '</p>';
}
$obs_estado = '';
if ($acta && !empty($acta['estado_equipo'])) {
    $obs_estado = '<p style="margin: 0 0 6px 0;"><strong>Estado del equipo:</strong> ' . htmlspecialchars($acta['estado_equipo']) . '</p>';
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
        <div class=\"codigo\">Código: <strong>$codigo_acta</strong>" . ($__es_acta_id ? " &nbsp;|&nbsp; ID Acta: <strong>{$acta_id}</strong>" : "") . "</div>
    </div>

    <table class=\"info-table\">
        <tr>
            <td class=\"label\">RESPONSABLE:</td>
            <td><strong>" . strtoupper($persona["nombres"]) . "</strong></td>
        </tr>
        <tr>
            <td class=\"label\">UNIDAD ADMINISTRATIVA:</td>
            <td>" . ($persona["cargo"] ?? '') . "</td>
        </tr>
        <tr>
            <td class=\"label\">FECHA DEVOLUCIÓN:</td>
            <td>" . $config['ciudad'] . ", " . $dia_acta . " de " . $mes_actual . " de " . $anio_acta . "</td>
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
        $obs_estado
        $obs_condiciones
        $obs_motivo
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
    $nombre_pdf = "Acta_Devolucion_" . ($persona["cedula"] ?? $persona_id) . "_" . date('Ymd_His', $ts_acta) . ".pdf";
    $mpdf->Output($nombre_pdf, "I");
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
