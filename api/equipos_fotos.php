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
        subirFoto();
        break;
    case 'listar':
        listarFotos();
        break;
    case 'eliminar':
        eliminarFoto();
        break;
    case 'set_principal':
        setPrincipal();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
}

function subirFoto() {
    global $conn;
    
    $equipo_id = isset($_POST['equipo_id']) ? intval($_POST['equipo_id']) : null;
    $descripcion = $_POST['descripcion'] ?? '';
    
    if (!$equipo_id) {
        echo json_encode(['error' => 'ID de equipo requerido']);
        return;
    }
    
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Error al subir archivo']);
        return;
    }
    
    $archivo = $_FILES['foto'];
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $mime = $archivo['type'];
    
    // Tipos permitidos
    $permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $permitidos)) {
        echo json_encode(['error' => 'Tipo de archivo no permitido']);
        return;
    }
    
    // Tamaño máximo 5MB
    if ($archivo['size'] > 5 * 1024 * 1024) {
        echo json_encode(['error' => 'El archivo excede los 5MB']);
        return;
    }
    
    // Crear directorio si no existe
    $uploadDir = '../../uploads/equipos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Verificar si es la primera foto (será principal)
    $check = $conn->query("SELECT COUNT(*) as total FROM equipos_fotos WHERE equipo_id = $equipo_id");
    $es_principal = $check->fetch_assoc()['total'] == 0;
    
    // Generar nombre único
    $nombreArchivo = uniqid('eq_') . '_' . time() . '.' . $ext;
    $ruta = $uploadDir . $nombreArchivo;
    
    if (move_uploaded_file($archivo['tmp_name'], $ruta)) {
        $sql = "INSERT INTO equipos_fotos 
                (equipo_id, nombre_archivo, ruta, descripcion, es_principal, usuario_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isssii', 
            $equipo_id, 
            $archivo['name'], 
            $ruta, 
            $descripcion, 
            $es_principal,
            $_SESSION['user_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'id' => $stmt->insert_id,
                'mensaje' => $es_principal ? 'Foto principal guardada' : 'Foto guardada correctamente'
            ]);
        } else {
            echo json_encode(['error' => 'Error al guardar en base de datos']);
        }
    } else {
        echo json_encode(['error' => 'Error al mover el archivo']);
    }
}

function listarFotos() {
    global $conn;
    
    $equipo_id = isset($_GET['equipo_id']) ? intval($_GET['equipo_id']) : null;
    
    if (!$equipo_id) {
        echo json_encode(['error' => 'ID de equipo requerido']);
        return;
    }
    
    $sql = "SELECT f.*, u.nombre as usuario_nombre 
            FROM equipos_fotos f 
            LEFT JOIN usuarios u ON f.usuario_id = u.id 
            WHERE f.equipo_id = $equipo_id
            ORDER BY f.es_principal DESC, f.created_at DESC";
    
    $result = $conn->query($sql);
    $fotos = [];
    
    while ($row = $result->fetch_assoc()) {
        // Convertir ruta relativa para URL
        $row['url'] = str_replace('../../', '/inventario_ti/', $row['ruta']);
        $row['fecha'] = date('d/m/Y H:i', strtotime($row['created_at']));
        $fotos[] = $row;
    }
    
    echo json_encode($fotos);
}

function eliminarFoto() {
    global $conn;
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    
    if (!$id) {
        echo json_encode(['error' => 'ID no válido']);
        return;
    }
    
    // Obtener ruta del archivo
    $sql = "SELECT ruta, equipo_id FROM equipos_fotos WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        echo json_encode(['error' => 'Foto no encontrada']);
        return;
    }
    
    $foto = $result->fetch_assoc();
    
    // Eliminar archivo físico
    if (file_exists($foto['ruta'])) {
        unlink($foto['ruta']);
    }
    
    // Eliminar de base de datos
    if ($conn->query("DELETE FROM equipos_fotos WHERE id = $id")) {
        // Si era principal, asignar otra como principal
        if ($conn->affected_rows > 0) {
            $conn->query("UPDATE equipos_fotos SET es_principal = 1 
                         WHERE equipo_id = {$foto['equipo_id']} 
                         AND id = (SELECT id FROM (SELECT id FROM equipos_fotos WHERE equipo_id = {$foto['equipo_id']} ORDER BY created_at ASC LIMIT 1) AS t)");
        }
        echo json_encode(['success' => true, 'mensaje' => 'Foto eliminada']);
    } else {
        echo json_encode(['error' => 'Error al eliminar']);
    }
}

function setPrincipal() {
    global $conn;
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    
    if (!$id) {
        echo json_encode(['error' => 'ID no válido']);
        return;
    }
    
    // Obtener equipo_id
    $sql = "SELECT equipo_id FROM equipos_fotos WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        echo json_encode(['error' => 'Foto no encontrada']);
        return;
    }
    
    $equipo_id = $result->fetch_assoc()['equipo_id'];
    
    // Quitar principal de todas
    $conn->query("UPDATE equipos_fotos SET es_principal = 0 WHERE equipo_id = $equipo_id");
    
    // Establecer nueva principal
    $conn->query("UPDATE equipos_fotos SET es_principal = 1 WHERE id = $id");
    
    echo json_encode(['success' => true, 'mensaje' => 'Foto principal actualizada']);
}
