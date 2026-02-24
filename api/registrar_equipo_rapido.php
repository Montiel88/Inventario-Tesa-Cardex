<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$datos = json_decode(file_get_contents('php://input'), true);

if (!$datos) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos no válidos']);
    exit;
}

$codigo_barras = $conn->real_escape_string($datos['codigo_barras']);
$tipo_equipo = $conn->real_escape_string($datos['tipo_equipo']);
$marca = $conn->real_escape_string($datos['marca'] ?? '');
$modelo = $conn->real_escape_string($datos['modelo'] ?? '');
$serie = $conn->real_escape_string($datos['numero_serie'] ?? '');
$especificaciones = $conn->real_escape_string($datos['especificaciones'] ?? '');

// Verificar si ya existe
$check = $conn->query("SELECT id FROM equipos WHERE codigo_barras = '$codigo_barras'");
if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'mensaje' => 'Ya existe un equipo con ese código']);
    exit;
}

$sql = "INSERT INTO equipos (codigo_barras, tipo_equipo, marca, modelo, numero_serie, especificaciones) 
        VALUES ('$codigo_barras', '$tipo_equipo', '$marca', '$modelo', '$serie', '$especificaciones')";

if ($conn->query($sql)) {
    echo json_encode([
        'success' => true,
        'mensaje' => 'Equipo registrado correctamente',
        'id' => $conn->insert_id
    ]);
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $conn->error]);
}
?>