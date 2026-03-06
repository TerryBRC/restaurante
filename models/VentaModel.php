<?php
require_once __DIR__ . '/../config/database.php';

class VentaModel {
    /**
     * Divide la cuenta en parciales y asigna productos a cada parcial
     * @param int $idVenta
     * @param array $parciales Ej: [1 => [id_detalle => cantidad, ...], ...]
     * @return bool
     */
    public function dividirCuenta($idVenta, $parciales) {
        try {
            // 1. Crear parciales en tabla parciales_venta
            $idsParciales = [];
            foreach ($parciales as $num => $productos) {
                $stmt = $this->conn->prepare('INSERT INTO parciales_venta (ID_Venta, nombre_cliente) VALUES (?, ?)');
                $stmt->execute([$idVenta, 'Parcial ' . $num]);
                $idsParciales[$num] = $this->conn->lastInsertId();
            }
            // 2. Asignar productos a parciales (actualizar detalle_venta)
            foreach ($parciales as $num => $productos) {
                $idParcial = $idsParciales[$num];
                foreach ($productos as $idDetalle => $cantidad) {
                    if ($cantidad > 0) {
                        // Actualizar el detalle con el ID_Parcial y la cantidad
                        $stmt = $this->conn->prepare('UPDATE detalle_venta SET ID_Parcial = ?, Cantidad = ? WHERE ID_Detalle = ?');
                        $stmt->execute([$idParcial, $cantidad, $idDetalle]);
                    }
                }
            }
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    public function actualizarCantidadDetalle($idDetalle, $nuevaCantidad) {
        try {
            // Obtener el precio actual del detalle (Precio_Venta)
            $stmt = $this->conn->prepare('SELECT Precio_Venta FROM detalle_venta WHERE ID_Detalle = ? LIMIT 1');
            $stmt->execute([$idDetalle]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return false;
            }
            $precio = (float)$row['Precio_Venta'];
            $subtotal = $precio * (float)$nuevaCantidad;
            $stmt2 = $this->conn->prepare('UPDATE detalle_venta SET Cantidad = ?, Subtotal = ? WHERE ID_Detalle = ?');
            return $stmt2->execute([$nuevaCantidad, $subtotal, $idDetalle]);
        } catch (PDOException $e) {
            return false;
        }
    }
    public function eliminarDetalle($idDetalle) {
        try {
            $stmt = $this->conn->prepare('DELETE FROM detalle_venta WHERE ID_Detalle = ?');
            return $stmt->execute([$idDetalle]);
        } catch (PDOException $e) {
            return false;
        }
    }
    // Método actualizarEstadoDetalle eliminado
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Crea una nueva venta y retorna el ID generado.
     * @param int $idCliente
     * @param int $idMesa
     * @param string $metodoPago
     * @param int $idEmpleado
     * @return int|false ID de la venta o false en error
     */
    public function createSale($idCliente, $idMesa, $metodoPago, $idEmpleado) {
        try {
            $stmt = $this->conn->prepare('CALL sp_CreateSale(?, ?, ?, ?)');
            $stmt->execute([$idCliente, $idMesa, $metodoPago, $idEmpleado]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result['ID_Venta'])) {
                return $result['ID_Venta'];
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Agrega un detalle de producto a una venta.
     * @param int $idVenta
     * @param int $idProducto
     * @param int $cantidad
     * @param float $precioVenta
     * @return bool
     */
    public function addSaleDetail($idVenta, $idProducto, $cantidad, $precioVenta, $preparacion = null) {
        try {
            // Normalizar y manejar preparación de forma robusta: trim, colapsar espacios.
            $preparacion_display = null;
            if ($preparacion !== null) {
                $preparacion_display = preg_replace('/\s+/u', ' ', trim($preparacion));
                // Si después de normalizar queda vacío, lo tratamos como sin preparación
                if ($preparacion_display === '') $preparacion_display = null;
            }

            // Si hay preparación (no nula), buscar coincidencias usando comparación insensible a mayúsculas/acentos
            if ($preparacion_display !== null) {
                // Normalización para comparación (lowercase multibyte)
                $preparacion_norm = mb_strtolower($preparacion_display, 'UTF-8');
                // Buscar un detalle que tenga la misma preparación normalizada (case-insensitive, collation safe)
                $stmt = $this->conn->prepare('SELECT ID_Detalle FROM detalle_venta WHERE ID_Venta = ? AND ID_Producto = ? AND LOWER(COALESCE(TRIM(preparacion), "")) COLLATE utf8mb4_general_ci = ? LIMIT 1');
                $stmt->execute([$idVenta, $idProducto, $preparacion_norm]);
                $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($detalle && isset($detalle['ID_Detalle'])) {
                    // Actualizar cantidad y subtotal si ya existe con la misma preparación
                    $stmt = $this->conn->prepare('UPDATE detalle_venta SET Cantidad = Cantidad + ?, Subtotal = (Cantidad + ?) * ? WHERE ID_Detalle = ?');
                    return $stmt->execute([$cantidad, $cantidad, $precioVenta, $detalle['ID_Detalle']]);
                } else {
                    // Insertar la preparación ya normalizada (mostrar tal cual, no forcemos lowercase en DB)
                    $stmt = $this->conn->prepare('INSERT INTO detalle_venta (ID_Venta, ID_Producto, Cantidad, Precio_Venta, Subtotal, preparacion) VALUES (?, ?, ?, ?, ?, ?)');
                    return $stmt->execute([$idVenta, $idProducto, $cantidad, $precioVenta, $cantidad * $precioVenta, $preparacion_display]);
                }
            }

            // Sin preparación, usar SP existente
            // Si hay filas existentes para este producto con PREPARACIÓN no vacía,
            // no debemos sumar la cantidad a una fila con preparación distinta.
            // En ese caso insertamos un nuevo detalle sin preparación.
            $stmtCheck = $this->conn->prepare('SELECT ID_Detalle FROM detalle_venta WHERE ID_Venta = ? AND ID_Producto = ? AND COALESCE(TRIM(preparacion), "") <> "" LIMIT 1');
            $stmtCheck->execute([$idVenta, $idProducto]);
            $existsWithPrep = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($existsWithPrep && isset($existsWithPrep['ID_Detalle'])) {
                $stmtIns = $this->conn->prepare('INSERT INTO detalle_venta (ID_Venta, ID_Producto, Cantidad, Precio_Venta, Subtotal, preparacion) VALUES (?, ?, ?, ?, ?, NULL)');
                return $stmtIns->execute([$idVenta, $idProducto, $cantidad, $precioVenta, $cantidad * $precioVenta]);
            }

            $stmt = $this->conn->prepare('CALL sp_AddSaleDetail(?, ?, ?, ?)');
            return $stmt->execute([$idVenta, $idProducto, $cantidad, $precioVenta]);
        
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Obtiene los detalles de una venta con JOINs a productos y categorías.
     * @param int $idVenta
     * @return array|false
     */
    public function getSaleDetails($idVenta) {
        try {
            $stmt = $this->conn->prepare(
                'SELECT dv.*, p.Nombre_Producto, p.Precio_Venta, c.Nombre_Categoria
                 FROM detalle_venta dv
                 INNER JOIN productos p ON dv.ID_Producto = p.ID_Producto
                 INNER JOIN categorias c ON p.ID_Categoria = c.ID_Categoria
                 WHERE dv.ID_Venta = ?'
            );
            $stmt->execute([$idVenta]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getDailySales($fecha) {
        try {
            $stmt = $this->conn->prepare('CALL sp_GetDailySales(?)');
            $stmt->execute([$fecha]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getVentaById($idVenta) {
        try {
            $stmt = $this->conn->prepare(
                'SELECT v.*, c.Nombre_Cliente, e.Nombre_Completo as Empleado, e.ID_Usuario, m.Numero_Mesa 
                FROM ventas v 
                INNER JOIN clientes c ON v.ID_Cliente = c.ID_Cliente 
                INNER JOIN empleados e ON v.ID_Empleado = e.ID_Empleado 
                LEFT JOIN mesas m ON v.ID_Mesa = m.ID_Mesa 
                WHERE v.ID_Venta = ?'
            );
            $stmt->execute([$idVenta]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getVentasPendientesCocina() {
        try {
                        $stmt = $this->conn->prepare(
                                'SELECT dv.*, dv.preparacion, p.Nombre_Producto, v.ID_Mesa, m.Numero_Mesa, v.Fecha_Hora 
                                FROM detalle_venta dv 
                                INNER JOIN productos p ON dv.ID_Producto = p.ID_Producto 
                                INNER JOIN ventas v ON dv.ID_Venta = v.ID_Venta 
                                INNER JOIN mesas m ON v.ID_Mesa = m.ID_Mesa 
                                INNER JOIN categorias c ON p.ID_Categoria = c.ID_Categoria 
                                WHERE c.Nombre_Categoria != "Bebidas" 
                                    AND v.Estado = "Pendiente" 
                                ORDER BY v.Fecha_Hora DESC'
                        );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getVentasPendientesBarra() {
        try {
                        $stmt = $this->conn->prepare(
                                'SELECT dv.*, p.Nombre_Producto, v.ID_Mesa, m.Numero_Mesa, v.Fecha_Hora 
                                FROM detalle_venta dv 
                                INNER JOIN productos p ON dv.ID_Producto = p.ID_Producto 
                                INNER JOIN ventas v ON dv.ID_Venta = v.ID_Venta 
                                INNER JOIN mesas m ON v.ID_Mesa = m.ID_Mesa 
                                INNER JOIN categorias c ON p.ID_Categoria = c.ID_Categoria 
                                WHERE c.Nombre_Categoria = "Bebidas" 
                                    AND v.Estado = "Pendiente" 
                                ORDER BY v.Fecha_Hora DESC'
                        );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizarTotal($idVenta) {
        try {
            $stmt = $this->conn->prepare(
                'UPDATE ventas v 
                SET Total = (SELECT SUM(Subtotal) FROM detalle_venta WHERE ID_Venta = v.ID_Venta) 
                WHERE ID_Venta = ?'
            );
            return $stmt->execute([$idVenta]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Traslada una venta completa de su mesa actual a otra mesa destino.
     * - Verifica que la venta exista y tenga detalles
     * - Verifica que la mesa destino exista y esté libre (Estado = 0)
     * - Realiza la operación en transacción y pone locks sobre las filas de mesas para evitar race conditions
     * - Registra una entrada de auditoría en movimientos (tipo 'Traslado')
     * @param int $idVenta
     * @param int $idMesaDestino
     * @param int|null $idUsuario
     * @return array ['success' => bool, 'error' => string|null, 'origen' => int|null]
     */
    public function trasladarVentaAMesa($idVenta, $idMesaDestino, $idUsuario = null) {
        try {
            // Validar existencia de venta y que tenga pedidos
            $stmt = $this->conn->prepare('SELECT ID_Venta, ID_Mesa FROM ventas WHERE ID_Venta = ? LIMIT 1');
            $stmt->execute([$idVenta]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$venta) return ['success' => false, 'error' => 'Venta no encontrada', 'origen' => null];
            $idMesaOrigen = $venta['ID_Mesa'];
            // Verificar tiene detalles
            $stmtD = $this->conn->prepare('SELECT COUNT(*) as cnt FROM detalle_venta WHERE ID_Venta = ?');
            $stmtD->execute([$idVenta]);
            $cnt = (int)$stmtD->fetch(PDO::FETCH_ASSOC)['cnt'];
            if ($cnt === 0) return ['success' => false, 'error' => 'La venta no tiene pedidos que trasladar', 'origen' => $idMesaOrigen];

            // Iniciar transacción
            $this->conn->beginTransaction();

            // Lock mesas involucradas para evitar concurrencia
            $stmtLock = $this->conn->prepare('SELECT ID_Mesa, Estado FROM mesas WHERE ID_Mesa IN (?, ?) FOR UPDATE');
            $stmtLock->execute([$idMesaDestino, $idMesaOrigen]);
            $mesasLock = $stmtLock->fetchAll(PDO::FETCH_ASSOC);

            // Verificar mesa destino existe y está libre
            $stmtCheck = $this->conn->prepare('SELECT Estado FROM mesas WHERE ID_Mesa = ? LIMIT 1');
            $stmtCheck->execute([$idMesaDestino]);
            $rowDest = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if (!$rowDest) {
                $this->conn->rollBack();
                return ['success' => false, 'error' => 'Mesa destino no encontrada', 'origen' => $idMesaOrigen];
            }
            if ((int)$rowDest['Estado'] !== 0) {
                $this->conn->rollBack();
                return ['success' => false, 'error' => 'Mesa destino no está libre', 'origen' => $idMesaOrigen];
            }

            // Actualizar venta a la nueva mesa
            $stmtUpd = $this->conn->prepare('UPDATE ventas SET ID_Mesa = ? WHERE ID_Venta = ?');
            $stmtUpd->execute([$idMesaDestino, $idVenta]);

            // Ocupar mesa destino y liberar origen si aplica
            $stmtOcc = $this->conn->prepare('UPDATE mesas SET Estado = 1 WHERE ID_Mesa = ?');
            $stmtOcc->execute([$idMesaDestino]);
            if ($idMesaOrigen) {
                $stmtFree = $this->conn->prepare('UPDATE mesas SET Estado = 0 WHERE ID_Mesa = ?');
                $stmtFree->execute([$idMesaOrigen]);
            }

            // Registrar movimiento/auditoría
            // Registrar movimiento/auditoría usando la misma conexión para participar en la transacción
            try {
                require_once __DIR__ . '/MovimientoModel.php';
                $desc = 'Traslado venta ID ' . $idVenta . ' de mesa ' . ($idMesaOrigen ?? 'N/A') . ' a mesa ' . $idMesaDestino;
                // Registrar mediante el método estático que usa la conexión proporcionada
                MovimientoModel::registrarMovimientoConConn($this->conn, 'Traslado', 0, $desc, $idUsuario, $idVenta);
            } catch (Exception $e) {
                // No fatal: solo log
            }

            $this->conn->commit();
            return ['success' => true, 'error' => null, 'origen' => $idMesaOrigen];
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            return ['success' => false, 'error' => 'Error en la base de datos', 'origen' => null];
        }
    }
    
    /**
     * Crea una venta marcada como delivery.
     * @param int|null $idCliente
     * @param int|null $idEmpleado
     * @param string $metodoPago
     * @return int|false
     */
    public function createDeliverySale($idCliente = null, $idEmpleado = null, $metodoPago = 'Delivery') {
        try {
            // Reusar createSale (sp) pasando null para mesa
            return $this->createSale($idCliente, null, $metodoPago, $idEmpleado);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Alias para agregar detalle a la venta (compatibilidad)
     */
    public function addDetalleVenta($idVenta, $idProducto, $cantidad, $precioVenta, $preparacion = null) {
        return $this->addSaleDetail($idVenta, $idProducto, $cantidad, $precioVenta, $preparacion);
    }

    /**
     * Obtener ventas pendientes de tipo delivery
     */
    public function getVentasDeliveryPendientes() {
        try {
            $stmt = $this->conn->prepare('SELECT v.*, c.Nombre_Cliente FROM ventas v LEFT JOIN clientes c ON v.ID_Cliente = c.ID_Cliente WHERE v.es_delivery = 1 AND v.Estado = "Pendiente" ORDER BY v.Fecha_Hora DESC');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error en getVentasDeliveryPendientes: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el estado de una venta (ej: Pendiente, Preparado, Entregado, Pagado)
     */
    public function updateEstado($idVenta, $estado) {
        try {
            $stmt = $this->conn->prepare('UPDATE ventas SET Estado = ? WHERE ID_Venta = ?');
            return $stmt->execute([$estado, $idVenta]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Devuelve venta con sus detalles (compatibilidad con la vista)
     */
    public function getVentaConDetalles($idVenta) {
        $venta = $this->getVentaById($idVenta);
        if (!$venta) return false;
        $venta['detalles'] = $this->getSaleDetails($idVenta);
        return $venta;
    }
    
    public function getVentaActivaByMesa($idMesa) {
        try {
            $stmt = $this->conn->prepare(
                'SELECT * FROM ventas 
                WHERE ID_Mesa = ? AND Estado = "Pendiente"
                ORDER BY Fecha_Hora DESC LIMIT 1'
            );
            $stmt->execute([$idMesa]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deleteSale($idVenta) {

    // Eliminar detalles primero
    $stmtDetalles = $this->conn->prepare("DELETE FROM detalle_venta WHERE ID_Venta = ?");
    $stmtDetalles->execute([$idVenta]);
    // Eliminar la venta principal
    $stmtVenta = $this->conn->prepare("DELETE FROM ventas WHERE ID_Venta = ?");
    return $stmtVenta->execute([$idVenta]);
    }
}