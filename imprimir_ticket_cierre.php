<?php
// imprimir_ticket_cierre.php: Imprime el ticket de CIERRE DE CAJA (sesión específica)
// Uso: imprimir_ticket_cierre.php?fecha=YYYY-MM-DD[&cierre_ts=YYYY-MM-DD HH:MM:SS]
// - Sin cierre_ts: Imprime el último cierre del día
// - Con cierre_ts: Imprime el cierre específico (para reimprimir)
// Si no hay fecha, usa la fecha actual

require_once __DIR__ . '/config/autoloader.php';
require_once __DIR__ . '/models/ReporteModel.php';
require_once __DIR__ . '/models/MovimientoModel.php';
require_once __DIR__ . '/helpers/TicketHelper.php';
require_once __DIR__ . '/models/ConfigModel.php';
require_once __DIR__ . '/models/PagoModel.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$cierreTimestamp = isset($_GET['cierre_ts']) ? $_GET['cierre_ts'] : null;

$movModel = new MovimientoModel();
$todosMovimientos = $movModel->obtenerMovimientos(null, $fecha, $fecha);

// ORDENAR movimientos por ID ascendente (cronológico)
usort($todosMovimientos, function($a, $b) {
    return (int)$a['ID_Movimiento'] - (int)$b['ID_Movimiento'];
});

// LOGICA: Encontrar el CIERRE específico o el ÚLTIMO CIERRE de la fecha
$ultimoCierre = null;
$fechaHoraUltimoCierre = null;
$apertura = 0;
$egresos = 0;
$idsVentasUltimoCierre = [];

if ($cierreTimestamp) {
    // Buscar el cierre específico por timestamp
    foreach ($todosMovimientos as $mov) {
        if ($mov['Tipo'] === 'Cierre' && $mov['Fecha_Hora'] === $cierreTimestamp) {
            $ultimoCierre = $mov;
            break;
        }
    }
}

// Encontrar la APERTURA y ventas del CIERRE específico
// Usar lógica de stack para sesiones anidadas
$pilaSesiones = [];

foreach ($todosMovimientos as $mov) {
    $tipo = $mov['Tipo'];
    
    if ($tipo === 'Apertura') {
        // Nueva sesión
        $pilaSesiones[] = [
            'apertura' => $mov,
            'ventas' => [],
            'egresos' => []
        ];
        
    } elseif ($tipo === 'Cierre') {
        // Encontramos un cierre - verificar si es el que buscamos
        if ($ultimoCierre && $mov['ID_Movimiento'] == $ultimoCierre['ID_Movimiento']) {
            // Este es el cierre que buscamos, usar esta sesión
            if (!empty($pilaSesiones)) {
                $sesion = end($pilaSesiones);
                $apertura = (float)$sesion['apertura']['Monto'];
                $idsVentasUltimoCierre = array_column($sesion['ventas'], 'ID_Venta');
                foreach ($sesion['egresos'] as $egreso) {
                    $egresos += (float)$egreso['Monto'];
                }
            }
            break;
        } else {
            // Es otro cierre, cerrar esa sesión
            if (!empty($pilaSesiones)) {
                array_pop($pilaSesiones);
            }
        }
        
    } elseif ($tipo === 'Ingreso' || $tipo === 'Egreso') {
        // Agregar a la sesión más reciente
        if (!empty($pilaSesiones)) {
            $sesion = &$pilaSesiones[count($pilaSesiones)-1];
            if ($tipo === 'Ingreso' && !empty($mov['ID_Venta'])) {
                $sesion['ventas'][] = $mov;
            } elseif ($tipo === 'Egreso') {
                $sesion['egresos'][] = $mov;
            }
        }
    }
}

// Si no hay ultimo cierre o no hay ventas, usar comportamiento legacy (todas las ventas del día)
$model = new ReporteModel();
if (!empty($idsVentasUltimoCierre)) {
    // Obtener ventas DEL ÚLTIMO CIERRE específicamente
    $ventas = $model->getVentasByIds($idsVentasUltimoCierre);
} else {
    // Fallback: usar todas las ventas del día
    $ventas = $model->getCierreCajaDiario($fecha);
}

// Calcular totales
$totalesPago = [ 'Efectivo' => 0, 'Tarjeta' => 0, 'Transferencia' => 0, 'Otro' => 0 ];
$granTotal = 0; $granServicio = 0; $granFinal = 0;
$totalesCambio = 0.0;

if ($ventas && count($ventas) > 0) {
    foreach ($ventas as $index => $venta) {
        $granTotal += $venta['Total'];
        $granServicio += $venta['Servicio'];
        $granFinal += $venta['TotalFinal'];
        // Preferir pagos persistidos
        $pagoModel = new PagoModel();
        $pagosVenta = $pagoModel->getPagosByVenta($venta['ID_Venta']);
        $restante = $venta['TotalFinal'];
        $sumaCambioRegistrado = 0.0;
        if ($pagosVenta && is_array($pagosVenta)) {
            foreach ($pagosVenta as $p) {
                $metodoRaw = trim($p['Metodo']);
                $monto = (float)$p['Monto'];
                if ((int)$p['Es_Cambio'] === 1) {
                    $sumaCambioRegistrado += $monto;
                    continue;
                }
                if (stripos($metodoRaw, 'efectivo') !== false) {
                    $totalesPago['Efectivo'] += $monto;
                } elseif (stripos($metodoRaw, 'tarjeta') !== false || stripos($metodoRaw, 'card') !== false) {
                    $totalesPago['Tarjeta'] += $monto;
                } elseif (stripos($metodoRaw, 'transfer') !== false || stripos($metodoRaw, 'transf') !== false) {
                    $totalesPago['Transferencia'] += $monto;
                } else {
                    $totalesPago['Otro'] += $monto;
                }
                $restante -= $monto;
            }
        } else {
            // Fallback al parsing legacy
            $mp = $venta['Metodo_Pago'];
            $restante = $venta['TotalFinal'];
            if ($mp) {
                $partes = explode(',', $mp);
                foreach ($partes as $parte) {
                    $parte = trim($parte);
                    if (stripos($parte, 'Efectivo:') !== false) {
                        $monto = floatval(preg_replace('/[^0-9.]/', '', substr($parte, stripos($parte, ':')+1)));
                        $totalesPago['Efectivo'] += $monto;
                        $restante -= $monto;
                    } elseif (stripos($parte, 'Tarjeta:') !== false) {
                        $monto = floatval(preg_replace('/[^0-9.]/', '', substr($parte, stripos($parte, ':')+1)));
                        $totalesPago['Tarjeta'] += $monto;
                        $restante -= $monto;
                    } elseif (stripos($parte, 'Transferencia:') !== false || stripos($parte, 'Transf') !== false) {
                        $monto = floatval(preg_replace('/[^0-9.]/', '', substr($parte, stripos($parte, ':')+1)));
                        $totalesPago['Transferencia'] += $monto;
                        $restante -= $monto;
                    }
                }
            }
            if ($restante > 0.01) {
                $totalesPago['Otro'] += $restante;
            }
        }
        $totalesCambio += $sumaCambioRegistrado;
    }
}

$ingresos = $movModel->obtenerIngresosNoVentas($fecha);
$efectivoEntregar = $apertura + $ingresos + $totalesPago['Efectivo'] - $egresos;

// Datos para el ticket
$configModel = new ConfigModel();
$restaurante = $configModel->get('nombre_app') ?: 'Restaurante';
// Obtener nombre del empleado desde la sesión (prefiere nombre completo)
$empleado = 'Desconocido';
// Intentar usar la clase Session si está disponible
if (file_exists(__DIR__ . '/config/Session.php')) {
    require_once __DIR__ . '/config/Session.php';
    Session::init();
    $empleado = Session::get('nombre_completo') ?: Session::get('username') ?: 'Desconocido';
} else {
    // Fallback directo a variables de sesión globales
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['nombre_completo'])) {
        $empleado = $_SESSION['nombre_completo'];
    } elseif (!empty($_SESSION['username'])) {
        $empleado = $_SESSION['username'];
    } elseif (!empty($_SESSION['user']['Nombre_Completo'])) {
        $empleado = $_SESSION['user']['Nombre_Completo'];
    }
}
$ticketId = date('Ymd') . rand(100,999);
$moneda = $configModel->get('moneda') ?: 'C$';

$cierreMonto = $ultimoCierre ? (float)$ultimoCierre['Monto'] : 0;

$ticket = TicketHelper::generarTicketCierreCaja(
    $restaurante,
    $fecha,
    $fecha,
    $totalesPago['Efectivo'],
    $totalesPago['Tarjeta'],
    $granTotal,
    $granServicio,
    $granFinal,
    $empleado,
    $ticketId,
    $moneda,
    $apertura,
    $egresos,
    $cierreMonto,
    $totalesPago['Transferencia'],  // Total transferencia
    $ingresos,                       // Ingresos adicionales
    $totalesCambio                   // Cambio dado
);

// Imprimir usando la impresora designada para cierre
$impresora = $configModel->get('impresora_ticket');

        try {
            $connector = new WindowsPrintConnector($impresora);
            $printer = new Printer($connector);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text($ticket . "\n");
            $printer->feed(2);
            $printer->cut();
            $printer->close();
            $msg = 'ok';
        } catch (Exception $e) {
            $msg = 'error';
        }
    $redirectUrl = 'reportes/cierre_caja?fecha=' . urlencode($fecha) . '&msg=' . $msg;
    header('Location: ' . $redirectUrl);
    exit;
