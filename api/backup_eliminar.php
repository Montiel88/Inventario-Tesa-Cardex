<?php
session_start();
require_once '../config/permisos.php';
verificarSesion();
requiereAdmin();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$filename = basename($input['filename'] ?? '');

if (!$filename || !preg_match('/^backup_.*\.sql$/', $filename)) {
    echo json_encode(['success' => false, 'message' => 'Nombre de archivo no válido']);
    exit;
}

$filepath = __DIR__ . '/../backups/' . $filename;
if (file_exists($filepath) && unlink($filepath)) {
    echo json_encode(['success' => true, 'message' => 'Backup eliminado']);
} else {
    echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el archivo']);
}
?>