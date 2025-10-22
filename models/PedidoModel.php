<?php
require_once __DIR__ . '/../config/database.php';

class PedidoModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Devuelve todos los pedidos (usa la vista pedidos_view)
     * @return array
     */
    public function getAllPedidos() {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM pedidos_view ORDER BY fecha_creado DESC');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PedidoModel::getAllPedidos error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Devuelve los pedidos de una fecha específica (YYYY-MM-DD).
     * Incluye el total pagado y el restante calculado usando pagos_pedido.
     * @param string $fecha
     * @return array
     */
    public function getPedidosByFecha($fecha) {
        try {
            $sql = "SELECT pv.*, COALESCE(pagos.sum_pagado, 0) as pagado, (COALESCE(pv.total_pedido,0) - COALESCE(pagos.sum_pagado,0)) as restante
                    FROM pedidos_view pv
                    LEFT JOIN (
                        SELECT ID_Pedido, SUM(CASE WHEN Es_Cambio=0 THEN Monto ELSE 0 END) as sum_pagado
                        FROM pagos_pedido
                        GROUP BY ID_Pedido
                    ) pagos ON pagos.ID_Pedido = pv.ID_Pedido
                    WHERE DATE(pv.fecha_creado) = ?
                    ORDER BY pv.fecha_creado DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$fecha]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PedidoModel::getPedidosByFecha error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Devuelve los detalles de un pedido
     */
    public function getPedidoDetalles($idPedido) {
        try {
            $stmt = $this->conn->prepare(
                'SELECT pd.*, p.Nombre_Producto FROM pedido_detalles pd
                 LEFT JOIN productos p ON pd.ID_Producto = p.ID_Producto
                 WHERE pd.ID_Pedido = ?'
            );
            $stmt->execute([$idPedido]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PedidoModel::getPedidoDetalles error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Devuelve la cabecera del pedido
     */
    public function getPedido($idPedido) {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM pedidos WHERE ID_Pedido = ? LIMIT 1');
            $stmt->execute([$idPedido]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PedidoModel::getPedido error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Expose the PDO connection for use by other models/controllers when needed
     */
    public function getDbConnection() {
        return $this->conn;
    }

    /**
     * Crea un pedido y sus detalles en transacción.
     * @param array $pedido Datos del pedido (tipo_entrega, nombre_cliente, telefono, direccion, notas)
     * @param array $detalles Array de items [ ['id_producto'=>..., 'cantidad'=>..., 'precio'=>..., 'preparacion'=>...], ... ]
     * @return int|false ID_Pedido o false en error
     */
    public function createPedido($pedido, $detalles) {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare('INSERT INTO pedidos (tipo_entrega, nombre_cliente, telefono, direccion, notas) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $pedido['tipo_entrega'] ?? 'local',
                $pedido['nombre_cliente'] ?? null,
                $pedido['telefono'] ?? null,
                $pedido['direccion'] ?? null,
                $pedido['notas'] ?? null
            ]);
            $idPedido = $this->conn->lastInsertId();
            $ins = $this->conn->prepare('INSERT INTO pedido_detalles (ID_Pedido, ID_Producto, Cantidad, Precio_Unitario, Subtotal, preparacion) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($detalles as $d) {
                $cantidad = isset($d['cantidad']) ? (int)$d['cantidad'] : 1;
                $precio = isset($d['precio']) ? (float)$d['precio'] : 0.0;
                $subtotal = $cantidad * $precio;
                $ins->execute([$idPedido, $d['id_producto'], $cantidad, $precio, $subtotal, $d['preparacion'] ?? null]);
            }
            $this->conn->commit();
            return (int)$idPedido;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('PedidoModel::createPedido error: ' . $e->getMessage());
            return false;
        }
    }
}
