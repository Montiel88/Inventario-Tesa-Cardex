<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$termino = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

if (strlen($termino) < 2) {
    echo json_encode(['success' => false, 'mensaje' => 'Ingrese al menos 2 caracteres']);
    exit;
}

// Buscar por cédula o por nombre
$sql = "SELECT id, cedula, nombres, correo, cargo, telefono 
        FROM personas 
        WHERE cedula LIKE '%$termino%' 
           OR nombres LIKE '%$termino%' 
        ORDER BY nombres 
        LIMIT 10";

$result = $conn->query($sql);

$personas = [];
while ($row = $result->fetch_assoc()) {
    $personas[] = $row;
}

echo json_encode([
    'success' => true,
    'resultados' => $personas,
    'total' => count($personas)
]);
?>