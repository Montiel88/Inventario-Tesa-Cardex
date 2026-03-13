<?php
header('Content-Type: application/json');
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$notificaciones = [];

// 1. PRÉSTAMOS PRÓXIMOS A VENCER (en los próximos 3 días)
$sql_prestamos = "SELECT p.id, p.fecha_estimada_devolucion, 
                         CONCAT(e.tipo_equipo, ' ', e.marca, ' ', e.modelo) as equipo,
                         per.nombres as persona
                  FROM prestamos_rapidos p
                  JOIN equipos e ON p.equipo_id = e.id
                  JOIN personas per ON p.persona_id = per.id
                  WHERE p.estado = 'activo' 
                    AND p.fecha_estimada_devolucion BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                  ORDER BY p.fecha_estimada_devolucion ASC";

$result = $conn->query($sql_prestamos);
while ($row = $result->fetch_assoc()) {
    $dias = (strtotime($row['fecha_estimada_devolucion']) - strtotime(date('Y-m-d'))) / 86400;
    $texto_dias = $dias == 0 ? 'HOY' : ($dias == 1 ? 'mañana' : "en $dias días");
    
    $notificaciones[] = [
        'tipo' => 'warning',
        'titulo' => '⏳ Préstamo próximo a vencer',
        'mensaje' => "{$row['equipo']} prestado a {$row['persona']} vence $texto_dias.",
        'icono' => 'fa-clock',
        'color' => '#f39c12'
    ];
}

// 2. COMPONENTES EN MAL ESTADO
$sql_componentes = "SELECT c.nombre_componente, c.tipo, e.codigo_barras
                    FROM componentes c
                    JOIN equipos e ON c.equipo_id = e.id
                    WHERE c.estado IN ('Malo', 'Regular', 'Por reemplazar')
                    LIMIT 5";

$result = $conn->query($sql_componentes);
while ($row = $result->fetch_assoc()) {
    $notificaciones[] = [
        'tipo' => 'danger',
        'titulo' => '🔧 Componente en mal estado',
        'mensaje' => "{$row['tipo']} - {$row['nombre_componente']} en equipo {$row['codigo_barras']}.",
        'icono' => 'fa-exclamation-triangle',
        'color' => '#e74c3c'
    ];
}

// 3. MANTENIMIENTOS EN CURSO
$sql_mantenimientos = "SELECT m.descripcion, e.codigo_barras
                       FROM mantenimientos m
                       JOIN equipos e ON m.equipo_id = e.id
                       WHERE m.estado = 'en_proceso'
                       LIMIT 5";

$result = $conn->query($sql_mantenimientos);
while ($row = $result->fetch_assoc()) {
    $notificaciones[] = [
        'tipo' => 'info',
        'titulo' => '🛠️ Mantenimiento en curso',
        'mensaje' => "Equipo {$row['codigo_barras']}: {$row['descripcion']}",
        'icono' => 'fa-tools',
        'color' => '#3498db'
    ];
}

// 4. EQUIPOS SIN UBICACIÓN
$sql_sin_ubicacion = "SELECT codigo_barras, tipo_equipo
                       FROM equipos
                       WHERE (ubicacion_id IS NULL OR ubicacion_id = 0)
                         AND fecha_eliminacion IS NULL
                       LIMIT 5";

$result = $conn->query($sql_sin_ubicacion);
while ($row = $result->fetch_assoc()) {
    $notificaciones[] = [
        'tipo' => 'secondary',
        'titulo' => '📍 Equipo sin ubicación',
        'mensaje' => "{$row['tipo_equipo']} - {$row['codigo_barras']} no tiene ubicación asignada.",
        'icono' => 'fa-map-marker-alt',
        'color' => '#95a5a6'
    ];
}

echo json_encode($notificaciones);
?>