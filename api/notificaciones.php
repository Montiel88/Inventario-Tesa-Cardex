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

// 2. Correos enviados (últimos 5)
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

// 3. Préstamos vencidos (más de 30 días)
$sql_vencidos = "SELECT a.id, a.fecha_asignacion, 
                        CONCAT(e.tipo_equipo, ' ', e.marca, ' ', e.modelo) as equipo,
                        per.nombres as persona,
                        e.id as equipo_id
                 FROM asignaciones a
                 JOIN equipos e ON a.equipo_id = e.id
                 JOIN personas per ON a.persona_id = per.id
                 WHERE a.fecha_devolucion IS NULL
                   AND a.fecha_asignacion < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 ORDER BY a.fecha_asignacion ASC
                 LIMIT 3";
$result = $conn->query($sql_vencidos);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dias_vencido = floor((strtotime(date('Y-m-d')) - strtotime($row['fecha_asignacion'])) / 86400);
        $notificaciones[] = [
            'tipo' => 'danger',
            'titulo' => '⚠️ Préstamo VENCIDO',
            'mensaje' => "{$row['equipo']} - {$row['persona']} ({$dias_vencido} días)",
            'icono' => 'fa-exclamation-circle',
            'url' => "/inventario_ti/modules/asignaciones/listar.php",
            'url_label' => 'Gestionar',
            'equipo_id' => $row['equipo_id']
        ];
    }
}

// 4. Préstamos por vencer (25-30 días)
$sql_por_vencer = "SELECT a.id, a.fecha_asignacion, 
                          CONCAT(e.tipo_equipo, ' ', e.marca, ' ', e.modelo) as equipo,
                          per.nombres as persona,
                          e.id as equipo_id
                   FROM asignaciones a
                   JOIN equipos e ON a.equipo_id = e.id
                   JOIN personas per ON a.persona_id = per.id
                   WHERE a.fecha_devolucion IS NULL
                     AND a.fecha_asignacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                     AND a.fecha_asignacion < DATE_SUB(CURDATE(), INTERVAL 25 DAY)
                   ORDER BY a.fecha_asignacion ASC
                   LIMIT 3";
$result = $conn->query($sql_por_vencer);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dias_restantes = 30 - floor((strtotime(date('Y-m-d')) - strtotime($row['fecha_asignacion'])) / 86400);
        $notificaciones[] = [
            'tipo' => 'warning',
            'titulo' => '⏰ Préstamo por Vencer',
            'mensaje' => "{$row['equipo']} - {$row['persona']} (vence en {$dias_restantes} días)",
            'icono' => 'fa-clock',
            'url' => "/inventario_ti/modules/asignaciones/listar.php",
            'url_label' => 'Ver',
            'equipo_id' => $row['equipo_id']
        ];
    }
}

// 5. Componentes en mal estado
$sql_componentes = "SELECT c.nombre_componente, c.tipo, e.codigo_barras, c.id as componente_id, c.equipo_id
                    FROM componentes c
                    JOIN equipos e ON c.equipo_id = e.id
                    WHERE c.estado IN ('Malo', 'Regular', 'Por reemplazar')
                    LIMIT 3";
$result = $conn->query($sql_componentes);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notificaciones[] = [
            'tipo' => 'danger',
            'titulo' => '🔧 Componente Dañado',
            'mensaje' => "{$row['tipo']} - {$row['nombre_componente']} ({$row['codigo_barras']})",
            'icono' => 'fa-exclamation-triangle',
            'url' => "/inventario_ti/modules/componentes/listar.php",
            'url_label' => 'Ver',
            'equipo_id' => $row['equipo_id'],
            'componente_id' => $row['componente_id']
        ];
    }
}

// 6. Equipos sin ubicación (cantidad)
$sql_sin_ubicacion = "SELECT COUNT(*) as total FROM equipos
                       WHERE (ubicacion_id IS NULL OR ubicacion_id = 0 OR ubicacion_id = '')
                         AND (fecha_eliminacion IS NULL OR fecha_eliminacion = '0000-00-00')";
$result = $conn->query($sql_sin_ubicacion);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['total'] > 0) {
        $notificaciones[] = [
            'tipo' => 'info',
            'titulo' => '📍 Equipos sin Ubicación',
            'mensaje' => "{$row['total']} equipos requieren asignación",
            'icono' => 'fa-map-marker-alt',
            'url' => "/inventario_ti/modules/equipos/sin_ubicacion.php",
            'url_label' => "Asignar ({$row['total']})",
            'total' => $row['total']
        ];
    }
}

// Ordenar por fecha (las más recientes primero)
usort($notificaciones, function($a, $b) {
    $fechaA = strtotime($a['fecha'] ?? '1970-01-01');
    $fechaB = strtotime($b['fecha'] ?? '1970-01-01');
    return $fechaB - $fechaA;
});

echo json_encode(['notificaciones' => $notificaciones]);
$conn->close();
?>