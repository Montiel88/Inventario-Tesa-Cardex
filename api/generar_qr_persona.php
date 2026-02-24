<?php
require_once '../config/database.php';

// Verificar que se proporcionó un ID de persona
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de persona no válido');
}

$persona_id = intval($_GET['id']);

// Obtener datos de la persona
$sql_persona = "SELECT id, nombres, cedula, cargo FROM personas WHERE id = $persona_id";
$result_persona = $conn->query($sql_persona);

if ($result_persona->num_rows == 0) {
    die('Persona no encontrada');
}

$persona = $result_persona->fetch_assoc();

// Obtener equipos asignados a esta persona
$sql_equipos = "SELECT e.id, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo, e.numero_serie, a.fecha_asignacion
                FROM equipos e
                JOIN asignaciones a ON e.id = a.equipo_id
                WHERE a.persona_id = $persona_id AND a.fecha_devolucion IS NULL
                ORDER BY a.fecha_asignacion DESC";

$result_equipos = $conn->query($sql_equipos);

$equipos = [];
while ($row = $result_equipos->fetch_assoc()) {
    $equipos[] = $row;
}

// Construir la URL que se codificará en el QR
// Esta URL apunta a una página que mostrará los equipos de la persona
$url_base = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$url_destino = $url_base . '/inventario_ti/modules/personas/ver_equipos_qr.php?id=' . $persona_id;

// Redirigir a la librería de generación de QR
// Usaremos la librería phpqrcode que ya tienes en vendor/
require_once '../vendor/phpqrcode/qrlib.php';

// Configurar el QR
$tamaño = 10; // Tamaño del QR (0-10)
$level = QR_ECLEVEL_M; // Nivel de corrección de errores
$margin = 2; // Margen

// Generar QR y enviarlo al navegador
QRcode::png($url_destino, null, $level, $tamaño, $margin);
exit();
?>