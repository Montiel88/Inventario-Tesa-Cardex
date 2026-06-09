-- Backup generado el 2026-03-27 22:51:35
-- Base de datos: inventario_ti
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `actas`;
CREATE TABLE `actas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_acta` varchar(50) NOT NULL COMMENT 'Código completo del acta (FOR-TH-04-01-202603-0001)',
  `tipo_acta` enum('entrega','devolucion','descargo') NOT NULL,
  `persona_id` int(11) NOT NULL COMMENT 'ID de la persona que recibe/entrega',
  `usuario_id` int(11) NOT NULL COMMENT 'ID del usuario que generó el acta (admin)',
  `fecha_generacion` datetime NOT NULL DEFAULT current_timestamp(),
  `archivo_pdf` varchar(255) DEFAULT NULL COMMENT 'Ruta del archivo PDF generado',
  `observaciones` text DEFAULT NULL,
  `equipos_ids` text DEFAULT NULL COMMENT 'IDs de equipos incluidos en el acta (separados por coma)',
  `secuencia` int(11) NOT NULL COMMENT 'Número de secuencia usado',
  PRIMARY KEY (`id`),
  KEY `idx_persona` (`persona_id`),
  KEY `idx_tipo` (`tipo_acta`),
  KEY `idx_fecha` (`fecha_generacion`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registro de todas las actas generadas';

INSERT INTO `actas` (`id`, `codigo_acta`, `tipo_acta`, `persona_id`, `usuario_id`, `fecha_generacion`, `archivo_pdf`, `observaciones`, `equipos_ids`, `secuencia`) VALUES
('1', 'FOR-TH-05-01-202603-0001', 'devolucion', '1', '1', '2026-03-03 12:05:52', NULL, NULL, '', '1'),
('2', 'FOR-TH-06-01-202603-0001', 'descargo', '1', '1', '2026-03-03 12:05:57', NULL, NULL, NULL, '1'),
('3', 'FOR-TH-05-01-202603-0001', 'devolucion', '1', '1', '2026-03-03 13:18:59', NULL, NULL, '', '1'),
('4', 'FOR-TH-06-01-202603-0002', 'descargo', '1', '1', '2026-03-03 13:19:21', NULL, NULL, NULL, '2'),
('5', 'FOR-TH-04-01-202603-0003', 'entrega', '1', '1', '2026-03-03 13:25:51', NULL, NULL, '', '0'),
('6', 'FOR-TH-04-01-202603-0004', 'entrega', '1', '1', '2026-03-03 13:27:49', NULL, NULL, '', '0'),
('7', 'FOR-TH-05-01-202603-0005', 'devolucion', '1', '1', '2026-03-03 13:29:54', NULL, NULL, '', '5'),
('8', 'FOR-TH-06-01-202603-0006', 'descargo', '1', '1', '2026-03-03 13:29:57', NULL, NULL, NULL, '0'),
('9', 'FOR-TH-04-01-202603-0007', 'entrega', '1', '1', '2026-03-03 13:31:46', NULL, NULL, '', '0'),
('10', 'FOR-TH-06-01-202603-0008', 'entrega', '1', '1', '2026-03-03 13:35:33', NULL, NULL, '', '0'),
('11', 'FOR-TH-05-01-202603-0009', 'devolucion', '1', '1', '2026-03-03 13:35:49', NULL, NULL, '', '9'),
('12', 'FOR-TH-06-01-202603-0010', 'descargo', '1', '1', '2026-03-03 13:36:05', NULL, NULL, NULL, '0'),
('13', 'FOR-TH-06-01-202603-0011', 'entrega', '1', '1', '2026-03-03 13:37:07', NULL, NULL, '', '0'),
('14', 'FOR-TH-06-01-202603-0012', 'entrega', '1', '1', '2026-03-03 13:44:05', NULL, NULL, '', '0'),
('15', 'FOR-TH-05-01-202603-0013', 'devolucion', '1', '1', '2026-03-03 13:44:35', NULL, NULL, '', '0'),
('16', 'FOR-TH-06-01-202603-0014', 'descargo', '1', '1', '2026-03-03 13:44:42', NULL, NULL, NULL, '0'),
('17', 'FOR-TH-06-01-202603-0015', 'entrega', '1', '1', '2026-03-03 13:50:43', NULL, NULL, '', '0'),
('18', 'FOR-TH-06-01-202603-0016', 'descargo', '1', '1', '2026-03-03 13:50:53', NULL, NULL, NULL, '0'),
('19', 'FOR-TH-05-01-202603-0017', 'devolucion', '1', '1', '2026-03-03 13:51:04', NULL, NULL, '', '0'),
('20', 'FOR-TH-06-01-202603-0018', 'descargo', '1', '1', '2026-03-03 13:52:18', NULL, NULL, NULL, '0'),
('21', 'FOR-TH-06-01-202603-0019', 'descargo', '1', '1', '2026-03-03 13:52:47', NULL, NULL, NULL, '0'),
('22', 'FOR-TH-06-01-202603-0020', 'entrega', '1', '1', '2026-03-03 13:52:53', NULL, NULL, '', '0'),
('23', 'FOR-TH-06-01-202603-0021', 'descargo', '1', '1', '2026-03-03 13:53:06', NULL, NULL, NULL, '0'),
('24', 'FOR-TH-05-01-202603-0022', 'devolucion', '1', '1', '2026-03-03 13:53:10', NULL, NULL, '', '0'),
('25', 'FOR-TH-06-01-202603-0023', 'descargo', '1', '1', '2026-03-03 13:53:50', NULL, NULL, NULL, '0'),
('26', 'FOR-TH-05-01-202603-0024', 'devolucion', '1', '1', '2026-03-03 14:05:21', NULL, NULL, '', '0'),
('27', 'FOR-TH-06-01-202603-0025', 'entrega', '1', '1', '2026-03-03 14:05:31', NULL, NULL, '', '0'),
('28', 'FOR-TH-06-01-202603-0026', 'descargo', '1', '1', '2026-03-03 14:10:18', NULL, NULL, NULL, '0'),
('29', 'FOR-TH-06-01-202603-0027', 'descargo', '1', '1', '2026-03-03 14:15:40', NULL, NULL, NULL, '0'),
('30', 'FOR-TH-06-01-202603-0028', 'descargo', '1', '1', '2026-03-03 14:18:01', NULL, NULL, NULL, '0'),
('31', 'FOR-TH-06-01-202603-0029', 'descargo', '1', '1', '2026-03-03 14:20:12', NULL, NULL, NULL, '0'),
('32', 'FOR-TH-06-01-202603-0030', 'descargo', '1', '1', '2026-03-03 14:22:20', NULL, NULL, NULL, '0'),
('33', 'FOR-TH-06-01-202603-0031', 'descargo', '1', '1', '2026-03-03 14:25:04', NULL, NULL, NULL, '0'),
('34', 'FOR-TH-06-01-202603-0032', 'entrega', '1', '1', '2026-03-03 14:33:25', NULL, NULL, '', '0'),
('35', 'FOR-TH-05-01-202603-0033', 'devolucion', '1', '1', '2026-03-03 14:33:31', NULL, NULL, '', '0'),
('36', 'FOR-TH-06-01-202603-0034', 'descargo', '1', '1', '2026-03-03 14:33:34', NULL, NULL, NULL, '0'),
('37', 'FOR-TH-05-01-202603-0035', 'devolucion', '83', '1', '2026-03-03 15:07:32', NULL, NULL, '8', '0'),
('38', 'FOR-TH-06-01-202603-0036', 'entrega', '83', '1', '2026-03-03 15:09:15', NULL, NULL, '', '0'),
('39', 'FOR-TH-04-01-202603-0001', 'devolucion', '83', '1', '2026-03-03 15:10:30', NULL, NULL, '8', '0'),
('40', 'FOR-TH-04-01-202603-0002', 'entrega', '83', '1', '2026-03-03 15:10:34', NULL, NULL, '', '0'),
('41', 'FOR-TH-06-01-202603-0003', 'descargo', '83', '1', '2026-03-03 15:13:17', NULL, NULL, NULL, '0'),
('42', 'FOR-TH-04-01-202603-0006', 'entrega', '2', '1', '2026-03-04 09:38:06', NULL, NULL, '', '0'),
('43', 'FOR-TH-04-01-202603-0007', 'devolucion', '2', '1', '2026-03-04 09:38:14', NULL, NULL, '', '0'),
('44', 'FOR-TH-06-01-202603-0008', 'descargo', '8', '1', '2026-03-04 09:44:06', NULL, NULL, NULL, '0'),
('45', 'FOR-TH-06-01-202603-0009', 'descargo', '8', '1', '2026-03-04 09:52:04', NULL, NULL, NULL, '0'),
('46', 'FOR-TH-04-01-202603-0010', 'devolucion', '8', '1', '2026-03-04 15:41:54', NULL, NULL, '', '0'),
('47', 'FOR-TH-04-01-202603-0011', 'entrega', '1', '1', '2026-03-05 12:03:29', NULL, NULL, '', '0'),
('48', 'FOR-TH-04-01-202603-0012', 'devolucion', '1', '1', '2026-03-05 12:03:42', NULL, NULL, '', '0'),
('49', 'FOR-TH-06-01-202603-0013', 'descargo', '83', '1', '2026-03-05 14:31:34', NULL, NULL, NULL, '0'),
('50', 'FOR-TH-00-01-202603-0017', '', '1', '1', '2026-03-05 15:07:36', NULL, NULL, NULL, '0'),
('51', 'FOR-TH-00-01-202603-0018', '', '1', '1', '2026-03-05 15:32:43', NULL, NULL, NULL, '0'),
('52', 'FOR-TH-00-01-202603-0021', '', '1', '1', '2026-03-05 15:51:42', NULL, NULL, NULL, '0'),
('53', 'FOR-TH-04-01-202603-0022', 'devolucion', '83', '1', '2026-03-05 15:52:34', NULL, NULL, '8', '0'),
('54', '', 'entrega', '83', '1', '2026-03-24 11:45:46', NULL, NULL, '', '0'),
('55', 'FOR-TH-04-01-202603-0023', 'devolucion', '83', '1', '2026-03-24 11:46:04', NULL, NULL, '15,13,8', '0'),
('56', 'FOR-TH-06-01-202603-0024', 'descargo', '83', '1', '2026-03-24 11:46:08', NULL, NULL, NULL, '0'),
('57', 'FOR-TH-06-01-202603-0025', 'descargo', '83', '1', '2026-03-24 11:52:38', NULL, NULL, NULL, '0'),
('58', 'FOR-TH-04-01-202603-0026', 'devolucion', '83', '1', '2026-03-24 11:52:43', NULL, NULL, '15,13,8', '0'),
('59', '', 'entrega', '83', '1', '2026-03-24 11:52:45', NULL, NULL, '', '0'),
('60', '', 'entrega', '83', '1', '2026-03-24 11:54:36', NULL, NULL, '', '0'),
('61', 'FOR-TH-01-01-202603-0027', '', '0', '1', '2026-03-26 15:50:43', NULL, NULL, NULL, '0'),
('62', 'FOR-TH-01-01-202603-0028', '', '0', '1', '2026-03-26 16:00:35', NULL, NULL, NULL, '0'),
('63', '', 'entrega', '83', '1', '2026-03-26 16:04:25', NULL, NULL, '15', '0');

DROP TABLE IF EXISTS `asignaciones`;
CREATE TABLE `asignaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipo_id` int(11) NOT NULL,
  `persona_id` int(11) NOT NULL,
  `ubicacion_id` int(11) DEFAULT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_devolucion` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipo_id` (`equipo_id`),
  KEY `persona_id` (`persona_id`),
  KEY `ubicacion_id` (`ubicacion_id`),
  CONSTRAINT `asignaciones_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asignaciones_ibfk_2` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asignaciones_ibfk_3` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `asignaciones` (`id`, `equipo_id`, `persona_id`, `ubicacion_id`, `fecha_asignacion`, `fecha_devolucion`, `observaciones`) VALUES
('10', '13', '84', NULL, '2026-03-25 22:56:37', NULL, 'Traspaso desde asignación ID: 9. '),
('12', '15', '74', NULL, '2026-03-26 17:05:04', NULL, 'Traspaso desde asignación ID: 11. ');

DROP TABLE IF EXISTS `asignaciones_componentes`;
CREATE TABLE `asignaciones_componentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `componente_id` int(11) NOT NULL,
  `persona_id` int(11) NOT NULL,
  `fecha_asignacion` datetime DEFAULT current_timestamp(),
  `fecha_devolucion` datetime DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `componente_id` (`componente_id`),
  KEY `persona_id` (`persona_id`),
  CONSTRAINT `asignaciones_componentes_ibfk_1` FOREIGN KEY (`componente_id`) REFERENCES `componentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asignaciones_componentes_ibfk_2` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `componentes`;
CREATE TABLE `componentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipo_id` int(11) DEFAULT NULL,
  `nombre_componente` varchar(100) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `especificaciones` text DEFAULT NULL,
  `estado` enum('Bueno','Regular','Malo','Por reemplazar') DEFAULT 'Bueno',
  `fecha_instalacion` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_eliminacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipo_id` (`equipo_id`),
  CONSTRAINT `componentes_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `componentes` (`id`, `equipo_id`, `nombre_componente`, `tipo`, `marca`, `modelo`, `numero_serie`, `especificaciones`, `estado`, `fecha_instalacion`, `observaciones`, `created_at`, `activo`, `fecha_eliminacion`) VALUES
('8', NULL, 'asda', 'Procesador', 'asd', 'asdas', 'dasd', 'sad', 'Bueno', '2026-03-04', '', '2026-03-04 13:31:08', '1', NULL);

DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(50) NOT NULL COMMENT 'Clave de configuración',
  `valor` text DEFAULT NULL,
  `tipo` varchar(20) DEFAULT 'texto' COMMENT 'texto, numero, fecha, etc',
  `descripcion` text DEFAULT NULL,
  `modificable` tinyint(1) DEFAULT 1 COMMENT '1=visible en panel, 0=solo sistema',
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave_unica` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `configuracion` (`id`, `clave`, `valor`, `tipo`, `descripcion`, `modificable`, `fecha_actualizacion`) VALUES
('1', 'formulario_entrega', 'FOR-TH-04', 'texto', 'Código para Acta de Entrega', '1', '2026-03-03 15:10:16'),
('2', 'formulario_devolucion', 'FOR-TH-04', 'texto', 'Código para Acta de Devolución', '1', '2026-03-03 15:10:16'),
('3', 'formulario_descargo', 'FOR-TH-06', 'texto', 'Código para Descargo', '1', '2026-03-03 12:09:44'),
('4', 'version', '01', 'texto', 'Versión del documento', '1', '2026-03-03 12:09:44'),
('5', 'secuencia_actual', '30', 'numero', 'Número secuencial actual', '1', '2026-03-26 17:05:06'),
('6', 'aprobador_nombre', '', 'texto', 'Nombre del aprobador', '1', '2026-03-03 13:35:25'),
('7', 'aprobador_cargo', 'CANCILLER', 'texto', 'Cargo del aprobador', '1', '2026-03-03 12:09:44'),
('8', 'departamento_entrega', 'Tecnologías de la Información', 'texto', 'Departamento que entrega', '1', '2026-03-03 12:09:44'),
('9', 'institucion_nombre', 'TECNOLÓGICO SAN ANTONIO - TESA', 'texto', 'Nombre de la institución', '1', '2026-03-03 12:09:44'),
('10', 'ciudad', 'Quito', 'texto', 'Ciudad', '1', '2026-03-03 12:09:44'),
('11', 'logo_url', '/inventario_ti/assets/img/logo-tesa.png', 'texto', 'Ruta del logo', '1', '2026-03-03 12:09:44'),
('12', 'mostrar_aprobado', '0', 'texto', 'Mostrar firma de aprobado (1=si, 0=no)', '1', '2026-03-04 11:48:21'),
('13', 'aprobador_aprueba_nombre', 'Pablo Morales', 'texto', 'Nombre de quien aprueba', '1', '2026-03-04 11:48:21'),
('14', 'aprobador_aprueba_cargo', 'Director Área T.I', 'texto', 'Cargo de quien aprueba', '1', '2026-03-04 11:48:21'),
('15', 'email_entrega', 'admin@tesa.edu.ec', 'texto', 'Email de contacto de quien entrega', '1', '2026-03-04 11:48:21');

DROP TABLE IF EXISTS `equipos`;
CREATE TABLE `equipos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_barras` varchar(50) NOT NULL,
  `tipo_equipo` varchar(50) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `especificaciones` text DEFAULT NULL,
  `estado` enum('Disponible','Asignado','En mantenimiento','Baja') DEFAULT 'Disponible',
  `ubicacion_id` int(11) DEFAULT NULL,
  `ubicacion_fija_id` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `qr_code` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_eliminacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_barras` (`codigo_barras`),
  UNIQUE KEY `codigo_barras_2` (`codigo_barras`),
  KEY `ubicacion_fija_id` (`ubicacion_fija_id`),
  KEY `idx_ubicacion` (`ubicacion_id`),
  CONSTRAINT `equipos_ibfk_1` FOREIGN KEY (`ubicacion_fija_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `equipos` (`id`, `codigo_barras`, `tipo_equipo`, `marca`, `modelo`, `numero_serie`, `especificaciones`, `estado`, `ubicacion_id`, `ubicacion_fija_id`, `observaciones`, `qr_code`, `fecha_registro`, `activo`, `fecha_eliminacion`) VALUES
('8', '3545543151', 'Laptop', 'hp', 'pavillon', '35451541231', '32', 'Baja', '6', NULL, '', NULL, '2026-03-02 11:39:06', '1', NULL),
('13', 'PRO-000009', 'Desktop', 'hp', 'pavillon', '', '', 'Asignado', NULL, NULL, NULL, NULL, '2026-03-05 14:30:52', '1', NULL),
('15', 'PRO-000014', 'Proyector', 'hp', 'hp', '', '', 'Asignado', '1', NULL, '', NULL, '2026-03-24 10:35:27', '1', NULL),
('16', 'PRO-000016', 'Desktop', 'hp', 'hp', '', '', 'Asignado', '6', NULL, '', NULL, '2026-03-26 15:39:53', '1', NULL),
('17', 'PRO-000017', 'Desktop', 'hp', 'hp', '', '', 'Disponible', '6', NULL, '', NULL, '2026-03-26 15:49:08', '1', NULL),
('18', 'PRO-000018', 'Desktop', 'hp', 'hp', '', '', 'Disponible', '6', NULL, '', NULL, '2026-03-26 15:50:36', '1', NULL),
('19', 'PRO-000019', 'Desktop Gamer', 'hp', 'hp', '', '', 'En mantenimiento', '5', NULL, '', NULL, '2026-03-26 16:00:32', '1', NULL);

DROP TABLE IF EXISTS `incidencias`;
CREATE TABLE `incidencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipo_id` int(11) NOT NULL,
  `persona_id` int(11) DEFAULT NULL,
  `usuario_registro` int(11) DEFAULT NULL,
  `tipo_incidencia` enum('daño','reparación','mantenimiento') NOT NULL,
  `descripcion` text NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_reporte` datetime DEFAULT current_timestamp(),
  `estado` enum('pendiente','en proceso','resuelto') DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  KEY `equipo_id` (`equipo_id`),
  KEY `persona_id` (`persona_id`),
  KEY `usuario_registro` (`usuario_registro`),
  CONSTRAINT `incidencias_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incidencias_ibfk_2` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidencias_ibfk_3` FOREIGN KEY (`usuario_registro`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) DEFAULT NULL,
  `detalle` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `logs` (`id`, `usuario_id`, `accion`, `detalle`, `ip`, `fecha`) VALUES
('1', '1', 'Crear equipo', 'Código: PRO-000019, Tipo: Desktop Gamer', '::1', '2026-03-26 16:00:32'),
('2', '1', 'Inicio de sesión', 'Usuario: admin@tesa.edu.ec', '::1', '2026-03-26 21:27:59'),
('3', '1', 'Inicio de sesión', 'Usuario: admin@tesa.edu.ec', '::1', '2026-03-27 09:30:21'),
('4', '1', 'Editar equipo', 'Equipo ID: 15 - Proyector (PRO-000014)', '::1', '2026-03-27 11:45:18'),
('5', '1', 'Crear persona', 'Cédula: 1750482760, Nombre: Montiel Carlos', '::1', '2026-03-27 11:46:46'),
('6', '1', 'Agregar mantenimiento', 'Equipo ID: 19 - Tipo: preventivo', '::1', '2026-03-27 11:47:52'),
('7', NULL, 'Login fallido', 'Contraseña incorrecta para email: admin@tesa.edu.ec', '::1', '2026-03-27 13:51:52'),
('8', '1', 'Inicio de sesión', 'Usuario: admin@tesa.edu.ec', '::1', '2026-03-27 13:52:03'),
('9', '1', 'Inicio de sesión', 'Usuario: admin@tesa.edu.ec', '::1', '2026-03-27 14:58:40');

DROP TABLE IF EXISTS `mantenimientos`;
CREATE TABLE `mantenimientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipo_id` int(11) NOT NULL,
  `fecha_ingreso` datetime NOT NULL,
  `fecha_salida` datetime DEFAULT NULL,
  `tipo_mantenimiento` enum('preventivo','correctivo') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tecnico` varchar(100) DEFAULT NULL,
  `proveedor` varchar(100) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('en_proceso','finalizado','cancelado') DEFAULT 'en_proceso',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipo_id` (`equipo_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `mantenimientos_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mantenimientos_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `mantenimientos` (`id`, `equipo_id`, `fecha_ingreso`, `fecha_salida`, `tipo_mantenimiento`, `descripcion`, `tecnico`, `proveedor`, `costo`, `observaciones`, `estado`, `created_at`, `created_by`) VALUES
('1', '19', '2026-03-27 11:47:52', NULL, 'preventivo', 'visibilidad borrosa', '', '', '0.50', '', 'en_proceso', '2026-03-27 11:47:52', '1');

DROP TABLE IF EXISTS `movimientos`;
CREATE TABLE `movimientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipo_id` int(11) NOT NULL,
  `persona_id` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('ENTRADA','ASIGNACION','DEVOLUCION','BAJA','REPARACION') NOT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL,
  `estado_equipo` varchar(50) DEFAULT NULL COMMENT 'Bueno, Regular, Malo, Dañado',
  `condiciones` text DEFAULT NULL COMMENT 'Descripción del estado físico',
  `foto_entrega` varchar(255) DEFAULT NULL COMMENT 'Ruta de foto al entregar',
  `foto_devolucion` varchar(255) DEFAULT NULL COMMENT 'Ruta de foto al devolver',
  `acta_generada` varchar(255) DEFAULT NULL COMMENT 'Ruta del PDF del acta',
  PRIMARY KEY (`id`),
  KEY `equipo_id` (`equipo_id`),
  KEY `persona_id` (`persona_id`),
  CONSTRAINT `movimientos_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_ibfk_2` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `movimientos` (`id`, `equipo_id`, `persona_id`, `tipo_movimiento`, `fecha_movimiento`, `observaciones`, `estado_equipo`, `condiciones`, `foto_entrega`, `foto_devolucion`, `acta_generada`) VALUES
('10', '13', NULL, 'BAJA', '2026-03-05 15:39:04', 'Baja - Motivo: Otro. ', NULL, NULL, NULL, NULL, NULL),
('11', '8', NULL, 'BAJA', '2026-03-05 15:42:21', 'Baja - Motivo: Otro. ', NULL, NULL, NULL, NULL, NULL),
('17', '13', '84', 'ASIGNACION', '2026-03-25 22:56:37', 'Asignación por traspaso', NULL, NULL, NULL, NULL, NULL),
('20', '15', '74', 'ASIGNACION', '2026-03-26 17:05:04', 'Asignación por traspaso', NULL, NULL, NULL, NULL, NULL);

DROP TABLE IF EXISTS `movimientos_componentes`;
CREATE TABLE `movimientos_componentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `componente_id` int(11) NOT NULL,
  `persona_id` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('ASIGNACION','DEVOLUCION','REEMPLAZO') NOT NULL,
  `fecha_movimiento` datetime DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL,
  `fecha_devolucion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `componente_id` (`componente_id`),
  KEY `persona_id` (`persona_id`),
  CONSTRAINT `movimientos_componentes_ibfk_1` FOREIGN KEY (`componente_id`) REFERENCES `componentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_componentes_ibfk_2` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `movimientos_componentes` (`id`, `componente_id`, `persona_id`, `tipo_movimiento`, `fecha_movimiento`, `observaciones`, `fecha_devolucion`) VALUES
('11', '8', NULL, 'ASIGNACION', '2026-03-04 14:06:04', '', NULL),
('12', '8', NULL, 'DEVOLUCION', '2026-03-04 14:07:57', 'Devolución registrada', NULL),
('13', '8', NULL, 'ASIGNACION', '2026-03-04 14:42:36', '', NULL),
('14', '8', NULL, 'DEVOLUCION', '2026-03-04 14:58:34', 'Devolución registrada', NULL),
('15', '8', NULL, 'ASIGNACION', '2026-03-04 15:04:31', '', NULL),
('16', '8', NULL, 'DEVOLUCION', '2026-03-04 15:04:38', 'Devolución registrada', NULL),
('17', '8', '10', 'ASIGNACION', '2026-03-04 15:08:36', '', NULL),
('18', '8', '10', 'DEVOLUCION', '2026-03-04 15:08:38', 'Devolución registrada', NULL),
('19', '8', NULL, 'ASIGNACION', '2026-03-24 10:50:58', '', NULL),
('20', '8', NULL, 'DEVOLUCION', '2026-03-24 20:15:06', 'Devolución registrada', NULL);

DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('success','error','warning','info') NOT NULL DEFAULT 'info',
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `leida` (`leida`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `notificaciones` (`id`, `usuario_id`, `tipo`, `titulo`, `mensaje`, `url`, `leida`, `created_at`) VALUES
('1', '1', 'success', '????️ Equipo agregado', 'Se agregó Desktop con código PRO-000018', '/inventario_ti/modules/equipos/detalle.php?id=18', '0', '2026-03-26 15:50:36'),
('2', '1', 'success', '????️ Equipo agregado', 'Se agregó Desktop Gamer con código PRO-000019', '/inventario_ti/modules/equipos/detalle.php?id=19', '0', '2026-03-26 16:00:32'),
('3', '1', 'success', '???? Persona agregada', 'Se agregó a Montiel Carlos (cédula 1750482760)', '/inventario_ti/modules/personas/detalle.php?id=85', '0', '2026-03-27 11:46:46');

DROP TABLE IF EXISTS `personas`;
CREATE TABLE `personas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo` enum('persona','salon','biblioteca','laboratorio') DEFAULT 'persona',
  `codigo_ubicacion` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_eliminacion` datetime DEFAULT NULL,
  `eliminado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `personas` (`id`, `cedula`, `nombres`, `correo`, `cargo`, `telefono`, `observaciones`, `fecha_registro`, `tipo`, `codigo_ubicacion`, `activo`, `fecha_eliminacion`, `eliminado_por`) VALUES
('1', '1802984326', 'ABRIL LUCERO DIANA CAROLINA', 'cabril@tesa.edu.ec', 'DIRECTORA DE CARRERA DISEÑO Y GESTIÓN DE MODAS', '+593 98 405 9903', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('2', '0908816184', 'ALBÁN MALDONADO GUILLERMO PATRICIO', 'rectorado@tesa.edu.ec', 'RECTOR', '+593 99 978 5711', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('3', '1725882763', 'ALVARADO EGAS DIEGO GONZALO', 'dalvarado@tesa.edu.ec', 'COORDINADOR DE TECNOLOGÍAS DE LA INFORMACIÓN', '+593 99 905 3204', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('4', '1715834311', 'BARRENO AGUAYO MICHEL HERNAN', 'mbarreno@tesa.edu.ec', 'COORDINADOR DE COMUNICACIÓN DIGITAL', '+593 99 861 0066', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('5', '1716174352', 'BRACHO IBARRA FLAVIO ORLANDO', 'obracho@tesa.edu.ec', 'COORDINADOR DE BIBLIOTECA', '+593 98 462 0288', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('6', '1719533828', 'CAJAMARCA TUPIZA CAMILA BELÉN', 'camilbelen@gmail.com', 'PASANTE DE COMUNICACIÓN DIGITAL', '+593 95 940 5859', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('7', '1755731740', 'CALERO TACO JOSÉ LUIS', 'luistaco132@gmail.com', 'AUXILIAR DE MANTENIMIENTO', '+593 98 378 1396', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('8', '1721083382', 'ESPINOZA PEREZ KATIUSKA EVELYN', 'kespinoza@tesa.edu.ec', 'VICERRECTORA ACADÉMICA', '+593 98 031 2923', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('9', '1311673014', 'GARCÍA BAYLÓN DIANA CRISTINA', 'dgarcia@tesa.edu.ec', 'DIRECTORA DE TALENTO HUMANO', '+593 93 958 1162', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('10', '1722871470', 'GUAMÁN PEÑAFIEL MAYRA DANIELA', 'dannydolly74@gmail.com', 'AUXILIAR DE MANTENIMIENTO', '+593 96 780 7320', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('11', '1709346587', 'IZURIETA TAPIA GRACE ROSARIO', 'gizurieta@tesa.edu.ec', 'COORDINADOR DE CARRERA ADMINISTRACIÓN', '+593 99 497 0490', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('12', '1724475445', 'JARA LEÓN JOSÉ JAVIER', 'jjara@tesa.edu.ec', 'COORDINADOR DE PLANIFICACIÓN Y CALIDAD', '+593 99 447 8054', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('13', '1724475379', 'JARA LEÓN KARLA SOFÍA', 'kjara@tesa.edu.ec', 'DIRECTORA DE ADMISIONES', '+593 99 522 3001', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('14', '1719752055', 'LESCANO RECALDE RUTH ELIZABETH', 'contabilidad@tesa.edu.ec', 'CONTADORA', '+593 99 835 6644', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('15', '1714596465', 'LONDOÑO PROAÑO RUTH SOLEDAD', 'slondono@tesa.edu.ec', 'DIRECTORA DE VINCULACIÓN', '+593 99 515 4612', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('16', '1757771280', 'LORENZO EVORA YENI', 'ylorenzo@tesa.edu.ec', 'DIRECTORA BIENESTAR INSTITUCIONAL', '+593 99 885 0882', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('17', '1715439574', 'MONTEVERDE BRAVO PAMELA', 'pmonteverde@tesa.edu.ec', 'DECANA DE PLANIFICACIÓN ACADÉMICA', '+593 99 854 7320', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('18', '1600540247', 'MORALES SALAZÁR LUIS PABLO', 'pmorales@tesa.edu.ec', 'DIRECTOR DE TECNOLOGÍAS DE LA INFORMACIÓN', '+593 98 487 3530', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('19', '1723337018', 'MUÑOZ ORTEGA LUIS AUGUSTO', 'lamunoz@tesa.edu.ec', 'DIRECTOR DE CARRERA APARATOLOGÍA DENTAL', '+593 99 846 4453', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('20', '1716533862', 'NARANJO GARCÍA JOHANNA MONSERRAT', 'jnaranjo@tesa.edu.ec', 'COORDINADORA DE PROYECTOS DE INVESTIGACIÓN Y VINCULACIÓN', '+593 99 144 7440', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('21', '1709640070', 'NOBOA REYES MARVIN JAIR', 'mnoboa@tesa.edu.ec', 'VICERRECTOR DE PLANIFICACIÓN Y CALIDAD', '+593 99 988 1911', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('22', '1721737078', 'PAUCAR TIPANTUÑA ANA EMPERATRIZ', 'apaucar@tesa.edu.ec', 'COORDINADORA DE PROYECTOS DE TITULACIÓN', '+593 99 286 5588', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('23', '1716453210', 'RECALDE CHALACÁN ÁNGEL REMIGIO', 'angelrecalde250@gmail.com', 'AUXILIAR DE MANTENIMIENTO', '+593 98 315 1888', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('24', '1713152450', 'ROJAS BUKOVAC SANTIAGO XAVIER', 'srojas@tesa.edu.ec', 'DECANO DE ESCUELA DE TECNOLOGÍAS Y INNOVACCIÓN', '+593 99 266 9761', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('25', '1750745620', 'RUDHOLM BALMACEDA PETER MICHAEL', 'prudholm@tesa.edu.ec', 'DIRECTOR DE LENGUAS', '+593 98 487 2059', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('26', '1704694197', 'SANTAMARIA LARCO PABLO ROGER', 'psantamaria@tesa.edu.ec', 'SUPERVISOR DE LABORATORIO', '+593 97 904 1507', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('27', '1720164233', 'SUASNAVAS VALENZUELA NATALI CRISTINA', 'nsuasnavas@tesa.edu.ec', 'DIRECTORA DECARRERA DERMATOCOSMIATRÍA', '+593 99 980 8090', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('28', '1717910507', 'TASIGUANO POZO CRISTIAN ANDRES', 'ctasiguano@tesa.edu.ec', 'DIRECTOR DE INVESTIGACIÓN Y EDITORIAL', '+593 98 466 2685', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('29', '1723891329', 'VÁZQUEZ JARA CYNTHIA', 'cvazquez@tesa.edu.ec', 'CANCILLER', '+593 99 974 7509', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('30', '1716927874', 'VILLARROEL DAVID', 'dvillarroel@tesa.edu.ec', 'COORDINADOR DE ESTRATEGIA DIGITAL', '+593 99 821 7400', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('31', '1727587832', 'YÁNEZ MARÍN DENISSE', 'dyanez@tesa.edu.ec', 'COORDINADOR DE CARRERA APARATOLOGÍA DENTAL', '+593 98 709 9169', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('32', '1751483189', 'MUÑOZ RAMIREZ KAREN NICOLE', 'kmunoz@tesa.edu.ec', 'ASESOR ADMISIONES', '+593 96 925 8447', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('33', '1309507299', 'ZAMBRANO MACÍAS DEMNYS PATRICIO', 'patricio_zambrano@outlook.com', 'CHOFER', '+593 99 735 2000', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('34', '1726867136', 'LOAYZA LALAMA ALISSON SAMANTHA', 'aloayza@tesa.edu.ec', 'SECRETARIA GENERAL', '+593 99 977 7021', '', '2026-02-21 00:19:08', 'persona', NULL, '1', NULL, NULL),
('74', 'UBI-SL101', 'Salón 101', NULL, 'Aula de clases', NULL, NULL, '2026-02-22 00:40:37', 'salon', 'SL-101', '1', NULL, NULL),
('75', 'UBI-SL102', 'Salón 102', NULL, 'Aula de clases', NULL, NULL, '2026-02-22 00:40:37', 'salon', 'SL-102', '1', NULL, NULL),
('76', 'UBI-LAB01', 'Laboratorio de Cómputo 1', NULL, 'Laboratorio', NULL, NULL, '2026-02-22 00:40:37', 'laboratorio', 'LAB-01', '1', NULL, NULL),
('84', '1717601460', 'richard', 'isaacmontiel151@gmail.com', 'estudiante', '0000000001', '', '2026-03-24 12:14:47', 'persona', NULL, '1', '2026-03-24 12:27:51', '1'),
('85', '1750482760', 'Montiel Carlos', 'isaacmontiel151@gmail.com', 'estudiante', '0992630785', '', '2026-03-27 11:46:46', 'persona', NULL, '1', NULL, NULL);

DROP TABLE IF EXISTS `prestamos_rapidos`;
CREATE TABLE `prestamos_rapidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipo_id` int(11) NOT NULL,
  `persona_id` int(11) NOT NULL,
  `fecha_prestamo` datetime NOT NULL,
  `fecha_estimada_devolucion` date NOT NULL,
  `fecha_devolucion_real` datetime DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('activo','devuelto','vencido') DEFAULT 'activo',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `equipo_id` (`equipo_id`),
  KEY `persona_id` (`persona_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `prestamos_rapidos_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestamos_rapidos_ibfk_2` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestamos_rapidos_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `secuencias_actas`;
CREATE TABLE `secuencias_actas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_acta` varchar(20) NOT NULL,
  `ultima_secuencia` int(11) NOT NULL DEFAULT 0,
  `anio` int(4) NOT NULL,
  `mes` int(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tipo_mes` (`tipo_acta`,`anio`,`mes`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `secuencias_actas` (`id`, `tipo_acta`, `ultima_secuencia`, `anio`, `mes`) VALUES
('1', 'entrega', '0', '2026', '3'),
('2', 'devolucion', '0', '2026', '3'),
('3', 'descargo', '0', '2026', '3');

DROP TABLE IF EXISTS `ubicaciones`;
CREATE TABLE `ubicaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('salon','biblioteca','laboratorio','oficina','bodega') NOT NULL,
  `codigo_ubicacion` varchar(20) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_ubicacion` (`codigo_ubicacion`),
  KEY `responsable_id` (`responsable_id`),
  CONSTRAINT `ubicaciones_ibfk_1` FOREIGN KEY (`responsable_id`) REFERENCES `personas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `ubicaciones` (`id`, `nombre`, `tipo`, `codigo_ubicacion`, `descripcion`, `responsable_id`) VALUES
('1', 'Salón 101', 'salon', 'SL-101', NULL, NULL),
('2', 'Salón 102', 'salon', 'SL-102', NULL, NULL),
('3', 'Laboratorio 1', 'laboratorio', 'LAB-01', NULL, NULL),
('4', 'Biblioteca Central', 'biblioteca', 'BIB-001', NULL, NULL),
('5', 'Aula Magna', 'salon', 'AM-01', NULL, NULL),
('6', 'Bodega Principal', 'salon', 'BOD-01', NULL, NULL),
('7', 'Bodega Secundaria', 'salon', 'BOD-02', NULL, NULL),
('8', 'Oficina Administrativa', 'salon', 'OFI-01', NULL, NULL);

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','usuario') DEFAULT 'usuario',
  `avatar` varchar(255) DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expira` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_reset_token` (`reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `avatar`, `ultimo_acceso`, `reset_token`, `reset_expira`, `created_at`, `creado`) VALUES
('1', 'Administrador', 'admin@tesa.edu.ec', '$2y$10$9.evTQdOxdp90zqnrFHaL.EFbktbyjn6jiNTL4KRjbv5ccrkXpM/u', 'admin', NULL, '2026-03-27 14:58:40', '2188ae49c08a88710c24479c4d8f09fe94c2d774ac031780750ce5bd055c926f', '2026-03-25 04:56:47', '2026-03-04 12:26:45', '2026-02-21 23:09:39'),
('3', 'Usuario Invitado', 'invitado@tesa.edu.ec', '$2y$10$DAcd4c27ATooQPMHTa9c8ugoQFQKbQeUzyrGFAjDIJGNQ8VXtli66', 'usuario', NULL, '2026-03-04 13:33:18', NULL, NULL, '2026-03-04 12:26:45', '2026-03-03 14:46:20');

SET FOREIGN_KEY_CHECKS=1;
