<?php
/**
 * Helper de Notificaciones del Sistema TESA
 * Permite registrar notificaciones en la base de datos para mostrar al usuario
 */

/**
 * Registra una notificación en la base de datos
 * 
 * @param int $usuario_id ID del usuario que recibe la notificación
 * @param string $tipo Tipo de notificación (success, error, warning, info)
 * @param string $titulo Título corto de la notificación
 * @param string $mensaje Mensaje detallado
 * @param string|null $url URL opcional para redirigir al hacer click
 * @return bool true si se registró correctamente, false si falló
 */
function registrar_notificacion($usuario_id, $tipo, $titulo, $mensaje, $url = null) {
    global $conn;
    
    if (!$conn) {
        return false;
    }
    
    $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, url, leida, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
    $stmt->bind_param("issss", $usuario_id, $tipo, $titulo, $mensaje, $url);
    
    return $stmt->execute();
}

/**
 * Obtiene las notificaciones no leídas de un usuario
 * 
 * @param int $usuario_id ID del usuario
 * @return array Lista de notificaciones
 */
function obtener_notificaciones_pendientes($usuario_id) {
    global $conn;
    
    if (!$conn) {
        return [];
    }
    
    $stmt = $conn->prepare("SELECT id, tipo, titulo, mensaje, url, created_at FROM notificaciones WHERE usuario_id = ? AND leida = 0 ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notificaciones = [];
    while ($row = $result->fetch_assoc()) {
        $notificaciones[] = $row;
    }
    
    return $notificaciones;
}

/**
 * Marca una notificación como leída
 * 
 * @param int $notificacion_id ID de la notificación
 * @param int $usuario_id ID del usuario (para validación)
 * @return bool true si se actualizó correctamente
 */
function marcar_notificacion_leida($notificacion_id, $usuario_id) {
    global $conn;
    
    if (!$conn) {
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $notificacion_id, $usuario_id);
    
    return $stmt->execute();
}

/**
 * Marca todas las notificaciones de un usuario como leídas
 * 
 * @param int $usuario_id ID del usuario
 * @return bool true si se actualizó correctamente
 */
function marcar_todas_leidas($usuario_id) {
    global $conn;
    
    if (!$conn) {
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?");
    $stmt->bind_param("i", $usuario_id);
    
    return $stmt->execute();
}

/**
 * Cuenta las notificaciones no leídas de un usuario
 * 
 * @param int $usuario_id ID del usuario
 * @return int Cantidad de notificaciones pendientes
 */
function contar_notificaciones_pendientes($usuario_id) {
    global $conn;
    
    if (!$conn) {
        return 0;
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leida = 0");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return (int)$row['total'];
}
?>
