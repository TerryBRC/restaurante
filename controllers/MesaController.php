<?php
require_once 'BaseController.php';
require_once dirname(__DIR__, 1) . '/models/MesaModel.php';
require_once dirname(__DIR__, 1) . '/models/VentaModel.php';
require_once dirname(__DIR__, 1) . '/models/ProductModel.php';
require_once dirname(__DIR__, 1) . '/config/Session.php';

class MesaController extends BaseController {
    // Procesa el formulario de agregar/editar mesa
    public function procesarMesa() {
        require_once dirname(__DIR__, 1) . '/models/MesaModel.php';
        require_once dirname(__DIR__, 1) . '/config/Session.php';
        Session::init();
        Session::checkRole(['Administrador', 'Mesero','Cajero']);
        $mesaModel = new MesaModel();
        $mensaje = '';

        // Manejar cambio de estado (disponible para Meseros y Administradores)
        if (isset($_POST['action']) && $_POST['action'] === 'toggle_estado') {
            $idMesa = (int)$_POST['id_mesa'];
            $estado = (int)$_POST['estado'];
            if ($mesaModel->updateTableStatus($idMesa, $estado)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado de la mesa']);
            }
            exit();
        }

        // Permitir que cualquier usuario autenticado pueda crear/editar/alternar mesas.
        // Solo requerimos que esté logueado.
        if (!Session::isLoggedIn()) {
            $_SESSION['mensaje'] = 'Debe iniciar sesión para realizar esta operación.';
            require_once dirname(__DIR__, 1) . '/config/base_url.php';
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $numeroMesa = isset($_POST['numero_mesa']) ? (int)$_POST['numero_mesa'] : 0;
            $capacidad = isset($_POST['capacidad']) ? (int)$_POST['capacidad'] : 0;

            // Validaciones básicas
            if ($numeroMesa <= 0 || $capacidad <= 0) {
                $_SESSION['mensaje'] = 'Por favor, ingrese valores válidos para el número de mesa y capacidad.';
                require_once dirname(__DIR__, 1) . '/config/base_url.php';
                header('Location: ' . BASE_URL . 'mesas');
                exit();
            }

            // Verificar si es una actualización o nuevo registro
            if (!empty($_POST['id_mesa'])) {
                // Actualización
                $idMesa = (int)$_POST['id_mesa'];
                if ($mesaModel->updateTable($idMesa, $numeroMesa, $capacidad)) {
                    $mensaje = 'Mesa actualizada exitosamente.';
                } else {
                    $mensaje = 'Error al actualizar la mesa.';
                }
            } else {
                // Nueva mesa
                if ($mesaModel->addTable($numeroMesa, $capacidad)) {
                    $mensaje = 'Mesa agregada exitosamente.';
                } else {
                    $mensaje = 'Error al agregar la mesa.';
                }
            }

            $_SESSION['mensaje'] = $mensaje;
        }
        require_once dirname(__DIR__, 1) . '/config/base_url.php';
        header('Location: ' . BASE_URL . 'mesas');
        exit();
    }
    public function dividirCuenta() {
        Session::init();
        Session::checkRole(['Administrador', 'Mesero', 'Cajero']);
        $idMesa = $_GET['id_mesa'] ?? $_POST['id_mesa'] ?? null;
        $ventaModel = new VentaModel();
        $mesaModel = new MesaModel();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $comanda = $ventaModel->getVentaActivaByMesa($idMesa);
            if (!$comanda) {
                $mensaje = 'No hay comanda activa para esta mesa.';
                $this->render('views/mesas/dividir_cuenta.php', compact('mensaje'));
                return;
            }
            $idVenta = $comanda['ID_Venta'];
            // Procesar los parciales enviados
            $parciales = [];
            foreach ($_POST as $key => $val) {
                if (preg_match('/^parcial(\d+)$/', $key, $m)) {
                    $num = $m[1];
                    $parciales[$num] = $val; // $val es array: [id_detalle => cantidad]
                }
            }
            $ok = $ventaModel->dividirCuenta($idVenta, $parciales);
            if ($ok) {
                $_SESSION['mensaje'] = 'División guardada correctamente.';
                header('Location: ' . BASE_URL . 'mesa?id_mesa=' . $idMesa);
                exit;
            } else {
                $mensaje = 'Error al guardar la división.';
            }
        }
        // GET normal: mostrar vista
        $mesa = $mesaModel->getTableById($idMesa);
        $comanda = $ventaModel->getVentaActivaByMesa($idMesa);
        if (!$comanda) {
            $mensaje = 'No hay comanda activa para esta mesa.';
            $this->render('views/mesas/dividir_cuenta.php', compact('mensaje'));
            return;
        }
        $detalles = $ventaModel->getSaleDetails($comanda['ID_Venta']);
        $csrf_token = Csrf::getToken();
        $this->render('views/mesas/dividir_cuenta.php', compact('mesa', 'comanda', 'detalles', 'csrf_token', 'idMesa'));
    }
    public function index() {
        Session::init();
        Session::checkRole(['Administrador', 'Mesero', 'Cajero']);
        $userRole = Session::getUserRole();
        $mesaModel = new MesaModel();
        $mesas = $mesaModel->getAllTables();
        $totalMesas = $mesaModel->getTotalTables();
        $mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
        unset($_SESSION['mensaje']);
        $this->render('views/mesas/index.php', compact('mesas', 'totalMesas', 'userRole', 'mensaje'));
    }

    public function detalle() {
        Session::init();
        Session::checkRole(['Administrador', 'Mesero', 'Cajero']);
        $idMesa = $_GET['id_mesa'] ?? null;
        if (!$idMesa) {
            $mensaje = 'Mesa no especificada.';
            $this->render('views/mesas/mesa.php', compact('mensaje'));
            return;
        }
        $ventaModel = new VentaModel();
        $productModel = new ProductModel();
        $mesaModel = new MesaModel();
        $mesa = $mesaModel->getTableById($idMesa);
        $comanda = $ventaModel->getVentaActivaByMesa($idMesa);
        $mostrarCrearComanda = (!$comanda && $mesa && $mesa['Estado'] == 0);
        $detalles = $comanda ? $ventaModel->getSaleDetails($comanda['ID_Venta']) : [];
        $csrf_token = Csrf::getToken();
        $userRole = Session::getUserRole();
        $this->render('views/mesas/mesa.php', compact('mesa', 'comanda', 'mostrarCrearComanda', 'detalles', 'csrf_token', 'idMesa', 'userRole'));
    }
    /**
     * Endpoint AJAX para trasladar una venta a otra mesa
     * POST params: id_venta, id_mesa_destino, imprimir_avisos (optional), csrf_token
     */
    public function trasladar() {
        require_once __DIR__ . '/../helpers/Csrf.php';
        require_once __DIR__ . '/../helpers/Validator.php';
        Session::init();
        // No usar checkRole porque hace redirect HTML; para AJAX devolvemos JSON
        if (!Session::isLoggedIn()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        $userRole = Session::getUserRole();
        if (!in_array($userRole, ['Administrador', 'Mesero'])) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $csrfToken = Validator::get($_POST, 'csrf_token', Validator::get($_SERVER, 'HTTP_X_CSRF_TOKEN', ''));
        if (!Csrf::validateToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF token inválido']);
            exit;
        }

        $idVenta = Validator::int(Validator::get($_POST, 'id_venta'));
        $idMesaDestino = Validator::int(Validator::get($_POST, 'id_mesa_destino'));
        $imprimirAvisos = Validator::get($_POST, 'imprimir_avisos', '0') === '1' || Validator::get($_POST, 'imprimir_avisos', false) === true;

        if (!$idVenta || !$idMesaDestino) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parámetros insuficientes']);
            exit;
        }

    $ventaModel = new VentaModel();
    $userId = Session::get('user_id');
        $res = $ventaModel->trasladarVentaAMesa($idVenta, $idMesaDestino, $userId);

        header('Content-Type: application/json');
        if ($res['success']) {
            echo json_encode(['success' => true, 'origen' => $res['origen'], 'message' => 'Traslado exitoso']);
            // Si se solicitó, generar avisos a cocina/barra
            try {
                if ($imprimirAvisos) {
                    require_once __DIR__ . '/../helpers/TicketHelper.php';
                    require_once __DIR__ . '/../helpers/ImpresoraHelper.php';
                    // Obtener venta y detalles
                    $venta = $ventaModel->getVentaConDetalles($idVenta);
                    if ($venta) {
                        // Generar comanda de TRASLADO para cocina y barra por separado
                        $comandaCocina = TicketHelper::generarComandaTraslado($venta, $res['origen'], $idMesaDestino, 'cocina', 40);
                        $comandaBarra = TicketHelper::generarComandaTraslado($venta, $res['origen'], $idMesaDestino, 'barra', 40);
                        // Imprimir si hay contenido
                        if (!empty(trim($comandaCocina))) {
                            $ok = ImpresoraHelper::imprimir('impresora_cocina', $comandaCocina);
                        }
                        if (!empty(trim($comandaBarra))) {
                            $ok2 = ImpresoraHelper::imprimir('impresora_barra', $comandaBarra);
                        }
                    }
                }
            } catch (Exception $e) {
            }
        } else {
            echo json_encode(['success' => false, 'error' => $res['error'] ?? 'Error desconocido']);
        }
        exit;
    }
    /**
     * Devuelve mesas libres (Estado = 0) en JSON para poblar el modal de traslado
     */
    public function listarLibres() {
        Session::init();
        // Para llamadas AJAX devolvemos JSON en lugar de redirect
        if (!Session::isLoggedIn()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        $userRole = Session::getUserRole();
        if (!in_array($userRole, ['Administrador', 'Mesero', 'Cajero'])) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
            exit;
        }

        $mesaModel = new MesaModel();
        $mesas = $mesaModel->getAllTables();
        $libres = array_filter($mesas, function($m) { return isset($m['Estado']) && (int)$m['Estado'] === 0; });
        header('Content-Type: application/json');
        echo json_encode(array_values($libres));
        exit;
    }
    
}
