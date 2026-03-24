<?php
header('Content-Type: application/json');
require_once '../config/database.php';
session_start();

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
if (!$usuario_id) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$notificaciones = [];

// 1. Notificaciones de la tabla 'notificaciones'
$sql = "SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY created_at DESC LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notificaciones[] = [
        'id' => $row['id'],
        'tipo' => $row['tipo'],
        'titulo' => $row['titulo'],
        'mensaje' => $row['mensaje'],
        'url' => $row['url'],
        'fecha' => $row['created_at'],
        'icono' => match($row['tipo']) {
            'success' => 'fa-check-circle',
            'error' => 'fa-times-circle',
            'warning' => 'fa-exclamation-triangle',
            default => 'fa-info-circle'
        }
    ];
}

// 2. Correos enviados (como antes)
$sql_correos = "SELECT c.*, p.nombres as destinatario
                FROM correos_enviados c
                LEFT JOIN personas p ON c.persona_id = p.id
                WHERE c.usuario_id = ?
                ORDER BY c.created_at DESC
                LIMIT 5";
$stmt = $conn->prepare($sql_correos);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notificaciones[] = [
        'tipo' => $row['email_enviado'] ? 'success' : 'error',
        'titulo' => $row['email_enviado'] ? '✉️ Correo enviado' : '❌ Error al enviar',
        'mensaje' => "Para: " . ($row['destinatario'] ?? $row['email_destino']) . " - " . $row['asunto'],
        'url' => "/inventario_ti/modules/correos/historial.php?id={$row['id']}",
        'fecha' => $row['created_at'],
        'icono' => $row['email_enviado'] ? 'fa-check-circle' : 'fa-times-circle'
    ];
}

// 3. Alerta de préstamos vencidos, por vencer, componentes dañados, etc. (como antes)
// ... (aquí se mantiene el resto de alertas de inventario que ya tenías)

// Ordenar por fecha (las más recientes primero)
usort($notificaciones, function($a, $b) {
    $fechaA = strtotime($a['fecha']);
    $fechaB = strtotime($b['fecha']);
    return $fechaB - $fechaA;
});

echo json_encode(['notificaciones' => $notificaciones]);
$conn->close();
?>