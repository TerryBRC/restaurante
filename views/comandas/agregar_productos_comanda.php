<?php
require_once dirname(__DIR__, 2) . '/config/Session.php';
require_once '../../models/VentaModel.php';
require_once '../../models/MesaModel.php';
require_once '../../models/ProductModel.php';

Session::init();
Session::checkRole(['Administrador', 'Mesero', 'Cajero']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

require_once __DIR__ . '/../../helpers/Csrf.php';
$csrfToken = $_POST['csrf_token'] ?? '';
if (!Csrf::validateToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'CSRF token inválido']);
    exit();
}

$idMesa = isset($_POST['id_mesa']) ? (int)$_POST['id_mesa'] : 0;
$idVenta = isset($_POST['id_venta']) ? (int)$_POST['id_venta'] : 0;
$items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];

if (!$idMesa || !$idVenta || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$ventaModel = new VentaModel();
$mesaModel = new MesaModel();

// Verificar que la mesa esté ocupada
$mesa = $mesaModel->getTableById($idMesa);
if (!$mesa) {
    echo json_encode(['success' => false, 'message' => 'Mesa no encontrada']);
    exit();
}

// Verificar que la venta exista y esté pendiente
$venta = $ventaModel->getVentaById($idVenta);
if (!$venta) {
    echo json_encode(['success' => false, 'message' => 'Venta no encontrada']);
    exit();
}

try {
    $productModel = new ProductModel();
    $usuario = $_SESSION['username'] ?? 'Desconocido';
    foreach ($items as $item) {
        $producto = $productModel->getProductsByCategory($item['id_producto']);
        // Validar stock
        if (isset($producto[0]) && $item['cantidad'] > $producto[0]['Stock']) {
            throw new Exception('Stock insuficiente para el producto: ' . $producto[0]['Nombre_Producto']);
        }
        // Agregar detalle de venta
        if (!$ventaModel->addSaleDetail(
            $idVenta,
            $item['id_producto'],
            $item['cantidad'],
            $item['precio_venta']
        )) {
            throw new Exception('Error al agregar producto a la venta');
        }
        // Aquí podrías registrar auditoría: usuario, producto, cantidad, fecha
    }

    // Actualizar el total de la venta
    if (!$ventaModel->actualizarTotal($idVenta)) {
        throw new Exception('Error al actualizar el total de la venta');
    }
    // Actualizar monto de servicio en la venta según configuración
    require_once __DIR__ . '/../../models/ConfigModel.php';
    $configModel = new ConfigModel();
    $servicioPct = (float) ($configModel->get('servicio') ?? 0);
    $venta = $ventaModel->getVentaById($idVenta);
    $totalActual = isset($venta['Total']) ? (float)$venta['Total'] : 0.0;
    $servicioMonto = $totalActual * $servicioPct;
    $conn = (new \Database())->connect();
    $stmt = $conn->prepare('UPDATE ventas SET Servicio = ? WHERE ID_Venta = ?');
    $stmt->execute([$servicioMonto, $idVenta]);

    echo json_encode(['success' => true, 'message' => 'Productos agregados exitosamente por ' . $usuario]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'debug' => $e->getMessage()]);
}
?>