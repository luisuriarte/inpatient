-- --------------------------------------------------------
-- Host:                         192.168.1.20
-- Versión del servidor:         10.11.8-MariaDB - MariaDB Server
-- SO del servidor:              Linux
-- HeidiSQL Versión:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Volcando estructura para tabla openemr.beds
DROP TABLE IF EXISTS `beds`;
CREATE TABLE IF NOT EXISTS `beds` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uuid` binary(16) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `obs` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `action` varchar(15) DEFAULT NULL,
  `user_modif` varchar(255) DEFAULT NULL,
  `datetime_modif` datetime DEFAULT NULL,
  UNIQUE KEY `uuid` (`uuid`) USING BTREE,
  KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla openemr.rooms
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uuid` binary(16) DEFAULT NULL,
  `units_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `number_of_beds` int(3) DEFAULT NULL,
  `obs` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `action` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `user_modif` varchar(255) DEFAULT NULL,
  `datetime_modif` datetime DEFAULT NULL,
  UNIQUE KEY `uuid` (`uuid`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla openemr.units
DROP TABLE IF EXISTS `units`;
CREATE TABLE IF NOT EXISTS `units` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uuid` binary(64) DEFAULT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL COMMENT 'Unit Name',
  `number_of_rooms` int(3) DEFAULT NULL,
  `obs` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL COMMENT '0-> Active, 1->Inactive',
  `action` varchar(15) DEFAULT NULL COMMENT 'add, edit & delete',
  `user_modif` varchar(255) DEFAULT NULL,
  `datetime_modif` datetime DEFAULT NULL,
  UNIQUE KEY `uuid` (`uuid`) USING BTREE,
  KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
