<?php
session_start();
ini_set('display_errors', 0);
require_once __DIR__ . '/../config/permisos.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 1) {
    http_response_code(403);
    exit('No autorizado');
}

$file = $_GET['file'] ?? '';
$file = basename($file); // evitar path traversal
$backup_dir = __DIR__ . '/../backups/';
$filepath = $backup_dir . $file;

if (!file_exists($filepath) || !is_file($filepath)) {
    http_response_code(404);
    exit('Archivo no encontrado');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
?>
