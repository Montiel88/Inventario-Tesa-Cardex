<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "Paso 1: Inicio<br>";

require_once '../config/database.php';
echo "Paso 2: Database cargado<br>";

require_once '../config/actas_config.php';
echo "Paso 3: Actas config cargado<br>";

echo "Paso 4: Verificando función generarCodigoActa...<br>";
if (!function_exists('generarCodigoActa')) {
    die("ERROR: función generarCodigoActa NO existe");
}
echo "Paso 5: Función OK<br>";

$persona_id = intval($_GET['persona_id'] ?? 0);
echo "Paso 6: ID persona = $persona_id<br>";

if (!$persona_id) {
    die("ERROR: ID de persona no válido");
}

$sql = "SELECT * FROM personas WHERE id = $persona_id";
$result = $conn->query($sql);
if (!$result) {
    die("ERROR en SQL: " . $conn->error);
}

$persona = $result->fetch_assoc();
if (!$persona) {
    die("ERROR: Persona no encontrada");
}

echo "Paso 7: Persona encontrada: " . $persona['nombres'] . "<br>";
echo "Paso 8: Script completado correctamente";
?>