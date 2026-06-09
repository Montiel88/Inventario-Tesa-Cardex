<?php
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/permisos.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$backup_dir = __DIR__ . '/../backups/';
$files = [];

if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

if (!is_dir($backup_dir) || !is_readable($backup_dir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'No se puede leer el directorio de backups']);
    exit;
}

$scanned = scandir($backup_dir);
foreach ($scanned as $file) {
    if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
        $files[] = [
            'name' => $file,
            'size' => filesize($backup_dir . $file),
            'date' => date('Y-m-d H:i:s', filemtime($backup_dir . $file))
        ];
    }
}
usort($files, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

echo json_encode(['success' => true, 'backups' => $files]);
?>
