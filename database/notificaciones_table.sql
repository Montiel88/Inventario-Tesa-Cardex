-- Tabla de notificaciones del sistema TESA
-- Almacena notificaciones para cada usuario

CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL COMMENT 'success, error, warning, info',
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `url` varchar(255) DEFAULT NULL COMMENT 'URL para redirigir al hacer click',
  `leida` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=no leída, 1=leída',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `leida` (`leida`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para mejorar rendimiento
ALTER TABLE `notificaciones`
  ADD KEY `idx_usuario_leida` (`usuario_id`, `leida`);
