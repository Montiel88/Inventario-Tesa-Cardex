<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function toLower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function hasAnyKeyword(string $text, array $keywords): bool
{
    foreach ($keywords as $keyword) {
        if ($keyword !== '' && str_contains($text, $keyword)) {
            return true;
        }
    }
    return false;
}

function dbScalar(\mysqli $conn, string $sql): string
{
    $result = $conn->query($sql);
    if (!$result) {
        return 'N/D';
    }
    $row = $result->fetch_row();
    $result->free();
    return isset($row[0]) ? (string)$row[0] : 'N/D';
}

function dbRows(\mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
    return $rows;
}

function dbTableExists(\mysqli $conn, string $table): bool
{
    $safeTable = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");
    if (!$result) {
        return false;
    }
    $exists = $result->num_rows > 0;
    $result->free();
    return $exists;
}

function formatRowsForContext(array $rows, array $fields): string
{
    if (count($rows) === 0) {
        return '- Sin datos';
    }
    $lines = [];
    foreach ($rows as $row) {
        $parts = [];
        foreach ($fields as $field) {
            $label = $field[0];
            $key = $field[1];
            $value = isset($row[$key]) && $row[$key] !== '' ? (string)$row[$key] : 'N/D';
            $parts[] = $label . ': ' . $value;
        }
        $lines[] = '- ' . implode(' | ', $parts);
    }
    return implode("\n", $lines);
}

function buildFullDatabaseOverview(\mysqli $conn): string
{
    $sections = [];
    $sections[] = 'RESUMEN_GLOBAL_BD:';

    $tableResult = $conn->query('SHOW TABLES');
    $tables = [];
    if ($tableResult) {
        while ($row = $tableResult->fetch_row()) {
            if (!isset($row[0])) {
                continue;
            }
            $tables[] = (string)$row[0];
        }
        $tableResult->free();
    }

    if (count($tables) > 0) {
        $sections[] = 'TABLAS_EN_BD: ' . implode(', ', $tables);
    }

    $counts = [];
    foreach ($tables as $table) {
        $safeTable = '`' . str_replace('`', '``', $table) . '`';
        $cnt = dbScalar($conn, "SELECT COUNT(*) FROM {$safeTable}");
        $counts[] = "{$table}={$cnt}";
    }
    if (count($counts) > 0) {
        $sections[] = 'CONTEOS_TABLAS: ' . implode(' | ', $counts);
    }

    $schemaTargets = ['equipos', 'personas', 'componentes', 'asignaciones', 'movimientos', 'movimientos_componentes', 'actas', 'mantenimientos', 'prestamos_rapidos'];
    foreach ($schemaTargets as $t) {
        if (!dbTableExists($conn, $t)) {
            continue;
        }
        $schemaRows = dbRows($conn, "SHOW COLUMNS FROM `{$t}`");
        $cols = [];
        foreach ($schemaRows as $c) {
            $field = (string)($c['Field'] ?? '');
            $type = (string)($c['Type'] ?? '');
            if ($field !== '') {
                $cols[] = "{$field}:{$type}";
            }
        }
        if (count($cols) > 0) {
            $sections[] = "ESQUEMA_{$t}: " . implode(', ', $cols);
        }
    }

    if (dbTableExists($conn, 'equipos')) {
        $lastEquipos = dbRows($conn, "SELECT id, codigo_barras, tipo_equipo, marca, modelo, estado, fecha_registro FROM equipos ORDER BY id DESC LIMIT 8");
        $sections[] = 'ULTIMOS_EQUIPOS:';
        $sections[] = formatRowsForContext($lastEquipos, [['id', 'id'], ['codigo', 'codigo_barras'], ['tipo', 'tipo_equipo'], ['marca', 'marca'], ['modelo', 'modelo'], ['estado', 'estado'], ['fecha', 'fecha_registro']]);
    }
    if (dbTableExists($conn, 'componentes')) {
        $lastComp = dbRows($conn, "SELECT id, nombre_componente, tipo, marca, modelo, estado, created_at FROM componentes ORDER BY id DESC LIMIT 8");
        $sections[] = 'ULTIMOS_COMPONENTES:';
        $sections[] = formatRowsForContext($lastComp, [['id', 'id'], ['nombre', 'nombre_componente'], ['tipo', 'tipo'], ['marca', 'marca'], ['modelo', 'modelo'], ['estado', 'estado'], ['fecha', 'created_at']]);
    }
    if (dbTableExists($conn, 'personas')) {
        $lastPersonas = dbRows($conn, "SELECT id, nombres, cedula, cargo, correo, fecha_registro FROM personas ORDER BY id DESC LIMIT 8");
        $sections[] = 'ULTIMAS_PERSONAS:';
        $sections[] = formatRowsForContext($lastPersonas, [['id', 'id'], ['nombres', 'nombres'], ['cedula', 'cedula'], ['cargo', 'cargo'], ['correo', 'correo'], ['fecha', 'fecha_registro']]);
    }
    if (dbTableExists($conn, 'asignaciones')) {
        $lastAsig = dbRows($conn, "SELECT id, equipo_id, persona_id, fecha_asignacion, fecha_devolucion FROM asignaciones ORDER BY id DESC LIMIT 8");
        $sections[] = 'ULTIMAS_ASIGNACIONES:';
        $sections[] = formatRowsForContext($lastAsig, [['id', 'id'], ['equipo_id', 'equipo_id'], ['persona_id', 'persona_id'], ['asignacion', 'fecha_asignacion'], ['devolucion', 'fecha_devolucion']]);
    }
    if (dbTableExists($conn, 'movimientos')) {
        $lastMov = dbRows($conn, "SELECT id, equipo_id, persona_id, tipo_movimiento, fecha_movimiento FROM movimientos ORDER BY id DESC LIMIT 8");
        $sections[] = 'ULTIMOS_MOVIMIENTOS_EQUIPOS:';
        $sections[] = formatRowsForContext($lastMov, [['id', 'id'], ['equipo_id', 'equipo_id'], ['persona_id', 'persona_id'], ['tipo', 'tipo_movimiento'], ['fecha', 'fecha_movimiento']]);
    }
    if (dbTableExists($conn, 'movimientos_componentes')) {
        $lastMovComp = dbRows($conn, "SELECT id, componente_id, persona_id, tipo_movimiento, fecha_movimiento FROM movimientos_componentes ORDER BY id DESC LIMIT 8");
        $sections[] = 'ULTIMOS_MOVIMIENTOS_COMPONENTES:';
        $sections[] = formatRowsForContext($lastMovComp, [['id', 'id'], ['componente_id', 'componente_id'], ['persona_id', 'persona_id'], ['tipo', 'tipo_movimiento'], ['fecha', 'fecha_movimiento']]);
    }
    if (dbTableExists($conn, 'actas')) {
        $lastActas = dbRows($conn, "SELECT id, codigo_acta, tipo_acta, persona_id, fecha_generacion FROM actas ORDER BY id DESC LIMIT 8");
        $sections[] = 'ULTIMAS_ACTAS:';
        $sections[] = formatRowsForContext($lastActas, [['id', 'id'], ['codigo', 'codigo_acta'], ['tipo', 'tipo_acta'], ['persona_id', 'persona_id'], ['fecha', 'fecha_generacion']]);
    }

    return implode("\n", $sections);
}

function buildDynamicDbContext(string $message): string
{
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
    $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
    $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'inventario_ti';
    $port = (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306);

    $conn = @new \mysqli($host, $user, $pass, $name, $port);
    if ($conn->connect_error) {
        return "BD_STATUS: ERROR\nDETALLE: No se pudo conectar a MySQL ({$conn->connect_error}).";
    }
    $conn->set_charset('utf8');

    $lowerMessage = toLower($message);
    $wantsSummary = hasAnyKeyword($lowerMessage, ['resumen', 'dashboard', 'general', 'estado actual', 'estadistica', 'estadísticas']);
    $wantsEquipos = hasAnyKeyword($lowerMessage, ['equipo', 'equipos', 'inventario', 'disponible', 'prestado', 'asignado', 'marca', 'modelo', 'codigo', 'código']);
    $wantsPersonas = hasAnyKeyword($lowerMessage, ['persona', 'personas', 'usuario', 'usuarios', 'cedula', 'cédula', 'cargo', 'correo']);
    $wantsPrestamos = hasAnyKeyword($lowerMessage, ['prestamo', 'préstamo', 'prestamos', 'préstamos', 'asignacion', 'asignación', 'devolucion', 'devolución']);
    $wantsMovimientos = hasAnyKeyword($lowerMessage, ['movimiento', 'movimientos', 'historial', 'ultima', 'última', 'reciente', 'recientes']);

    $context = [];
    $context[] = 'BD_STATUS: OK';
    $context[] = 'GENERADO_EN: ' . date('Y-m-d H:i:s');

    if (dbTableExists($conn, 'equipos')) {
        $context[] = 'TOTAL_EQUIPOS: ' . dbScalar($conn, "SELECT COUNT(*) FROM equipos WHERE fecha_eliminacion IS NULL OR fecha_eliminacion = '0000-00-00 00:00:00'");
    }
    if (dbTableExists($conn, 'personas')) {
        $context[] = 'TOTAL_PERSONAS: ' . dbScalar($conn, 'SELECT COUNT(*) FROM personas');
    }
    if (dbTableExists($conn, 'asignaciones')) {
        $context[] = 'PRESTAMOS_ACTIVOS_ASIGNACIONES: ' . dbScalar($conn, 'SELECT COUNT(*) FROM asignaciones WHERE fecha_devolucion IS NULL');
    }
    if (dbTableExists($conn, 'prestamos_rapidos')) {
        $context[] = 'PRESTAMOS_RAPIDOS_ACTIVOS: ' . dbScalar($conn, "SELECT COUNT(*) FROM prestamos_rapidos WHERE estado IN ('activo','ACTIVO')");
    }

    if ($wantsSummary || $wantsEquipos) {
        if (dbTableExists($conn, 'equipos')) {
            $equiposEstado = dbRows($conn, "SELECT COALESCE(NULLIF(estado,''),'(sin estado)') AS estado, COUNT(*) AS total FROM equipos WHERE fecha_eliminacion IS NULL OR fecha_eliminacion = '0000-00-00 00:00:00' GROUP BY COALESCE(NULLIF(estado,''),'(sin estado)') ORDER BY total DESC");
            $context[] = 'EQUIPOS_POR_ESTADO:';
            $context[] = formatRowsForContext($equiposEstado, [['estado', 'estado'], ['total', 'total']]);

            $equiposTipo = dbRows($conn, "SELECT COALESCE(NULLIF(tipo_equipo,''),'(sin tipo)') AS tipo, COUNT(*) AS total FROM equipos WHERE fecha_eliminacion IS NULL OR fecha_eliminacion = '0000-00-00 00:00:00' GROUP BY COALESCE(NULLIF(tipo_equipo,''),'(sin tipo)') ORDER BY total DESC LIMIT 8");
            $context[] = 'TOP_TIPOS_EQUIPO:';
            $context[] = formatRowsForContext($equiposTipo, [['tipo', 'tipo'], ['total', 'total']]);
        }
    }

    if ($wantsPrestamos) {
        if (dbTableExists($conn, 'asignaciones') && dbTableExists($conn, 'equipos') && dbTableExists($conn, 'personas')) {
            $prestados = dbRows($conn, "SELECT e.codigo_barras, e.tipo_equipo, e.marca, e.modelo, p.nombres AS persona, a.fecha_asignacion FROM asignaciones a JOIN equipos e ON e.id = a.equipo_id JOIN personas p ON p.id = a.persona_id WHERE a.fecha_devolucion IS NULL ORDER BY a.fecha_asignacion DESC LIMIT 10");
            $context[] = 'PRESTAMOS_ACTIVOS_DETALLE:';
            $context[] = formatRowsForContext($prestados, [['codigo', 'codigo_barras'], ['equipo', 'tipo_equipo'], ['marca', 'marca'], ['modelo', 'modelo'], ['persona', 'persona'], ['fecha', 'fecha_asignacion']]);
        }
    }

    if ($wantsMovimientos && dbTableExists($conn, 'movimientos')) {
        $movs = dbRows($conn, "SELECT m.tipo_movimiento, m.fecha_movimiento, e.codigo_barras, e.tipo_equipo, p.nombres AS persona FROM movimientos m LEFT JOIN equipos e ON e.id = m.equipo_id LEFT JOIN personas p ON p.id = m.persona_id ORDER BY m.fecha_movimiento DESC LIMIT 12");
        $context[] = 'MOVIMIENTOS_RECIENTES:';
        $context[] = formatRowsForContext($movs, [['tipo', 'tipo_movimiento'], ['fecha', 'fecha_movimiento'], ['codigo', 'codigo_barras'], ['equipo', 'tipo_equipo'], ['persona', 'persona']]);
    }

    if ($wantsPersonas && dbTableExists($conn, 'personas')) {
        $needle = trim($message);
        if ($needle !== '') {
            $safeNeedle = $conn->real_escape_string($needle);
            $foundPersons = dbRows($conn, "SELECT id, nombres, cedula, cargo, correo FROM personas WHERE nombres LIKE '%{$safeNeedle}%' OR cedula LIKE '%{$safeNeedle}%' ORDER BY nombres LIMIT 8");
            if (count($foundPersons) > 0) {
                $context[] = 'PERSONAS_RELACIONADAS:';
                $context[] = formatRowsForContext($foundPersons, [['id', 'id'], ['nombre', 'nombres'], ['cedula', 'cedula'], ['cargo', 'cargo'], ['correo', 'correo']]);
            }
        }
    }

    if ($wantsEquipos && dbTableExists($conn, 'equipos')) {
        $safeNeedle = $conn->real_escape_string(trim($message));
        if ($safeNeedle !== '') {
            $foundEquipos = dbRows($conn, "SELECT id, codigo_barras, tipo_equipo, marca, modelo, estado FROM equipos WHERE codigo_barras LIKE '%{$safeNeedle}%' OR tipo_equipo LIKE '%{$safeNeedle}%' OR marca LIKE '%{$safeNeedle}%' OR modelo LIKE '%{$safeNeedle}%' ORDER BY id DESC LIMIT 10");
            if (count($foundEquipos) > 0) {
                $context[] = 'EQUIPOS_RELACIONADOS:';
                $context[] = formatRowsForContext($foundEquipos, [['id', 'id'], ['codigo', 'codigo_barras'], ['tipo', 'tipo_equipo'], ['marca', 'marca'], ['modelo', 'modelo'], ['estado', 'estado']]);
            }
        }
    }

    $context[] = buildFullDatabaseOverview($conn);

    $conn->close();
    return implode("\n", $context);
}

function connectAppDb(): \mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
    $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
    $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'inventario_ti';
    $port = (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306);

    $conn = @new \mysqli($host, $user, $pass, $name, $port);
    if ($conn->connect_error) {
        throw new \RuntimeException('No se pudo conectar con la base de datos: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8');
    return $conn;
}

function parseCommandParams(string $message): array
{
    $params = [];
    if (preg_match_all('/([a-zA-Z_]+)\s*[:=]\s*("([^"]*)"|\'([^\']*)\'|([^\s,]+))/u', $message, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $key = toLower(trim((string)$match[1]));
            $value = $match[3] !== '' ? $match[3] : ($match[4] !== '' ? $match[4] : $match[5]);
            $params[$key] = trim((string)$value);
        }
    }
    return $params;
}

function parseStatus(string $value): string
{
    $u = strtoupper(trim($value));
    $map = [
        'BUENO' => 'BUENO',
        'REGULAR' => 'REGULAR',
        'MALO' => 'MALO',
        'DANADO' => 'DAÑADO',
        'DAÑADO' => 'DAÑADO',
    ];
    return $map[$u] ?? 'BUENO';
}

function isAdminUser(): bool
{
    $rol = $_SESSION['user_rol'] ?? '';
    return $rol === 'admin' || $rol === 1 || $rol === '1';
}

function guestWriteDeniedMessage(): string
{
    return 'Lo siento, te recuerdo que estas en modo invitado. No tengo acceso a esas funciones, pero si puedo ayudarte a consultar muchas mas cosas.';
}

function extractCedulaFromText(string $message): ?string
{
    if (preg_match('/\b(\d{10})\b/', $message, $m)) {
        return $m[1];
    }
    return null;
}

function extractCodigoFromText(string $message): ?string
{
    if (preg_match('/\b([A-Za-z]{2,5}-\d{3,10})\b/', $message, $m)) {
        return strtoupper($m[1]);
    }
    return null;
}

function findPersonaByHint(\mysqli $conn, array $params, string $message): ?array
{
    $personaId = isset($params['persona_id']) ? (int)$params['persona_id'] : null;
    $cedula = $params['cedula'] ?? extractCedulaFromText($message);
    if ($cedula !== null || $personaId !== null) {
        return findPersonaByRef($conn, $personaId, $cedula);
    }

    $nameHint = trim((string)($params['nombres'] ?? ($params['nombre'] ?? '')));
    if ($nameHint === '') {
        return null;
    }
    $like = '%' . $nameHint . '%';
    $stmt = $conn->prepare('SELECT id, nombres, cedula, cargo FROM personas WHERE nombres LIKE ? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function isWriteAction(string $action): bool
{
    return in_array($action, [
        'crear_prestamo',
        'registrar_devolucion',
        'crear_equipo',
        'crear_persona',
        'dar_baja_equipo',
        'crear_componente',
        'asignar_componente',
        'devolver_componente',
    ], true);
}

function isCapabilityQuestion(string $message): bool
{
    $m = toLower($message);
    return str_contains($m, '?')
        || str_contains($m, 'puedes')
        || str_contains($m, 'podrias')
        || str_contains($m, 'podrías')
        || str_contains($m, 'se puede');
}

function detectEquipoTipoFromText(string $message): ?string
{
    $m = toLower($message);
    $map = [
        'laptop' => 'Laptop',
        'mouse' => 'Mouse',
        'teclado' => 'Teclado',
        'monitor' => 'Monitor',
        'impresora' => 'Impresora',
        'proyector' => 'Proyector',
        'tablet' => 'Tablet',
        'parlantes' => 'Parlantes',
        'camara' => 'Cámara',
        'cámara' => 'Cámara',
    ];
    foreach ($map as $needle => $tipo) {
        if (str_contains($m, $needle)) {
            return $tipo;
        }
    }
    return null;
}

function extractWordAfterLabel(string $message, array $labels): ?string
{
    foreach ($labels as $label) {
        $pattern = '/\b' . preg_quote($label, '/') . '\s+([^\s,.;]+)/iu';
        if (preg_match($pattern, $message, $m)) {
            return trim((string)$m[1]);
        }
    }
    return null;
}

function mergeAutoParams(string $action, array $params, string $message): array
{
    if (!isset($params['codigo']) && !isset($params['codigo_barras'])) {
        $codigo = extractCodigoFromText($message);
        if ($codigo) {
            $params['codigo'] = $codigo;
        }
    }
    if (!isset($params['cedula'])) {
        $cedula = extractCedulaFromText($message);
        if ($cedula) {
            $params['cedula'] = $cedula;
        }
    }

    if ($action === 'crear_equipo') {
        if (!isset($params['tipo']) && !isset($params['tipo_equipo'])) {
            $tipo = detectEquipoTipoFromText($message);
            if ($tipo) {
                $params['tipo'] = $tipo;
            }
        }
        if (!isset($params['marca'])) {
            $marca = extractWordAfterLabel($message, ['marca']);
            if ($marca) {
                $params['marca'] = $marca;
            }
        }
        if (!isset($params['modelo'])) {
            $modelo = extractWordAfterLabel($message, ['modelo']);
            if ($modelo) {
                $params['modelo'] = $modelo;
            }
        }
    }

    if ($action === 'crear_persona') {
        if (!isset($params['correo']) && preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $message, $m)) {
            $params['correo'] = $m[0];
        }
        if (!isset($params['nombres']) && preg_match('/(?:nombre|nombres)\s*[:=]?\s*("([^"]+)"|\'([^\']+)\'|([A-Za-zÁÉÍÓÚÑáéíóúñ ]{4,}))/u', $message, $m)) {
            $params['nombres'] = trim((string)($m[2] ?: ($m[3] ?: $m[4])));
        }
    }

    return $params;
}

function findEquipoByRef(\mysqli $conn, ?int $equipoId, ?string $codigo): ?array
{
    if ($equipoId !== null) {
        $stmt = $conn->prepare("SELECT id, codigo_barras, tipo_equipo, marca, modelo, estado FROM equipos WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $equipoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    if ($codigo !== null && $codigo !== '') {
        $stmt = $conn->prepare("SELECT id, codigo_barras, tipo_equipo, marca, modelo, estado FROM equipos WHERE codigo_barras = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    return null;
}

function findPersonaByRef(\mysqli $conn, ?int $personaId, ?string $cedula): ?array
{
    if ($personaId !== null) {
        $stmt = $conn->prepare("SELECT id, nombres, cedula, cargo FROM personas WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $personaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    if ($cedula !== null && $cedula !== '') {
        $stmt = $conn->prepare("SELECT id, nombres, cedula, cargo FROM personas WHERE cedula = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $cedula);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    return null;
}

function detectAction(string $message): ?string
{
    $m = toLower($message);

    if (preg_match('/\b(crear|registrar|hacer)\s+(un\s+)?(prestamo|pr[eé]stamo)\b/u', $m) || preg_match('/\bprestar\b/u', $m)) {
        return 'crear_prestamo';
    }
    if (preg_match('/\b(registrar|hacer)?\s*(una\s+)?(devolucion|devoluci[oó]n)\b/u', $m) || preg_match('/\bdevolver\b/u', $m)) {
        return 'registrar_devolucion';
    }
    if (preg_match('/\b(crear|agregar|registrar)\s+(un\s+)?equipo\b/u', $m)) {
        return 'crear_equipo';
    }
    if (preg_match('/\b(crear|agregar|registrar)\s+(una\s+)?persona\b/u', $m)) {
        return 'crear_persona';
    }
    if (preg_match('/\b(baja|dar de baja)\b/u', $m)) {
        return 'dar_baja_equipo';
    }
    if (preg_match('/\b(crear|agregar|registrar)\s+(un\s+)?componente\b/u', $m)) {
        return 'crear_componente';
    }
    if (preg_match('/\b(asignar)\s+componente\b/u', $m)) {
        return 'asignar_componente';
    }
    if (preg_match('/\b(devolver)\s+componente\b/u', $m) || preg_match('/\bdevolucion\s+componente\b/u', $m)) {
        return 'devolver_componente';
    }

    return null;
}

function executeAction(string $message): ?string
{
    $action = detectAction($message);
    if ($action === null) {
        return null;
    }

    $params = mergeAutoParams($action, parseCommandParams($message), $message);
    $conn = connectAppDb();
    $userId = (int)($_SESSION['user_id'] ?? 0);

    $inTransaction = false;
    try {
        if (isWriteAction($action) && !isAdminUser()) {
            return guestWriteDeniedMessage();
        }

        if ($action === 'crear_prestamo') {
            $equipoId = isset($params['equipo_id']) ? (int)$params['equipo_id'] : null;
            $codigo = $params['codigo'] ?? ($params['codigo_barras'] ?? null);
            $personaId = isset($params['persona_id']) ? (int)$params['persona_id'] : null;
            $cedula = $params['cedula'] ?? null;
            $obs = trim((string)($params['observacion'] ?? ($params['obs'] ?? 'Préstamo registrado desde asistente')));

            if (($equipoId === null && !$codigo) || ($personaId === null && !$cedula)) {
                if (isCapabilityQuestion($message)) {
                    return 'Si, puedo crear prestamos. Enviame: crear prestamo codigo=PRO-000001 cedula=0102030405 observacion="..."';
                }
                return 'Para crear el préstamo usa: crear prestamo codigo=PRO-000001 cedula=0102030405 observacion="..."';
            }

            $equipo = findEquipoByRef($conn, $equipoId, $codigo);
            if (!$equipo) {
                return 'No encontré el equipo solicitado. Revisa `codigo` o `equipo_id`.';
            }

            $persona = findPersonaByRef($conn, $personaId, $cedula);
            if (!$persona) {
                return 'No encontré la persona solicitada. Revisa `cedula` o `persona_id`.';
            }

            $stmtCheck = $conn->prepare('SELECT id FROM asignaciones WHERE equipo_id = ? AND fecha_devolucion IS NULL LIMIT 1');
            $stmtCheck->bind_param('i', $equipo['id']);
            $stmtCheck->execute();
            $busy = $stmtCheck->get_result()->fetch_assoc();
            $stmtCheck->close();
            if ($busy) {
                return 'El equipo ya está prestado. Primero registra la devolución.';
            }

            $conn->begin_transaction();
            $inTransaction = true;
            $stmt1 = $conn->prepare('INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion, observaciones) VALUES (?, ?, NOW(), ?)');
            $stmt1->bind_param('iis', $equipo['id'], $persona['id'], $obs);
            $stmt1->execute();
            $stmt1->close();

            $nuevoEstado = 'Asignado';
            $stmt2 = $conn->prepare('UPDATE equipos SET estado = ? WHERE id = ?');
            $stmt2->bind_param('si', $nuevoEstado, $equipo['id']);
            $stmt2->execute();
            $stmt2->close();

            $tipoMov = 'ASIGNACION';
            $stmt3 = $conn->prepare('INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) VALUES (?, ?, ?, ?)');
            $stmt3->bind_param('iiss', $equipo['id'], $persona['id'], $tipoMov, $obs);
            $stmt3->execute();
            $stmt3->close();

            $conn->commit();
            $inTransaction = false;
            return "Prestamo creado con exito. Equipo {$equipo['codigo_barras']} asignado a {$persona['nombres']} ({$persona['cedula']}).";
        }

        if ($action === 'registrar_devolucion') {
            $equipoId = isset($params['equipo_id']) ? (int)$params['equipo_id'] : null;
            $codigo = $params['codigo'] ?? ($params['codigo_barras'] ?? null);
            $obs = trim((string)($params['observacion'] ?? ($params['obs'] ?? 'Devolución registrada desde asistente')));
            $cond = trim((string)($params['condiciones'] ?? ''));
            $estadoEquipo = parseStatus((string)($params['estado'] ?? ($params['estado_equipo'] ?? 'BUENO')));

            if ($equipoId === null && !$codigo) {
                if (isCapabilityQuestion($message)) {
                    return 'Si, puedo registrar devoluciones. Enviame: devolver equipo codigo=PRO-000001 estado=BUENO observacion="..."';
                }
                return 'Para devolver usa: devolver equipo codigo=PRO-000001 estado=BUENO observacion="..."';
            }

            $equipo = findEquipoByRef($conn, $equipoId, $codigo);
            if (!$equipo) {
                return 'No encontré el equipo solicitado para devolución.';
            }

            $stmtA = $conn->prepare('SELECT a.id, a.persona_id, p.nombres, p.cedula FROM asignaciones a JOIN personas p ON p.id = a.persona_id WHERE a.equipo_id = ? AND a.fecha_devolucion IS NULL LIMIT 1');
            $stmtA->bind_param('i', $equipo['id']);
            $stmtA->execute();
            $asig = $stmtA->get_result()->fetch_assoc();
            $stmtA->close();
            if (!$asig) {
                return 'Ese equipo no tiene un préstamo activo.';
            }

            $conn->begin_transaction();
            $inTransaction = true;
            $stmt1 = $conn->prepare('UPDATE asignaciones SET fecha_devolucion = CURDATE(), observaciones = ? WHERE id = ?');
            $stmt1->bind_param('si', $obs, $asig['id']);
            $stmt1->execute();
            $stmt1->close();

            $nuevoEstado = ($estadoEquipo === 'BUENO') ? 'Disponible' : 'En mantenimiento';
            $stmt2 = $conn->prepare('UPDATE equipos SET estado = ? WHERE id = ?');
            $stmt2->bind_param('si', $nuevoEstado, $equipo['id']);
            $stmt2->execute();
            $stmt2->close();

            if ($estadoEquipo !== 'BUENO') {
                $tipoM = 'correctivo';
                $obsM = 'Generado automaticamente por devolucion desde chat.';
                $desc = "Ingreso por devolucion en estado {$estadoEquipo}. {$cond}";
                $stmtM = $conn->prepare('INSERT INTO mantenimientos (equipo_id, fecha_ingreso, tipo_mantenimiento, descripcion, observaciones, created_by) VALUES (?, NOW(), ?, ?, ?, ?)');
                $stmtM->bind_param('isssi', $equipo['id'], $tipoM, $desc, $obsM, $userId);
                $stmtM->execute();
                $stmtM->close();
            }

            $tipoMov = 'DEVOLUCION';
            $stmt3 = $conn->prepare('INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones, estado_equipo, condiciones) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt3->bind_param('iissss', $equipo['id'], $asig['persona_id'], $tipoMov, $obs, $estadoEquipo, $cond);
            $stmt3->execute();
            $stmt3->close();

            $conn->commit();
            $inTransaction = false;
            return "Devolucion registrada con exito para {$equipo['codigo_barras']}. Recibido de {$asig['nombres']} ({$asig['cedula']}). Estado final: {$nuevoEstado}.";
        }

        if ($action === 'crear_equipo') {
            $tipo = trim((string)($params['tipo'] ?? ($params['tipo_equipo'] ?? '')));
            $codigo = trim((string)($params['codigo'] ?? ($params['codigo_barras'] ?? '')));
            $marca = trim((string)($params['marca'] ?? ''));
            $modelo = trim((string)($params['modelo'] ?? ''));
            $serie = trim((string)($params['serie'] ?? ($params['numero_serie'] ?? '')));
            $especificaciones = trim((string)($params['especificaciones'] ?? ''));
            $obs = trim((string)($params['observaciones'] ?? ($params['obs'] ?? '')));
            $ubicacionId = isset($params['ubicacion_id']) ? (int)$params['ubicacion_id'] : null;

            if ($tipo === '') {
                if (isCapabilityQuestion($message)) {
                    return 'Si, puedo agregar equipos (modo admin). Ejemplo: crear equipo tipo=Laptop marca=Dell modelo=Latitude';
                }
                return 'Para crear equipo usa: crear equipo tipo=Laptop marca=Dell modelo=Latitude codigo=PRO-000999';
            }

            if ($codigo !== '') {
                $stmtCheck = $conn->prepare('SELECT id FROM equipos WHERE codigo_barras = ? LIMIT 1');
                $stmtCheck->bind_param('s', $codigo);
                $stmtCheck->execute();
                $dup = $stmtCheck->get_result()->fetch_assoc();
                $stmtCheck->close();
                if ($dup) {
                    return "El codigo {$codigo} ya existe. Usa otro codigo.";
                }
            } else {
                $next = dbScalar($conn, 'SELECT IFNULL(MAX(id),0)+1 FROM equipos');
                $codigo = 'PRO-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
            }

            $estado = 'Disponible';
            if ($ubicacionId !== null && $ubicacionId > 0) {
                $stmt = $conn->prepare('INSERT INTO equipos (codigo_barras, tipo_equipo, marca, modelo, numero_serie, especificaciones, observaciones, ubicacion_id, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('sssssssis', $codigo, $tipo, $marca, $modelo, $serie, $especificaciones, $obs, $ubicacionId, $estado);
            } else {
                $stmt = $conn->prepare('INSERT INTO equipos (codigo_barras, tipo_equipo, marca, modelo, numero_serie, especificaciones, observaciones, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('ssssssss', $codigo, $tipo, $marca, $modelo, $serie, $especificaciones, $obs, $estado);
            }
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            return "Equipo creado con exito. ID {$newId}, codigo {$codigo}.";
        }

        if ($action === 'crear_persona') {
            $cedula = trim((string)($params['cedula'] ?? ''));
            $nombres = trim((string)($params['nombres'] ?? ($params['nombre'] ?? '')));
            $correo = trim((string)($params['correo'] ?? ''));
            $cargo = trim((string)($params['cargo'] ?? ''));
            $telefono = trim((string)($params['telefono'] ?? ''));
            $tipo = trim((string)($params['tipo'] ?? 'persona'));
            $codigoUbicacion = trim((string)($params['codigo_ubicacion'] ?? ''));

            if ($cedula === '' || $nombres === '') {
                if (isCapabilityQuestion($message)) {
                    return 'Si, puedo crear personas (modo admin). Ejemplo: crear persona cedula=0102030405 nombres="Juan Perez"';
                }
                return 'Para crear persona usa: crear persona cedula=0102030405 nombres="Juan Perez" cargo=Docente correo=correo@dominio.com';
            }

            $stmtCheck = $conn->prepare('SELECT id FROM personas WHERE cedula = ? LIMIT 1');
            $stmtCheck->bind_param('s', $cedula);
            $stmtCheck->execute();
            $dup = $stmtCheck->get_result()->fetch_assoc();
            $stmtCheck->close();
            if ($dup) {
                return "La cedula {$cedula} ya esta registrada.";
            }

            $activo = 1;
            $stmt = $conn->prepare('INSERT INTO personas (cedula, nombres, correo, cargo, telefono, tipo, codigo_ubicacion, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssssi', $cedula, $nombres, $correo, $cargo, $telefono, $tipo, $codigoUbicacion, $activo);
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            return "Persona creada con exito. ID {$newId}, nombre {$nombres}, cedula {$cedula}.";
        }

        if ($action === 'dar_baja_equipo') {
            $equipoId = isset($params['equipo_id']) ? (int)$params['equipo_id'] : null;
            $codigo = $params['codigo'] ?? ($params['codigo_barras'] ?? null);
            $obs = trim((string)($params['observacion'] ?? ($params['obs'] ?? 'Baja registrada desde asistente')));

            if ($equipoId === null && !$codigo) {
                return 'Para dar de baja usa: baja equipo codigo=PRO-000001 observacion="Motivo de baja"';
            }

            $equipo = findEquipoByRef($conn, $equipoId, $codigo);
            if (!$equipo) {
                return 'No encontré el equipo para dar de baja.';
            }

            $stmtCheck = $conn->prepare('SELECT id FROM asignaciones WHERE equipo_id = ? AND fecha_devolucion IS NULL LIMIT 1');
            $stmtCheck->bind_param('i', $equipo['id']);
            $stmtCheck->execute();
            $busy = $stmtCheck->get_result()->fetch_assoc();
            $stmtCheck->close();
            if ($busy) {
                return 'No se puede dar de baja un equipo con préstamo activo. Primero registra devolución.';
            }

            $conn->begin_transaction();
            $inTransaction = true;
            $estado = 'Baja';
            $stmt1 = $conn->prepare('UPDATE equipos SET estado = ?, fecha_eliminacion = NOW(), eliminado_por = ? WHERE id = ?');
            $stmt1->bind_param('sii', $estado, $userId, $equipo['id']);
            $stmt1->execute();
            $stmt1->close();

            $tipoMov = 'BAJA';
            $stmt2 = $conn->prepare('INSERT INTO movimientos (equipo_id, tipo_movimiento, observaciones) VALUES (?, ?, ?)');
            $stmt2->bind_param('iss', $equipo['id'], $tipoMov, $obs);
            $stmt2->execute();
            $stmt2->close();
            $conn->commit();
            $inTransaction = false;
            return "Equipo {$equipo['codigo_barras']} dado de baja con exito.";
        }

        if ($action === 'crear_componente') {
            $nombre = trim((string)($params['nombre'] ?? ($params['nombre_componente'] ?? '')));
            $tipo = trim((string)($params['tipo'] ?? ''));
            $marca = trim((string)($params['marca'] ?? ''));
            $modelo = trim((string)($params['modelo'] ?? ''));
            $serie = trim((string)($params['serie'] ?? ($params['numero_serie'] ?? '')));
            $especificaciones = trim((string)($params['especificaciones'] ?? ''));
            $estado = trim((string)($params['estado'] ?? 'Bueno'));
            $equipoId = isset($params['equipo_id']) ? (int)$params['equipo_id'] : null;
            $obs = trim((string)($params['observaciones'] ?? ($params['obs'] ?? '')));

            if ($nombre === '' || $tipo === '') {
                if (isCapabilityQuestion($message)) {
                    return 'Si, puedo agregar componentes (modo admin). Ejemplo: crear componente nombre="RAM 8GB" tipo=Memoria equipo_id=12';
                }
                return 'Para crear componente usa: crear componente nombre="RAM 8GB" tipo=Memoria marca=Kingston equipo_id=12';
            }

            if ($equipoId !== null && $equipoId > 0) {
                $eq = findEquipoByRef($conn, $equipoId, null);
                if (!$eq) {
                    return 'No existe el equipo indicado para asociar el componente.';
                }
                $stmt = $conn->prepare('INSERT INTO componentes (equipo_id, nombre_componente, tipo, marca, modelo, numero_serie, especificaciones, estado, observaciones, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->bind_param('issssssss', $equipoId, $nombre, $tipo, $marca, $modelo, $serie, $especificaciones, $estado, $obs);
            } else {
                $stmt = $conn->prepare('INSERT INTO componentes (nombre_componente, tipo, marca, modelo, numero_serie, especificaciones, estado, observaciones, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->bind_param('ssssssss', $nombre, $tipo, $marca, $modelo, $serie, $especificaciones, $estado, $obs);
            }
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            return "Componente creado con exito. ID {$newId}, nombre {$nombre}.";
        }

        if ($action === 'asignar_componente') {
            $componenteId = isset($params['componente_id']) ? (int)$params['componente_id'] : null;
            $persona = findPersonaByHint($conn, $params, $message);
            $obs = trim((string)($params['observacion'] ?? ($params['obs'] ?? 'Asignacion de componente desde asistente')));

            if ($componenteId === null || !$persona) {
                return 'Para asignar componente usa: asignar componente componente_id=5 cedula=0102030405 observacion="..."';
            }

            $stmtC = $conn->prepare('SELECT id, nombre_componente, tipo FROM componentes WHERE id = ? AND (fecha_eliminacion IS NULL) LIMIT 1');
            $stmtC->bind_param('i', $componenteId);
            $stmtC->execute();
            $comp = $stmtC->get_result()->fetch_assoc();
            $stmtC->close();
            if (!$comp) {
                return 'No encontre el componente solicitado.';
            }

            $stmtBusy = $conn->prepare("SELECT mc.id FROM movimientos_componentes mc WHERE mc.componente_id = ? AND mc.tipo_movimiento = 'ASIGNACION' AND NOT EXISTS (SELECT 1 FROM movimientos_componentes mc2 WHERE mc2.componente_id = mc.componente_id AND mc2.tipo_movimiento = 'DEVOLUCION' AND mc2.fecha_movimiento > mc.fecha_movimiento) LIMIT 1");
            $stmtBusy->bind_param('i', $componenteId);
            $stmtBusy->execute();
            $busy = $stmtBusy->get_result()->fetch_assoc();
            $stmtBusy->close();
            if ($busy) {
                return 'Ese componente ya esta asignado. Debes devolverlo primero.';
            }

            $tipoMov = 'ASIGNACION';
            $stmt = $conn->prepare('INSERT INTO movimientos_componentes (componente_id, persona_id, tipo_movimiento, observaciones) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiss', $componenteId, $persona['id'], $tipoMov, $obs);
            $stmt->execute();
            $stmt->close();
            return "Componente {$comp['nombre_componente']} asignado a {$persona['nombres']} ({$persona['cedula']}).";
        }

        if ($action === 'devolver_componente') {
            $componenteId = isset($params['componente_id']) ? (int)$params['componente_id'] : null;
            $obs = trim((string)($params['observacion'] ?? ($params['obs'] ?? 'Devolucion de componente desde asistente')));

            if ($componenteId === null) {
                return 'Para devolver componente usa: devolver componente componente_id=5 observacion="..."';
            }

            $stmtC = $conn->prepare('SELECT id, nombre_componente, tipo FROM componentes WHERE id = ? LIMIT 1');
            $stmtC->bind_param('i', $componenteId);
            $stmtC->execute();
            $comp = $stmtC->get_result()->fetch_assoc();
            $stmtC->close();
            if (!$comp) {
                return 'No encontre el componente solicitado.';
            }

            $stmtAssigned = $conn->prepare("SELECT mc.persona_id, p.nombres, p.cedula FROM movimientos_componentes mc LEFT JOIN personas p ON p.id = mc.persona_id WHERE mc.componente_id = ? AND mc.tipo_movimiento = 'ASIGNACION' AND NOT EXISTS (SELECT 1 FROM movimientos_componentes mc2 WHERE mc2.componente_id = mc.componente_id AND mc2.tipo_movimiento = 'DEVOLUCION' AND mc2.fecha_movimiento > mc.fecha_movimiento) ORDER BY mc.fecha_movimiento DESC LIMIT 1");
            $stmtAssigned->bind_param('i', $componenteId);
            $stmtAssigned->execute();
            $asignado = $stmtAssigned->get_result()->fetch_assoc();
            $stmtAssigned->close();
            if (!$asignado) {
                return 'Ese componente no tiene asignacion activa.';
            }

            $tipoMov = 'DEVOLUCION';
            $stmt = $conn->prepare('INSERT INTO movimientos_componentes (componente_id, persona_id, tipo_movimiento, observaciones) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiss', $componenteId, $asignado['persona_id'], $tipoMov, $obs);
            $stmt->execute();
            $stmt->close();
            return "Componente {$comp['nombre_componente']} devuelto correctamente (responsable anterior: {$asignado['nombres']}).";
        }

        return null;
    } catch (\Throwable $e) {
        if ($inTransaction) {
            $conn->rollback();
        }
        throw $e;
    } finally {
        $conn->close();
    }
}

function handleReadQueries(string $message): ?string
{
    $params = parseCommandParams($message);
    $m = toLower($message);

    $hasWord = static function (string $txt, array $words): bool {
        return hasAnyKeyword($txt, $words);
    };

    $conn = connectAppDb();
    try {
        if ($hasWord($m, ['hoy']) && $hasWord($m, ['asign', 'agreg', 'equip', 'component'])) {
            $equiposAgregados = dbScalar($conn, 'SELECT COUNT(*) FROM equipos WHERE DATE(fecha_registro) = CURDATE()');
            $componentesAgregados = dbScalar($conn, 'SELECT COUNT(*) FROM componentes WHERE DATE(created_at) = CURDATE()');
            $equiposAsignados = dbScalar($conn, 'SELECT COUNT(*) FROM asignaciones WHERE DATE(fecha_asignacion) = CURDATE()');
            $componentesAsignados = dbScalar($conn, "SELECT COUNT(*) FROM movimientos_componentes WHERE tipo_movimiento = 'ASIGNACION' AND DATE(fecha_movimiento) = CURDATE()");
            $equiposDevueltos = dbScalar($conn, "SELECT COUNT(*) FROM movimientos WHERE tipo_movimiento = 'DEVOLUCION' AND DATE(fecha_movimiento) = CURDATE()");
            $componentesDevueltos = dbScalar($conn, "SELECT COUNT(*) FROM movimientos_componentes WHERE tipo_movimiento = 'DEVOLUCION' AND DATE(fecha_movimiento) = CURDATE()");

            return "Resumen de hoy:\n"
                . "- Equipos agregados: {$equiposAgregados}\n"
                . "- Componentes agregados: {$componentesAgregados}\n"
                . "- Equipos asignados: {$equiposAsignados}\n"
                . "- Componentes asignados: {$componentesAsignados}\n"
                . "- Equipos devueltos: {$equiposDevueltos}\n"
                . "- Componentes devueltos: {$componentesDevueltos}";
        }

        if ($hasWord($m, ['equipo', 'codigo', 'código', 'disponible', 'prestado', 'asignado']) && $hasWord($m, ['estado', 'disponible', 'prestado', 'asignado'])) {
            $equipoId = isset($params['equipo_id']) ? (int)$params['equipo_id'] : null;
            $codigo = $params['codigo'] ?? ($params['codigo_barras'] ?? extractCodigoFromText($message));
            $equipo = findEquipoByRef($conn, $equipoId, $codigo);
            if (!$equipo) {
                return 'No encontré ese equipo. Envíame `codigo=...` o `equipo_id=...` para verificar su estado.';
            }

            $stmt = $conn->prepare('SELECT p.nombres, p.cedula, a.fecha_asignacion FROM asignaciones a JOIN personas p ON p.id = a.persona_id WHERE a.equipo_id = ? AND a.fecha_devolucion IS NULL ORDER BY a.fecha_asignacion DESC LIMIT 1');
            $stmt->bind_param('i', $equipo['id']);
            $stmt->execute();
            $asig = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($asig) {
                return "Estado de {$equipo['codigo_barras']}: {$equipo['estado']}.\nPrestado a: {$asig['nombres']} ({$asig['cedula']}) desde {$asig['fecha_asignacion']}.";
            }
            return "Estado de {$equipo['codigo_barras']}: {$equipo['estado']}.\nActualmente no tiene préstamo activo.";
        }

        if ($hasWord($m, ['acta']) && $hasWord($m, ['persona', 'cedula', 'cédula'])) {
            $persona = findPersonaByHint($conn, $params, $message);
            if (!$persona) {
                return 'No pude identificar la persona. Usa `cedula=...` o `persona_id=...`.';
            }

            $stmt = $conn->prepare('SELECT codigo_acta, tipo_acta, fecha_generacion, archivo_pdf FROM actas WHERE persona_id = ? ORDER BY fecha_generacion DESC LIMIT 10');
            $stmt->bind_param('i', $persona['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $stmt->close();

            if (count($rows) === 0) {
                return "No hay actas registradas para {$persona['nombres']} ({$persona['cedula']}).";
            }

            $lines = [];
            foreach ($rows as $r) {
                $file = $r['archivo_pdf'] ? " | archivo: {$r['archivo_pdf']}" : '';
                $lines[] = "- {$r['codigo_acta']} | {$r['tipo_acta']} | {$r['fecha_generacion']}{$file}";
            }
            return "Actas de {$persona['nombres']} ({$persona['cedula']}):\n" . implode("\n", $lines);
        }

        if ($hasWord($m, ['persona', 'buscar', 'tiene', 'equipos', 'componentes', 'componente'])) {
            $persona = findPersonaByHint($conn, $params, $message);
            if (!$persona) {
                return 'No pude identificar la persona. Usa `cedula=...`, `persona_id=...` o `nombre="..."`.';
            }

            $stmtEq = $conn->prepare('SELECT e.id, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo, a.fecha_asignacion FROM asignaciones a JOIN equipos e ON e.id = a.equipo_id WHERE a.persona_id = ? AND a.fecha_devolucion IS NULL ORDER BY a.fecha_asignacion DESC');
            $stmtEq->bind_param('i', $persona['id']);
            $stmtEq->execute();
            $resEq = $stmtEq->get_result();
            $equipos = [];
            while ($r = $resEq->fetch_assoc()) {
                $equipos[] = $r;
            }
            $stmtEq->close();

            $stmtCd = $conn->prepare("SELECT c.id, c.nombre_componente, c.tipo, c.marca, c.modelo, mc.fecha_movimiento FROM movimientos_componentes mc JOIN componentes c ON c.id = mc.componente_id WHERE mc.persona_id = ? AND mc.tipo_movimiento = 'ASIGNACION' AND NOT EXISTS (SELECT 1 FROM movimientos_componentes mc2 WHERE mc2.componente_id = mc.componente_id AND mc2.tipo_movimiento = 'DEVOLUCION' AND mc2.fecha_movimiento > mc.fecha_movimiento) ORDER BY mc.fecha_movimiento DESC");
            $stmtCd->bind_param('i', $persona['id']);
            $stmtCd->execute();
            $resCd = $stmtCd->get_result();
            $componentesDirectos = [];
            while ($r = $resCd->fetch_assoc()) {
                $componentesDirectos[] = $r;
            }
            $stmtCd->close();

            $componentesDeEquipos = [];
            if (count($equipos) > 0) {
                $ids = array_map(static fn($e) => (int)$e['id'], $equipos);
                $idsCsv = implode(',', $ids);
                $q = "SELECT c.id, c.nombre_componente, c.tipo, c.marca, c.modelo, c.equipo_id FROM componentes c WHERE c.equipo_id IN ({$idsCsv}) AND (c.fecha_eliminacion IS NULL)";
                $componentesDeEquipos = dbRows($conn, $q);
            }

            $out = "Persona: {$persona['nombres']} ({$persona['cedula']})\n";
            $out .= "Equipos asignados activos: " . count($equipos) . "\n";
            foreach (array_slice($equipos, 0, 8) as $e) {
                $out .= "- {$e['codigo_barras']} | {$e['tipo_equipo']} {$e['marca']} {$e['modelo']} | desde {$e['fecha_asignacion']}\n";
            }
            $out .= "Componentes directos activos: " . count($componentesDirectos) . "\n";
            foreach (array_slice($componentesDirectos, 0, 8) as $c) {
                $out .= "- {$c['nombre_componente']} ({$c['tipo']}) {$c['marca']} {$c['modelo']}\n";
            }
            $out .= "Componentes en equipos asignados: " . count($componentesDeEquipos);
            return trim($out);
        }

        if ($hasWord($m, ['cuantos', 'cuántos', 'total']) && $hasWord($m, ['equipos', 'componentes', 'personas', 'prestamos', 'préstamos'])) {
            $totE = dbScalar($conn, "SELECT COUNT(*) FROM equipos WHERE fecha_eliminacion IS NULL");
            $totC = dbScalar($conn, "SELECT COUNT(*) FROM componentes WHERE fecha_eliminacion IS NULL");
            $totP = dbScalar($conn, 'SELECT COUNT(*) FROM personas WHERE fecha_eliminacion IS NULL');
            $activosPrest = dbScalar($conn, 'SELECT COUNT(*) FROM asignaciones WHERE fecha_devolucion IS NULL');
            return "Totales actuales:\n- Equipos: {$totE}\n- Componentes: {$totC}\n- Personas: {$totP}\n- Préstamos activos: {$activosPrest}";
        }

        return null;
    } finally {
        $conn->close();
    }
}

try {
    if (class_exists(\Dotenv\Dotenv::class)) {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido']);
        exit;
    }

    $message = trim((string)($input['message'] ?? ''));
    if ($message === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Mensaje vacío']);
        exit;
    }

    $actionReply = executeAction($message);
    if ($actionReply !== null) {
        if (ob_get_length() > 0) {
            ob_clean();
        }
        echo json_encode(['reply' => $actionReply]);
        exit;
    }

    $readReply = handleReadQueries($message);
    if ($readReply !== null) {
        if (ob_get_length() > 0) {
            ob_clean();
        }
        echo json_encode(['reply' => $readReply]);
        exit;
    }

    $ollamaBaseUrl = rtrim((string)($_ENV['OLLAMA_BASE_URL'] ?? getenv('OLLAMA_BASE_URL') ?: 'http://localhost:11434'), '/');
    $ollamaModel = (string)($_ENV['OLLAMA_MODEL'] ?? getenv('OLLAMA_MODEL') ?: 'llama3.2:1b');
    $ollamaTimeout = (int)($_ENV['OLLAMA_TIMEOUT'] ?? getenv('OLLAMA_TIMEOUT') ?: 120);

    $dbContext = buildDynamicDbContext($message);
    $modoUsuario = isAdminUser() ? 'ADMIN' : 'INVITADO_LECTURA';
    $systemPrompt = 'Eres el asistente virtual del sistema de inventario TI de TESA. Tu prioridad es responder usando los datos reales entregados en CONTEXTO_BD. Si hay datos en CONTEXTO_BD, NO respondas que no tienes acceso. Si faltan datos concretos, dilo claramente y sugiere una consulta específica. Responde en español, claro y breve.';
    $finalPrompt = $systemPrompt
        . "\n\nMODO_USUARIO: {$modoUsuario}"
        . "\n\nCONTEXTO_BD:\n"
        . $dbContext
        . "\n\nPREGUNTA_USUARIO:\n"
        . $message
        . "\n\nINSTRUCCIONES_ADICIONALES:\n"
        . "- Usa cifras y nombres exactos del CONTEXTO_BD cuando existan.\n"
        . "- Si el usuario pide cantidades, da primero el número.\n"
        . "- Si detectas posible acción operativa (crear préstamo, devolución, registro), indica la ruta exacta del módulo para hacerlo.\n"
        . "\nRESPUESTA:";

    $ch = curl_init($ollamaBaseUrl . '/api/generate');
    if ($ch === false) {
        throw new \RuntimeException('No se pudo inicializar cURL para Ollama.');
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $ollamaModel,
            'prompt' => $finalPrompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.7,
                'num_predict' => 180,
            ],
            'keep_alive' => '10m',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $ollamaTimeout,
    ]);

    $rawResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($rawResponse === false) {
        throw new \RuntimeException('Error de conexión con Ollama: ' . $curlError);
    }

    $responseData = json_decode($rawResponse, true);
    if (!is_array($responseData)) {
        throw new \RuntimeException('Respuesta inválida de Ollama (no JSON).');
    }

    if ($httpCode >= 400) {
        $ollamaError = (string)($responseData['error'] ?? ('HTTP ' . $httpCode));
        throw new \RuntimeException('Ollama devolvió un error: ' . $ollamaError);
    }

    $reply = trim((string)($responseData['response'] ?? ''));
    if ($reply === '') {
        http_response_code(502);
        echo json_encode(['error' => 'Respuesta vacía del asistente']);
        exit;
    }

    if (ob_get_length() > 0) {
        ob_clean();
    }
    echo json_encode(['reply' => $reply]);
} catch (\Throwable $t) {
    $msg = $t->getMessage();
    error_log('Error en api/chat.php: ' . $msg);

    $lowerMsg = function_exists('mb_strtolower')
        ? mb_strtolower($msg, 'UTF-8')
        : strtolower($msg);

    if (ob_get_length() > 0) {
        ob_clean();
    }

    if (str_contains($lowerMsg, 'model') || str_contains($lowerMsg, 'not found')) {
        http_response_code(400);
        echo json_encode(['error' => 'El modelo de Ollama no está disponible. Revisa OLLAMA_MODEL y ejecuta "ollama pull".']);
        exit;
    }

    if (str_contains($lowerMsg, 'connection') || str_contains($lowerMsg, 'failed to connect')) {
        http_response_code(503);
        echo json_encode(['error' => 'No se pudo conectar con Ollama. Verifica que esté ejecutándose en OLLAMA_BASE_URL.']);
        exit;
    }

    http_response_code(500);
    echo json_encode(['error' => 'No se pudo generar respuesta con Ollama. Revisa logs y configuración OLLAMA_* en .env.']);
}

ob_end_flush();
