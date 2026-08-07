<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
if (!$usuario_id) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$stmt = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ? AND leida = 0");
$stmt->bind_param('i', $usuario_id);
$ok = $stmt->execute();

echo json_encode([
    'success' => (bool)$ok
]);
$conn->close();
?>
