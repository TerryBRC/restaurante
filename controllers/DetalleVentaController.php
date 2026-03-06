<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../models/VentaModel.php';

class DetalleVentaController extends BaseController {
    public function actualizarEstado() {
        require_once __DIR__ . '/../helpers/Csrf.php';
        require_once __DIR__ . '/../helpers/Validator.php';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = Validator::get($_POST, 'csrf_token', Validator::get($_SERVER, 'HTTP_X_CSRF_TOKEN', ''));
            if (!Csrf::validateToken($csrfToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'CSRF token inválido']);
                exit;
            }
            $idDetalle = Validator::int(Validator::get($_POST, 'id_detalle'));
            $estado = Validator::sanitizeString(Validator::get($_POST, 'estado'));
            // Método eliminado: actualizarEstadoDetalle
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Datos insuficientes']);
        exit;
    }
    public function eliminarProducto() {
        require_once __DIR__ . '/../helpers/Csrf.php';
        require_once __DIR__ . '/../helpers/Validator.php';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = Validator::get($_POST, 'csrf_token', Validator::get($_SERVER, 'HTTP_X_CSRF_TOKEN', ''));
            if (!Csrf::validateToken($csrfToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'CSRF token inválido']);
                exit;
            }
            $idDetalle = Validator::int(Validator::get($_POST, 'id_detalle'));
            $cantidadEliminar = Validator::int(Validator::get($_POST, 'cantidad'));
            if ($idDetalle !== null) {
                $ventaModel = new VentaModel();
                // Obtener cantidad actual y detalles del producto antes de modificar/eliminar
                $conn = (new Database())->connect();
                $stmt = $conn->prepare('SELECT dv.Cantidad, dv.ID_Venta, dv.ID_Producto, dv.preparacion, p.Nombre_Producto, c.is_food, c.Nombre_Categoria FROM detalle_venta dv LEFT JOIN productos p ON dv.ID_Producto = p.ID_Producto LEFT JOIN categorias c ON p.ID_Categoria = c.ID_Categoria WHERE dv.ID_Detalle = ?');
                $stmt->execute([$idDetalle]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $actual = $row ? $row['Cantidad'] : null;
                $idVenta = $row ? $row['ID_Venta'] : null;
                if ($actual === null || $idVenta === null) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Detalle no encontrado']);
                    exit;
                }
                // Capture product info for notification
                $prodNombre = $row['Nombre_Producto'] ?? '';
                $prodPrep = isset($row['preparacion']) ? trim($row['preparacion']) : '';
                $prodIsFood = isset($row['is_food']) ? $row['is_food'] : null;
                $prodCategoria = $row['Nombre_Categoria'] ?? '';
                $removedQty = 0;
                if ($cantidadEliminar !== null && $cantidadEliminar < $actual) {
                    $result = $ventaModel->actualizarCantidadDetalle($idDetalle, $actual - $cantidadEliminar);
                    $removedQty = (int)$cantidadEliminar;
                } else {
                    $result = $ventaModel->eliminarDetalle($idDetalle);
                    $removedQty = (int)$actual;
                }
                // Recalcular y persistir Total y Servicio usando la MISMA conexión para evitar condiciones de carrera
                // Actualizar Total directamente con la suma de detalle_venta
                $stmtTotal = $conn->prepare('UPDATE ventas v SET Total = (SELECT IFNULL(SUM(Subtotal),0) FROM detalle_venta WHERE ID_Venta = v.ID_Venta) WHERE ID_Venta = ?');
                $stmtTotal->execute([$idVenta]);
                // Actualizar monto de servicio en la venta según configuración usando la misma conexión
                require_once __DIR__ . '/../models/ConfigModel.php';
                $configModel = new ConfigModel();
                $servicioPct = (float) ($configModel->get('servicio') ?? 0);
                // Obtener total actualizado desde la misma conexión
                $stmtGet = $conn->prepare('SELECT Total FROM ventas WHERE ID_Venta = ? LIMIT 1');
                $stmtGet->execute([$idVenta]);
                $rowTotal = $stmtGet->fetch(PDO::FETCH_ASSOC);
                $totalActual = isset($rowTotal['Total']) ? (float)$rowTotal['Total'] : 0.0;
                $servicioMonto = $totalActual * $servicioPct;
                $stmt2 = $conn->prepare('UPDATE ventas SET Servicio = ? WHERE ID_Venta = ?');
                $stmt2->execute([$servicioMonto, $idVenta]);
                // Obtener venta actualizada con detalles y devolver totales para que el cliente pueda actualizar la UI sin recargar
                $ventaActualizada = $ventaModel->getVentaConDetalles($idVenta);

                // Preparar e imprimir aviso de eliminación (usar same-connection print is fine)
                try {
                    require_once __DIR__ . '/../helpers/ImpresoraHelper.php';
                    // Determinar destino: usar is_food cuando esté disponible
                    $dest = 'cocina';
                    if ($prodIsFood !== null) {
                        $dest = ($prodIsFood == 1 || $prodIsFood === '1') ? 'cocina' : 'barra';
                    } else {
                        $catLower = strtolower(trim($prodCategoria));
                        $barCats = ['bebidas','licores','cockteles','cervezas'];
                        $dest = in_array($catLower, $barCats) ? 'barra' : 'cocina';
                    }
                    $ventaInfo = $ventaModel->getVentaById($idVenta);
                    $numeroMesa = $ventaInfo['Numero_Mesa'] ?? $idVenta;
                    $contenido = "\n";
                    $contenido .= "====== AVISO DE ELIMINACIÓN ======\n";
                    $contenido .= "Mesa: " . $numeroMesa . "\n";
                    $contenido .= "Producto: " . $removedQty . "x " . ($prodNombre ?: 'Producto') . "\n";
                    if ($prodPrep !== '') $contenido .= "   > Preparación: " . $prodPrep . "\n";
                    $contenido .= "Motivo: Eliminado desde el detalle\n";
                    $contenido .= "--------------------------\n";
                    // save debug copy
                    /*$printsDir = __DIR__ . '/../tools/prints';
                    if (!is_dir($printsDir)) @mkdir($printsDir, 0755, true);
                    $fileName = $printsDir . '/eliminacion_' . $dest . '_mesa_' . $idVenta . '_' . date('Ymd_His') . '.txt';
                    @file_put_contents($fileName, $contenido);*/
                    $clave = $dest === 'cocina' ? 'impresora_cocina' : 'impresora_barra';
                    @ImpresoraHelper::imprimir($clave, $contenido);
                } catch (Exception $e) {
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => (bool)$result, 'venta' => $ventaActualizada]);
                exit;
                }
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Datos insuficientes']);
            exit;
        }
}
