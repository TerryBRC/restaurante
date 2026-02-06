<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../models/MovimientoModel.php';
require_once __DIR__ . '/../models/ReporteModel.php';
require_once __DIR__ . '/../config/Session.php';
require_once __DIR__ . '/../helpers/Csrf.php';
require_once __DIR__ . '/../helpers/TicketHelper.php';

class CajaController extends BaseController {
    public function apertura() {
        Session::init();
        Session::checkRole(['Administrador', 'Cajero']);
        $mensaje = '';
        $errores = [];
        $csrf_token = Csrf::generateToken();
        $movimientoModel = new MovimientoModel();
        $cajaAbierta = $movimientoModel->cajaAbierta();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
                $errores[] = 'CSRF token inválido';
            }
            $monto = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
            if ($monto <= 0) {
                $errores[] = 'El monto debe ser mayor a cero';
            }
            if (!$errores && !$cajaAbierta) {
                $idUsuario = $_SESSION['user_id'] ?? ($_SESSION['user']['ID_usuario'] ?? null);
                $ok = $movimientoModel->registrarMovimiento('Apertura', $monto, 'Apertura de caja', $idUsuario);
                if ($ok) {
                    $mensaje = 'Caja abierta correctamente';
                    $cajaAbierta = true;
                } else {
                    $errores[] = 'Error al registrar apertura';
                }
            }
        }
        $this->render('views/caja/apertura.php', compact('mensaje', 'errores', 'csrf_token', 'cajaAbierta'));
    }

    public function cierre() {
        Session::init();
        Session::checkRole(['Administrador', 'Cajero']);
        $mensaje = '';
        $errores = [];
        $csrf_token = Csrf::generateToken();
        $movimientoModel = new MovimientoModel();
        $reporteModel = new ReporteModel();
        $cajaAbierta = $movimientoModel->cajaAbierta();
        $saldo = $movimientoModel->obtenerSaldoCajaAbierta();
        
        // Verificar si ya hay cierre hoy
        $cierreHoy = $movimientoModel->cierreHoy();
        $ultimoCierre = $cierreHoy ? $movimientoModel->obtenerUltimoCierreHoy() : null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
                $errores[] = 'CSRF token inválido';
            }
            if (!$cajaAbierta) {
                $errores[] = 'No hay caja abierta para cerrar';
            }
            if (!$errores) {
                $idUsuario = $_SESSION['user_id'] ?? ($_SESSION['user']['ID_usuario'] ?? null);
                $ok = $movimientoModel->registrarMovimiento('Cierre', $saldo, 'Cierre de caja', $idUsuario);
                if ($ok) {
                    $mensaje = 'Caja cerrada correctamente';
                    $cajaAbierta = false;
                    $cierreHoy = true;
                    $ultimoCierre = $movimientoModel->obtenerUltimoCierreHoy();
                } else {
                    $errores[] = 'Error al registrar cierre';
                }
            }
        }
        $this->render('views/caja/cierre.php', compact('mensaje', 'errores', 'csrf_token', 'cajaAbierta', 'saldo', 'cierreHoy', 'ultimoCierre'));
    }

    /**
     * Imprime el ticket de PRE-CIERRE (lo acumulado si no se ha cerrado)
     */
    public function imprimirPreCierre() {
        Session::init();
        Session::checkRole(['Administrador', 'Cajero']);
        
        $movimientoModel = new MovimientoModel();
        $reporteModel = new ReporteModel();
        $cajaAbierta = $movimientoModel->cajaAbierta();
        
        if (!$cajaAbierta) {
            $_SESSION['error'] = 'No hay caja abierta para generar pre-cierre';
            header('Location: ' . BASE_URL . 'caja/cierre');
            exit();
        }
        
        $datos = $reporteModel->obtenerReporteCierreCaja();
        TicketHelper::generarTicketPreCierreCaja($datos);
        
        $_SESSION['mensaje'] = 'Pre-cierre impreso correctamente';
        header('Location: ' . BASE_URL . 'caja/cierre');
        exit();
    }

    /**
     * Imprime el ticket del ÚLTIMO CIERRE (si ya se cerró hoy)
     */
    public function imprimirCierre() {
        Session::init();
        Session::checkRole(['Administrador', 'Cajero']);
        
        $movimientoModel = new MovimientoModel();
        $cierreHoy = $movimientoModel->cierreHoy();
        $ultimoCierre = $cierreHoy ? $movimientoModel->obtenerUltimoCierreHoy() : null;
        
        if (!$ultimoCierre) {
            $_SESSION['error'] = 'No hay cierre registrado hoy para imprimir';
            header('Location: ' . BASE_URL . 'caja/cierre');
            exit();
        }
        
        $datos = [
            'monto_cierre' => floatval($ultimoCierre['Monto']),
            'fecha_cierre' => $ultimoCierre['Fecha_Hora'],
            'usuario' => $_SESSION['user_name'] ?? ($_SESSION['user']['Nombre_Completo'] ?? 'N/A')
        ];
        TicketHelper::generarTicketCierreCajaSimplificado($datos);
        
        $_SESSION['mensaje'] = 'Último cierre impreso correctamente';
        header('Location: ' . BASE_URL . 'caja/cierre');
        exit();
    }
}
