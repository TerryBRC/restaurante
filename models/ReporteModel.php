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
}