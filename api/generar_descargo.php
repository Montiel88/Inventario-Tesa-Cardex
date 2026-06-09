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

$sql_persona = "SELECT * FROM personas WHERE id = $persona_id";
$persona = $conn->query($sql_persona)->fetch_assoc();
if (!$persona) die("Persona no encontrada");

// Obtener datos del ADMIN que genera el acta
$user_id = $_SESSION["user_id"] ?? 1;
$sql_admin = "SELECT * FROM usuarios WHERE id = $user_id";
$admin_db = $conn->query($sql_admin)->fetch_assoc();
$admin_nombre = $admin_db["nombre"] ?? $_SESSION["user_name"] ?? "Administrador";
$admin_cargo = "Administrador del Sistema";

$codigo_acta = generarCodigoActa('descargo');

$meses = array(
    "January" => "ENERO", "February" => "FEBRERO", "March" => "MARZO",
    "April" => "ABRIL", "May" => "MAYO", "June" => "JUNIO",
    "July" => "JULIO", "August" => "AGOSTO", "September" => "SEPTIEMBRE",
    "October" => "OCTUBRE", "November" => "NOVIEMBRE", "December" => "DICIEMBRE"
);
$mes_actual = $meses[date("F")];
$anio_actual = date("Y");
$dia_actual = date("d");

// Guardar en BD si existe la tabla
$check_table = $conn->query("SHOW TABLES LIKE 'actas'");
if ($check_table && $check_table->num_rows > 0) {
    $sql_insert = "INSERT INTO actas (codigo_acta, tipo_acta, persona_id, usuario_id, fecha_generacion) 
                   VALUES ('$codigo_acta', 'descargo', $persona_id, " . ($_SESSION["user_id"] ?? 1) . ", NOW())";
    $conn->query($sql_insert);
}

// ============================================
// HTML - FORMATO COMPACTO CON DOS FIRMAS
// ============================================
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Descargo de Responsabilidad</title>
    <style>
        /* ===== CONFIGURACIÓN GENERAL ===== */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 10.5pt;
            line-height: 1.3;
            color: #333;
        }
        
        /* ===== ENCABEZADO COMPACTO ===== */
        .header {
            text-align: center;
            margin-bottom: 15px;  /* Aumentado para separar del texto */
            margin-top: 30px;      /* Ajustado para bajar el título */
        }
        
        .header img {
            max-width: 60px;
            height: auto;
            margin-bottom: 0;
        }
        
        h1 {
            font-size: 16pt;
            font-weight: bold;
            color: #5a2d8c;
            margin: 0;
            text-transform: uppercase;
        }
        
        h2 {
            font-size: 13pt;
            font-weight: bold;
            color: #f3b229;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            border-bottom: 1px solid #5a2d8c;
            padding-bottom: 2px;
        }
        
        .codigo {
            font-size: 8pt;
            color: #666;
            margin-bottom: 2px;
            font-family: monospace;
        }
        
        /* ===== FECHA ===== */
        .fecha {
            text-align: right;
            margin: 2px 0 10px 0;  /* Aumentado margen inferior */
            font-style: italic;
            font-size: 9pt;
            color: #444;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 3px;
        }
        
        /* ===== CONTENIDO PRINCIPAL ===== */
        .content {
            text-align: justify;
            margin-top: 5px;
        }
        
        .content p {
            margin: 4px 0;
            text-align: justify;
        }
        
        .content ol {
            margin: 4px 0 4px 15px;
            padding-left: 5px;
        }
        
        .content li {
            margin: 3px 0;
            text-align: justify;
            line-height: 1.3;
        }
        
        .content strong {
            color: #5a2d8c;
        }
        
        /* ===== TEXTO ADICIONAL ===== */
        .vigor {
            font-family: 'Arial', sans-serif;
            margin: 12px 0 10px 0;
            font-weight: bold;
            color: #424141;
            text-align: left;  /* Cambiado de center a left */
            border-top: 1px dotted #ccc;
            border-bottom: 1px dotted #ccc;
            padding: 5px 0 5px 5px;  /* Padding izquierdo */
        }
        
        /* ===== FIRMAS LADO A LADO ===== */
        .firmas-container {
            margin-top: 95px;
            overflow: hidden;
            width: 100%;
        }
        
        .firma-box {
            float: left;
            width: 48%;
            text-align: center;
        }
        
        .firma-left {
            margin-right: 4%;
        }
        
        .firma-linea {
            border-top: 2px solid #333;
            width: 90%;
            margin: 8px auto 3px auto;
        }
                
        .firma-nombre {
            font-weight: bold;
            font-size: 10pt;
            color: #5a2d8c;
            margin: 2px 0;
        }
        
        .firma-cargo {
            font-size: 8pt;
            color: #666;
            margin: 0;
        }
        
        .firma-detalle {
            font-size: 8pt;
            color: #999;
            margin-top: 2px;
        }
        
        .clear {
            clear: both;
        }
        
        /* ===== PIE DE PÁGINA ===== */
        .footer {
            text-align: center;
            font-size: 6.5pt;
            color: #999;
            margin-top: 50px;
            border-top: 1px solid #eee;
            padding-top: 3px;
        }
    </style>
</head>
<body>

<!-- ============================================ -->
<!-- ENCABEZADO -->
<!-- ============================================ -->
<div class=\"header\">
    <img src=\"" . $logo_base64 . "\" alt=\"Logo TESA\">
    <h1>" . $config['institucion_nombre'] . "</h1>
    <h2>DESCARGO DE RESPONSABILIDAD</h2>
    <div class=\"codigo\">Código: <strong>$codigo_acta</strong></div>
</div>

<!-- ============================================ -->
<!-- FECHA -->
<!-- ============================================ -->
<div class=\"fecha\">
    Quito, $dia_actual de $mes_actual de $anio_actual
</div>

<!-- ============================================ -->
<!-- TEXTO DEL DESCARGO (7 PUNTOS COMPLETOS) -->
<!-- ============================================ -->
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
</div>

<!-- ============================================ -->
<!-- TEXTO DE VIGENCIA (ALINEADO A LA IZQUIERDA) -->
<!-- ============================================ -->
<div class=\"vigor\">
    Este descargo de responsabilidad entra en vigor a partir de la fecha de la firma por parte del empleado.
</div>

<!-- ============================================ -->
<!-- FIRMAS LADO A LADO (SIN TÍTULOS) -->
<!-- ============================================ -->
<div class=\"firmas-container\">
    <!-- FIRMA DEL EMPLEADO (izquierda) - SIN TÍTULO -->
    <div class=\"firma-box firma-left\">
        <div class=\"firma-linea\"></div>
        <div class=\"firma-nombre\">" . strtoupper($persona["nombres"]) . "</div>
        <div class=\"firma-cargo\">EL EMPLEADO</div>
        <div class=\"firma-detalle\">C.I. " . $persona["cedula"] . "</div>
    </div>
    
    <!-- FIRMA DE LA INSTITUCIÓN (derecha) - SIN TÍTULO -->
    <div class=\"firma-box\">
        <div class=\"firma-linea\"></div>
        <div class=\"firma-nombre\">" . strtoupper($admin_nombre) . "</div>
        <div class=\"firma-cargo\">" . $admin_cargo . "</div>
        <div class=\"firma-detalle\">" . $config['institucion_nombre'] . "</div>
    </div>
    <div class=\"clear\"></div>
</div>

<!-- ============================================ -->
<!-- PIE DE PÁGINA -->
<!-- ============================================ -->
<div class=\"footer\">
    Documento generado electrónicamente - Sistema de Inventario TESA v3.0 - Código: $codigo_acta
</div>

</body>
</html>";

try {
    // ============================================
    // CONFIGURACIÓN MPDF - MÁRGENES REDUCIDOS
    // ============================================
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_top' => 17,
        'margin_bottom' => 8,
        'margin_left' => 12,
        'margin_right' => 12,
        'default_font_size' => 9.5,
        'default_font' => 'arial'
    ]);
    
    $mpdf->WriteHTML($html);
    $mpdf->Output("Descargo_" . $persona["cedula"] . ".pdf", "I");
    
} catch (Exception $e) {
    echo "Error al generar PDF: " . $e->getMessage();
}
?>