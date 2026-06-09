<?php
// includes/logs.php
if (!function_exists('registrarLog')) {
    function registrarLog($conn, $accion, $detalle = '', $usuario_id = null) {
        // Si no se pasa usuario_id, intentar obtenerlo de la sesión
        if ($usuario_id === null && isset($_SESSION['usuario_id'])) {
            $usuario_id = $_SESSION['usuario_id'];
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $stmt = $conn->prepare("INSERT INTO logs (usuario_id, accion, detalle, ip, fecha) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $usuario_id, $accion, $detalle, $ip);
        return $stmt->execute();
    }
}
?>