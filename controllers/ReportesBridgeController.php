<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../config/Session.php';

class ReportesBridgeController extends BaseController {
    // Carga directamente la vista de cierre ventas + egresos sin tocar ReporteController
    public function cierre_ventas_egresos() {
        // Control de sesión/roles
        Session::init();
        Session::checkRole(['Administrador','Cajero']);

        // Incluir la vista que ya contiene su propia lógica (models, impresión, etc.)
        include __DIR__ . '/../views/reportes/cierre_caja_ventas_egresos.php';
    }
}
