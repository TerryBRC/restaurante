-- Migration: create pagos_pedido table
-- Run this on your MySQL server to add the payments table for pedidos
CREATE TABLE IF NOT EXISTS `pagos_pedido` (
  `ID_Pago` int NOT NULL AUTO_INCREMENT,
  `ID_Pedido` int NOT NULL,
  `Metodo` varchar(255) DEFAULT NULL,
  `Monto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `Es_Cambio` tinyint(1) NOT NULL DEFAULT '0',
  `Fecha_Hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_Pago`),
  KEY `fk_pagos_pedido_pedido_idx` (`ID_Pedido`),
  CONSTRAINT `fk_pagos_pedido_pedido` FOREIGN KEY (`ID_Pedido`) REFERENCES `pedidos` (`ID_Pedido`) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Optional: sample index to speed lookups by pedido
CREATE INDEX IF NOT EXISTS idx_pagos_pedido_idpedido ON pagos_pedido (ID_Pedido);
