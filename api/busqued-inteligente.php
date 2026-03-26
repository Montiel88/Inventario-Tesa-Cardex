<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$q = $_GET['q'] ?? '';
$filtros = isset($_GET['filtros']) ? json_decode($_GET['filtros'], true) : [];

$resultados = [
    'equipos' => [],
    'personas' => [],
    'componentes' => []
];

if (strlen($q) < 2) {
    echo json_encode($resultados);
    exit;
}

$q_like = "%$q%";

// Buscar en Equipos
if (empty($filtros) || in_array('equipos', $filtros)) {
    $stmt = $conn->prepare("SELECT id, codigo_barras, tipo_equipo, marca, modelo, estado FROM equipos WHERE codigo_barras LIKE ? OR tipo_equipo LIKE ? OR marca LIKE ? OR modelo LIKE ? LIMIT 5");
    $stmt->bind_param('ssss', $q_like, $q_like, $q_like, $q_like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $resultados['equipos'][] = $row;
    }
}

// Buscar en Personas
if (empty($filtros) || in_array('personas', $filtros)) {
    $stmt = $conn->prepare("SELECT id, nombres, cedula, correo FROM personas WHERE nombres LIKE ? OR cedula LIKE ? OR correo LIKE ? LIMIT 5");
    $stmt->bind_param('sss', $q_like, $q_like, $q_like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $resultados['personas'][] = $row;
    }
}

// Buscar en Componentes
if (empty($filtros) || in_array('componentes', $filtros)) {
    $stmt = $conn->prepare("SELECT id, nombre, tipo, marca, modelo FROM componentes WHERE nombre LIKE ? OR tipo LIKE ? OR marca LIKE ? OR modelo LIKE ? LIMIT 5");
    $stmt->bind_param('ssss', $q_like, $q_like, $q_like, $q_like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $resultados['componentes'][] = $row;
    }
}

$conn->close();

echo json_encode($resultados);
?>