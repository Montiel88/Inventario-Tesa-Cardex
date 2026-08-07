<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('html_errors', '0');

header('X-Content-Type-Options: nosniff');
header_remove('X-Powered-By');

function responderJsonActa($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

set_exception_handler(function ($e) {
    responderJsonActa([
        'success' => false,
        'message' => 'Excepción del servidor: ' . $e->getMessage(),
        'file' => $e->getFile() . ':' . $e->getLine(),
    ], 500);
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    responderJsonActa([
        'success' => false,
        'message' => 'Error PHP: ' . $message,
        'file' => $file . ':' . $line,
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
            'success' => false,
            'message' => 'Error fatal: ' . $err['message'],
            'file' => $err['file'] . ':' . $err['line'],
            'output' => $out ? mb_substr($out, 0, 300) : null,
        ], JSON_UNESCAPED_UNICODE);
    }
});
ob_start();

session_start();
if (!isset($_SESSION['user_id'])) {
    responderJsonActa(['success' => false, 'message' => 'Sesión no iniciada'], 401);
}
$es_admin = ($_SESSION['user_rol'] == 1);
if (!$es_admin) {
    responderJsonActa(['success' => false, 'message' => 'Sin permisos (solo administradores)'], 403);
}

require_once __DIR__ . '/../config/database.php';

// ====== AUTO-INSTALL: Tablas + columnas + carpeta ======
function autoInstalarActas(&$conn) {
    // 1) Columna acta_firmada en movimientos
    $cols_mov = [];
    $rc = $conn->query("SHOW COLUMNS FROM `movimientos`");
    if ($rc) while ($r = $rc->fetch_assoc()) $cols_mov[$r['Field']] = true;
    if (empty($cols_mov['acta_firmada'])) {
        @$conn->query("ALTER TABLE `movimientos` ADD `acta_firmada` VARCHAR(500) NULL");
    }
    if (empty($cols_mov['acta_firmada_at'])) {
        @$conn->query("ALTER TABLE `movimientos` ADD `acta_firmada_at` DATETIME NULL");
    }
    if (empty($cols_mov['acta_firmada_user'])) {
        @$conn->query("ALTER TABLE `movimientos` ADD `acta_firmada_user` INT UNSIGNED NULL");
    }

    // 2) Tabla actas + columna archivo_firmado (si existen actas)
    $t = $conn->query("SHOW TABLES LIKE 'actas'");
    $existeActas = $t && $t->num_rows > 0;
    if ($existeActas) {
        $cols_a = [];
        $rc2 = $conn->query("SHOW COLUMNS FROM `actas`");
        if ($rc2) while ($r = $rc2->fetch_assoc()) $cols_a[$r['Field']] = true;
        if (empty($cols_a['archivo_firmado'])) {
            @$conn->query("ALTER TABLE `actas` ADD `archivo_firmado` VARCHAR(500) NULL");
        }
    }

    // 3) Carpeta uploads/actas_firmadas + htaccess
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'actas_firmadas';
    if (!is_dir($base)) @mkdir($base, 0755, true);
    if (is_dir($base)) {
        $ht = $base . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($ht)) {
            @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|php5)$\">\nRequire all denied\n</FilesMatch>\nAddType application/pdf .pdf\n");
        }
        if (!file_exists($base . DIRECTORY_SEPARATOR . 'index.html')) {
            @file_put_contents($base . DIRECTORY_SEPARATOR . 'index.html', '<!-- empty -->');
        }
    }
}
autoInstalarActas($conn);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responderJsonActa(['success' => false, 'message' => 'Método no permitido (usa POST)'], 405);
}

$acta_id = intval($_POST['acta_id'] ?? 0);
$movimiento_id = intval($_POST['movimiento_id'] ?? 0);

if ($acta_id <= 0 && $movimiento_id <= 0) {
    responderJsonActa(['success' => false, 'message' => 'ID no válido: envía acta_id o movimiento_id'], 400);
}

if (!isset($_FILES['archivo_firmado']) || !is_array($_FILES['archivo_firmado'])) {
    responderJsonActa(['success' => false, 'message' => 'No se recibió archivo (campo requerido: archivo_firmado)'], 400);
}
$archivo = $_FILES['archivo_firmado'];
$err = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
if ($err !== UPLOAD_ERR_OK) {
    $mensajes = [
        UPLOAD_ERR_INI_SIZE => 'Archivo excede el límite de php.ini (upload_max_filesize)',
        UPLOAD_ERR_FORM_SIZE => 'Archivo excede el límite del formulario (post_max_size)',
        UPLOAD_ERR_PARTIAL => 'Subida parcial',
        UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal de PHP',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir en disco',
        UPLOAD_ERR_EXTENSION => 'Subida detenida por extensión PHP',
    ];
    responderJsonActa(['success' => false, 'message' => 'Error al subir archivo: ' . ($mensajes[$err] ?? ('código ' . $err))], 400);
}
if (!is_uploaded_file($archivo['tmp_name'])) {
    responderJsonActa(['success' => false, 'message' => 'Archivo no subido por HTTP'], 400);
}

$filename = $archivo['name'] ?? '';
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$allowed = ['pdf'];
if (!in_array($ext, $allowed, true)) {
    responderJsonActa(['success' => false, 'message' => 'Solo se permiten archivos PDF (subiste .' . htmlspecialchars($ext) . ')'], 400);
}

$tamano = (int)($archivo['size'] ?? 0);
if ($tamano > 15 * 1024 * 1024) {
    responderJsonActa(['success' => false, 'message' => 'El PDF excede los 15MB'], 400);
}

// Validar que sea realmente un PDF (primeros bytes)
$handle = @fopen($archivo['tmp_name'], 'rb');
if ($handle) {
    $primeros = fread($handle, 4);
    fclose($handle);
    if ($primeros === false || strpos($primeros, '%PDF') !== 0) {
        responderJsonActa(['success' => false, 'message' => 'El archivo no parece ser un PDF válido (no empieza con %PDF).'], 400);
    }
}

$carpetaDestinoAbs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'actas_firmadas' . DIRECTORY_SEPARATOR;
if (!is_dir($carpetaDestinoAbs) && !@mkdir($carpetaDestinoAbs, 0755, true)) {
    responderJsonActa(['success' => false, 'message' => 'No se pudo crear la carpeta uploads/actas_firmadas (verifica permisos)'], 500);
}

$identificador = $acta_id > 0 ? 'acta_' . $acta_id : 'mov_' . $movimiento_id;
$base = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)pathinfo($filename, PATHINFO_FILENAME));
if ($base === '') $base = 'acta';
$nuevo_nombre = 'firmado_' . $identificador . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '_' . time() . '.pdf';
$ruta_final_abs = $carpetaDestinoAbs . $nuevo_nombre;
$ruta_final_rel = 'uploads/actas_firmadas/' . $nuevo_nombre;

if (!@move_uploaded_file($archivo['tmp_name'], $ruta_final_abs)) {
    responderJsonActa(['success' => false, 'message' => 'Error al mover el archivo al servidor (verifica permisos en uploads/actas_firmadas)'], 500);
}
@chmod($ruta_final_abs, 0644);

$ok = true;
$mensajes = [];
$now = date('Y-m-d H:i:s');
$uid = $_SESSION['user_id'] ?? null;

if ($acta_id > 0) {
    $stmt = $conn->prepare("UPDATE actas SET archivo_firmado = ? WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('si', $ruta_final_rel, $acta_id);
        if (!$stmt->execute()) {
            $ok = false;
            $mensajes[] = 'No se pudo actualizar actas: ' . $stmt->error;
        }
    } else {
        $ok = false;
        $mensajes[] = 'Prepare actas falló: ' . $conn->error;
    }
}

if ($movimiento_id > 0) {
    $stmt = $conn->prepare("UPDATE movimientos SET acta_firmada = ?, acta_firmada_at = ?, acta_firmada_user = ? WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ssii', $ruta_final_rel, $now, $uid, $movimiento_id);
        if (!$stmt->execute()) {
            $ok = false;
            $mensajes[] = 'No se pudo actualizar movimientos: ' . $stmt->error;
        }
    } else {
        $ok = false;
        $mensajes[] = 'Prepare movimientos falló: ' . $conn->error;
    }
}

if (!$ok) {
    responderJsonActa(['success' => false, 'message' => implode('. ', $mensajes)], 500);
}

responderJsonActa([
    'success' => true,
    'message' => 'Archivo subido correctamente',
    'ruta' => $ruta_final_rel,
    'movimiento_id' => $movimiento_id ?: null,
    'acta_id' => $acta_id ?: null,
], 200);
