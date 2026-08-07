<?php
// ============
// API Documentos Adjuntos para Equipos y Personas
// ============

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('html_errors', '0');

header('X-Content-Type-Options: nosniff');
header_remove('X-Powered-By');

function responderJson($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

set_exception_handler(function ($e) {
    responderJson([
        'error' => 'Excepción del servidor: ' . $e->getMessage(),
        'file'  => $e->getFile() . ':' . $e->getLine(),
    ], 500);
});
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    responderJson([
        'error' => 'Error PHP: ' . $message,
        'file'  => $file . ':' . $line,
    ], 500);
}, E_ALL);

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        $out = ob_get_clean();
        echo json_encode([
            'error' => 'Fatal error: ' . $err['message'],
            'file'  => $err['file'] . ':' . $err['line'],
            'output' => $out ? 'Hubo salida previa (posible warning o echo). Truncado: ' . mb_substr($out, 0, 200) : null,
        ], JSON_UNESCAPED_UNICODE);
    }
});
ob_start();

session_start();
if (!isset($_SESSION['user_id'])) {
    responderJson(['error' => 'No autorizado'], 401);
}

$es_admin = ($_SESSION['user_rol'] == 1);
require_once __DIR__ . '/../config/database.php';

// ============ AUTO-INSTALL: Tabla y carpetas (si no existen) ============
function autoInstalar(&$conn) {
    $tabla = 'documentos_adjuntos';
    $t = $conn->query("SHOW TABLES LIKE '$tabla'");
    $existe = $t && $t->num_rows > 0;
    if (!$existe) {
        $sql = "CREATE TABLE `$tabla` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `equipo_id` INT UNSIGNED NULL,
            `persona_id` INT UNSIGNED NULL,
            `tipo_documento` VARCHAR(50) NOT NULL DEFAULT 'otro',
            `nombre_original` VARCHAR(255) NOT NULL,
            `nombre_archivo` VARCHAR(255) NOT NULL,
            `ruta` VARCHAR(500) NOT NULL,
            `tamano` INT UNSIGNED NOT NULL DEFAULT 0,
            `mime_type` VARCHAR(120) NULL,
            `descripcion` VARCHAR(500) NULL,
            `usuario_id` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_doc_equipo` (`equipo_id`),
            INDEX `idx_doc_persona` (`persona_id`),
            INDEX `idx_doc_tipo` (`tipo_documento`),
            INDEX `idx_doc_fecha` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($sql);
    }
    // Asegurar columnas (por si la tabla existía pero le faltaba campos)
    $cols = [];
    $rc = $conn->query("SHOW COLUMNS FROM `$tabla`");
    if ($rc) while ($r = $rc->fetch_assoc()) $cols[$r['Field']] = true;
    $map = [
        'id' => "ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST",
        'equipo_id' => "ADD `equipo_id` INT UNSIGNED NULL, ADD INDEX `idx_doc_equipo` (`equipo_id`)",
        'persona_id' => "ADD `persona_id` INT UNSIGNED NULL, ADD INDEX `idx_doc_persona` (`persona_id`)",
        'tipo_documento' => "ADD `tipo_documento` VARCHAR(50) NOT NULL DEFAULT 'otro', ADD INDEX `idx_doc_tipo` (`tipo_documento`)",
        'nombre_original' => "ADD `nombre_original` VARCHAR(255) NOT NULL",
        'nombre_archivo' => "ADD `nombre_archivo` VARCHAR(255) NOT NULL",
        'ruta' => "ADD `ruta` VARCHAR(500) NOT NULL",
        'tamano' => "ADD `tamano` INT UNSIGNED NOT NULL DEFAULT 0",
        'mime_type' => "ADD `mime_type` VARCHAR(120) NULL",
        'descripcion' => "ADD `descripcion` VARCHAR(500) NULL",
        'usuario_id' => "ADD `usuario_id` INT UNSIGNED NULL",
        'created_at' => "ADD `created_at` DATETIME NOT NULL, ADD INDEX `idx_doc_fecha` (`created_at`)",
    ];
    foreach ($map as $c => $q) {
        if (empty($cols[$c])) {
            @$conn->query("ALTER TABLE `$tabla` $q");
        }
    }
    // Asegurar carpeta uploads/documentos + htaccess
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documentos';
    if (!is_dir($base)) {
        @mkdir($base, 0755, true);
    }
    if (is_dir($base)) {
        $ht = $base . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($ht)) {
            @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|php5|php7)$\">\nRequire all denied\n</FilesMatch>\nAddType application/octet-stream .pdf .doc .docx .xls .xlsx .zip\n");
        }
        if (!file_exists($base . DIRECTORY_SEPARATOR . 'index.html')) {
            @file_put_contents($base . DIRECTORY_SEPARATOR . 'index.html', '<!-- empty -->');
        }
    }
}
autoInstalar($conn);

// ============ Helper: bind_param compatible con PHP viejo (sin splat, sin mysqlnd)
function stmtBind($stmt, $types, array $params) {
    if ($params === []) return true;
    if (strlen($types) !== count($params)) return false;
    // PHP 5.6+ permite ... pero mysqlnd no siempre. Usar refs con call_user_func_array
    $refs = [];
    foreach ($params as $k => $_) {
        $refs[$k] = &$params[$k];
    }
    array_unshift($refs, $types);
    return call_user_func_array([$stmt, 'bind_param'], $refs);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($method === 'POST' ? ($_POST['action'] ?? '') : '');

if ($action === '') {
    responderJson(['error' => 'Acción no válida. Usa ?action=listar|subir|eliminar|descargar'], 400);
}

switch ($action) {
    case 'subir':        subirDocumento(); break;
    case 'listar':        listarDocumentos(); break;
    case 'eliminar':      eliminarDocumento(); break;
    case 'descargar':    descargarDocumento(); break;
    default:
        responderJson(['error' => 'Acción no válida: ' . $action], 400);
}

function requiereAdmin($mensaje = 'Sin permisos') {
    global $es_admin;
    if (!$es_admin) responderJson(['error' => $mensaje], 403);
}

function subirDocumento() {
    requiereAdmin('Sin permisos para subir documentos');
    global $conn;

    $equipo_id = isset($_POST['equipo_id']) ? intval($_POST['equipo_id']) : null;
    $persona_id = isset($_POST['persona_id']) ? intval($_POST['persona_id']) : null;
    $tipo_documento = trim((string)($_POST['tipo_documento'] ?? 'otro'));
    if ($tipo_documento === '') $tipo_documento = 'otro';
    $descripcion = trim((string)($_POST['descripcion'] ?? ''));

    $tipos_validos = ['factura','garantia','manual','certificado','mantenimiento','otro'];
    if (!in_array($tipo_documento, $tipos_validos, true)) $tipo_documento = 'otro';

    if (($equipo_id ?? 0) <= 0 && ($persona_id ?? 0) <= 0) {
        responderJson(['error' => 'Debe especificar equipo o persona (equipo_id / persona_id)'], 400);
    }

    if (!isset($_FILES['archivo']) || !is_array($_FILES['archivo'])) {
        responderJson(['error' => 'No se recibió archivo (falta campo "archivo")'], 400);
    }
    $archivo = $_FILES['archivo'];
    $err = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err !== UPLOAD_ERR_OK) {
        $mensajes = [
            UPLOAD_ERR_INI_SIZE => 'Archivo excede el límite de php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Archivo excede el límite del formulario',
            UPLOAD_ERR_PARTIAL => 'Subida parcial',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal en PHP',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir en disco',
            UPLOAD_ERR_EXTENSION => 'Subida detenida por extensión PHP',
        ];
        $mensaje_error = isset($mensajes[$err]) ? $mensajes[$err] : ('código ' . $err);
        responderJson(['error' => 'Error al subir archivo: ' . $mensaje_error], 400);
    }
    if (!is_uploaded_file($archivo['tmp_name'])) {
        responderJson(['error' => 'Archivo no subido por HTTP'], 400);
    }

    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $mime = $archivo['type'] ?? '';
    $permitidos = ['pdf','jpg','jpeg','png','gif','doc','docx','xls','xlsx'];
    if (!in_array($ext, $permitidos, true)) {
        responderJson(['error' => 'Tipo de archivo no permitido (' . htmlspecialchars($ext) . '). Permitidos: ' . implode(',', $permitidos)], 400);
    }

    $tamano = (int)($archivo['size'] ?? 0);
    if ($tamano > 10 * 1024 * 1024) {
        responderJson(['error' => 'El archivo excede los 10MB'], 400);
    }

    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documentos' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
        responderJson(['error' => 'No se pudo crear carpeta uploads/documentos'], 500);
    }

    $nombreOriginal = $archivo['name'];
    $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)pathinfo($nombreOriginal, PATHINFO_FILENAME));
    if ($base === '') $base = 'doc';
    $nombreArchivo = $base . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '_' . time() . '.' . $ext;
    $ruta = $uploadDir . $nombreArchivo;

    if (!@move_uploaded_file($archivo['tmp_name'], $ruta)) {
        responderJson(['error' => 'No se pudo mover archivo a uploads/documentos (verifica permisos)'], 500);
    }
    @chmod($ruta, 0644);
    $tamanoFinal = filesize($ruta) ?: $tamano;

    $sql = "INSERT INTO documentos_adjuntos
        (equipo_id, persona_id, tipo_documento, nombre_original, nombre_archivo, ruta, tamano, mime_type, descripcion, usuario_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) responderJson(['error' => 'Prepare falló: ' . $conn->error], 500);

    $eid = ($equipo_id ?? 0) > 0 ? $equipo_id : null;
    $pid = ($persona_id ?? 0) > 0 ? $persona_id : null;
    $uid = $_SESSION['user_id'] ?? null;
    $ok = stmtBind($stmt, 'iissssissi', [$eid, $pid, $tipo_documento, $nombreOriginal, $nombreArchivo, $ruta, (int)$tamanoFinal, $mime, $descripcion, $uid]);
    if ($ok === false) responderJson(['error' => 'bind_param falló'], 500);
    if (!$stmt->execute()) responderJson(['error' => 'Execute falló: ' . $stmt->error], 500);

    responderJson([
        'success' => true,
        'id' => (int)$stmt->insert_id,
        'mensaje' => 'Documento subido correctamente',
        'nombre_original' => $nombreOriginal,
        'tamano' => (int)$tamanoFinal,
    ]);
}

function listarDocumentos() {
    global $conn;

    $equipo_id = isset($_GET['equipo_id']) ? intval($_GET['equipo_id']) : 0;
    $persona_id = isset($_GET['persona_id']) ? intval($_GET['persona_id']) : 0;

    $where = [];
    $params = [];
    $types = '';
    if ($equipo_id > 0) { $where[] = "d.equipo_id = ?"; $params[] = $equipo_id; $types .= 'i'; }
    if ($persona_id > 0) { $where[] = "d.persona_id = ?"; $params[] = $persona_id; $types .= 'i'; }

    if (empty($where)) responderJson(['error' => 'Parámetros insuficientes: ?equipo_id=... o ?persona_id=...'], 400);

    $sql = "SELECT d.*, u.nombre as usuario_nombre
            FROM documentos_adjuntos d
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            WHERE (" . implode(' OR ', $where) . ")
            ORDER BY d.created_at DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) responderJson(['error' => 'Prepare falló (listar): ' . $conn->error], 500);
    if ($types !== '') {
        if (!stmtBind($stmt, $types, $params)) responderJson(['error' => 'bind_param falló (listar)'], 500);
    }
    if (!$stmt->execute()) responderJson(['error' => 'Execute falló (listar): ' . $stmt->error], 500);
    $res = $stmt->get_result();
    $docs = [];
    while ($row = $res ? $res->fetch_assoc() : []) {
        $row['fecha'] = !empty($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-';
        $row['tamano_format'] = formatBytes($row['tamano'] ?? 0);
        $docs[] = $row;
    }
    responderJson($docs);
}

function eliminarDocumento() {
    requiereAdmin('Sin permisos para eliminar documentos');
    global $conn;

    $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
    if ($id <= 0) responderJson(['error' => 'ID inválido'], 400);

    $stmt = $conn->prepare("SELECT ruta FROM documentos_adjuntos WHERE id = ? LIMIT 1");
    if (!$stmt) responderJson(['error' => 'Prepare falló: ' . $conn->error], 500);
    stmtBind($stmt, 'i', [$id]);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) responderJson(['error' => 'Documento no encontrado'], 404);
    $doc = $res->fetch_assoc();

    if (!empty($doc['ruta']) && file_exists($doc['ruta'])) @unlink($doc['ruta']);

    $d = $conn->prepare("DELETE FROM documentos_adjuntos WHERE id = ? LIMIT 1");
    if (!$d) responderJson(['error' => 'Prepare delete falló: ' . $conn->error], 500);
    stmtBind($d, 'i', [$id]);
    if (!$d->execute()) responderJson(['error' => 'Execute delete falló: ' . $d->error], 500);

    responderJson(['success' => true, 'mensaje' => 'Documento eliminado']);
}

function descargarDocumento() {
    global $conn;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) { http_response_code(400); die('ID inválido'); }

    $stmt = $conn->prepare("SELECT * FROM documentos_adjuntos WHERE id = ? LIMIT 1");
    if (!$stmt) { http_response_code(500); die('Error interno'); }
    stmtBind($stmt, 'i', [$id]);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) { http_response_code(404); die('Documento no encontrado'); }
    $doc = $res->fetch_assoc();

    if (empty($doc['ruta']) || !file_exists($doc['ruta'])) { http_response_code(404); die('Archivo no encontrado en el servidor'); }

    $filename = $doc['nombre_original'] ?? $doc['nombre_archivo'] ?? 'documento';
    $mime = $doc['mime_type'] ?? mime_content_type($doc['ruta']) ?: 'application/octet-stream';
    $size = $doc['tamano'] ?? filesize($doc['ruta']);

    while (ob_get_level()) ob_end_clean();

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename) . '; filename="' . rawurlencode($filename) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $size);
    readfile($doc['ruta']);
    exit;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B','KB','MB','GB','TB'];
    $bytes = max((int)$bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    if ($pow > 0) $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
