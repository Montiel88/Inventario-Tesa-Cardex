<?php

class NotificadorEmail {
    private $config = null;
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->cargarConfiguracion();
    }
    
    private function cargarConfiguracion() {
        $sql = "SELECT * FROM configuraciones_email WHERE activo = 1 LIMIT 1";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $this->config = $result->fetch_assoc();
        }
    }
    
    public function estaActivo() {
        return $this->config !== null;
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    public function guardarConfiguracion($datos) {
        // Encriptar contraseña
        $password = password_hash($datos['smtp_password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO configuraciones_email 
                (smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, 
                 email_from, email_from_nombre, notificar_asignacion, notificar_devolucion, 
                 notificar_vencimiento, dias_antes_vencimiento, activo, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                smtp_host = VALUES(smtp_host), smtp_port = VALUES(smtp_port),
                smtp_username = VALUES(smtp_username), smtp_password = VALUES(smtp_password),
                smtp_encryption = VALUES(smtp_encryption), email_from = VALUES(email_from),
                email_from_nombre = VALUES(email_from_nombre), notificar_asignacion = VALUES(notificar_asignacion),
                notificar_devolucion = VALUES(notificar_devolucion), notificar_vencimiento = VALUES(notificar_vencimiento),
                dias_antes_vencimiento = VALUES(dias_antes_vencimiento), activo = VALUES(activo),
                updated_at = NOW()";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sisssssiiiii',
            $datos['smtp_host'],
            $datos['smtp_port'],
            $datos['smtp_username'],
            $password,
            $datos['smtp_encryption'],
            $datos['email_from'],
            $datos['email_from_nombre'],
            $datos['notificar_asignacion'],
            $datos['notificar_devolucion'],
            $datos['notificar_vencimiento'],
            $datos['dias_antes_vencimiento']
        );
        
        return $stmt->execute();
    }
    
    public function enviarEmail($destino, $asunto, $mensaje, $tipo = 'general') {
        if (!$this->config) {
            return ['success' => false, 'error' => 'Configuración SMTP no disponible'];
        }
        
        // Usar mail() de PHP por defecto si no hay SMTP configurado
        // En un entorno de producción, usar PHPMailer
        
        $headers = "From: {$this->config['email_from_nombre']} <{$this->config['email_from']}>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $result = mail($destino, $asunto, $mensaje, $headers);
        
        // Registrar en bitácora
        $this->registrarNotificacion($tipo, $asunto, $mensaje, $destino, $result);
        
        return ['success' => $result, 'error' => $result ? null : 'Error al enviar email'];
    }
    
    public function notificarAsignacion($persona, $equipo) {
        if (!$this->config || !$this->config['notificar_asignacion']) {
            return false;
        }
        
        if (empty($persona['email'])) {
            return false;
        }
        
        $asunto = "Nuevo equipo asignado - {$equipo['codigo_barras']}";
        $mensaje = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #5a2d8c; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>INSTITUTO TECNOLÓGICO SAN ANTONIO - TESA</h2>
                </div>
                <div class='content'>
                    <h3>Nuevo Equipo Asignado</h3>
                    <p>Hola <strong>{$persona['nombres']}</strong>,</p>
                    <p>Se te ha asignado un nuevo equipo:</p>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Código:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['codigo_barras']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Tipo:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['tipo_equipo']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Marca:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['marca']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Modelo:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['modelo']}</td>
                        </tr>
                    </table>
                    <p>Por favor, revisa el equipo y reporta cualquier anomalía.</p>
                </div>
                <div class='footer'>
                    Sistema de Gestión de Inventario TESA
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->enviarEmail($persona['email'], $asunto, $mensaje, 'asignacion');
    }
    
    public function notificarDevolucion($persona, $equipo) {
        if (!$this->config || !$this->config['notificar_devolucion']) {
            return false;
        }
        
        if (empty($persona['email'])) {
            return false;
        }
        
        $asunto = "Equipo devuelto - {$equipo['codigo_barras']}";
        $mensaje = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>INSTITUTO TECNOLÓGICO SAN ANTONIO - TESA</h2>
                </div>
                <div class='content'>
                    <h3>Equipo Devuelto</h3>
                    <p>Hola <strong>{$persona['nombres']}</strong>,</p>
                    <p>Has devuelto el siguiente equipo:</p>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Código:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['codigo_barras']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Tipo:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['tipo_equipo']}</td>
                        </tr>
                    </table>
                    <p>Gracias por cuidar el equipo.</p>
                </div>
                <div class='footer'>
                    Sistema de Gestión de Inventario TESA
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->enviarEmail($persona['email'], $asunto, $mensaje, 'devolucion');
    }
    
    public function notificarVencimiento($persona, $equipo, $dias_vencimiento) {
        if (!$this->config || !$this->config['notificar_vencimiento']) {
            return false;
        }
        
        if (empty($persona['email'])) {
            return false;
        }
        
        $asunto = "Recordatorio: Préstamo próximo a vencer - {$equipo['codigo_barras']}";
        $mensaje = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #ffc107; color: #333; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>INSTITUTO TECNOLÓGICO SAN ANTONIO - TESA</h2>
                </div>
                <div class='content'>
                    <h3>Recordatorio de Préstamo</h3>
                    <p>Hola <strong>{$persona['nombres']}</strong>,</p>
                    <p>Tu préstamo del equipo vence en <strong>{$dias_vencimiento} día(s)</strong>:</p>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Código:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['codigo_barras']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Tipo:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['tipo_equipo']}</td>
                        </tr>
                    </table>
                    <p>Por favor, coordina la devolución o renovación del préstamo.</p>
                </div>
                <div class='footer'>
                    Sistema de Gestión de Inventario TESA
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->enviarEmail($persona['email'], $asunto, $mensaje, 'vencimiento');
    }
    
    private function registrarNotificacion($tipo, $titulo, $mensaje, $email_destino, $enviado) {
        $sql = "INSERT INTO notificaciones (tipo, titulo, mensaje, email_destino, enviado, fecha_envio, created_at)
                VALUES (?, ?, ?, ?, ?, " . ($enviado ? 'NOW()' : 'NULL') . ", NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssi', $tipo, $titulo, $mensaje, $email_destino, $enviado);
        $stmt->execute();
    }
    
    public function verificarPrestamosVencidos() {
        if (!$this->config || !$this->config['notificar_vencimiento']) {
            return [];
        }
        
        $dias = $this->config['dias_antes_vencimiento'];
        
        $sql = "SELECT a.*, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo,
                       p.nombres, p.email
                FROM asignaciones a
                JOIN equipos e ON a.equipo_id = e.id
                JOIN personas p ON a.persona_id = p.id
                WHERE a.fecha_devolucion IS NULL 
                AND DATEDIFF(NOW(), a.fecha_asignacion) >= 30
                AND p.email IS NOT NULL";
        
        $result = $this->conn->query($sql);
        $notificados = [];
        
        while ($row = $result->fetch_assoc()) {
            $dias_transcurridos = floor((time() - strtotime($row['fecha_asignacion'])) / (60 * 60 * 24));
            
            if ($dias_transcurridos >= 30) {
                $this->notificarVencimiento(
                    ['nombres' => $row['nombres'], 'email' => $row['email']],
                    $row,
                    $dias_transcurridos
                );
                $notificados[] = $row['codigo_barras'];
            }
        }
        
        return $notificados;
    }
}
