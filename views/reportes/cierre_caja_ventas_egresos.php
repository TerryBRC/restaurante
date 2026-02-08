<?php
// views/reportes/cierre_caja_ventas_egresos.php
// Reporte de cierre: listado de ventas y movimientos de egreso
// Parámetro GET: fecha=YYYY-MM-DD

require_once dirname(__DIR__, 2) . '/config/Session.php';
require_once dirname(__DIR__, 2) . '/models/ReporteModel.php';
require_once dirname(__DIR__, 2) . '/models/MovimientoModel.php';

Session::init();
Session::checkRole(['Administrador','Cajero']);

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$model = new ReporteModel();
$movModel = new MovimientoModel();

// Obtener todos los movimientos del día y agrupar por sesiones
$todosMovimientos = $movModel->obtenerMovimientos(null, $fecha, $fecha);
$sesiones = [];
$pilaSesiones = [];

if ($todosMovimientos && is_array($todosMovimientos)) {
    usort($todosMovimientos, function($a, $b) {
        return strtotime($a['Fecha_Hora']) - strtotime($b['Fecha_Hora']);
    });
    
    foreach ($todosMovimientos as $mov) {
        $tipo = $mov['Tipo'];
        if ($tipo === 'Apertura') {
            $nuevaSesion = [
                'apertura' => $mov,
                'ventas' => [],
                'cierre' => null,
                'egresos' => []
            ];
            $pilaSesiones[] = $nuevaSesion;
            $sesiones[] = &$pilaSesiones[count($pilaSesiones)-1];
        } elseif ($tipo === 'Cierre') {
            if (!empty($pilaSesiones)) {
                $idx = count($pilaSesiones) - 1;
                $pilaSesiones[$idx]['cierre'] = $mov;
                array_pop($pilaSesiones);
            }
        } elseif ($tipo === 'Egreso') {
            if (!empty($pilaSesiones)) {
                $idx = count($pilaSesiones) - 1;
                $pilaSesiones[$idx]['egresos'][] = $mov;
            }
        }
    }
}

// Determinar qué sesiones mostrar
$sesionAMostrar = !empty($sesiones) ? end($sesiones) : null;

// Manejo de impresión
$printMessage = '';
if (isset($_GET['imprimir']) && $_GET['imprimir'] === '1') {
    require_once dirname(__DIR__, 2) . '/helpers/ImpresoraHelper.php';
    require_once dirname(__DIR__, 2) . '/helpers/TicketHelper.php';
    require_once dirname(__DIR__, 2) . '/models/ConfigModel.php';
    require_once dirname(__DIR__, 2) . '/models/PagoModel.php';
    
    $ventasAImprimir = [];
    $apertura = 0;
    $egresos = 0;
    $cierreMonto = 0;
    
    if ($sesionAMostrar) {
        $apertura = (float)$sesionAMostrar['apertura']['Monto'];
        foreach ($sesionAMostrar['egresos'] as $eg) $egresos += (float)$eg['Monto'];
        if ($sesionAMostrar['cierre']) $cierreMonto = (float)$sesionAMostrar['cierre']['Monto'];
        foreach ($sesionAMostrar['ventas'] as $ingreso) {
            if (!empty($ingreso['ID_Venta'])) {
                $v = $model->getVentasByIds([$ingreso['ID_Venta']])[0] ?? null;
                if ($v) $ventasAImprimir[] = $v;
            }
        }
    } else {
        $ventasAImprimir = $model->getCierreCajaDiario($fecha);
    }
    
    // Calcular totales
    $totalesPago = ['Efectivo' => 0, 'Tarjeta' => 0, 'Transferencia' => 0, 'Otro' => 0];
    $granTotal = 0; $granServicio = 0; $granFinal = 0;
    
    foreach ($ventasAImprimir as $venta) {
        $granTotal += $venta['Total'];
        $granServicio += $venta['Servicio'];
        $granFinal += $venta['TotalFinal'];
        
        $pagoModel = new PagoModel();
        $pagosVenta = $pagoModel->getPagosByVenta($venta['ID_Venta']);
        if ($pagosVenta && is_array($pagosVenta)) {
            foreach ($pagosVenta as $p) {
                if ((int)$p['Es_Cambio'] === 1) continue;
                $metodoRaw = trim($p['Metodo']);
                $monto = (float)$p['Monto'];
                if (stripos($metodoRaw, 'efectivo') !== false) $totalesPago['Efectivo'] += $monto;
                elseif (stripos($metodoRaw, 'tarjeta') !== false || stripos($metodoRaw, 'card') !== false) $totalesPago['Tarjeta'] += $monto;
                elseif (stripos($metodoRaw, 'transfer') !== false || stripos($metodoRaw, 'transf') !== false) $totalesPago['Transferencia'] += $monto;
                else $totalesPago['Otro'] += $monto;
            }
        }
    }
    
    $empleado = Session::get('nombre_completo') ?: (Session::get('username') ?: 'Usuario');
    $cm = new ConfigModel();
    $nombreApp = $cm->get('nombre_app') ?: 'RESTAURANTE';
    $moneda = $cm->get('moneda') ?: 'C$';
    $ticketId = date('YmdHis') . rand(100,999);
    
    $ticketTxt = TicketHelper::generarTicketCierreCaja(
        $nombreApp, $fecha, $fecha,
        $totalesPago['Efectivo'], $totalesPago['Tarjeta'],
        $granTotal, $granServicio, $granFinal,
        $empleado, $ticketId, $moneda,
        $apertura, $egresos, $cierreMonto
    );
    
    // Agregar detalle de ventas al ticket
    $ticketTxt .= str_repeat('-', 35) . "\n";
    $ticketTxt .= "DETALLE DE VENTAS:\n";
    foreach ($ventasAImprimir as $v) {
        $ticketTxt .= sprintf("V:%d %s C$%.2f\n", 
            $v['ID_Venta'], 
            date('H:i', strtotime($v['Fecha_Hora'])),
            $v['TotalFinal']
        );
    }
    $ticketTxt .= str_repeat('=', 35) . "\n";
    
    $impName = $cm->get('impresora_ticket_cierre') ?: $cm->get('impresora_ticket');
    if ($impName) {
        try {
            $ok = ImpresoraHelper::imprimir_directo($impName, $ticketTxt);
            $printMessage = $ok ? 'Ticket enviado a: ' . htmlspecialchars($impName) : 'Error al imprimir';
        } catch (Exception $e) {
            $printMessage = 'Error: ' . $e->getMessage();
        }
    } else {
        $printMessage = 'No hay impresora configurada';
    }
}

// Obtener ventas a mostrar
$ventasAMostrar = [];
$egresosMostrar = [];
$aperturaMostrar = 0;
$cierreMostrar = null;

if ($sesionAMostrar) {
    $aperturaMostrar = (float)$sesionAMostrar['apertura']['Monto'];
    $cierreMostrar = $sesionAMostrar['cierre'];
    foreach ($sesionAMostrar['ventas'] as $ingreso) {
        if (!empty($ingreso['ID_Venta'])) {
            $v = $model->getVentasByIds([$ingreso['ID_Venta']])[0] ?? null;
            if ($v) $ventasAMostrar[] = $v;
        }
    }
    $egresosMostrar = $sesionAMostrar['egresos'];
} else {
    $ventasAMostrar = $model->getCierreCajaDiario($fecha);
    $egresosMostrar = $movModel->obtenerMovimientos('Egreso', $fecha, $fecha);
}

// Calcular totales
$totalVentas = 0.0; $totalServicio = 0.0; $totalConServicio = 0.0;
foreach ($ventasAMostrar as $v) {
    $total = floatval($v['Total'] ?? 0);
    $serv = floatval($v['Servicio'] ?? 0);
    $totalVentas += $total;
    $totalServicio += $serv;
    $totalConServicio += ($total + $serv);
}

$totalEgresos = 0.0;
foreach ($egresosMostrar as $m) {
    $totalEgresos += floatval($m['Monto'] ?? 0);
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Cierre de Caja - Ventas y Egresos</h2>
        <div class="d-flex gap-2">
            <form method="get" class="d-flex align-items-center">
                <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
                <button type="submit" name="imprimir" value="1" class="btn btn-primary btn-sm">
                    <i class="bi bi-printer"></i> Imprimir Ticket
                </button>
            </form>
            <form method="get" class="d-flex align-items-center">
                <label class="me-2 mb-0 text-nowrap">Fecha:</label>
                <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" class="form-control form-control-sm" style="width: 140px;">
                <button class="btn btn-sm btn-outline-primary ms-2" type="submit">Ver</button>
            </form>
        </div>
    </div>

    <?php if (!empty($printMessage)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($printMessage) ?></div>
    <?php endif; ?>

    <!-- Resumen de la sesión -->
    <?php if ($sesionAMostrar): ?>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6 class="card-title mb-1">Apertura</h6>
                    <h4 class="mb-0">C$ <?= number_format($aperturaMostrar, 2) ?></h4>
                    <small><?= date('d/m/Y H:i', strtotime($sesionAMostrar['apertura']['Fecha_Hora'])) ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6 class="card-title mb-1">Total Ventas</h6>
                    <h4 class="mb-0">C$ <?= number_format($totalConServicio, 2) ?></h4>
                    <small><?= count($ventasAMostrar) ?> venta(s)</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card <?= $cierreMostrar ? 'bg-secondary' : 'bg-warning' ?> text-white">
                <div class="card-body text-center">
                    <h6 class="card-title mb-1"><?= $cierreMostrar ? 'Cierre' : 'Estado' ?></h6>
                    <h4 class="mb-0"><?= $cierreMostrar ? 'C$ ' . number_format($cierreMostrar['Monto'], 2) : 'En curso' ?></h4>
                    <?php if ($cierreMostrar): ?>
                        <small><?= date('d/m/Y H:i', strtotime($cierreMostrar['Fecha_Hora'])) ?></small>
                    <?php else: ?>
                        <small>Caja abierta</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ventas -->
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-cart"></i> Resumen de Ventas (<?= htmlspecialchars($fecha) ?>)</h5>
            <span class="badge bg-light text-dark">Total: C$ <?= number_format($totalConServicio, 2) ?></span>
        </div>
        <div class="card-body">
            <?php if (empty($ventasAMostrar)): ?>
                <div class="alert alert-info mb-0">No hay ventas registradas.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th># Venta</th>
                                <th>Fecha/Hora</th>
                                <th>Usuario</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Servicio</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventasAMostrar as $v): ?>
                                <tr>
                                    <td><?= (int)$v['ID_Venta'] ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($v['Fecha_Hora']))) ?></td>
                                    <td><?= htmlspecialchars($v['usuario'] ?? '') ?></td>
                                    <td class="text-end">C$ <?= number_format(floatval($v['Total'] ?? 0), 2) ?></td>
                                    <td class="text-end">C$ <?= number_format(floatval($v['Servicio'] ?? 0), 2) ?></td>
                                    <td class="text-end fw-bold">C$ <?= number_format(floatval($v['TotalFinal'] ?? 0), 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-success">
                            <tr>
                                <th colspan="3" class="text-end">Totales:</th>
                                <th class="text-end">C$ <?= number_format($totalVentas, 2) ?></th>
                                <th class="text-end">C$ <?= number_format($totalServicio, 2) ?></th>
                                <th class="text-end">C$ <?= number_format($totalConServicio, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Egresos -->
    <div class="card border-danger">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-dash-circle"></i> Movimientos de Egreso (<?= htmlspecialchars($fecha) ?>)</h5>
            <span class="badge bg-light text-danger">Total: -C$ <?= number_format($totalEgresos, 2) ?></span>
        </div>
        <div class="card-body">
            <?php if (empty($egresosMostrar)): ?>
                <div class="alert alert-info mb-0">No hay movimientos de egreso.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Fecha/Hora</th>
                                <th>Descripción</th>
                                <th>Usuario</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($egresosMostrar as $m): ?>
                                <tr>
                                    <td><?= (int)$m['ID_Movimiento'] ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($m['Fecha_Hora']))) ?></td>
                                    <td><?= htmlspecialchars($m['Descripcion'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($m['Nombre_Usuario'] ?? '') ?></td>
                                    <td class="text-end text-danger">-C$ <?= number_format(floatval($m['Monto'] ?? 0), 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-danger">
                            <tr>
                                <th colspan="4" class="text-end">Total Egresos:</th>
                                <th class="text-end text-danger">-C$ <?= number_format($totalEgresos, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sesiones del día -->
    <?php if (count($sesiones) > 1): ?>
    <div class="mt-4">
        <h5>Sesiones del Día</h5>
        <div class="list-group">
            <?php foreach (array_reverse($sesiones) as $i => $s): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Sesión <?= count($sesiones) - $i ?></strong>
                        <br>
                        <small class="text-muted">
                            Apertura: C$ <?= number_format($s['apertura']['Monto'], 2) ?> 
                            (<?= date('H:i', strtotime($s['apertura']['Fecha_Hora'])) ?>)
                            <?php if ($s['cierre']): ?>
                                | Cierre: C$ <?= number_format($s['cierre']['Monto'], 2) ?>
                            <?php else: ?>
                                | En curso
                            <?php endif; ?>
                        </small>
                    </div>
                    <span class="badge bg-primary rounded-pill"><?= count($s['ventas']) ?> ventas</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="<?= BASE_URL ?>reportes" class="btn btn-secondary btn-sm">Volver a Reportes</a>
    </div>
</div>
