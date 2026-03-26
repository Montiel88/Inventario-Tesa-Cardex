<?php
// includes/logs_functions.php
if (!function_exists('registrarLog')) {
    /**
     * Registra una acción en la tabla de logs
     * @param mysqli $conn Conexión a la base de datos
     * @param string $accion Nombre de la acción (ej: "Inicio de sesión", "Crear producto")
     * @param string $detalle Detalle adicional de la acción
     * @param int|null $usuario_id ID del usuario (opcional, se toma de la sesión si no se envía)
     * @return bool True si se insertó correctamente, false en caso contrario
     */
    function registrarLog($conn, $accion, $detalle = '', $usuario_id = null) {
        // Si no se pasa usuario_id, intentar obtenerlo de la sesión
        if ($usuario_id === null && isset($_SESSION['user_id'])) {
            $usuario_id = $_SESSION['user_id'];
        }

        // Obtener IP del cliente
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Preparar y ejecutar la inserción
        $stmt = $conn->prepare("INSERT INTO logs (usuario_id, accion, detalle, ip, fecha) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("isss", $usuario_id, $accion, $detalle, $ip);
            $resultado = $stmt->execute();
            $stmt->close();
            return $resultado;
        }
        return false;
    }
}
?>