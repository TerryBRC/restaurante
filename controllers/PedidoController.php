<?php
require_once 'BaseController.php';
require_once dirname(__DIR__, 1) . '/models/PedidoModel.php';
require_once dirname(__DIR__, 1) . '/config/Session.php';

class PedidoController extends BaseController {
    public function index() {
        Session::init();
        Session::checkRole(['Administrador','Mesero','Cajero']);
        $pedidoModel = new PedidoModel();
        // permitir filtrar por fecha mediante GET ?fecha=YYYY-MM-DD, por defecto hoy
        $fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : date('Y-m-d');
        $pedidos = $pedidoModel->getPedidosByFecha($fecha);
        $this->render('views/pedidos/index.php', compact('pedidos'));
    }

    public function ver() {
        Session::init();
        Session::checkRole(['Administrador','Mesero','Cajero']);
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['mensaje'] = 'Pedido no especificado';
            header('Location: ' . BASE_URL . 'pedidos');
            exit;
        }
        $pedidoModel = new PedidoModel();
        $detalles = $pedidoModel->getPedidoDetalles($id);
        $pedido = $pedidoModel->getPedido($id);
        // cargar pagos asociados
        $pagos = [];
        try {
            if (!class_exists('PagoPedidoModel')) require_once dirname(__DIR__, 1) . '/models/PagoPedidoModel.php';
            $db = $pedidoModel->getDbConnection();
            $pp = new PagoPedidoModel($db);
            $pagos = $pp->getPagosByPedido($id);
        } catch (Exception $e) {
            error_log('PedidoController::ver - no se pudieron cargar pagos: ' . $e->getMessage());
        }

        $this->render('views/pedidos/ver.php', compact('detalles','id','pedido','pagos'));
    }

    public function nuevo() {
        Session::init();
        Session::checkRole(['Administrador','Mesero','Cajero']);
        // Mostrar vista de creación que reutiliza menu_productos
        $this->render('views/pedidos/create.php');
    }

    public function crear() {
        Session::init();
        Session::checkRole(['Administrador','Mesero','Cajero']);
        // Esperar POST con JSON: pedido + detalles
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        require_once __DIR__ . '/../helpers/Csrf.php';
        $data = $_POST;
        $csrf = $data['csrf_token'] ?? '';
        if (!Csrf::validateToken($csrf)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF token inválido']);
            exit;
        }
        // campos básicos
        $pedido = [
            'tipo_entrega' => $data['tipo_entrega'] ?? 'local',
            'nombre_cliente' => $data['nombre_cliente'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'notas' => $data['notas'] ?? null
        ];
        // detalles: JSON en campo 'detalles'
        $detalles = [];
        if (!empty($data['detalles'])) {
            $detalles = json_decode($data['detalles'], true);
            if (!is_array($detalles)) $detalles = [];
        }
        $pedidoModel = new PedidoModel();
        $idPedido = $pedidoModel->createPedido($pedido, $detalles);
        if (!$idPedido) {
            echo json_encode(['success' => false, 'error' => 'Error al crear pedido']);
            exit;
        }

        // Generar impresión similar a comanda: obtener datos del pedido para generar comanda
        $printResults = [];
        try {
            require_once __DIR__ . '/../helpers/TicketHelper.php';
            require_once __DIR__ . '/../helpers/ImpresoraHelper.php';
            // cargar configuración para saber si debemos intentar imprimir
            if (!class_exists('ConfigModel')) require_once dirname(__DIR__, 1) . '/models/ConfigModel.php';
            $cm = new ConfigModel();
            $conf = $cm->getAll();

            $pedidoForPrint = ['detalles' => $detalles, 'Nombre_Cliente' => $pedido['nombre_cliente'] ?? 'Cliente', 'Fecha_Hora' => date('Y-m-d H:i:s'), 'ID_Pedido' => $idPedido, 'tipo_entrega' => $pedido['tipo_entrega'] ?? null, 'notas' => $pedido['notas'] ?? null];
            // generar e imprimir para cocina y barra por separado usando nuevo helper TicketPedidoHelper
            require_once __DIR__ . '/../helpers/TicketPedidoHelper.php';
            $comandas = TicketPedidoHelper::buildComandas($pedidoForPrint, 40, true);
            $comandaCocina = $comandas['cocina'] ?? '';
            $comandaBarra = $comandas['barra'] ?? '';

            // --- Cocina ---
            $usarCocina = !empty($conf['usar_impresora_cocina']);
            if (!$usarCocina) {
                // no intentar imprimir si la impresora de cocina está deshabilitada en configuración
                $printResults['cocina'] = 'disabled';
            } else {
                if (!empty(trim($comandaCocina))) {
                    $ok = ImpresoraHelper::imprimir('impresora_cocina', $comandaCocina);
                    if ($ok) $printResults['cocina'] = 'printed';
                    else {
                        $printResults['cocina'] = 'failed';
                        error_log('PedidoController::crear - fallo imprimir comanda cocina para pedido ' . $idPedido);
                    }
                } else {
                    $printResults['cocina'] = 'empty';
                }
            }

            // --- Barra ---
            $usarBarra = !empty($conf['usar_impresora_barra']);
            if (!$usarBarra) {
                $printResults['barra'] = 'disabled';
            } else {
                if (!empty(trim($comandaBarra))) {
                    $ok2 = ImpresoraHelper::imprimir('impresora_barra', $comandaBarra);
                    if ($ok2) $printResults['barra'] = 'printed';
                    else {
                        $printResults['barra'] = 'failed';
                        error_log('PedidoController::crear - fallo imprimir comanda barra para pedido ' . $idPedido);
                    }
                } else {
                    $printResults['barra'] = 'empty';
                }
            }
        } catch (Exception $e) {
            error_log('PedidoController::crear - error gen/print comanda: ' . $e->getMessage());
            $printResults['error'] = $e->getMessage();
        }

    // incluir resultado de intentos de impresión para que el frontend pueda mostrar mensajes al usuario
    echo json_encode(['success' => true, 'id' => $idPedido, 'print_results' => $printResults]);
        exit;
    }

    public function prefactura() {
        Session::init();
        Session::checkRole(['Administrador','Mesero','Cajero']);
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo 'Pedido no especificado';
            exit;
        }
        $pedidoModel = new PedidoModel();
        $pedido = $pedidoModel->getPedido($id);
        $detalles = $pedidoModel->getPedidoDetalles($id);
        if (!$pedido) {
            http_response_code(404);
            echo 'Pedido no encontrado';
            exit;
        }
        $payload = array_merge($pedido, ['detalles' => $detalles]);
        require_once __DIR__ . '/../helpers/TicketPedidoHelper.php';
        // obtener moneda/servicio desde config
        $moneda = '$'; $servicio = 0.0;
        try {
            if (!class_exists('ConfigModel')) require_once dirname(__DIR__, 1) . '/models/ConfigModel.php';
            $cm = new ConfigModel(); $conf = $cm->getAll();
            if (isset($conf['moneda']) && trim($conf['moneda']) !== '') $moneda = $conf['moneda'];
            if (isset($conf['servicio'])) $servicio = floatval($conf['servicio']);
        } catch (Exception $e) {}
        $texto = TicketPedidoHelper::generarPrefacturaPedido($payload, $id, $moneda, 'Pendiente', 0.0, $servicio);
        // Si la petición viene por AJAX, enviar a impresora en servidor
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            try {
                require_once __DIR__ . '/../helpers/ImpresoraHelper.php';
                $ok = ImpresoraHelper::imprimir('impresora_ticket', $texto);
                if ($ok) echo json_encode(['success' => true]);
                else { http_response_code(500); echo json_encode(['success' => false, 'error' => 'Fallo imprimiendo prefactura']); }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error imprimiendo: ' . $e->getMessage()]);
            }
            exit;
        }
        // fallback: descarga de texto
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="prefactura_pedido_' . $id . '.txt"');
        echo $texto;
        exit;
    }

    public function factura() {
        Session::init();
        Session::checkRole(['Administrador','Mesero','Cajero']);
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo 'Pedido no especificado';
            exit;
        }
        $pedidoModel = new PedidoModel();
        $pedido = $pedidoModel->getPedido($id);
        $detalles = $pedidoModel->getPedidoDetalles($id);
        if (!$pedido) {
            http_response_code(404);
            echo 'Pedido no encontrado';
            exit;
        }
        $payload = array_merge($pedido, ['detalles' => $detalles]);
        require_once __DIR__ . '/../helpers/TicketPedidoHelper.php';
        $moneda = '$'; $servicio = 0.0;
        try {
            if (!class_exists('ConfigModel')) require_once dirname(__DIR__, 1) . '/models/ConfigModel.php';
            $cm = new ConfigModel(); $conf = $cm->getAll();
            if (isset($conf['moneda']) && trim($conf['moneda']) !== '') $moneda = $conf['moneda'];
            if (isset($conf['servicio'])) $servicio = floatval($conf['servicio']);
        } catch (Exception $e) {}
        $texto = TicketPedidoHelper::generarFacturaPedido($payload, $id, $moneda, 'Pendiente', 0.0, $servicio);
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            try {
                require_once __DIR__ . '/../helpers/ImpresoraHelper.php';
                $ok = ImpresoraHelper::imprimir('impresora_ticket', $texto);
                if ($ok) echo json_encode(['success' => true]);
                else { http_response_code(500); echo json_encode(['success' => false, 'error' => 'Fallo imprimiendo factura']); }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error imprimiendo: ' . $e->getMessage()]);
            }
            exit;
        }
        // fallback: descarga de texto
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="factura_pedido_' . $id . '.txt"');
        echo $texto;
        exit;
    }

    public function pagar() {
        Session::init();
        header('Content-Type: application/json; charset=utf-8');
        // Para llamadas AJAX/Fetch, no redirigir a login: devolver JSON 401/403
        if (!Session::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        $userRole = Session::getUserRole();
        if (!in_array($userRole, ['Administrador','Mesero','Cajero'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        require_once __DIR__ . '/../helpers/Csrf.php';
        $data = $_POST;
        $csrf = $data['csrf_token'] ?? '';
        if (!Csrf::validateToken($csrf)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF token inválido']);
            exit;
        }
        $idPedido = isset($data['ID_Pedido']) ? intval($data['ID_Pedido']) : 0;
        $metodo = $data['Metodo'] ?? 'Efectivo';
        $monto = isset($data['Monto']) ? floatval($data['Monto']) : 0.0;
        $esCambio = isset($data['Es_Cambio']) ? intval($data['Es_Cambio']) : 0;

        if ($idPedido <= 0 || $monto <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Datos de pago inválidos']);
            exit;
        }

        // obtener total del pedido y pagos previos
        try {
            $pedidoModel = new PedidoModel();
            $detalles = $pedidoModel->getPedidoDetalles($idPedido);
            $totalPedido = 0.0;
            foreach ($detalles as $dt) {
                $totalPedido += isset($dt['Subtotal']) ? floatval($dt['Subtotal']) : 0.0;
            }
            // obtener pagos previos
            if (!class_exists('PagoPedidoModel')) require_once dirname(__DIR__, 1) . '/models/PagoPedidoModel.php';
            $pp = new PagoPedidoModel($pedidoModel->getDbConnection());
            $pagosPrev = $pp->getPagosByPedido($idPedido);
            $pagosAplicados = 0.0;
            foreach ($pagosPrev as $pv) {
                // sumar sólo pagos que no sean 'Es_Cambio'
                if (isset($pv['Es_Cambio']) && intval($pv['Es_Cambio']) === 0) {
                    $pagosAplicados += floatval($pv['Monto']);
                }
            }
            $restanteAntes = max(0.0, $totalPedido - $pagosAplicados);
            // determinar cuánto aplicar del monto recibido
            $montoRecibido = floatval($monto);
            $montoAplicar = min($montoRecibido, $restanteAntes);
            $cambio = max(0.0, $montoRecibido - $restanteAntes);
            if ($montoAplicar <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No hay saldo pendiente para aplicar el pago']);
                exit;
            }
        } catch (Exception $e) {
            error_log('PedidoController::pagar calcular total error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al calcular totales']);
            exit;
        }

        // validar y sanitizar 'Metodo'
        // permitir sólo letras, números, espacios, guiones y comas, longitud máxima 50
        $metodo = trim($metodo);
        if (strlen($metodo) > 50) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Método demasiado largo (máx 50)']);
            exit;
        }
        if (!preg_match('/^[A-Za-z0-9\-\,\s]+$/u', $metodo)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Método contiene caracteres inválidos']);
            exit;
        }

        // persistir pago
        try {
            if (!class_exists('PagoPedidoModel')) require_once dirname(__DIR__, 1) . '/models/PagoPedidoModel.php';
            $db = (new PedidoModel())->getDbConnection();
            $pagoModel = new PagoPedidoModel($db);
            // insertar pago aplicado (montoAplicar)
            $idPagoAplicado = $pagoModel->createPago([
                'ID_Pedido' => $idPedido,
                'Metodo' => $metodo,
                'Monto' => $montoAplicar,
                'Es_Cambio' => 0
            ]);
            $idPagoCambio = 0;
            if ($idPagoAplicado) {
                // si hay cambio, insertar registro de cambio separado
                if ($cambio > 0) {
                    $metodoCambio = 'Cambio';
                    $idPagoCambio = $pagoModel->createPago([
                        'ID_Pedido' => $idPedido,
                        'Metodo' => $metodoCambio,
                        'Monto' => $cambio,
                        'Es_Cambio' => 1
                    ]);
                }
                $restanteDespues = max(0.0, $totalPedido - ($pagosAplicados + $montoAplicar));
                echo json_encode([
                    'success' => true,
                    'ID_Pago' => $idPagoAplicado,
                    'ID_Pago_Cambio' => $idPagoCambio,
                    'monto_aplicado' => $montoAplicar,
                    'cambio' => $cambio,
                    'restante' => $restanteDespues,
                    'total' => $totalPedido
                ]);
                exit;
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'No se pudo guardar el pago']);
                exit;
            }
        } catch (Exception $e) {
            error_log('PedidoController::pagar error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno']);
            exit;
        }
    }
}
