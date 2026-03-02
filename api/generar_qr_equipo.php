<?php
require_once '../config/database.php';

// Verificar que se proporcionó un ID de equipo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de equipo no válido');
}

$equipo_id = intval($_GET['id']);

// Obtener datos del equipo
$sql_equipo = "SELECT e.*, 
                      CASE WHEN a.persona_id IS NOT NULL THEN p.nombres ELSE 'DISPONIBLE' END as asignado_a,
                      a.fecha_asignacion
               FROM equipos e
               LEFT JOIN asignaciones a ON e.id = a.equipo_id AND a.fecha_devolucion IS NULL
               LEFT JOIN personas p ON a.persona_id = p.id
               WHERE e.id = $equipo_id";

$result_equipo = $conn->query($sql_equipo);

if ($result_equipo->num_rows == 0) {
    die('Equipo no encontrado');
}

$equipo = $result_equipo->fetch_assoc();

// Construir los datos que irán en el QR (formato JSON para más información)
$datos_qr = [
    'id' => $equipo['id'],
    'codigo' => $equipo['codigo_barras'],
    'tipo' => $equipo['tipo_equipo'],
    'marca' => $equipo['marca'],
    'modelo' => $equipo['modelo'],
    'serie' => $equipo['numero_serie'],
    'estado' => $equipo['asignado_a'] != 'DISPONIBLE' ? 'PRESTADO' : 'DISPONIBLE',
    'asignado_a' => $equipo['asignado_a'],
    'fecha' => $equipo['fecha_asignacion'] ?? null
];

// Convertir a JSON
$json_datos = json_encode($datos_qr, JSON_UNESCAPED_UNICODE);

// Construir URL alternativa (para compatibilidad)
$url_base = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$url_destino = $url_base . '/inventario_ti/modules/escaneo/verificar.php?codigo=' . $equipo['codigo_barras'];

// Redirigir a la librería de generación de QR
require_once '../vendor/phpqrcode/qrlib.php';

// Configurar el QR
$tamaño = 10;
$level = QR_ECLEVEL_M;
$margin = 2;

// Generar QR con los datos JSON
// NOTA: qrlib.php no soporta directamente enviar al navegador, necesitamos guardar temporalmente

// Crear directorio temporal si no existe
$temp_dir = '../temp/';
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

$temp_file = $temp_dir . 'qr_' . $equipo_id . '.png';

// Generar QR con la URL (más compatible)
QRcode::png($url_destino, $temp_file, $level, $tamaño, $margin);

// Enviar el archivo al navegador
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="qr_equipo_' . $equipo['codigo_barras'] . '.png"');
readfile($temp_file);

// Opcional: eliminar el archivo temporal después de enviarlo
unlink($temp_file);
exit();
?>