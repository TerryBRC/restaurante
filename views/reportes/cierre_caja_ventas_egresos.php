<?php
// views/reportes/cierre_caja_ventas_egresos.php
// Reporte de cierre: listado de ventas y movimientos de egreso (en un mismo archivo)
// Muestra por venta: monto total, servicio separado y total con servicio
// Parámetro GET: fecha=YYYY-MM-DD (por defecto hoy)

require_once dirname(__DIR__, 2) . '/config/Session.php';
require_once dirname(__DIR__, 2) . '/models/ReporteModel.php';
require_once dirname(__DIR__, 2) . '/models/MovimientoModel.php';

Session::init();
Session::checkRole(['Administrador','Cajero']);

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$model = new ReporteModel();
$movModel = new MovimientoModel();

$ventas = $model->getCierreCajaDiario($fecha);
$egresos = $movModel->obtenerMovimientos('Egreso', $fecha, $fecha);

// Manejo de impresión directo desde esta vista si se solicita ?imprimir=1
$printMessage = '';
if (isset($_GET['imprimir']) && ($_GET['imprimir'] === '1' || $_GET['imprimir'] === 'true')) {
    require_once dirname(__DIR__, 2) . '/helpers/ImpresoraHelper.php';
    require_once dirname(__DIR__, 2) . '/helpers/TicketHelper.php';
    require_once dirname(__DIR__, 2) . '/models/ConfigModel.php';
    // Preparar datos para el ticket
    $detallesTicket = [];
    $totalTmp = 0.0; $servTmp = 0.0;
    foreach ($ventas as $v) {
        $monto = floatval($v['Total'] ?? 0);
        $serv = floatval($v['Servicio'] ?? 0);
        $detallesTicket[] = [
            'id' => $v['ID_Venta'],
            'fecha' => $v['Fecha_Hora'],
            'usuario' => $v['usuario'] ?? '',
            'metodo' => $v['Metodo_Pago'] ?? '',
            'monto' => $monto,
            'servicio' => $serv,
            'total_con_servicio' => $monto + $serv
        ];
        $totalTmp += $monto; $servTmp += $serv;
    }
    // Generar texto del ticket (usar helper si existe)
    $ticketTxt = '';
    if (function_exists('\TicketHelper::generarTicketCierreCaja') || method_exists('TicketHelper', 'generarTicketCierreCaja')) {
        try {
            $cm = new ConfigModel();
            $nombreApp = $cm->get('nombre_app') ?: 'RESTAURANTE';
            $ticketTxt = TicketHelper::generarTicketCierreCaja($nombreApp, $fecha, $detallesTicket, $totalTmp, $servTmp);
        } catch (Exception $e) {
            $ticketTxt = "CIERRE DE CAJA - " . $fecha . "\n";
            foreach ($detallesTicket as $d) {
                $ticketTxt .= sprintf("V:%s %s %s - %s\n", $d['id'], $d['fecha'], $d['usuario'], number_format($d['total_con_servicio'],2));
            }
            $ticketTxt .= "\nTotal ventas: " . number_format($totalTmp,2) . "\n";
            $ticketTxt .= "Total servicio: " . number_format($servTmp,2) . "\n";
            $ticketTxt .= "Total con servicio: " . number_format($totalTmp + $servTmp,2) . "\n";
        }
    } else {
        $ticketTxt .= "CIERRE DE CAJA - " . $fecha . "\n";
        foreach ($detallesTicket as $d) {
            $ticketTxt .= sprintf("V:%s %s %s - %s\n", $d['id'], $d['fecha'], $d['usuario'], number_format($d['total_con_servicio'],2));
        }
        $ticketTxt .= "\nTotal ventas: " . number_format($totalTmp,2) . "\n";
        $ticketTxt .= "Total servicio: " . number_format($servTmp,2) . "\n";
        $ticketTxt .= "Total con servicio: " . number_format($totalTmp + $servTmp,2) . "\n";
    }

    // Obtener impresora desde config
    $cm = new ConfigModel();
    $impName = $cm->get('impresora_ticket_cierre') ?: $cm->get('impresora_ticket');
    if ($impName) {
        try {
            $ok = ImpresoraHelper::imprimir_directo($impName, $ticketTxt);
            if ($ok) {
                $printMessage = 'Ticket de cierre enviado a impresora: ' . htmlspecialchars($impName);
            } else {
                $printMessage = 'Error al imprimir en impresora: ' . htmlspecialchars($impName);
            }
        } catch (Exception $e) {
            $printMessage = 'Excepción imprimiendo: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $printMessage = 'No hay impresora configurada (impresora_ticket_cierre o impresora_ticket)';
    }
}

// Totales
$totalVentas = 0.0;
$totalServicio = 0.0;
$totalConServicio = 0.0;
foreach ($ventas as $v) {
    $total = isset($v['Total']) ? floatval($v['Total']) : 0.0;
    $serv = isset($v['Servicio']) ? floatval($v['Servicio']) : 0.0;
    $totalVentas += $total;
    $totalServicio += $serv;
    $totalConServicio += ($total + $serv);
}

$totalEgresos = 0.0;
foreach ($egresos as $m) {
    $totalEgresos += isset($m['Monto']) ? floatval($m['Monto']) : 0.0;
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Cierre de Caja - Ventas y Egresos</h2>
    <form method="get" class="d-flex align-items-center">
        <label class="me-2 mb-0">Fecha:</label>
        <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" class="form-control form-control-sm me-2">
        <button class="btn btn-sm btn-outline-primary" type="submit">Ver</button>
    </form>
</div>

<?php if (!empty($printMessage)): ?>
    <div class="mb-3">
        <div class="alert alert-info"><?= htmlspecialchars($printMessage) ?></div>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">Resumen de Ventas (<?= htmlspecialchars($fecha) ?>)</h5>
        <?php if (empty($ventas)): ?>
            <div class="alert alert-info">No hay ventas registradas para la fecha seleccionada.</div>
        <?php else: ?>
            <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Método Pago</th>
                        <th class="text-end">Monto Venta</th>
                        <th class="text-end">Servicio</th>
                        <th class="text-end">Total con Servicio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['ID_Venta']) ?></td>
                            <td><?= htmlspecialchars($v['Fecha_Hora']) ?></td>
                            <td><?= htmlspecialchars($v['usuario'] ?? '') ?></td>
                            <td><?= htmlspecialchars($v['Metodo_Pago'] ?? '') ?></td>
                            <td class="text-end"><?= number_format(floatval($v['Total'] ?? 0), 2) ?></td>
                            <td class="text-end"><?= number_format(floatval($v['Servicio'] ?? 0), 2) ?></td>
                            <td class="text-end"><?= number_format(floatval(($v['Total'] ?? 0) + ($v['Servicio'] ?? 0)), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Totales:</th>
                        <th class="text-end"><?= number_format($totalVentas, 2) ?></th>
                        <th class="text-end"><?= number_format($totalServicio, 2) ?></th>
                        <th class="text-end"><?= number_format($totalConServicio, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Movimientos de Egreso (<?= htmlspecialchars($fecha) ?>)</h5>
        <?php if (empty($egresos)): ?>
            <div class="alert alert-info">No hay movimientos de egreso para la fecha seleccionada.</div>
        <?php else: ?>
            <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Usuario</th>
                        <th class="text-end">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($egresos as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['ID_Movimiento'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['Fecha_Hora'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['Descripcion'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['Nombre_Usuario'] ?? '') ?></td>
                            <td class="text-end">-<?= number_format(floatval($m['Monto'] ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total Egresos:</th>
                        <th class="text-end">-<?= number_format($totalEgresos, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-3">
    <a href="<?= BASE_URL ?>reportes/cierre_caja_export?fecha=<?= urlencode($fecha) ?>" class="btn btn-outline-secondary btn-sm">Exportar cierre completo (HTML)</a>
</div>

