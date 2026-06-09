-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: inventario_ti
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `actas`
--

DROP TABLE IF EXISTS `actas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registro de todas las actas generadas';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actas`
--

LOCK TABLES `actas` WRITE;
/*!40000 ALTER TABLE `actas` DISABLE KEYS */;
INSERT INTO `actas` VALUES (1,'FOR-TH-05-01-202603-0001','devolucion',1,1,'2026-03-03 12:05:52',NULL,NULL,'',1),(2,'FOR-TH-06-01-202603-0001','descargo',1,1,'2026-03-03 12:05:57',NULL,NULL,NULL,1),(3,'FOR-TH-05-01-202603-0001','devolucion',1,1,'2026-03-03 13:18:59',NULL,NULL,'',1),(4,'FOR-TH-06-01-202603-0002','descargo',1,1,'2026-03-03 13:19:21',NULL,NULL,NULL,2),(5,'FOR-TH-04-01-202603-0003','entrega',1,1,'2026-03-03 13:25:51',NULL,NULL,'',0),(6,'FOR-TH-04-01-202603-0004','entrega',1,1,'2026-03-03 13:27:49',NULL,NULL,'',0),(7,'FOR-TH-05-01-202603-0005','devolucion',1,1,'2026-03-03 13:29:54',NULL,NULL,'',5),(8,'FOR-TH-06-01-202603-0006','descargo',1,1,'2026-03-03 13:29:57',NULL,NULL,NULL,0),(9,'FOR-TH-04-01-202603-0007','entrega',1,1,'2026-03-03 13:31:46',NULL,NULL,'',0),(10,'FOR-TH-06-01-202603-0008','entrega',1,1,'2026-03-03 13:35:33',NULL,NULL,'',0),(11,'FOR-TH-05-01-202603-0009','devolucion',1,1,'2026-03-03 13:35:49',NULL,NULL,'',9),(12,'FOR-TH-06-01-202603-0010','descargo',1,1,'2026-03-03 13:36:05',NULL,NULL,NULL,0),(13,'FOR-TH-06-01-202603-0011','entrega',1,1,'2026-03-03 13:37:07',NULL,NULL,'',0),(14,'FOR-TH-06-01-202603-0012','entrega',1,1,'2026-03-03 13:44:05',NULL,NULL,'',0),(15,'FOR-TH-05-01-202603-0013','devolucion',1,1,'2026-03-03 13:44:35',NULL,NULL,'',0),(16,'FOR-TH-06-01-202603-0014','descargo',1,1,'2026-03-03 13:44:42',NULL,NULL,NULL,0),(17,'FOR-TH-06-01-202603-0015','entrega',1,1,'2026-03-03 13:50:43',NULL,NULL,'',0),(18,'FOR-TH-06-01-202603-0016','descargo',1,1,'2026-03-03 13:50:53',NULL,NULL,NULL,0),(19,'FOR-TH-05-01-202603-0017','devolucion',1,1,'2026-03-03 13:51:04',NULL,NULL,'',0),(20,'FOR-TH-06-01-202603-0018','descargo',1,1,'2026-03-03 13:52:18',NULL,NULL,NULL,0),(21,'FOR-TH-06-01-202603-0019','descargo',1,1,'2026-03-03 13:52:47',NULL,NULL,NULL,0),(22,'FOR-TH-06-01-202603-0020','entrega',1,1,'2026-03-03 13:52:53',NULL,NULL,'',0),(23,'FOR-TH-06-01-202603-0021','descargo',1,1,'2026-03-03 13:53:06',NULL,NULL,NULL,0),(24,'FOR-TH-05-01-202603-0022','devolucion',1,1,'2026-03-03 13:53:10',NULL,NULL,'',0),(25,'FOR-TH-06-01-202603-0023','descargo',1,1,'2026-03-03 13:53:50',NULL,NULL,NULL,0),(26,'FOR-TH-05-01-202603-0024','devolucion',1,1,'2026-03-03 14:05:21',NULL,NULL,'',0),(27,'FOR-TH-06-01-202603-0025','entrega',1,1,'2026-03-03 14:05:31',NULL,NULL,'',0),(28,'FOR-TH-06-01-202603-0026','descargo',1,1,'2026-03-03 14:10:18',NULL,NULL,NULL,0),(29,'FOR-TH-06-01-202603-0027','descargo',1,1,'2026-03-03 14:15:40',NULL,NULL,NULL,0),(30,'FOR-TH-06-01-202603-0028','descargo',1,1,'2026-03-03 14:18:01',NULL,NULL,NULL,0),(31,'FOR-TH-06-01-202603-0029','descargo',1,1,'2026-03-03 14:20:12',NULL,NULL,NULL,0),(32,'FOR-TH-06-01-202603-0030','descargo',1,1,'2026-03-03 14:22:20',NULL,NULL,NULL,0),(33,'FOR-TH-06-01-202603-0031','descargo',1,1,'2026-03-03 14:25:04',NULL,NULL,NULL,0),(34,'FOR-TH-06-01-202603-0032','entrega',1,1,'2026-03-03 14:33:25',NULL,NULL,'',0),(35,'FOR-TH-05-01-202603-0033','devolucion',1,1,'2026-03-03 14:33:31',NULL,NULL,'',0),(36,'FOR-TH-06-01-202603-0034','descargo',1,1,'2026-03-03 14:33:34',NULL,NULL,NULL,0),(37,'FOR-TH-05-01-202603-0035','devolucion',83,1,'2026-03-03 15:07:32',NULL,NULL,'8',0),(38,'FOR-TH-06-01-202603-0036','entrega',83,1,'2026-03-03 15:09:15',NULL,NULL,'',0),(39,'FOR-TH-04-01-202603-0001','devolucion',83,1,'2026-03-03 15:10:30',NULL,NULL,'8',0),(40,'FOR-TH-04-01-202603-0002','entrega',83,1,'2026-03-03 15:10:34',NULL,NULL,'',0),(41,'FOR-TH-06-01-202603-0003','descargo',83,1,'2026-03-03 15:13:17',NULL,NULL,NULL,0),(42,'FOR-TH-04-01-202603-0006','entrega',2,1,'2026-03-04 09:38:06',NULL,NULL,'',0),(43,'FOR-TH-04-01-202603-0007','devolucion',2,1,'2026-03-04 09:38:14',NULL,NULL,'',0),(44,'FOR-TH-06-01-202603-0008','descargo',8,1,'2026-03-04 09:44:06',NULL,NULL,NULL,0),(45,'FOR-TH-06-01-202603-0009','descargo',8,1,'2026-03-04 09:52:04',NULL,NULL,NULL,0),(46,'FOR-TH-04-01-202603-0010','devolucion',8,1,'2026-03-04 15:41:54',NULL,NULL,'',0),(47,'FOR-TH-04-01-202603-0011','devolucion',14,1,'2026-03-05 12:08:22',NULL,NULL,'',0),(48,'FOR-TH-04-01-202603-0012','devolucion',17,1,'2026-03-05 13:31:54',NULL,NULL,'',0),(49,'FOR-TH-00-01-202603-0013','',1,1,'2026-03-05 15:40:53',NULL,NULL,NULL,0),(50,'FOR-TH-00-01-202603-0015','',1,1,'2026-03-05 15:42:02',NULL,NULL,NULL,0),(51,'FOR-TH-06-01-202603-0016','descargo',17,1,'2026-03-05 15:42:35',NULL,NULL,NULL,0),(52,'','entrega',83,1,'2026-03-09 09:57:42',NULL,NULL,'',0),(53,'FOR-TH-04-01-202603-0017','devolucion',83,1,'2026-03-09 09:57:50',NULL,NULL,'8',0),(54,'FOR-TH-06-01-202603-0018','descargo',83,1,'2026-03-09 09:58:05',NULL,NULL,NULL,0),(55,'','entrega',83,1,'2026-03-09 09:58:09',NULL,NULL,'',0),(56,'FOR-TH-04-01-202603-0019','entrega',83,1,'2026-03-09 10:12:03',NULL,NULL,'',0),(57,'FOR-TH-04-01-202603-0020','entrega',83,1,'2026-03-09 10:14:56',NULL,NULL,'',0),(58,'FOR-TH-04-01-202603-0021','entrega',83,1,'2026-03-09 10:15:04',NULL,NULL,'',0),(59,'FOR-TH-04-01-202603-0022','entrega',83,1,'2026-03-09 10:16:04',NULL,NULL,'',0),(60,'FOR-TH-04-01-202603-0023','entrega',83,1,'2026-03-09 10:17:20',NULL,NULL,'',0),(61,'FOR-TH-04-01-202603-0024','devolucion',83,1,'2026-03-09 10:17:51',NULL,NULL,'8',0),(62,'FOR-TH-04-01-202603-0025','entrega',83,1,'2026-03-09 10:18:00',NULL,NULL,'',0),(63,'FOR-TH-04-01-202603-0026','entrega',83,1,'2026-03-09 10:19:06',NULL,NULL,'',0),(64,'FOR-TH-04-01-202603-0027','entrega',83,1,'2026-03-09 10:19:14',NULL,NULL,'',0),(65,'FOR-TH-04-01-202603-0028','entrega',83,1,'2026-03-09 10:21:26',NULL,NULL,'',0),(66,'FOR-TH-04-01-202603-0029','entrega',83,1,'2026-03-09 10:21:29',NULL,NULL,'',0),(67,'FOR-TH-04-01-202603-0030','entrega',83,1,'2026-03-09 10:24:23',NULL,NULL,'',0),(68,'FOR-TH-04-01-202603-0031','entrega',83,1,'2026-03-09 10:26:04',NULL,NULL,'',0),(69,'','entrega',83,1,'2026-03-09 10:35:52',NULL,NULL,'',0),(70,'','entrega',83,1,'2026-03-09 10:52:20',NULL,NULL,'',0),(71,'FOR-TH-01-01-202603-0032','',0,1,'2026-03-13 12:07:47',NULL,NULL,NULL,0),(72,'','entrega',1,1,'2026-03-24 12:24:54',NULL,NULL,'',0),(73,'FOR-TH-04-01-202603-0033','devolucion',1,1,'2026-03-24 12:25:00',NULL,NULL,'',0),(74,'','entrega',1,1,'2026-03-25 13:19:30',NULL,NULL,'',0),(75,'','entrega',84,1,'2026-03-25 14:04:56',NULL,NULL,'',0),(76,'FOR-TH-06-01-202603-0034','descargo',19,1,'2026-03-25 15:27:08',NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `actas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asignaciones`
--

DROP TABLE IF EXISTS `asignaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asignaciones`
--

LOCK TABLES `asignaciones` WRITE;
/*!40000 ALTER TABLE `asignaciones` DISABLE KEYS */;
INSERT INTO `asignaciones` VALUES (6,8,83,NULL,'2026-03-02 16:39:06','2026-03-03','');
/*!40000 ALTER TABLE `asignaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asignaciones_componentes`
--

DROP TABLE IF EXISTS `asignaciones_componentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asignaciones_componentes`
--

LOCK TABLES `asignaciones_componentes` WRITE;
/*!40000 ALTER TABLE `asignaciones_componentes` DISABLE KEYS */;
/*!40000 ALTER TABLE `asignaciones_componentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `componentes`
--

DROP TABLE IF EXISTS `componentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `email_notificado` tinyint(1) DEFAULT 0 COMMENT '1=se notificó por email el daño',
  PRIMARY KEY (`id`),
  KEY `equipo_id` (`equipo_id`),
  CONSTRAINT `componentes_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `componentes`
--

LOCK TABLES `componentes` WRITE;
/*!40000 ALTER TABLE `componentes` DISABLE KEYS */;
INSERT INTO `componentes` VALUES (8,NULL,'asda','Procesador','asd','asdas','dasd','sad','Bueno','2026-03-04','','2026-03-04 18:31:08',1,NULL,0);
/*!40000 ALTER TABLE `componentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion`
--

LOCK TABLES `configuracion` WRITE;
/*!40000 ALTER TABLE `configuracion` DISABLE KEYS */;
INSERT INTO `configuracion` VALUES (1,'formulario_entrega','FOR-TH-04','texto','Código para Acta de Entrega',1,'2026-03-03 15:10:16'),(2,'formulario_devolucion','FOR-TH-04','texto','Código para Acta de Devolución',1,'2026-03-03 15:10:16'),(3,'formulario_descargo','FOR-TH-06','texto','Código para Descargo',1,'2026-03-03 12:09:44'),(4,'version','01','texto','Versión del documento',1,'2026-03-03 12:09:44'),(5,'secuencia_actual','35','numero','Número secuencial actual',1,'2026-03-25 15:27:08'),(6,'aprobador_nombre','','texto','Nombre del aprobador',1,'2026-03-03 13:35:25'),(7,'aprobador_cargo','CANCILLER','texto','Cargo del aprobador',1,'2026-03-03 12:09:44'),(8,'departamento_entrega','Tecnologías de la Información','texto','Departamento que entrega',1,'2026-03-03 12:09:44'),(9,'institucion_nombre','TECNOLÓGICO SAN ANTONIO - TESA','texto','Nombre de la institución',1,'2026-03-03 12:09:44'),(10,'ciudad','Quito','texto','Ciudad',1,'2026-03-03 12:09:44'),(11,'logo_url','/inventario_ti/assets/img/logo-tesa.png','texto','Ruta del logo',1,'2026-03-03 12:09:44'),(12,'mostrar_aprobado','0','texto','Mostrar firma de aprobado (1=si, 0=no)',1,'2026-03-04 11:48:21'),(13,'aprobador_aprueba_nombre','Pablo Morales','texto','Nombre de quien aprueba',1,'2026-03-04 11:48:21'),(14,'aprobador_aprueba_cargo','Director Área T.I','texto','Cargo de quien aprueba',1,'2026-03-04 11:48:21'),(15,'email_entrega','admin@tesa.edu.ec','texto','Email de contacto de quien entrega',1,'2026-03-04 11:48:21');
/*!40000 ALTER TABLE `configuracion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversaciones`
--

DROP TABLE IF EXISTS `conversaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `respuesta` text DEFAULT NULL,
  `contexto` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contexto`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `conversaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversaciones`
--

LOCK TABLES `conversaciones` WRITE;
/*!40000 ALTER TABLE `conversaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `correos_enviados`
--

DROP TABLE IF EXISTS `correos_enviados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `correos_enviados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `persona_id` int(11) NOT NULL,
  `asignacion_id` int(11) DEFAULT NULL,
  `componente_id` int(11) DEFAULT NULL,
  `tipo_motivo` varchar(50) NOT NULL COMMENT 'vencido, por_vencer, danado, manual, recordatorio',
  `asunto` varchar(500) NOT NULL,
  `mensaje` text NOT NULL,
  `email_destino` varchar(255) NOT NULL,
  `email_enviado` tinyint(1) DEFAULT 0 COMMENT '1=enviado, 0=fallido/no enviado',
  `error_email` text DEFAULT NULL COMMENT 'Mensaje de error si falló el envío',
  `usuario_id` int(11) NOT NULL COMMENT 'Usuario que envió el correo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_persona` (`persona_id`),
  KEY `idx_asignacion` (`asignacion_id`),
  KEY `idx_componente` (`componente_id`),
  KEY `idx_tipo_motivo` (`tipo_motivo`),
  KEY `idx_email_enviado` (`email_enviado`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `correos_enviados_ibfk_1` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `correos_enviados_ibfk_2` FOREIGN KEY (`asignacion_id`) REFERENCES `asignaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `correos_enviados_ibfk_3` FOREIGN KEY (`componente_id`) REFERENCES `componentes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `correos_enviados_ibfk_4` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de correos enviados a personas';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `correos_enviados`
--

LOCK TABLES `correos_enviados` WRITE;
/*!40000 ALTER TABLE `correos_enviados` DISABLE KEYS */;
INSERT INTO `correos_enviados` VALUES (1,84,NULL,NULL,'manual','prueba','sacsaascsacscacsacascsacascscsc','axelpsoriano03@gmail.com',0,'SMTP Error: Could not authenticate.',1,'2026-03-24 18:15:15'),(2,84,NULL,NULL,'manual','wfdaswdas','sadsadsdasds','axelpsoriano03@gmail.com',0,'SMTP Error: Could not authenticate.',1,'2026-03-24 18:20:27'),(3,84,NULL,NULL,'manual','xaxZXzX','zXXXXXsSzXZXzXzxzXzx','axelpsoriano03@gmail.com',1,NULL,1,'2026-03-24 18:27:28'),(4,84,NULL,NULL,'manual','prueba','mensaje de prueba','axelpsoriano03@gmail.com',1,NULL,1,'2026-03-24 18:28:17'),(5,84,NULL,NULL,'manual','prueb 3','mensaje de prueba','axelpsoriano03@gmail.com',0,'SMTP Error: Could not authenticate.',1,'2026-03-24 18:41:53'),(6,84,NULL,NULL,'manual','prueba 3','mensaje de prueba ignorar','axelpsoriano03@gmail.com',1,NULL,1,'2026-03-24 18:44:12'),(7,84,NULL,NULL,'manual','sac','sca','axelpsoriano03@gmail.com',1,NULL,1,'2026-03-24 19:30:21'),(8,84,NULL,NULL,'manual','URGENTE: Préstamo Vencido - [CÓDIGO]','Estimado/a [NOMBRE],\r\n\r\nLe informamos que su préstamo del equipo [EQUIPO] con código [CÓDIGO] se encuentra VENCIDO desde hace [DÍAS] días.\r\n\r\nLa fecha estimada de devolución era: [FECHA]\r\n\r\nPor favor, coordine la devolución inmediata del equipo en las oficinas del departamento de TI.\r\n\r\nGracias por su atención.\r\n\r\nDepartamento de Tecnología - TESA','axelpsoriano03@gmail.com',1,NULL,1,'2026-03-24 19:34:27'),(9,84,NULL,NULL,'manual','prueba','dfsasadadsasd','axelpsoriano03@gmail.com',1,NULL,1,'2026-03-24 20:00:14'),(10,84,NULL,NULL,'manual','prueba','esteb es un correo de prueba ignorar mensaje','axelpsoriano03@gmail.com',1,NULL,1,'2026-03-25 19:57:15');
/*!40000 ALTER TABLE `correos_enviados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipos`
--

DROP TABLE IF EXISTS `equipos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `foto` varchar(255) DEFAULT NULL,
  `qr_code` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_eliminacion` datetime DEFAULT NULL,
  `eliminado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_barras` (`codigo_barras`),
  UNIQUE KEY `codigo_barras_2` (`codigo_barras`),
  KEY `ubicacion_fija_id` (`ubicacion_fija_id`),
  KEY `idx_ubicacion` (`ubicacion_id`),
  KEY `eliminado_por` (`eliminado_por`),
  CONSTRAINT `equipos_ibfk_1` FOREIGN KEY (`ubicacion_fija_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `equipos_ibfk_2` FOREIGN KEY (`eliminado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipos`
--

LOCK TABLES `equipos` WRITE;
/*!40000 ALTER TABLE `equipos` DISABLE KEYS */;
INSERT INTO `equipos` VALUES (8,'3545543151','Laptop','hp','pavillon','35451541231','32','',NULL,NULL,NULL,NULL,NULL,'2026-03-02 16:39:06',1,'2026-03-24 09:42:41',1),(13,'9868686867sf','Cámara','23r','2323','233','rgreg','Disponible',NULL,NULL,'rgrgrgr',NULL,NULL,'2026-03-05 16:05:23',1,NULL,NULL),(14,'aWDWd','Cámara','wq','qwd','SC','c','Disponible',NULL,NULL,'',NULL,NULL,'2026-03-05 16:50:51',1,NULL,NULL),(15,'aX','Parlantes','asd','asd','ads','sd','Disponible',NULL,NULL,'sad',NULL,NULL,'2026-03-05 16:51:06',1,NULL,NULL),(16,'sadadxzc','Otro','xzc','ZC','zxc','xzc','Disponible',NULL,NULL,'zczxc',NULL,NULL,'2026-03-05 16:51:25',1,NULL,NULL),(17,'czxc','Impresora','zxc','zxcX','qdWD','SACc','Disponible',NULL,NULL,'csACSC',NULL,NULL,'2026-03-05 16:51:42',1,NULL,NULL),(18,'9868686867asc','Mouse','sac','sa','sac','csa','Baja',NULL,NULL,'',NULL,NULL,'2026-03-05 20:38:09',1,NULL,NULL),(19,'wqdwqd','Laptop Empresarial','sad','asd','Dasd','SD','Disponible',NULL,NULL,'sad','',NULL,'2026-03-24 21:26:33',1,NULL,NULL);
/*!40000 ALTER TABLE `equipos` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `generar_codigo_barras` BEFORE INSERT ON `equipos` FOR EACH ROW BEGIN
    IF NEW.codigo_barras IS NULL OR NEW.codigo_barras = '' THEN
        SET NEW.codigo_barras = CONCAT('PRO-', LPAD(NEW.id, 6, '0'));
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `incidencias`
--

DROP TABLE IF EXISTS `incidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incidencias`
--

LOCK TABLES `incidencias` WRITE;
/*!40000 ALTER TABLE `incidencias` DISABLE KEYS */;
INSERT INTO `incidencias` VALUES (1,17,83,1,'mantenimiento','en mantenimiento(Prueba)','as','2026-03-05 14:09:51','pendiente');
/*!40000 ALTER TABLE `incidencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantenimientos`
--

DROP TABLE IF EXISTS `mantenimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantenimientos`
--

LOCK TABLES `mantenimientos` WRITE;
/*!40000 ALTER TABLE `mantenimientos` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantenimientos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos`
--

DROP TABLE IF EXISTS `movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos`
--

LOCK TABLES `movimientos` WRITE;
/*!40000 ALTER TABLE `movimientos` DISABLE KEYS */;
INSERT INTO `movimientos` VALUES (7,8,83,'ASIGNACION','2026-03-02 16:39:06',NULL,NULL,NULL,NULL,NULL,NULL),(8,8,83,'DEVOLUCION','2026-03-03 20:07:00','','INCOMPLETO','',NULL,'',NULL),(9,18,NULL,'BAJA','2026-03-05 20:41:27','Baja - Motivo: Otro. qdsqwd',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `movimientos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_componentes`
--

DROP TABLE IF EXISTS `movimientos_componentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_componentes`
--

LOCK TABLES `movimientos_componentes` WRITE;
/*!40000 ALTER TABLE `movimientos_componentes` DISABLE KEYS */;
INSERT INTO `movimientos_componentes` VALUES (11,8,83,'ASIGNACION','2026-03-04 14:06:04','',NULL),(12,8,83,'DEVOLUCION','2026-03-04 14:07:57','Devolución registrada',NULL),(13,8,83,'ASIGNACION','2026-03-04 14:42:36','',NULL),(14,8,83,'DEVOLUCION','2026-03-04 14:58:34','Devolución registrada',NULL),(15,8,83,'ASIGNACION','2026-03-04 15:04:31','',NULL),(16,8,83,'DEVOLUCION','2026-03-04 15:04:38','Devolución registrada',NULL),(17,8,10,'ASIGNACION','2026-03-04 15:08:36','',NULL),(18,8,10,'DEVOLUCION','2026-03-04 15:08:38','Devolución registrada',NULL);
/*!40000 ALTER TABLE `movimientos_componentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('success','error','info','warning') NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
INSERT INTO `notificaciones` VALUES (1,1,'success','???? Persona agregada','Se agregó a mateo (cédula 1749853759)','/inventario_ti/modules/personas/detalle.php?id=85',0,'2026-03-24 22:16:19'),(2,1,'success','✉️ Correo enviado','Correo enviado a axel: prueba','/inventario_ti/modules/correos/historial.php?id=10',0,'2026-03-25 19:57:15');
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personas`
--

DROP TABLE IF EXISTS `personas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personas`
--

LOCK TABLES `personas` WRITE;
/*!40000 ALTER TABLE `personas` DISABLE KEYS */;
INSERT INTO `personas` VALUES (1,'1802984326','ABRIL LUCERO DIANA CAROLINA','cabril@tesa.edu.ec','DIRECTORA DE CARRERA DISEÑO Y GESTIÓN DE MODAS','+593 98 405 9903','','2026-02-21 05:19:08','persona',NULL,1,NULL),(2,'0908816184','ALBÁN MALDONADO GUILLERMO PATRICIO','rectorado@tesa.edu.ec','RECTOR','+593 99 978 5711','','2026-02-21 05:19:08','persona',NULL,1,NULL),(3,'1725882763','ALVARADO EGAS DIEGO GONZALO','dalvarado@tesa.edu.ec','COORDINADOR DE TECNOLOGÍAS DE LA INFORMACIÓN','+593 99 905 3204','','2026-02-21 05:19:08','persona',NULL,1,NULL),(4,'1715834311','BARRENO AGUAYO MICHEL HERNAN','mbarreno@tesa.edu.ec','COORDINADOR DE COMUNICACIÓN DIGITAL','+593 99 861 0066','','2026-02-21 05:19:08','persona',NULL,1,NULL),(5,'1716174352','BRACHO IBARRA FLAVIO ORLANDO','obracho@tesa.edu.ec','COORDINADOR DE BIBLIOTECA','+593 98 462 0288','','2026-02-21 05:19:08','persona',NULL,1,NULL),(6,'1719533828','CAJAMARCA TUPIZA CAMILA BELÉN','camilbelen@gmail.com','PASANTE DE COMUNICACIÓN DIGITAL','+593 95 940 5859','','2026-02-21 05:19:08','persona',NULL,1,NULL),(7,'1755731740','CALERO TACO JOSÉ LUIS','luistaco132@gmail.com','AUXILIAR DE MANTENIMIENTO','+593 98 378 1396','','2026-02-21 05:19:08','persona',NULL,1,NULL),(8,'1721083382','ESPINOZA PEREZ KATIUSKA EVELYN','kespinoza@tesa.edu.ec','VICERRECTORA ACADÉMICA','+593 98 031 2923','','2026-02-21 05:19:08','persona',NULL,1,NULL),(9,'1311673014','GARCÍA BAYLÓN DIANA CRISTINA','dgarcia@tesa.edu.ec','DIRECTORA DE TALENTO HUMANO','+593 93 958 1162','','2026-02-21 05:19:08','persona',NULL,1,NULL),(10,'1722871470','GUAMÁN PEÑAFIEL MAYRA DANIELA','dannydolly74@gmail.com','AUXILIAR DE MANTENIMIENTO','+593 96 780 7320','','2026-02-21 05:19:08','persona',NULL,1,NULL),(11,'1709346587','IZURIETA TAPIA GRACE ROSARIO','gizurieta@tesa.edu.ec','COORDINADOR DE CARRERA ADMINISTRACIÓN','+593 99 497 0490','','2026-02-21 05:19:08','persona',NULL,1,NULL),(12,'1724475445','JARA LEÓN JOSÉ JAVIER','jjara@tesa.edu.ec','COORDINADOR DE PLANIFICACIÓN Y CALIDAD','+593 99 447 8054','','2026-02-21 05:19:08','persona',NULL,1,NULL),(13,'1724475379','JARA LEÓN KARLA SOFÍA','kjara@tesa.edu.ec','DIRECTORA DE ADMISIONES','+593 99 522 3001','','2026-02-21 05:19:08','persona',NULL,1,NULL),(14,'1719752055','LESCANO RECALDE RUTH ELIZABETH','contabilidad@tesa.edu.ec','CONTADORA','+593 99 835 6644','','2026-02-21 05:19:08','persona',NULL,1,NULL),(15,'1714596465','LONDOÑO PROAÑO RUTH SOLEDAD','slondono@tesa.edu.ec','DIRECTORA DE VINCULACIÓN','+593 99 515 4612','','2026-02-21 05:19:08','persona',NULL,1,NULL),(16,'1757771280','LORENZO EVORA YENI','ylorenzo@tesa.edu.ec','DIRECTORA BIENESTAR INSTITUCIONAL','+593 99 885 0882','','2026-02-21 05:19:08','persona',NULL,1,NULL),(17,'1715439574','MONTEVERDE BRAVO PAMELA','pmonteverde@tesa.edu.ec','DECANA DE PLANIFICACIÓN ACADÉMICA','+593 99 854 7320','','2026-02-21 05:19:08','persona',NULL,1,NULL),(18,'1600540247','MORALES SALAZÁR LUIS PABLO','pmorales@tesa.edu.ec','DIRECTOR DE TECNOLOGÍAS DE LA INFORMACIÓN','+593 98 487 3530','','2026-02-21 05:19:08','persona',NULL,1,NULL),(19,'1723337018','MUÑOZ ORTEGA LUIS AUGUSTO','lamunoz@tesa.edu.ec','DIRECTOR DE CARRERA APARATOLOGÍA DENTAL','+593 99 846 4453','','2026-02-21 05:19:08','persona',NULL,1,NULL),(20,'1716533862','NARANJO GARCÍA JOHANNA MONSERRAT','jnaranjo@tesa.edu.ec','COORDINADORA DE PROYECTOS DE INVESTIGACIÓN Y VINCULACIÓN','+593 99 144 7440','','2026-02-21 05:19:08','persona',NULL,1,NULL),(21,'1709640070','NOBOA REYES MARVIN JAIR','mnoboa@tesa.edu.ec','VICERRECTOR DE PLANIFICACIÓN Y CALIDAD','+593 99 988 1911','','2026-02-21 05:19:08','persona',NULL,1,NULL),(22,'1721737078','PAUCAR TIPANTUÑA ANA EMPERATRIZ','apaucar@tesa.edu.ec','COORDINADORA DE PROYECTOS DE TITULACIÓN','+593 99 286 5588','','2026-02-21 05:19:08','persona',NULL,1,NULL),(23,'1716453210','RECALDE CHALACÁN ÁNGEL REMIGIO','angelrecalde250@gmail.com','AUXILIAR DE MANTENIMIENTO','+593 98 315 1888','','2026-02-21 05:19:08','persona',NULL,1,NULL),(24,'1713152450','ROJAS BUKOVAC SANTIAGO XAVIER','srojas@tesa.edu.ec','DECANO DE ESCUELA DE TECNOLOGÍAS Y INNOVACCIÓN','+593 99 266 9761','','2026-02-21 05:19:08','persona',NULL,1,NULL),(25,'1750745620','RUDHOLM BALMACEDA PETER MICHAEL','prudholm@tesa.edu.ec','DIRECTOR DE LENGUAS','+593 98 487 2059','','2026-02-21 05:19:08','persona',NULL,1,NULL),(26,'1704694197','SANTAMARIA LARCO PABLO ROGER','psantamaria@tesa.edu.ec','SUPERVISOR DE LABORATORIO','+593 97 904 1507','','2026-02-21 05:19:08','persona',NULL,1,NULL),(27,'1720164233','SUASNAVAS VALENZUELA NATALI CRISTINA','nsuasnavas@tesa.edu.ec','DIRECTORA DECARRERA DERMATOCOSMIATRÍA','+593 99 980 8090','','2026-02-21 05:19:08','persona',NULL,1,NULL),(28,'1717910507','TASIGUANO POZO CRISTIAN ANDRES','ctasiguano@tesa.edu.ec','DIRECTOR DE INVESTIGACIÓN Y EDITORIAL','+593 98 466 2685','','2026-02-21 05:19:08','persona',NULL,1,NULL),(29,'1723891329','VÁZQUEZ JARA CYNTHIA','cvazquez@tesa.edu.ec','CANCILLER','+593 99 974 7509','','2026-02-21 05:19:08','persona',NULL,1,NULL),(30,'1716927874','VILLARROEL DAVID','dvillarroel@tesa.edu.ec','COORDINADOR DE ESTRATEGIA DIGITAL','+593 99 821 7400','','2026-02-21 05:19:08','persona',NULL,1,NULL),(31,'1727587832','YÁNEZ MARÍN DENISSE','dyanez@tesa.edu.ec','COORDINADOR DE CARRERA APARATOLOGÍA DENTAL','+593 98 709 9169','','2026-02-21 05:19:08','persona',NULL,1,NULL),(32,'1751483189','MUÑOZ RAMIREZ KAREN NICOLE','kmunoz@tesa.edu.ec','ASESOR ADMISIONES','+593 96 925 8447','','2026-02-21 05:19:08','persona',NULL,1,NULL),(33,'1309507299','ZAMBRANO MACÍAS DEMNYS PATRICIO','patricio_zambrano@outlook.com','CHOFER','+593 99 735 2000','','2026-02-21 05:19:08','persona',NULL,1,NULL),(34,'1726867136','LOAYZA LALAMA ALISSON SAMANTHA','aloayza@tesa.edu.ec','SECRETARIA GENERAL','+593 99 977 7021','','2026-02-21 05:19:08','persona',NULL,1,NULL),(74,'UBI-SL101','Salón 101',NULL,'Aula de clases',NULL,NULL,'2026-02-22 05:40:37','salon','SL-101',1,NULL),(75,'UBI-SL102','Salón 102',NULL,'Aula de clases',NULL,NULL,'2026-02-22 05:40:37','salon','SL-102',1,NULL),(76,'UBI-LAB01','Laboratorio de Cómputo 1',NULL,'Laboratorio',NULL,NULL,'2026-02-22 05:40:37','laboratorio','LAB-01',1,NULL),(83,'1717601461','Carlos Montiel','isacc48@hotmail.com','estudiante','0992630785','se envia  mouse en bune estado','2026-03-02 16:38:08','persona',NULL,1,NULL),(84,'1750482760','axel','axelpsoriano03@gmail.com','practicante','0963704531','','2026-03-24 16:59:36','persona',NULL,1,NULL),(85,'1749853759','mateo','mateo@gmail.com','practicante','0963704531','','2026-03-24 22:16:19','persona',NULL,1,'2026-03-24 17:18:12');
/*!40000 ALTER TABLE `personas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prestamos_rapidos`
--

DROP TABLE IF EXISTS `prestamos_rapidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prestamos_rapidos`
--

LOCK TABLES `prestamos_rapidos` WRITE;
/*!40000 ALTER TABLE `prestamos_rapidos` DISABLE KEYS */;
/*!40000 ALTER TABLE `prestamos_rapidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `secuencias_actas`
--

DROP TABLE IF EXISTS `secuencias_actas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `secuencias_actas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_acta` varchar(20) NOT NULL,
  `ultima_secuencia` int(11) NOT NULL DEFAULT 0,
  `anio` int(4) NOT NULL,
  `mes` int(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tipo_mes` (`tipo_acta`,`anio`,`mes`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `secuencias_actas`
--

LOCK TABLES `secuencias_actas` WRITE;
/*!40000 ALTER TABLE `secuencias_actas` DISABLE KEYS */;
INSERT INTO `secuencias_actas` VALUES (1,'entrega',0,2026,3),(2,'devolucion',0,2026,3),(3,'descargo',0,2026,3);
/*!40000 ALTER TABLE `secuencias_actas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ubicaciones`
--

DROP TABLE IF EXISTS `ubicaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicaciones`
--

LOCK TABLES `ubicaciones` WRITE;
/*!40000 ALTER TABLE `ubicaciones` DISABLE KEYS */;
INSERT INTO `ubicaciones` VALUES (1,'Salón 101','salon','SL-101',NULL,NULL),(2,'Salón 102','salon','SL-102',NULL,NULL),(3,'Laboratorio 1','laboratorio','LAB-01',NULL,NULL),(4,'Biblioteca Central','biblioteca','BIB-001',NULL,NULL),(5,'Aula Magna','salon','AM-01',NULL,NULL),(6,'Bodega Principal','salon','BOD-01',NULL,NULL),(7,'Bodega Secundaria','salon','BOD-02',NULL,NULL),(8,'Oficina Administrativa','salon','OFI-01',NULL,NULL);
/*!40000 ALTER TABLE `ubicaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','usuario') DEFAULT 'usuario',
  `avatar` varchar(255) DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','admin@tesa.edu.ec','$2y$10$9.evTQdOxdp90zqnrFHaL.EFbktbyjn6jiNTL4KRjbv5ccrkXpM/u','admin',NULL,'2026-03-25 15:36:18','2026-03-04 12:26:45','2026-02-22 04:09:39'),(3,'Usuario Invitado','invitado@tesa.edu.ec','$2y$10$DAcd4c27ATooQPMHTa9c8ugoQFQKbQeUzyrGFAjDIJGNQ8VXtli66','usuario',NULL,'2026-03-04 13:33:18','2026-03-04 12:26:45','2026-03-03 19:46:20');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'inventario_ti'
--

--
-- Dumping routines for database 'inventario_ti'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-25 15:45:02
