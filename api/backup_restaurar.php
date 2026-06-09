<?php
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permisos.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$file = $_POST['file'] ?? '';
$file = basename($file);
$backup_dir = __DIR__ . '/../backups/';
$filepath = $backup_dir . $file;

if (!file_exists($filepath)) {
    echo json_encode(['success' => false, 'error' => 'Archivo no encontrado']);
    exit;
}

$sql = file_get_contents($filepath);
if ($sql === false) {
    echo json_encode(['success' => false, 'error' => 'No se pudo leer el archivo']);
    exit;
}

$queries = explode(";\n", $sql);
$success_count = 0;

$conn->begin_transaction();
try {
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query === '') {
            continue;
        }
        if (!$conn->query($query)) {
            throw new Exception("Error en consulta: " . $conn->error);
        }
        $success_count++;
    }
    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Restauración completada. $success_count consultas ejecutadas."]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
