<?php    
require_once 'BaseController.php';
require_once __DIR__ . '/../models/VentaModel.php';


class ComandaAjaxController extends BaseController {
    public function liberarMesa() {
    require_once __DIR__ . '/../helpers/Csrf.php';
    require_once __DIR__ . '/../helpers/Validator.php';
    require_once __DIR__ . '/../models/MesaModel.php';
    require_once __DIR__ . '/../models/VentaModel.php';
    require_once __DIR__ . '/../models/LiberacionMesaModel.php';
    require_once __DIR__ . '/../config/Session.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = Validator::get($_POST, 'csrf_token', Validator::get($_SERVER, 'HTTP_X_CSRF_TOKEN', ''));
            if (!Csrf::validateToken($csrfToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'CSRF token inválido']);
                exit;
            }
            $idMesa = Validator::int(Validator::get($_POST, 'id_mesa'));
            $idVenta = Validator::int(Validator::get($_POST, 'id_venta'));
            $motivo = Validator::sanitizeString(Validator::get($_POST, 'motivo', ''));
            $descripcion = Validator::sanitizeString(Validator::get($_POST, 'descripcion', ''));
            $idUsuario = null;
            if (isset($_SESSION['user_id'])) {
                $idUsuario = $_SESSION['user_id'];
            } elseif (isset($_SESSION['user']['ID_usuario'])) {
                $idUsuario = $_SESSION['user']['ID_usuario'];
            }
            if ($idMesa === null || $idVenta === null || !$motivo || !$idUsuario) {
                echo json_encode(['success' => false, 'error' => 'Datos insuficientes']);
                exit;
            }
            $ventaModel = new VentaModel();
            $liberacionModel = new LiberacionMesaModel();
            $detalles = $ventaModel->getSaleDetails($idVenta);
            $conn = (new Database())->connect();
            try {
                $conn->beginTransaction();
                // Registrar liberación
                $liberacionModel->registrarLiberacion($idMesa, $idUsuario, $motivo, $descripcion);
                if (empty($detalles)) {
                    // Eliminar la venta si no tiene detalles
                    $ventaModel->deleteSale($idVenta);
                }
                // Liberar la mesa usando la MISMA conexión
                $stmt = $conn->prepare('CALL sp_UpdateTableStatus(?, ?)');
                $stmt->execute([$idMesa, 0]);
                $conn->commit();
                // Obtener venta actualizada con detalles para devolver al cliente
                $ventaActualizada = $ventaModel->getVentaConDetalles($idVenta);
                echo json_encode(['success' => true, 'venta' => $ventaActualizada]);
            } catch (Exception $e) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    public function agregarProductos() {
        require_once __DIR__ . '/../helpers/Csrf.php';
        require_once __DIR__ . '/../helpers/Validator.php';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = Validator::get($_POST, 'csrf_token', Validator::get($_SERVER, 'HTTP_X_CSRF_TOKEN', ''));
            if (!Csrf::validateToken($csrfToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'CSRF token inválido']);
                exit;
            }
            $idMesa = Validator::int(Validator::get($_POST, 'id_mesa'));
            $productos = Validator::get($_POST, 'productos');
            $productos = is_string($productos) ? json_decode($productos, true) : $productos;
            if ($idMesa === null || empty($productos)) {
                echo json_encode(['success' => false, 'error' => 'Datos insuficientes']);
                exit;
            }
            $ventaModel = new VentaModel();
            require_once __DIR__ . '/../models/ProductModel.php';
            $productModel = new ProductModel();
            $comanda = $ventaModel->getVentaActivaByMesa($idMesa);
            if (!$comanda) {
                echo json_encode(['success' => false, 'error' => 'No hay comanda activa']);
                exit;
            }
            $idVenta = $comanda['ID_Venta'];

            // Ya no se imprime aquí. Solo se guarda. La impresión se maneja por imprimirComanda.

            $conn = (new Database())->connect();
            try {
                $conn->beginTransaction();
                foreach ($productos as $p) {
                    $pid = Validator::int(Validator::get($p, 'id'));
                    $cantidad = Validator::int(Validator::get($p, 'cantidad'));
                    $nombre = Validator::sanitizeString(Validator::get($p, 'nombre', ''));
                    $preparacion = Validator::sanitizeString(Validator::get($p, 'preparacion', null));
                    if ($pid === null || $cantidad === null) {
                        throw new Exception('Producto inválido: ' . $nombre);
                    }
                    // Consultar el precio real del producto
                    $productoDB = $productModel->getAllProducts();
                    $precioReal = null;
                    foreach ($productoDB as $prod) {
                        if ($prod['ID_Producto'] == $pid) {
                            $precioReal = $prod['Precio_Venta'];
                            break;
                        }
                    }
                    if ($precioReal === null) {
                        throw new Exception('No se encontró el producto en la base de datos: ' . $nombre);
                    }
                    $res = $ventaModel->addSaleDetail($idVenta, $pid, $cantidad, $precioReal, $preparacion);
                    if (!$res) {
                        throw new Exception('Error al agregar producto: ' . $nombre);
                    }
                }
                $ventaModel->actualizarTotal($idVenta);
                // Actualizar monto de servicio en la venta según configuración
                require_once __DIR__ . '/../models/ConfigModel.php';
                $configModel = new ConfigModel();
                $servicioPct = (float)($configModel->get('servicio') ?? 0);
                // Obtener el total actualizado
                $venta = $ventaModel->getVentaById($idVenta);
                $totalActual = isset($venta['Total']) ? (float)$venta['Total'] : 0.0;
                $servicioMonto = $totalActual * $servicioPct;
                $stmt = $conn->prepare('UPDATE ventas SET Servicio = ? WHERE ID_Venta = ?');
                $stmt->execute([$servicioMonto, $idVenta]);
                $conn->commit();
                // Devolver la venta actualizada con detalles para que el cliente pueda actualizar la UI
                $ventaActualizada = $ventaModel->getVentaConDetalles($idVenta);

                // Intentar imprimir las comandas nuevas (barra / cocina) usando el helper.
                // No abortamos la operación si la impresión falla; solo lo intentamos y seguimos.
                try {
                    require_once __DIR__ . '/../helpers/ImpresoraHelper.php';
                    // Reusar la lógica de filtrado por is_food similar a ComandaController::imprimirComanda
                    $byId = [];
                    $byName = [];
                    $allProducts = $productModel->getAllProducts();
                    foreach ($allProducts as $prod) {
                        if (isset($prod['ID_Producto'])) $byId[(int)$prod['ID_Producto']] = $prod;
                        if (isset($prod['Nombre_Producto'])) $byName[strtolower(trim($prod['Nombre_Producto']))] = $prod;
                    }

                    $comanda = [];
                    foreach ($productos as $p) {
                        $match = null;
                        if (isset($p['id']) && intval($p['id']) > 0 && isset($byId[intval($p['id'])])) {
                            $match = $byId[intval($p['id'])];
                        } elseif (isset($p['nombre']) && isset($byName[strtolower(trim($p['nombre']))])) {
                            $match = $byName[strtolower(trim($p['nombre']))];
                        }
                        $isFood = null;
                        if ($match && isset($match['is_food'])) {
                            $isFood = ($match['is_food'] == 1 || $match['is_food'] === '1') ? 1 : 0;
                        } elseif (isset($p['categoria'])) {
                            $cat = trim($p['categoria']);
                            $catLower = strtolower($cat);
                            $barCats = ['bebidas','licores','cockteles','cervezas'];
                            $isFood = in_array($catLower, $barCats) ? 0 : 1;
                        }
                        if ($isFood === 0) {
                            $comanda[] = array_merge($p, ['Tipo_Producto' => ($match['Tipo_Producto'] ?? 'Bebida')]);
                        } else {
                            $comanda[] = array_merge($p, ['Tipo_Producto' => ($match['Tipo_Producto'] ?? 'Comida')]);
                        }
                    }

                    if (!empty($comanda)) {
                        // Obtener datos de la mesa y la venta para encabezado
                        require_once dirname(__DIR__, 1) . '/models/MesaModel.php';
                        $mesaModel = new MesaModel();
                        $mesa = $mesaModel->getTableById($idMesa);
                        $numeroMesa = $mesa && isset($mesa['Numero_Mesa']) ? $mesa['Numero_Mesa'] : $idMesa;
                        $fechaHora = $ventaActualizada && isset($ventaActualizada['Fecha_Hora']) ? $ventaActualizada['Fecha_Hora'] : date('Y-m-d H:i:s');
                        $comanda[0]['Numero_Mesa'] = $numeroMesa;
                        $comanda[0]['Fecha_Hora'] = $fechaHora;

                        // Construir contenido para barra y cocina
                        $contenidoBarra = "\n====== COMANDA BARRA ======\n";
                        $contenidoCocina = "\n====== COMANDA COCINA ======\n";
                        $usuario = isset($_SESSION['username']) ? $_SESSION['username'] : 'Desconocido';
                        $contenidoBarra .= "Usuario: " . $usuario . "\nMesa: " . $numeroMesa . "\nHora: " . $fechaHora . "\n--------------------------\n";
                        $contenidoCocina .= "Usuario: " . $usuario . "\nMesa: " . $numeroMesa . "\nHora: " . $fechaHora . "\n--------------------------\n";

                        foreach ($comanda as $item) {
                            $qty = isset($item['cantidad']) ? $item['cantidad'] : (isset($item['Cantidad']) ? $item['Cantidad'] : 1);
                            $name = isset($item['nombre']) ? $item['nombre'] : (isset($item['Nombre_Producto']) ? $item['Nombre_Producto'] : 'Producto');
                            $prep = isset($item['preparacion']) ? $item['preparacion'] : (isset($item['Preparacion']) ? $item['Preparacion'] : '');
                            $line = $qty . 'x ' . strtoupper($name) . "\n";
                            if (isset($item['Tipo_Producto']) && strtolower($item['Tipo_Producto']) === 'bebida') {
                                $contenidoBarra .= $line;
                                if (trim((string)$prep) !== '') $contenidoBarra .= "   > " . $prep . "\n";
                            } else {
                                $contenidoCocina .= $line;
                                if (trim((string)$prep) !== '') $contenidoCocina .= "   > " . $prep . "\n";
                            }
                        }
                        $contenidoBarra .= "--------------------------\n\n";
                        $contenidoCocina .= "--------------------------\n\n";

                        // Printing moved to central endpoint `comandas/imprimirComanda`
                        // To avoid duplicate prints, do not call ImpresoraHelper here.
                        if (trim($contenidoBarra) !== "\n====== COMANDA BARRA ======\n") {
                            error_log('ComandaAjaxController: prepared barra content (printing skipped here)');
                            // @ImpresoraHelper::imprimir('impresora_barra', $contenidoBarra);
                        }
                        if (trim($contenidoCocina) !== "\n====== COMANDA COCINA ======\n") {
                            error_log('ComandaAjaxController: prepared cocina content (printing skipped here)');
                            // @ImpresoraHelper::imprimir('impresora_cocina', $contenidoCocina);
                        }
                    }
                } catch (Exception $e) {
                    // No interrumpir el flujo por errores de impresión; se podría loguear
                    error_log('Error imprimiendo comanda desde agregarProductos: ' . $e->getMessage());
                }

                echo json_encode(['success' => true, 'venta' => $ventaActualizada]);
            } catch (Exception $e) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }
        public function crearComanda() {
            require_once __DIR__ . '/../helpers/Csrf.php';
            require_once __DIR__ . '/../helpers/Validator.php';
            require_once __DIR__ . '/../models/MesaModel.php';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $csrfToken = Validator::get($_POST, 'csrf_token', Validator::get($_SERVER, 'HTTP_X_CSRF_TOKEN', ''));
                if (!Csrf::validateToken($csrfToken)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'CSRF token inválido']);
                    exit;
                }
                $idMesa = Validator::int(Validator::get($_POST, 'id_mesa'));
                $productos = Validator::get($_POST, 'productos', []);
                $productos = is_string($productos) ? json_decode($productos, true) : $productos;
                if ($idMesa === null) {
                    error_log('ID de mesa no recibido');
                    echo json_encode(['success' => false, 'error' => 'ID de mesa requerido']);
                    exit;
                }
                $ventaModel = new VentaModel();
                $mesaModel = new MesaModel();
                // Verificar que la mesa existe y está libre
                $mesa = $mesaModel->getTableById($idMesa);
                if (!$mesa) {
                    error_log('Mesa no encontrada: ' . $idMesa);
                    echo json_encode(['success' => false, 'error' => 'Mesa no encontrada']);
                    exit;
                }
                if ($mesa['Estado'] != 0) {
                    error_log('La mesa no está libre: ' . print_r($mesa, true));
                    echo json_encode(['success' => false, 'error' => 'La mesa no está libre']);
                    exit;
                }
                try {
                    $conn = (new Database())->connect();
                    $conn->beginTransaction();
                    // Crear la venta (comanda)
                    $idEmpleado = isset($_SESSION['empleado_id']) ? $_SESSION['empleado_id'] : null;
                    $idVenta = $ventaModel->createSale(1, $idMesa, 'Efectivo', $idEmpleado); // Cliente por defecto: 1
                    // Guardar el monto de servicio inicial en la venta (aunque sea 0), para que esté persistido desde la creación
                    require_once __DIR__ . '/../models/ConfigModel.php';
                    $configModel = new ConfigModel();
                    $servicioPct = (float)($configModel->get('servicio') ?? 0);
                    // Actualizar total y servicio
                    $ventaModel->actualizarTotal($idVenta);
                    $ventaTmp = $ventaModel->getVentaById($idVenta);
                    $totalTmp = isset($ventaTmp['Total']) ? (float)$ventaTmp['Total'] : 0.0;
                    $servicioMonto = $totalTmp * $servicioPct;
                    $stmtSvc = $conn->prepare('UPDATE ventas SET Servicio = ? WHERE ID_Venta = ?');
                    $stmtSvc->execute([$servicioMonto, $idVenta]);
                    // Agregar productos si existen
                    if (!empty($productos)) {
                        foreach ($productos as $p) {
                            $pid = Validator::int(Validator::get($p, 'id'));
                            $cantidad = Validator::int(Validator::get($p, 'cantidad'));
                            $precio = Validator::float(Validator::get($p, 'precio'));
                            $nombre = Validator::sanitizeString(Validator::get($p, 'nombre', ''));
                        }
                        $ventaModel->actualizarTotal($idVenta);
                    }
                    // Marcar la mesa como ocupada
                    $mesaModel->updateTableStatus($idMesa, 1); // 1 = ocupada
                    $conn->commit();
                    echo json_encode(['success' => true, 'id_venta' => $idVenta]);
                } catch (Exception $e) {
                    if (isset($conn)) $conn->rollBack();
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                exit;
            }
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
}
