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
$unread_count = 0;

function tesa_table_exists(mysqli $conn, string $table): bool {
    $t = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '{$t}'");
    return (bool)($r && $r->num_rows > 0);
}

function tesa_add_query_params(string $url, array $params): string {
    $base = $url;
    $frag = '';
    $posHash = strpos($url, '#');
    if ($posHash !== false) {
        $base = substr($url, 0, $posHash);
        $frag = substr($url, $posHash);
    }

    $parts = parse_url($base);
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    foreach ($params as $k => $v) {
        $query[$k] = $v;
    }

    $scheme   = $parts['scheme'] ?? '';
    $host     = $parts['host'] ?? '';
    $port     = isset($parts['port']) ? (':' . $parts['port']) : '';
    $user     = $parts['user'] ?? '';
    $pass     = $parts['pass'] ?? '';
    $pass     = $pass !== '' ? (':' . $pass) : '';
    $auth     = $user !== '' ? ($user . $pass . '@') : '';
    $path     = $parts['path'] ?? '';
    $qs       = http_build_query($query);
    $prefix   = $scheme !== '' ? ($scheme . '://') : '';
    $origin   = $host !== '' ? ($host . $port) : '';

    $rebuilt = $prefix . $auth . $origin . $path;
    if ($qs !== '') {
        $rebuilt .= '?' . $qs;
    }
    return $rebuilt . $frag;
}

function tesa_get_key_from_url(string $url): string {
    $parts = parse_url($url);
    if (empty($parts['query'])) return '';
    $q = [];
    parse_str($parts['query'], $q);
    return (string)($q['k'] ?? '');
}

function tesa_sync_system_notifications(mysqli $conn, int $usuario_id): void {
    if (!tesa_table_exists($conn, 'notificaciones')) return;
    $rol = (int)($_SESSION['user_rol'] ?? $_SESSION['rol_id'] ?? 0);
    if ($rol !== 1) return;

    $activos = [];

    $upsert = function(string $key, string $tipo, string $titulo, string $mensaje, string $url) use ($conn, $usuario_id, &$activos) {
        $urlConKey = tesa_add_query_params($url, ['ref' => 'system', 'k' => $key]);
        $activos[$key] = true;

        $stmt_find = $conn->prepare("SELECT id FROM notificaciones WHERE usuario_id = ? AND url LIKE ? LIMIT 1");
        if (!$stmt_find) return;
        $like = '%k=' . $key . '%';
        $stmt_find->bind_param('is', $usuario_id, $like);
        if (!$stmt_find->execute()) return;
        $res = $stmt_find->get_result();
        $row = $res ? $res->fetch_assoc() : null;

        if ($row && isset($row['id'])) {
            $id = (int)$row['id'];
            $stmt_upd = $conn->prepare("UPDATE notificaciones SET tipo = ?, titulo = ?, mensaje = ?, url = ?, leida = 0, created_at = NOW() WHERE id = ? AND usuario_id = ?");
            if (!$stmt_upd) return;
            $stmt_upd->bind_param('ssssii', $tipo, $titulo, $mensaje, $urlConKey, $id, $usuario_id);
            $stmt_upd->execute();
            return;
        }

        $stmt_ins = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, url, leida, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
        if (!$stmt_ins) return;
        $stmt_ins->bind_param('issss', $usuario_id, $tipo, $titulo, $mensaje, $urlConKey);
        $stmt_ins->execute();
    };

    // Préstamos vencidos (>30 días)
    $sql_vencidos = "SELECT a.id, a.fecha_asignacion,
                            CONCAT(e.tipo_equipo, ' ', e.marca, ' ', e.modelo) as equipo,
                            per.nombres as persona
                     FROM asignaciones a
                     JOIN equipos e ON a.equipo_id = e.id
                     JOIN personas per ON a.persona_id = per.id
                     WHERE a.fecha_devolucion IS NULL
                       AND a.fecha_asignacion < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                     ORDER BY a.fecha_asignacion ASC
                     LIMIT 30";
    $res_v = $conn->query($sql_vencidos);
    if ($res_v) {
        while ($row = $res_v->fetch_assoc()) {
            $asig_id = (int)($row['id'] ?? 0);
            if ($asig_id <= 0) continue;
            $fecha = (string)($row['fecha_asignacion'] ?? '');
            $mensaje = trim(($row['equipo'] ?? '') . ' - ' . ($row['persona'] ?? '')) . ($fecha ? " (desde {$fecha})" : '');
            $upsert("asig_{$asig_id}_vencido", 'danger', '⚠️ Préstamo VENCIDO', $mensaje, '/inventario_ti/modules/asignaciones/listar.php');
        }
    }

    // Préstamos por vencer (25-30 días)
    $sql_pv = "SELECT a.id, a.fecha_asignacion,
                      CONCAT(e.tipo_equipo, ' ', e.marca, ' ', e.modelo) as equipo,
                      per.nombres as persona
               FROM asignaciones a
               JOIN equipos e ON a.equipo_id = e.id
               JOIN personas per ON a.persona_id = per.id
               WHERE a.fecha_devolucion IS NULL
                 AND a.fecha_asignacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 AND a.fecha_asignacion < DATE_SUB(CURDATE(), INTERVAL 25 DAY)
               ORDER BY a.fecha_asignacion ASC
               LIMIT 30";
    $res_pv = $conn->query($sql_pv);
    if ($res_pv) {
        while ($row = $res_pv->fetch_assoc()) {
            $asig_id = (int)($row['id'] ?? 0);
            if ($asig_id <= 0) continue;
            $fecha = (string)($row['fecha_asignacion'] ?? '');
            $mensaje = trim(($row['equipo'] ?? '') . ' - ' . ($row['persona'] ?? '')) . ($fecha ? " (desde {$fecha})" : '');
            $upsert("asig_{$asig_id}_porvencer", 'warning', '⏰ Préstamo por Vencer', $mensaje, '/inventario_ti/modules/asignaciones/listar.php');
        }
    }

    // Componentes dañados
    $sql_comp = "SELECT c.id as componente_id, c.nombre_componente, c.tipo, e.codigo_barras
                 FROM componentes c
                 JOIN equipos e ON c.equipo_id = e.id
                 WHERE c.estado IN ('Malo', 'Regular', 'Por reemplazar')
                 LIMIT 30";
    $res_c = $conn->query($sql_comp);
    if ($res_c) {
        while ($row = $res_c->fetch_assoc()) {
            $cid = (int)($row['componente_id'] ?? 0);
            if ($cid <= 0) continue;
            $mensaje = trim(($row['tipo'] ?? '') . ' - ' . ($row['nombre_componente'] ?? '')) . ' (' . ($row['codigo_barras'] ?? '') . ')';
            $upsert("comp_{$cid}_danado", 'danger', '🔧 Componente Dañado', $mensaje, '/inventario_ti/modules/componentes/listar.php');
        }
    }

    // Equipos sin ubicación (1 notificación resumen)
    $sql_su = "SELECT COUNT(*) as total FROM equipos
               WHERE (ubicacion_id IS NULL OR ubicacion_id = 0 OR ubicacion_id = '')
                 AND (fecha_eliminacion IS NULL OR fecha_eliminacion = '0000-00-00')";
    $res_su = $conn->query($sql_su);
    $total_su = 0;
    if ($res_su) {
        $row = $res_su->fetch_assoc();
        $total_su = (int)($row['total'] ?? 0);
    }
    if ($total_su > 0) {
        $upsert('equipos_sin_ubicacion', 'info', '📍 Equipos sin Ubicación', "{$total_su} equipos requieren asignación", '/inventario_ti/modules/equipos/sin_ubicacion.php');
    }

    // Marcar como resueltas (leida=1) las notificaciones del sistema que ya no aplican
    $stmt_all = $conn->prepare("SELECT id, url FROM notificaciones WHERE usuario_id = ? AND leida = 0 AND url LIKE '%ref=system%'");
    if ($stmt_all) {
        $stmt_all->bind_param('i', $usuario_id);
        if ($stmt_all->execute()) {
            $res_all = $stmt_all->get_result();
            if ($res_all) {
                while ($r = $res_all->fetch_assoc()) {
                    $key = tesa_get_key_from_url((string)($r['url'] ?? ''));
                    if ($key === '') continue;
                    if (isset($activos[$key])) continue;
                    $id = (int)($r['id'] ?? 0);
                    if ($id <= 0) continue;
                    $stmt_res = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
                    if (!$stmt_res) continue;
                    $stmt_res->bind_param('ii', $id, $usuario_id);
                    $stmt_res->execute();
                }
            }
        }
    }
}

// 1) Sincroniza alertas del sistema -> notificaciones (pendientes) y luego lista desde la tabla
if (tesa_table_exists($conn, 'notificaciones')) {
    tesa_sync_system_notifications($conn, $usuario_id);

    $sql_unread = "SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leida = 0";
    $stmt_unread = $conn->prepare($sql_unread);
    if ($stmt_unread) {
        $stmt_unread->bind_param('i', $usuario_id);
        if ($stmt_unread->execute()) {
            $result_unread = $stmt_unread->get_result();
            $row_unread = $result_unread ? $result_unread->fetch_assoc() : null;
            $unread_count = (int)($row_unread['total'] ?? 0);
        }
    }

    $sql = "SELECT * FROM notificaciones WHERE usuario_id = ? AND leida = 0 ORDER BY created_at DESC LIMIT 20";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $usuario_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $notificaciones[] = [
                        'id' => $row['id'],
                        'tipo' => $row['tipo'],
                        'titulo' => $row['titulo'],
                        'mensaje' => $row['mensaje'],
                        'url' => $row['url'],
                        'fecha' => $row['created_at'],
                        'leida' => (int)($row['leida'] ?? 0),
                        'icono' => match($row['tipo']) {
                            'success' => 'fa-check-circle',
                            'error' => 'fa-times-circle',
                            'warning' => 'fa-exclamation-triangle',
                            default => 'fa-info-circle'
                        }
                    ];
                }
            }
        }
    }
}

// 2. Correos enviados (últimos 5)
if (tesa_table_exists($conn, 'correos_enviados')) {
    $sql_correos = "SELECT c.*, p.nombres as destinatario
                    FROM correos_enviados c
                    LEFT JOIN personas p ON c.persona_id = p.id
                    WHERE c.usuario_id = ?
                    ORDER BY c.created_at DESC
                    LIMIT 5";
    $stmt = $conn->prepare($sql_correos);
    if ($stmt) {
        $stmt->bind_param('i', $usuario_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result) {
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
            }
        }
    }
}

// Ordenar por fecha (las más recientes primero)
usort($notificaciones, function($a, $b) {
    $fechaA = strtotime($a['fecha'] ?? '1970-01-01');
    $fechaB = strtotime($b['fecha'] ?? '1970-01-01');
    return $fechaB - $fechaA;
});

echo json_encode([
    'unread_count' => $unread_count,
    'notificaciones' => $notificaciones
]);
$conn->close();
?>
