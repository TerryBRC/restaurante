<?php
require_once dirname(__DIR__, 2) . '/config/Session.php';
require_once '../../models/VentaModel.php';
require_once '../../models/MesaModel.php';

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
$items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];

if (!$idMesa) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$ventaModel = new VentaModel();
$mesaModel = new MesaModel();

try {
    // Iniciar la venta
    $idVenta = $ventaModel->createSale(
        1,  //Cliente por defecto (C/F)
        $idMesa,
        'Efectivo', // Método de pago por defecto
        Session::get('empleado_id')
    );

    if (!$idVenta) {
        throw new Exception('Error al crear la venta');
    }

    // Si hay productos, agregarlos y actualizar total
    if (!empty($items)) {
        foreach ($items as $item) {
            if (!$ventaModel->addSaleDetail(
                $idVenta,
                $item['id_producto'],
                $item['cantidad'],
                $item['precio_venta']
            )) {
                throw new Exception('Error al agregar producto a la venta');
            }
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
    // Validación de estado de preparación eliminada
    }

    // Marcar la mesa como ocupada
    if (!$mesaModel->updateTableStatus($idMesa, 1)) {
        throw new Exception('Error al actualizar el estado de la mesa');
    }

    // Registrar usuario que cierra la comanda (auditoría)
    $usuario = $_SESSION['username'] ?? 'Desconocido';
    // Aquí podrías guardar en una tabla de auditoría si lo deseas

    echo json_encode(['success' => true, 'message' => 'Comanda procesada exitosamente por ' . $usuario]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'debug' => $e->getMessage()]);
}
// Fin del script
?>
