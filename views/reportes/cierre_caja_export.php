<?php
// This export view expects the same variables as cierre_caja.php: $ventas, $fecha, $movimientos
require_once dirname(__DIR__, 2) . '/config/base_url.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cierre de Caja - <?= htmlspecialchars($fecha) ?></title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <h2>Cierre de Caja - <?= htmlspecialchars($fecha) ?></h2>
    <h4>Resumen</h4>
    <?php
    // Reuse the same logic as in cierre_caja.php to compute totals
    $totalesPago = ['Efectivo'=>0,'Tarjeta'=>0,'Transferencia'=>0,'Otro'=>0];
    $granTotal = 0; $granServicio = 0; $granFinal = 0; $totalesCambio = 0.0;
    if ($ventas && count($ventas) > 0) {
        require_once __DIR__ . '/../../models/PagoModel.php';
        $pagoModel = new PagoModel();
        foreach ($ventas as $venta) {
            $granTotal += $venta['Total'];
            $granServicio += $venta['Servicio'];
            $granFinal += $venta['TotalFinal'];
            $pagosVenta = $pagoModel->getPagosByVenta($venta['ID_Venta']);
            $restante = (float)$venta['TotalFinal'];
            $sumaCambioRegistrado = 0.0;
            if ($pagosVenta && is_array($pagosVenta)) {
                foreach ($pagosVenta as $p) {
                    $metodoRaw = trim($p['Metodo']);
                    $monto = (float)$p['Monto'];
                    if ((int)$p['Es_Cambio'] === 1) { $sumaCambioRegistrado += $monto; continue; }
                    if (stripos($metodoRaw,'efectivo')!==false) $totalesPago['Efectivo'] += $monto;
                    elseif (stripos($metodoRaw,'tarjeta')!==false||stripos($metodoRaw,'card')!==false) $totalesPago['Tarjeta'] += $monto;
                    elseif (stripos($metodoRaw,'transfer')!==false||stripos($metodoRaw,'transf')!==false) $totalesPago['Transferencia'] += $monto;
                    else $totalesPago['Otro'] += $monto;
                    $restante -= $monto;
                }
            }
            $totalesCambio += $sumaCambioRegistrado;
        }
    }
    // Movimientos
    $apertura = 0; $egresos = 0;
    if (isset($movimientos) && is_array($movimientos)) {
        foreach ($movimientos as $mov) {
            if ($mov['Tipo'] === 'Apertura') $apertura += $mov['Monto'];
            elseif ($mov['Tipo'] === 'Egreso') $egresos += $mov['Monto'];
        }
    }
    require_once dirname(__DIR__, 2) . '/models/MovimientoModel.php';
    $movModel = new MovimientoModel();
    $ingresos = $movModel->obtenerIngresosNoVentas($fecha);
    $efectivoEntregar = $apertura + $ingresos + $totalesPago['Efectivo'] - $egresos - $totalesCambio;
    ?>

    <table>
        <tr><th>Apertura de Caja</th><td class="right">C$ <?= number_format($apertura,2) ?></td></tr>
        <tr><th>Ingresos (no ventas)</th><td class="right">C$ <?= number_format($ingresos,2) ?></td></tr>
        <tr><th>Egresos</th><td class="right">C$ <?= number_format($egresos,2) ?></td></tr>
        <tr><th>Efectivo por Ventas</th><td class="right">C$ <?= number_format($totalesPago['Efectivo'],2) ?></td></tr>
        <tr><th>Cambio entregado</th><td class="right">C$ <?= number_format($totalesCambio,2) ?></td></tr>
        <tr><th>Efectivo a Entregar</th><td class="right"><b>C$ <?= number_format($efectivoEntregar,2) ?></b></td></tr>
    </table>

    <h4>Ventas por Método</h4>
    <table>
        <tr><th>Efectivo</th><td class="right">C$ <?= number_format($totalesPago['Efectivo'],2) ?></td></tr>
        <tr><th>Tarjeta</th><td class="right">C$ <?= number_format($totalesPago['Tarjeta'],2) ?></td></tr>
        <tr><th>Transferencia</th><td class="right">C$ <?= number_format($totalesPago['Transferencia'],2) ?></td></tr>
        <tr><th>Otro</th><td class="right">C$ <?= number_format($totalesPago['Otro'],2) ?></td></tr>
        <tr><th>Total Ventas</th><td class="right"><b>C$ <?= number_format($granFinal,2) ?></b></td></tr>
    </table>

    <h4>Detalle de Ventas</h4>
    <?php if ($ventas && count($ventas) > 0): ?>
    <table>
        <thead>
            <tr><th># Venta</th><th>Fecha/Hora</th><th>Usuario</th><th>Método</th><th>Total</th><th>Servicio</th><th>Total Final</th></tr>
        </thead>
        <tbody>
            <?php foreach ($ventas as $venta): ?>
            <tr>
                <td><?= (int)$venta['ID_Venta'] ?></td>
                <td><?= htmlspecialchars($venta['Fecha_Hora']) ?></td>
                <td><?= htmlspecialchars($venta['usuario']) ?></td>
                <td><?= htmlspecialchars($venta['Metodo_Pago']) ?></td>
                <td class="right">C$ <?= number_format($venta['Total'],2) ?></td>
                <td class="right">C$ <?= number_format($venta['Servicio'],2) ?></td>
                <td class="right"><b>C$ <?= number_format($venta['TotalFinal'],2) ?></b></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No hay ventas registradas en esta fecha.</p>
    <?php endif; ?>

    <h4>Detalle de Pedidos</h4>
    <?php 
    $granTotalPedidos = 0; 
    $granPagadoPedidos = 0;
    $granRestantePedidos = 0;
    if (isset($pedidos) && $pedidos && count($pedidos) > 0): 
        foreach ($pedidos as $pedido) {
            $granTotalPedidos += (float)($pedido['total_pedido'] ?? 0);
            $granPagadoPedidos += (float)($pedido['pagado'] ?? 0);
            $granRestantePedidos += (float)($pedido['restante'] ?? 0);
        }
    ?>
    <table>
        <thead>
            <tr>
                <th># Pedido</th>
                <th>Fecha/Hora</th>
                <th>Cliente</th>
                <th>Tipo Entrega</th>
                <th>Total</th>
                <th>Pagado</th>
                <th>Restante</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
            <tr>
                <td><?= (int)$pedido['ID_Pedido'] ?></td>
                <td><?= htmlspecialchars($pedido['fecha_creado']) ?></td>
                <td><?= htmlspecialchars($pedido['nombre_cliente'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($pedido['tipo_entrega'] ?? 'N/A') ?></td>
                <td class="right">C$ <?= number_format($pedido['total_pedido'] ?? 0, 2) ?></td>
                <td class="right">C$ <?= number_format($pedido['pagado'] ?? 0, 2) ?></td>
                <td class="right"><b>C$ <?= number_format($pedido['restante'] ?? 0, 2) ?></b></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4">Totales:</th>
                <th class="right">C$ <?= number_format($granTotalPedidos, 2) ?></th>
                <th class="right">C$ <?= number_format($granPagadoPedidos, 2) ?></th>
                <th class="right"><b>C$ <?= number_format($granRestantePedidos, 2) ?></b></th>
            </tr>
        </tfoot>
    </table>
    <?php else: ?>
        <p>No hay pedidos registrados en esta fecha.</p>
    <?php endif; ?>

</body>
</html>