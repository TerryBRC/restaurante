<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../models/ReporteModel.php';

class ReporteController extends BaseController {
    public function inventario() {
        $model = new ReporteModel();
        $productos = $model->getInventario();
        if (isset($_GET['exportar']) && $_GET['exportar'] === 'excel') {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="inventario_' . date('Ymd') . '.xls"');
            echo "<table border='1'>";
            echo "<tr><th>#</th><th>Producto</th><th>Categoría</th><th>Stock</th><th>Precio Costo</th><th>Precio Venta</th></tr>";
            foreach ($productos as $i => $prod) {
                echo "<tr>";
                echo "<td>" . ($i+1) . "</td>";
                echo "<td>" . htmlspecialchars($prod['Nombre_Producto']) . "</td>";
                echo "<td>" . htmlspecialchars($prod['Nombre_Categoria']) . "</td>";
                echo "<td>" . (int)$prod['Stock'] . "</td>";
                echo "<td>" . number_format($prod['Precio_Costo'], 2) . "</td>";
                echo "<td>" . number_format($prod['Precio_Venta'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            exit;
        }
        $this->render('views/reportes/inventario.php', compact('productos'));
    }
    public function index() {
        $this->render('views/reportes/index.php');
    }

    public function ventas_dia() {
        $model = new ReporteModel();
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $ventas = $model->getVentasDiariasPorEmpleado($fecha);
        $this->render('views/reportes/ventas_dia.php', compact('ventas', 'fecha'));
    }

    public function productos_vendidos() {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $model = new ReporteModel();
        $ventas = $model->getProductosVendidosPorFecha($fecha);
        $this->render('views/reportes/productos_vendidos.php', compact('ventas', 'fecha'));
    }

    public function cierre_caja() {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $model = new ReporteModel();
        $ventas = $model->getCierreCajaDiario($fecha);

        // Obtener movimientos del día
        require_once __DIR__ . '/../models/MovimientoModel.php';
        $movModel = new MovimientoModel();
        $movimientos = $movModel->obtenerMovimientos(null, $fecha, $fecha);

        // Obtener pedidos del día
        require_once __DIR__ . '/../models/PedidoModel.php';
        $pedidoModel = new PedidoModel();
        $pedidos = $pedidoModel->getPedidosByFecha($fecha);

        $this->render('views/reportes/cierre_caja.php', compact('ventas', 'fecha', 'movimientos', 'pedidos'));
    }

    public function cierre_caja_export() {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $model = new ReporteModel();
        $ventas = $model->getCierreCajaDiario($fecha);

        // Obtener movimientos del día
        require_once __DIR__ . '/../models/MovimientoModel.php';
        $movModel = new MovimientoModel();
        $movimientos = $movModel->obtenerMovimientos(null, $fecha, $fecha);

        // Obtener pedidos del día
        require_once __DIR__ . '/../models/PedidoModel.php';
        $pedidoModel = new PedidoModel();
        $pedidos = $pedidoModel->getPedidosByFecha($fecha);

        // Render view into a string
        ob_start();
        include __DIR__ . '/../views/reportes/cierre_caja_export.php';
        $html = ob_get_clean();

        // Send headers to force download as HTML file (browser can save as .html or print to PDF)
        $filename = 'cierre_caja_' . $fecha . '.html';
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $html;
        exit;
    }

        public function imprimir_ticket_productos_vendidos() {
        require_once __DIR__ . '/../helpers/TicketHelper.php';
        require_once __DIR__ . '/../config/Session.php';
        Session::init();
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $model = new ReporteModel();
        $ventas = $model->getProductosVendidosPorFecha($fecha);
        // Agrupar y preparar datos para el ticket
        $productos = [];
        $granTotal = 0;
        foreach ($ventas as $v) {
            $productos[] = [
                'nombre' => $v['Nombre_Producto'],
                'categoria' => $v['Nombre_Categoria'],
                'cantidad' => $v['Cantidad'],
                'total' => $v['TotalVendido'],
            ];
            $granTotal += $v['TotalVendido'];
        }
        $restaurante = 'RESTAURANTE';
        $moneda = 'C$';
        $empleado = Session::get('nombre_completo') ?: (Session::get('username') ?: 'Usuario');
        $ticket = TicketHelper::generarTicketProductosVendidos($restaurante, date('d/m/Y', strtotime($fecha)), $productos, $granTotal, $moneda, $empleado);
        // Imprimir usando escpos-php o mostrar para copiar
        require_once __DIR__ . '/../helpers/ImpresoraHelper.php';
        $impresora = ImpresoraHelper::obtenerImpresora('impresora_ticket');
        $ok = false;
        $error = '';
        if ($impresora) {
            try {
                if (!ImpresoraHelper::imprimir_directo($impresora, $ticket)) {
                    throw new Exception('No se pudo imprimir el ticket.');
                }
                $ok = true;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'No se ha configurado la impresora de tickets.';
        }
        require_once __DIR__ . '/../config/Session.php';
        if ($ok) {
            Session::set('flash_success', 'Ticket impreso correctamente.');
        } else {
            Session::set('flash_error', 'Error al imprimir: ' . $error);
        }
        header('Location: ' . BASE_URL . 'reportes/productos_vendidos?fecha=' . urlencode($fecha));
        exit;
    }
}