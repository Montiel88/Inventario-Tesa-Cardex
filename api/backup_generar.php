<?php
session_start();
ini_set('display_errors', 0); // evitar contaminar JSON
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permisos.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Configuración
$backup_dir = __DIR__ . '/../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}
if (!is_dir($backup_dir) || !is_writable($backup_dir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'El directorio de backups no es escribible']);
    exit;
}

$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
$filepath = $backup_dir . $filename;

// Intentar usar mysqldump (más rápido)
$mysql_config = [
    'host' => $host,
    'user' => $user,
    'pass' => $password,
    'db'   => $database
];

// Buscar mysqldump en PATH o en rutas comunes de XAMPP
$mysqldump_path = null;
$possible_paths = [
    'C:\\xampp\\mysql\\bin\\mysqldump.exe',
    'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
    'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
    'C:\\Program Files (x86)\\MySQL\\MySQL Server 5.5\\bin\\mysqldump.exe',
    'mysqldump' // asumir que está en PATH
];

foreach ($possible_paths as $p) {
    if ($p === 'mysqldump') {
        $found = trim((string) shell_exec('where mysqldump 2>nul'));
        if ($found !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $found);
            $mysqldump_path = $lines[0];
            break;
        }
    } elseif (file_exists($p)) {
        $mysqldump_path = $p;
        break;
    }
}

$success = false;
$error = '';

if ($mysqldump_path) {
    // Usar mysqldump con --result-file para no mezclar stdout/stderr
    $output = [];
    // En Windows las comillas simples no funcionan, construimos con comillas dobles
    $cmd = sprintf(
        '"%s" --host="%s" --user="%s" --password="%s" --single-transaction --quick --routines --events --result-file="%s" "%s" 2>&1',
        $mysqldump_path,
        $mysql_config['host'],
        $mysql_config['user'],
        $mysql_config['pass'],
        $filepath,
        $mysql_config['db']
    );

    exec($cmd, $output, $return_var);
    if ($return_var === 0 && file_exists($filepath) && filesize($filepath) > 0) {
        $success = true;
    } else {
        $error = "mysqldump falló: " . implode("\n", $output);
    }
} else {
    $error = 'mysqldump no encontrado en el sistema';
}

if (!$success) {
    // Fallback: exportar con PHP (más lento pero seguro)
    $success = exportarConPHP($filepath, $error);
    if (!$success && empty($error)) {
        $error = "Error al exportar con PHP";
    }
}

if ($success) {
    echo json_encode(['success' => true, 'filename' => $filename, 'message' => 'Backup generado correctamente']);
} else {
    echo json_encode(['success' => false, 'error' => $error]);
}

function exportarConPHP($filepath, &$error = null) {
    global $conn;

    $tables = [];
    $result = $conn->query("SHOW TABLES");
    if ($result === false) {
        $error = "No se pudieron obtener las tablas: " . $conn->error;
        return false;
    }
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    $sql = "-- Backup generado el " . date('Y-m-d H:i:s') . "\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        // Estructura
        $createRes = $conn->query("SHOW CREATE TABLE `$table`");
        if ($createRes === false) {
            $error = "No se pudo obtener la estructura de $table: " . $conn->error;
            return false;
        }
        $create = $createRes->fetch_row();
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $create[1] . ";\n\n";
        
        // Datos
        $res = $conn->query("SELECT * FROM `$table`");
        if ($res === false) {
            $error = "No se pudieron obtener datos de $table: " . $conn->error;
            return false;
        }
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        if (count($rows) > 0) {
            $columns = array_keys($rows[0]);
            $sql .= "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES\n";
            $values = [];
            foreach ($rows as $row) {
                $escaped = array_map(function($v) use ($conn) {
                    return $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'";
                }, $row);
                $values[] = "(" . implode(", ", $escaped) . ")";
            }
            $sql .= implode(",\n", $values) . ";\n\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    if (file_put_contents($filepath, $sql) === false) {
        $error = "No se pudo escribir el archivo de respaldo.";
        return false;
    }
    
    return true;
}
?>
