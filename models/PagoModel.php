<?php
require_once __DIR__ . '/../config/database.php';

class PagoModel {
    private $conn;
    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    /**
     * Registra un pago individual asociado a una venta
     * @param int $idVenta
     * @param string $metodo
     * @param float $monto
     * @param bool $esCambio
     * @return bool
     */
    public function registrarPago($idVenta, $metodo, $monto, $esCambio = false) {
        try {
            $stmt = $this->conn->prepare('INSERT INTO pagos (ID_Venta, Metodo, Monto, Es_Cambio, Fecha_Hora) VALUES (?, ?, ?, ?, NOW())');
            return $stmt->execute([$idVenta, $metodo, $monto, $esCambio ? 1 : 0]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getPagosByVenta($idVenta) {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM pagos WHERE ID_Venta = ? ORDER BY Fecha_Hora');
            $stmt->execute([$idVenta]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
}
