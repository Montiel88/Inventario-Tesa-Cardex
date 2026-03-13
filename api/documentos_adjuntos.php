<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

if ($_SESSION['user_rol'] != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permisos']);
    exit();
}

require_once '../../config/database.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'subir':
        subirDocumento();
        break;
    case 'listar':
        listarDocumentos();
        break;
    case 'eliminar':
        eliminarDocumento();
        break;
    case 'descargar':
        descargarDocumento();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
}

function subirDocumento() {
    global $conn;
    
    $equipo_id = isset($_POST['equipo_id']) ? intval($_POST['equipo_id']) : null;
    $persona_id = isset($_POST['persona_id']) ? intval($_POST['persona_id']) : null;
    $tipo_documento = $_POST['tipo_documento'] ?? 'otro';
    $descripcion = $_POST['descripcion'] ?? '';
    
    if (!$equipo_id && !$persona_id) {
        echo json_encode(['error' => 'Debe especificar equipo o persona']);
        return;
    }
    
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Error al subir archivo']);
        return;
    }
    
    $archivo = $_FILES['archivo'];
    $ext = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $mime = $archivo['type'];
    
    // Tipos permitidos
    $permitidos = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx'];
    if (!in_array(strtolower($ext), $permitidos)) {
        echo json_encode(['error' => 'Tipo de archivo no permitido']);
        return;
    }
    
    // Tamaño máximo 10MB
    if ($archivo['size'] > 10 * 1024 * 1024) {
        echo json_encode(['error' => 'El archivo excede los 10MB']);
        return;
    }
    
    // Crear directorio si no existe
    $uploadDir = '../../uploads/documentos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generar nombre único
    $nombreOriginal = $archivo['name'];
    $nombreArchivo = uniqid('doc_') . '_' . time() . '.' . $ext;
    $ruta = $uploadDir . $nombreArchivo;
    
    if (move_uploaded_file($archivo['tmp_name'], $ruta)) {
        $sql = "INSERT INTO documentos_adjuntos 
                (equipo_id, persona_id, tipo_documento, nombre_original, nombre_archivo, ruta, tamano, mime_type, descripcion, usuario_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iissssissi', 
            $equipo_id, 
            $persona_id, 
            $tipo_documento, 
            $nombreOriginal, 
            $nombreArchivo, 
            $ruta, 
            $archivo['size'], 
            $mime, 
            $descripcion, 
            $_SESSION['user_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'id' => $stmt->insert_id,
                'mensaje' => 'Documento subido correctamente'
            ]);
        } else {
            echo json_encode(['error' => 'Error al guardar en base de datos']);
        }
    } else {
        echo json_encode(['error' => 'Error al mover el archivo']);
    }
}

function listarDocumentos() {
    global $conn;
    
    $equipo_id = isset($_GET['equipo_id']) ? intval($_GET['equipo_id']) : null;
    $persona_id = isset($_GET['persona_id']) ? intval($_GET['persona_id']) : null;
    
    $where = [];
    if ($equipo_id) $where[] = "equipo_id = $equipo_id";
    if ($persona_id) $where[] = "persona_id = $persona_id";
    
    if (empty($where)) {
        echo json_encode(['error' => 'Parámetros insuficientes']);
        return;
    }
    
    $sql = "SELECT d.*, u.nombre as usuario_nombre 
            FROM documentos_adjuntos d 
            LEFT JOIN usuarios u ON d.usuario_id = u.id 
            WHERE " . implode(' OR ', $where) . "
            ORDER BY d.created_at DESC";
    
    $result = $conn->query($sql);
    $documentos = [];
    
    while ($row = $result->fetch_assoc()) {
        $row['fecha'] = date('d/m/Y H:i', strtotime($row['created_at']));
        $row['tamano_format'] = formatBytes($row['tamano']);
        $documentos[] = $row;
    }
    
    echo json_encode($documentos);
}

function eliminarDocumento() {
    global $conn;
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    
    if (!$id) {
        echo json_encode(['error' => 'ID no válido']);
        return;
    }
    
    // Obtener ruta del archivo
    $sql = "SELECT ruta FROM documentos_adjuntos WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        echo json_encode(['error' => 'Documento no encontrado']);
        return;
    }
    
    $doc = $result->fetch_assoc();
    
    // Eliminar archivo físico
    if (file_exists($doc['ruta'])) {
        unlink($doc['ruta']);
    }
    
    // Eliminar de base de datos
    if ($conn->query("DELETE FROM documentos_adjuntos WHERE id = $id")) {
        echo json_encode(['success' => true, 'mensaje' => 'Documento eliminado']);
    } else {
        echo json_encode(['error' => 'Error al eliminar']);
    }
}

function descargarDocumento() {
    global $conn;
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    if (!$id) {
        die('ID no válido');
    }
    
    $sql = "SELECT * FROM documentos_adjuntos WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        die('Documento no encontrado');
    }
    
    $doc = $result->fetch_assoc();
    
    if (!file_exists($doc['ruta'])) {
        die('Archivo no encontrado en servidor');
    }
    
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $doc['mime_type']);
    header('Content-Disposition: attachment; filename="' . $doc['nombre_original'] . '"');
    header('Content-Length: ' . $doc['tamano']);
    readfile($doc['ruta']);
    exit;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
