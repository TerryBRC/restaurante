<?php
require_once __DIR__ . '/../config/database.php';

class ReporteModel {
    public function getVentasDiariasPorEmpleado($fecha) {
                $conn = (new Database())->connect();
                $stmt = $conn->prepare("
                        SELECT e.Nombre_Completo AS empleado, COUNT(v.ID_Venta) AS ventas, SUM(v.Total + IFNULL(v.Servicio,0)) AS total
                        FROM ventas v
                        INNER JOIN empleados e ON v.ID_Empleado = e.ID_Empleado
                        WHERE DATE(v.Fecha_Hora) = ?
                            AND v.Estado = 'Pagada'
                        GROUP BY e.ID_Empleado
                        ORDER BY total DESC
                ");
                $stmt->execute([$fecha]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductosVendidosPorFecha($fecha) {
        $conn = (new Database())->connect();
        $stmt = $conn->prepare("
            SELECT 
                p.Nombre_Producto, 
                c.Nombre_Categoria, 
                SUM(dv.Cantidad) AS Cantidad, 
                SUM(dv.Subtotal) AS TotalVendido
            FROM detalle_venta dv
            INNER JOIN productos p ON dv.ID_Producto = p.ID_Producto
            INNER JOIN categorias c ON p.ID_Categoria = c.ID_Categoria
            INNER JOIN ventas v ON dv.ID_Venta = v.ID_Venta
            WHERE DATE(v.Fecha_Hora) = ?
              AND v.Estado = 'Pagada'
            GROUP BY p.ID_Producto, c.ID_Categoria
            ORDER BY TotalVendido DESC
        ");
        $stmt->execute([$fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCierreCajaDiario($fecha) {
                $conn = (new Database())->connect();
                $stmt = $conn->prepare("
                        SELECT v.ID_Venta, v.Fecha_Hora, e.Nombre_Completo AS usuario, v.Metodo_Pago, v.Total, IFNULL(v.Servicio,0) AS Servicio, (v.Total + IFNULL(v.Servicio,0)) AS TotalFinal
                        FROM ventas v
                        INNER JOIN empleados e ON v.ID_Empleado = e.ID_Empleado
                        WHERE DATE(v.Fecha_Hora) = ?
                            AND v.Estado = 'Pagada'
                        ORDER BY v.Fecha_Hora ASC
                ");
                $stmt->execute([$fecha]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInventario() {
        $conn = (new Database())->connect();
        $stmt = $conn->prepare("
            SELECT p.*, c.Nombre_Categoria
            FROM productos p
            INNER JOIN categorias c ON p.ID_Categoria = c.ID_Categoria
            ORDER BY p.Nombre_Producto ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas desde una fecha/hora específica (para cierre de caja por sesión)
     * @param string $fechaHora Fecha y hora en formato 'Y-m-d H:i:s'
     * @return array
     */
    public function getVentasDesde($fechaHora) {
        $conn = (new Database())->connect();
        $stmt = $conn->prepare("
            SELECT v.ID_Venta, v.Fecha_Hora, e.Nombre_Completo AS usuario, v.Metodo_Pago, v.Total, IFNULL(v.Servicio,0) AS Servicio, (v.Total + IFNULL(v.Servicio,0)) AS TotalFinal
            FROM ventas v
            INNER JOIN empleados e ON v.ID_Empleado = e.ID_Empleado
            WHERE v.Fecha_Hora >= ?
                AND v.Estado = 'Pagada'
            ORDER BY v.Fecha_Hora ASC
        ");
        $stmt->execute([$fechaHora]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas específicas por IDs (para cierre de caja por sesión)
     * @param array $idsVentas Array de IDs de ventas
     * @return array
     */
    public function getVentasByIds($idsVentas) {
        if (empty($idsVentas)) {
            return [];
        }
        $conn = (new Database())->connect();
        $placeholders = implode(',', array_fill(0, count($idsVentas), '?'));
        $stmt = $conn->prepare("
            SELECT v.ID_Venta, v.Fecha_Hora, e.Nombre_Completo AS usuario, v.Metodo_Pago, v.Total, IFNULL(v.Servicio,0) AS Servicio, (v.Total + IFNULL(v.Servicio,0)) AS TotalFinal
            FROM ventas v
            INNER JOIN empleados e ON v.ID_Empleado = e.ID_Empleado
            WHERE v.ID_Venta IN ($placeholders)
            ORDER BY v.Fecha_Hora ASC
        ");
        $stmt->execute($idsVentas);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene datos para el reporte de cierre de caja (pre-cierre)
     * @return array ['monto_apertura', 'total_efectivo', 'total_tarjeta', 'total_ventas', 'total_ingresos', 'total_egresos']
     */
    public function obtenerReporteCierreCaja() {
        $conn = (new Database())->connect();
        
        // 1. Obtener monto de apertura desde la última apertura
        $stmt = $conn->query("SELECT Monto FROM movimientos WHERE Tipo='Apertura' ORDER BY ID_Movimiento DESC LIMIT 1");
        $apertura = $stmt->fetch(PDO::FETCH_ASSOC);
        $monto_apertura = $apertura ? floatval($apertura['Monto']) : 0;
        
        // 2. Obtener fecha de la última apertura para filtrar ventas
        $stmt = $conn->query("SELECT Fecha_Hora FROM movimientos WHERE Tipo='Apertura' ORDER BY ID_Movimiento DESC LIMIT 1");
        $fechaApertura = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 3. Obtener ventas desde la última apertura (solo pagadas)
        if ($fechaApertura) {
            $stmt = $conn->prepare("
                SELECT
                    Metodo_Pago,
                    COUNT(*) as cantidad,
                    SUM(Total) as total_ventas,
                    SUM(Servicio) as total_servicio
                FROM ventas
                WHERE Fecha_Hora >= ?
                    AND Estado = 'Pagada'
                GROUP BY Metodo_Pago
            ");
            $stmt->execute([$fechaApertura['Fecha_Hora']]);
            $ventasPorMetodo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $ventasPorMetodo = [];
        }
        
        // 4. Calcular totales por método de pago
        $total_efectivo = 0;
        $total_tarjeta = 0;
        $total_ventas = 0;
        
        foreach ($ventasPorMetodo as $venta) {
            $monto = floatval($venta['total_ventas']) + floatval($venta['total_servicio']);
            $total_ventas += $monto;
            
            if (stripos($venta['Metodo_Pago'], 'efectivo') !== false) {
                $total_efectivo += $monto;
            } elseif (stripos($venta['Metodo_Pago'], 'tarjeta') !== false || stripos($venta['Metodo_Pago'], 'card') !== false) {
                $total_tarjeta += $monto;
            }
        }
        
        // 5. Obtener ingresos (que no sean apertura o cierre) desde la última apertura
        if ($fechaApertura) {
            $stmt = $conn->prepare("
                SELECT SUM(Monto) as total
                FROM movimientos
                WHERE Tipo = 'Ingreso'
                    AND Fecha_Hora >= ?
            ");
            $stmt->execute([$fechaApertura['Fecha_Hora']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_ingresos = $row ? floatval($row['total']) : 0;
        } else {
            $total_ingresos = 0;
        }
        
        // 6. Obtener egresos desde la última apertura
        if ($fechaApertura) {
            $stmt = $conn->prepare("
                SELECT SUM(Monto) as total
                FROM movimientos
                WHERE Tipo = 'Egreso'
                    AND Fecha_Hora >= ?
            ");
            $stmt->execute([$fechaApertura['Fecha_Hora']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_egresos = $row ? floatval($row['total']) : 0;
        } else {
            $total_egresos = 0;
        }
        
        return [
            'monto_apertura' => $monto_apertura,
            'total_efectivo' => $total_efectivo,
            'total_tarjeta' => $total_tarjeta,
            'total_ventas' => $total_ventas,
            'total_ingresos' => $total_ingresos,
            'total_egresos' => $total_egresos
        ];
    }
}