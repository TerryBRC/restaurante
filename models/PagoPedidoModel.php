<?php
class PagoPedidoModel {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Insert a payment for a pedido
     * @param array $data ['ID_Pedido', 'Metodo', 'Monto', 'Es_Cambio']
     * @return int Inserted ID_Pago
     */
    public function createPago(array $data) {
        $sql = "INSERT INTO pagos_pedido (ID_Pedido, Metodo, Monto, Es_Cambio, Fecha_Hora) VALUES (:id_pedido, :metodo, :monto, :es_cambio, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_pedido', $data['ID_Pedido'], PDO::PARAM_INT);
        $stmt->bindValue(':metodo', $data['Metodo'], PDO::PARAM_STR);
        $stmt->bindValue(':monto', $data['Monto']);
        $stmt->bindValue(':es_cambio', $data['Es_Cambio'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return 0;
    }

    /**
     * Get pagos for a pedido
     */
    public function getPagosByPedido($idPedido) {
        $sql = "SELECT * FROM pagos_pedido WHERE ID_Pedido = :id ORDER BY Fecha_Hora DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idPedido, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
