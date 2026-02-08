CREATE DATABASE  IF NOT EXISTS `rest_bar` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `rest_bar`;
-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: rest_bar
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `ID_Categoria` int NOT NULL AUTO_INCREMENT,
  `Nombre_Categoria` varchar(50) NOT NULL,
  `is_food` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID_Categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `ID_Cliente` int NOT NULL AUTO_INCREMENT,
  `Nombre_Cliente` varchar(100) NOT NULL,
  `RUC` varchar(100) DEFAULT NULL,
  `Telefono` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`ID_Cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `detalle_venta`
--

DROP TABLE IF EXISTS `detalle_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_venta` (
  `ID_Detalle` int NOT NULL AUTO_INCREMENT,
  `ID_Venta` int DEFAULT NULL,
  `ID_Producto` int DEFAULT NULL,
  `Precio_Venta` decimal(10,2) NOT NULL,
  `Cantidad` int NOT NULL,
  `Subtotal` decimal(10,2) DEFAULT NULL,
  `ID_Parcial` int DEFAULT NULL,
  `Preparacion` text,
  PRIMARY KEY (`ID_Detalle`),
  KEY `ID_Venta` (`ID_Venta`),
  KEY `ID_Producto` (`ID_Producto`),
  KEY `fk_detalle_parcial` (`ID_Parcial`),
  CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`ID_Venta`) REFERENCES `ventas` (`ID_Venta`),
  CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`ID_Producto`) REFERENCES `productos` (`ID_Producto`),
  CONSTRAINT `fk_detalle_parcial` FOREIGN KEY (`ID_Parcial`) REFERENCES `parciales_venta` (`ID_Parcial`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `empleados`
--

DROP TABLE IF EXISTS `empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empleados` (
  `ID_Empleado` int NOT NULL AUTO_INCREMENT,
  `Nombre_Completo` varchar(100) NOT NULL,
  `Correo` varchar(100) DEFAULT NULL,
  `Telefono` varchar(15) DEFAULT NULL,
  `Fecha_Contratacion` date NOT NULL,
  `ID_Usuario` int DEFAULT NULL,
  PRIMARY KEY (`ID_Empleado`),
  UNIQUE KEY `Correo` (`Correo`),
  KEY `ID_Usuario` (`ID_Usuario`),
  CONSTRAINT `empleados_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuarios` (`ID_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `liberaciones_mesa`
--

DROP TABLE IF EXISTS `liberaciones_mesa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `liberaciones_mesa` (
  `ID_Liberacion` int NOT NULL AUTO_INCREMENT,
  `ID_Mesa` int NOT NULL,
  `ID_Usuario` int NOT NULL,
  `Motivo` varchar(100) NOT NULL,
  `Descripcion` text,
  `Fecha_Hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_Liberacion`),
  KEY `ID_Mesa` (`ID_Mesa`),
  KEY `ID_Usuario` (`ID_Usuario`),
  CONSTRAINT `liberaciones_mesa_ibfk_1` FOREIGN KEY (`ID_Mesa`) REFERENCES `mesas` (`ID_Mesa`),
  CONSTRAINT `liberaciones_mesa_ibfk_2` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuarios` (`ID_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mesas`
--

DROP TABLE IF EXISTS `mesas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mesas` (
  `ID_Mesa` int NOT NULL AUTO_INCREMENT,
  `Numero_Mesa` int NOT NULL,
  `Capacidad` int DEFAULT NULL,
  `Estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID_Mesa`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `movimientos`
--

DROP TABLE IF EXISTS `movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos` (
  `ID_Movimiento` int NOT NULL AUTO_INCREMENT,
  `Fecha_Hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Tipo` varchar(30) NOT NULL,
  `Monto` decimal(10,2) NOT NULL,
  `Descripcion` varchar(255) DEFAULT NULL,
  `ID_Usuario` int DEFAULT NULL,
  `ID_Venta` int DEFAULT NULL,
  PRIMARY KEY (`ID_Movimiento`),
  KEY `ID_Usuario` (`ID_Usuario`),
  KEY `ID_Venta` (`ID_Venta`),
  CONSTRAINT `movimientos_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuarios` (`ID_usuario`),
  CONSTRAINT `movimientos_ibfk_2` FOREIGN KEY (`ID_Venta`) REFERENCES `ventas` (`ID_Venta`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos` (
  `ID_Pago` int NOT NULL AUTO_INCREMENT,
  `ID_Venta` int NOT NULL,
  `Metodo` varchar(255) DEFAULT NULL,
  `Monto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `Es_Cambio` tinyint(1) NOT NULL DEFAULT '0',
  `Fecha_Hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_Pago`),
  KEY `idx_pago_venta` (`ID_Venta`),
  CONSTRAINT `fk_pagos_venta` FOREIGN KEY (`ID_Venta`) REFERENCES `ventas` (`ID_Venta`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pagos_pedido`
--

DROP TABLE IF EXISTS `pagos_pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos_pedido` (
  `ID_Pago` int NOT NULL AUTO_INCREMENT,
  `ID_Pedido` int NOT NULL,
  `Metodo` varchar(255) DEFAULT NULL,
  `Monto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `Es_Cambio` tinyint(1) NOT NULL DEFAULT '0',
  `Fecha_Hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_Pago`),
  KEY `fk_pagos_pedido_pedido_idx` (`ID_Pedido`),
  KEY `idx_pagos_pedido_idpedido` (`ID_Pedido`),
  CONSTRAINT `fk_pagos_pedido_pedido` FOREIGN KEY (`ID_Pedido`) REFERENCES `pedidos` (`ID_Pedido`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `parciales_venta`
--

DROP TABLE IF EXISTS `parciales_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parciales_venta` (
  `ID_Parcial` int NOT NULL AUTO_INCREMENT,
  `ID_Venta` int NOT NULL,
  `nombre_cliente` varchar(100) DEFAULT NULL,
  `pagado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID_Parcial`),
  KEY `ID_Venta` (`ID_Venta`),
  CONSTRAINT `parciales_venta_ibfk_1` FOREIGN KEY (`ID_Venta`) REFERENCES `ventas` (`ID_Venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedido_detalles`
--

DROP TABLE IF EXISTS `pedido_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_detalles` (
  `ID_Detalle_Pedido` int NOT NULL AUTO_INCREMENT,
  `ID_Pedido` int NOT NULL,
  `ID_Producto` int NOT NULL,
  `Cantidad` int NOT NULL DEFAULT '1',
  `Precio_Unitario` decimal(12,2) NOT NULL,
  `Subtotal` decimal(12,2) NOT NULL,
  `preparacion` varchar(255) DEFAULT NULL,
  `fecha_creado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_Detalle_Pedido`),
  KEY `fk_pedido_det_pedido` (`ID_Pedido`),
  KEY `fk_pedido_det_producto` (`ID_Producto`),
  CONSTRAINT `fk_pedido_det_pedido` FOREIGN KEY (`ID_Pedido`) REFERENCES `pedidos` (`ID_Pedido`) ON DELETE CASCADE,
  CONSTRAINT `fk_pedido_det_producto` FOREIGN KEY (`ID_Producto`) REFERENCES `productos` (`ID_Producto`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `ID_Pedido` int NOT NULL AUTO_INCREMENT,
  `tipo_entrega` enum('local','para_llevar','recoger','delivery') NOT NULL DEFAULT 'local',
  `nombre_cliente` varchar(150) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `notas` text,
  `fecha_creado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_Pedido`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `pedidos_view`
--

DROP TABLE IF EXISTS `pedidos_view`;
/*!50001 DROP VIEW IF EXISTS `pedidos_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `pedidos_view` AS SELECT 
 1 AS `ID_Pedido`,
 1 AS `tipo_entrega`,
 1 AS `nombre_cliente`,
 1 AS `telefono`,
 1 AS `fecha_creado`,
 1 AS `total_pedido`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `ID_Producto` int NOT NULL AUTO_INCREMENT,
  `Nombre_Producto` varchar(100) NOT NULL,
  `Precio_Costo` decimal(10,2) NOT NULL,
  `Precio_Venta` decimal(10,2) NOT NULL,
  `ID_Categoria` int DEFAULT NULL,
  `Stock` int NOT NULL,
  PRIMARY KEY (`ID_Producto`),
  KEY `ID_Categoria` (`ID_Categoria`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`ID_Categoria`) REFERENCES `categorias` (`ID_Categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `ID_Rol` int NOT NULL AUTO_INCREMENT,
  `Nombre_Rol` varchar(50) NOT NULL,
  PRIMARY KEY (`ID_Rol`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `ID_usuario` int NOT NULL AUTO_INCREMENT,
  `Nombre_Usuario` varchar(30) DEFAULT NULL,
  `Contrasenia` varchar(255) DEFAULT NULL,
  `ID_Rol` int DEFAULT NULL,
  `Estado` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ID_usuario`),
  UNIQUE KEY `Nombre_Usuario_UNIQUE` (`Nombre_Usuario`),
  KEY `ID_Rol` (`ID_Rol`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`ID_Rol`) REFERENCES `roles` (`ID_Rol`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `ID_Venta` int NOT NULL AUTO_INCREMENT,
  `ID_Cliente` int DEFAULT NULL,
  `ID_Mesa` int DEFAULT NULL,
  `Fecha_Hora` datetime NOT NULL,
  `Total` decimal(10,2) DEFAULT NULL,
  `Metodo_Pago` varchar(200) DEFAULT NULL,
  `ID_Empleado` int DEFAULT NULL,
  `Estado` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `Servicio` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`ID_Venta`),
  KEY `ID_Cliente` (`ID_Cliente`),
  KEY `ID_Mesa` (`ID_Mesa`),
  KEY `ID_Empleado` (`ID_Empleado`),
  CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`ID_Cliente`) REFERENCES `clientes` (`ID_Cliente`),
  CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`ID_Mesa`) REFERENCES `mesas` (`ID_Mesa`),
  CONSTRAINT `ventas_ibfk_3` FOREIGN KEY (`ID_Empleado`) REFERENCES `empleados` (`ID_Empleado`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'rest_bar'
--

--
-- Dumping routines for database 'rest_bar'
--
/*!50003 DROP PROCEDURE IF EXISTS `sp_AddProduct` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AddProduct`(
    IN p_nombre VARCHAR(100),
    IN p_precio_costo DECIMAL(10,2),
    IN p_precio_venta DECIMAL(10,2),
    IN p_id_categoria INT,
    IN p_stock INT
)
BEGIN
    INSERT INTO productos (Nombre_Producto, Precio_Costo, Precio_Venta, ID_Categoria, Stock)
    VALUES (p_nombre, p_precio_costo, p_precio_venta, p_id_categoria, p_stock);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_AddSaleDetail` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AddSaleDetail`(
    IN p_id_venta INT,
    IN p_id_producto INT,
    IN p_cantidad INT,
    IN p_precio DECIMAL(10,2)
)
BEGIN
    DECLARE detalle_id INT;
    SELECT ID_Detalle INTO detalle_id
    FROM detalle_venta
    WHERE ID_Venta = p_id_venta AND ID_Producto = p_id_producto
    LIMIT 1;

    IF detalle_id IS NOT NULL THEN
        UPDATE detalle_venta
        SET Cantidad = Cantidad + p_cantidad
        WHERE ID_Detalle = detalle_id;
        -- Corregido: ahora el subtotal es la cantidad actualizada * precio
        UPDATE detalle_venta
        SET Subtotal = Cantidad * p_precio
        WHERE ID_Detalle = detalle_id;
    ELSE
        INSERT INTO detalle_venta (ID_Venta, ID_Producto, Cantidad, Precio_Venta, Subtotal)
        VALUES (p_id_venta, p_id_producto, p_cantidad, p_precio, p_cantidad * p_precio);
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_CheckUserExists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CheckUserExists`(
    IN p_NombreUsuario VARCHAR(30),
    OUT p_Exists BOOLEAN
)
BEGIN
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE Nombre_Usuario = p_NombreUsuario
    ) INTO p_Exists;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_CreateCategory` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreateCategory`(in p_Nombre_Categoria varchar(50))
begin
	insert into categorias(Nombre_Categoria) values(p_Nombre_Categoria);
end ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_CreateSale` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreateSale`(
    IN p_idCliente INT,
    IN p_idMesa INT,
    IN p_metodoPago VARCHAR(50),
    IN p_idEmpleado INT
)
BEGIN
    INSERT INTO ventas (ID_Cliente, ID_Mesa, Metodo_Pago, ID_Empleado, Estado, Fecha_Hora)
    VALUES (p_idCliente, p_idMesa, p_metodoPago, p_idEmpleado, 'Pendiente', NOW());
    SELECT LAST_INSERT_ID() AS ID_Venta;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_CreateUserWithEmployee` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreateUserWithEmployee`(
    IN p_NombreUsuario VARCHAR(30),
    IN p_Contrasenia VARCHAR(255),
    IN p_IDRol INT,
    IN p_NombreCompleto VARCHAR(100),
    IN p_Correo VARCHAR(100),
    IN p_Telefono VARCHAR(15),
    IN p_FechaContratacion DATE
)
BEGIN
    DECLARE v_IDUsuario INT;
    
    START TRANSACTION;
    
    INSERT INTO usuarios (Nombre_Usuario, Contrasenia, ID_Rol)
    VALUES (p_NombreUsuario, p_Contrasenia, p_IDRol);
    
    SET v_IDUsuario = LAST_INSERT_ID();
        
    INSERT INTO empleados (
        Nombre_Completo,
        Correo,
        Telefono,
        Fecha_Contratacion,
        ID_Usuario
    )
    VALUES (
        p_NombreCompleto,
        p_Correo,
        p_Telefono,
        p_FechaContratacion,
        v_IDUsuario
    );
    
    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_DeleteEmployee` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_DeleteEmployee`(IN p_ID_Empleado INT)
BEGIN
    START TRANSACTION;
    
    DELETE u FROM usuarios u
    INNER JOIN empleados e ON u.ID_Usuario = e.ID_Usuario
    WHERE e.ID_Empleado = p_ID_Empleado;
    
    DELETE FROM empleados WHERE ID_Empleado = p_ID_Empleado;
    
    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_GetAllCategories` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetAllCategories`()
begin
select * from categorias;
end ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_GetAllEmployees` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetAllEmployees`()
BEGIN
    SELECT e.ID_Empleado, e.ID_Usuario, e.Nombre_Completo, e.Telefono, e.Correo, u.Nombre_Usuario,u.ID_Rol
    FROM empleados e
    JOIN usuarios u ON e.ID_Usuario = u.ID_usuario;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_GetAllProducts` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetAllProducts`()
BEGIN
    SELECT p.*, c.Nombre_Categoria 
    FROM productos p
    INNER JOIN categorias c ON p.ID_Categoria = c.ID_Categoria
    ORDER BY p.Nombre_Producto;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_GetAllRols` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetAllRols`()
BEGIN
	Select * from roles;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_GetAllTables` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetAllTables`()
BEGIN
    SELECT * FROM mesas ORDER BY Numero_Mesa;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_GetDailySales` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetDailySales`(
    IN p_fecha DATE
)
BEGIN
    SELECT v.*, c.Nombre_Cliente, e.Nombre_Completo as Empleado
    FROM ventas v
    INNER JOIN clientes c ON v.ID_Cliente = c.ID_Cliente
    INNER JOIN empleados e ON v.ID_Empleado = e.ID_Empleado
    WHERE DATE(v.Fecha_Hora) = p_fecha;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_GetDashboardStats` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetDashboardStats`()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM mesas WHERE Estado = 1) as MesasOcupadas,
        (SELECT COUNT(*) FROM mesas) as TotalMesas,
        (SELECT COALESCE(SUM(Total), 0) FROM ventas WHERE DATE(Fecha_Hora) = CURDATE()) as VentasDiarias,
        (SELECT COUNT(*) FROM ventas WHERE DATE(Fecha_Hora) = CURDATE()) as OrdenesHoy;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_GetEmployeeById` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetEmployeeById`(IN p_ID_Usuario INT)
BEGIN
    SELECT e.*, u.Nombre_Usuario ,u.ID_Rol
    FROM empleados e
    JOIN usuarios u ON e.ID_Usuario = u.ID_usuario  -- Ensure column names match the schema
    WHERE e.ID_Usuario = p_ID_Usuario;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_GetSaleDetails` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetSaleDetails`(
    IN p_id_venta INT
)
BEGIN
    SELECT d.*, p.Nombre_Producto
    FROM detalle_venta d
    INNER JOIN productos p ON d.ID_Producto = p.ID_Producto
    WHERE d.ID_Venta = p_id_venta;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_UpdateEmployee` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_UpdateEmployee`(
    IN p_ID_Empleado INT,
    IN p_Nombre_Completo VARCHAR(100),
    IN p_Correo VARCHAR(100),
    IN p_Telefono VARCHAR(15),
    IN p_ID_Usuario INT,
    IN p_Nombre_Usuario VARCHAR(30),
    IN p_ID_Rol INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error al actualizar empleado y usuario';
    END;

    START TRANSACTION;

    -- Actualizar tabla empleados
    UPDATE empleados
    SET
        Nombre_Completo = p_Nombre_Completo,
        Correo = p_Correo,
        Telefono = p_Telefono,
        ID_Usuario = p_ID_Usuario
    WHERE
        ID_Empleado = p_ID_Empleado;

    -- Actualizar tabla usuarios
    UPDATE usuarios
    SET
        Nombre_Usuario = p_Nombre_Usuario,
        ID_Rol = p_ID_Rol
    WHERE
        ID_usuario = p_ID_Usuario;

    COMMIT;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_UpdateProduct` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_UpdateProduct`(
    IN p_id_producto INT,
    IN p_nombre VARCHAR(100),
    IN p_precio_costo DECIMAL(10,2),
    IN p_precio_venta DECIMAL(10,2),
    IN p_id_categoria INT,
    IN p_stock INT
)
BEGIN
    UPDATE productos 
    SET Nombre_Producto = p_nombre,
        Precio_Costo = p_precio_costo,
        Precio_Venta = p_precio_venta,
        ID_Categoria = p_id_categoria,
        Stock = p_stock
    WHERE ID_Producto = p_id_producto;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_UpdateTableStatus` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_UpdateTableStatus`(
    IN p_id_mesa INT,
    IN p_estado BOOLEAN
)
BEGIN
    UPDATE mesas SET Estado = p_estado WHERE ID_Mesa = p_id_mesa;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_UpdateUser` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_UpdateUser`(
    IN p_user_id INT,
    IN p_username VARCHAR(30),
    IN p_password VARCHAR(255),
    IN p_role_id INT
)
BEGIN
    IF p_password IS NOT NULL THEN
        UPDATE usuarios 
        SET Nombre_Usuario = p_username,
            Contrasenia = p_password,
            ID_Rol = p_role_id
        WHERE ID_Usuario = p_user_id;
    ELSE
        UPDATE usuarios 
        SET Nombre_Usuario = p_username,
            ID_Rol = p_role_id
        WHERE ID_Usuario = p_user_id;
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_ValidateUser` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ValidateUser`(IN p_username VARCHAR(30))
BEGIN
    SELECT 
        u.ID_Usuario,
        u.Nombre_Usuario,
        u.Contrasenia,
        r.Nombre_Rol,
        e.Nombre_Completo
    FROM usuarios u
    INNER JOIN roles r ON u.ID_Rol = r.ID_Rol
    LEFT JOIN empleados e ON u.ID_Usuario = e.ID_Usuario
    WHERE u.Nombre_Usuario = p_username;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `pedidos_view`
--

/*!50001 DROP VIEW IF EXISTS `pedidos_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `pedidos_view` AS select `p`.`ID_Pedido` AS `ID_Pedido`,`p`.`tipo_entrega` AS `tipo_entrega`,`p`.`nombre_cliente` AS `nombre_cliente`,`p`.`telefono` AS `telefono`,`p`.`fecha_creado` AS `fecha_creado`,coalesce(sum(`pd`.`Subtotal`),0) AS `total_pedido` from (`pedidos` `p` left join `pedido_detalles` `pd` on((`pd`.`ID_Pedido` = `p`.`ID_Pedido`))) group by `p`.`ID_Pedido` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-07 20:25:59
