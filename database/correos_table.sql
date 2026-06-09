-- Tabla para almacenar el historial de correos enviados
CREATE TABLE IF NOT EXISTS correos_enviados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL,
    asignacion_id INT DEFAULT NULL,
    componente_id INT DEFAULT NULL,
    tipo_motivo VARCHAR(50) NOT NULL COMMENT 'vencido, por_vencer, danado, manual, recordatorio',
    asunto VARCHAR(500) NOT NULL,
    mensaje TEXT NOT NULL,
    email_destino VARCHAR(255) NOT NULL,
    email_enviado TINYINT(1) DEFAULT 0 COMMENT '1=enviado, 0=fallido/no enviado',
    error_email TEXT DEFAULT NULL COMMENT 'Mensaje de error si falló el envío',
    usuario_id INT NOT NULL COMMENT 'Usuario que envió el correo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (persona_id) REFERENCES personas(id) ON DELETE CASCADE,
    FOREIGN KEY (asignacion_id) REFERENCES asignaciones(id) ON DELETE SET NULL,
    FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    INDEX idx_persona (persona_id),
    INDEX idx_asignacion (asignacion_id),
    INDEX idx_componente (componente_id),
    INDEX idx_tipo_motivo (tipo_motivo),
    INDEX idx_email_enviado (email_enviado),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de correos enviados a personas';

-- Agregar columna email_notificado a componentes si no existe
ALTER TABLE componentes 
ADD COLUMN IF NOT EXISTS email_notificado TINYINT(1) DEFAULT 0 COMMENT '1=se notificó por email el daño';
