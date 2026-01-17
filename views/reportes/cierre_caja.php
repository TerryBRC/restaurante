<?php require_once dirname(__DIR__, 2) . '/config/base_url.php'; ?>
<div class="container py-4">
    <h2 class="mb-4">Cierre de Caja - <?= isset($cajaAbierta) && $cajaAbierta ? 'Sesión Actual' : 'Última Sesión' ?></h2>
    
    <?php if (isset($fechaApertura) && $fechaApertura): ?>
        <div class="alert alert-info mb-4">
            <strong>Sesión desde:</strong> <?= date('d/m/Y H:i:s', strtotime($fechaApertura)) ?>
            <?php if (isset($cajaAbierta) && $cajaAbierta): ?>
                <span class="badge bg-success ms-2">Caja Abierta</span>
            <?php else: ?>
                <span class="badge bg-secondary ms-2">Caja Cerrada</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'ok'): ?>
            <div class="alert alert-success">Ticket de cierre enviado a la impresora correctamente.</div>
        <?php elseif ($_GET['msg'] === 'error'): ?>
            <div class="alert alert-danger">Error al imprimir el ticket de cierre. Verifique la impresora.</div>
        <?php endif; ?>
    <?php endif; ?>
    <?php
    // Inicializar acumuladores para métodos de pago y movimientos
    $totalesPago = [
        'Efectivo' => 0,
        'Tarjeta' => 0,
        'Transferencia' => 0,
        'Otro' => 0
    ];
    $granTotal = 0; $granServicio = 0; $granFinal = 0;
    if ($ventas && count($ventas) > 0) {
        foreach ($ventas as $venta) {
            $granTotal += $venta['Total'];
            $granServicio += $venta['Servicio'];
            $granFinal += $venta['TotalFinal'];
            // Desglose de métodos de pago por venta usando la tabla `pagos` cuando esté disponible
            // Esto evita depender de la cadena Metodo_Pago y permite un desglose más preciso.
            require_once dirname(__DIR__, 2) . '/models/PagoModel.php';
            $pagoModel = new PagoModel();
            $pagosVenta = $pagoModel->getPagosByVenta($venta['ID_Venta']);
            $restante = (float)$venta['TotalFinal'];
            $sumaPagosClasificados = 0.0;
            $sumaCambioRegistrado = 0.0;
            if ($pagosVenta && is_array($pagosVenta)) {
                foreach ($pagosVenta as $p) {
                    $metodoRaw = trim($p['Metodo']);
                    $monto = (float)$p['Monto'];
                    if ((int)$p['Es_Cambio'] === 1) {
                        // monto registrado como cambio (efectivo devuelto)
                        $sumaCambioRegistrado += $monto;
                        continue;
                    }
                    // Clasificar por palabras clave
                    if (stripos($metodoRaw, 'efectivo') !== false) {
                        $totalesPago['Efectivo'] += $monto;
                    } elseif (stripos($metodoRaw, 'tarjeta') !== false || stripos($metodoRaw, 'card') !== false) {
                        $totalesPago['Tarjeta'] += $monto;
                    } elseif (stripos($metodoRaw, 'transfer') !== false || stripos($metodoRaw, 'transf') !== false) {
                        $totalesPago['Transferencia'] += $monto;
                    } else {
                        $totalesPago['Otro'] += $monto;
                    }
                    $sumaPagosClasificados += $monto;
                    $restante -= $monto;
                }
            } else {
                // Fallback: si no hay registros en pagos, intentar parsear Metodo_Pago legacy
                $mp = $venta['Metodo_Pago'];
                if ($mp) {
                    $partes = explode(',', $mp);
                    foreach ($partes as $parte) {
                        $parte = trim($parte);
                        $monto = 0.0;
                        if (stripos($parte, ':') !== false) {
                            $monto = floatval(preg_replace('/[^0-9.]/', '', substr($parte, stripos($parte, ':')+1)));
                        }
                        if ($monto <= 0) continue;
                        if (stripos($parte, 'Efectivo:') !== false) {
                            $totalesPago['Efectivo'] += $monto;
                        } elseif (stripos($parte, 'Tarjeta:') !== false) {
                            $totalesPago['Tarjeta'] += $monto;
                        } elseif (stripos($parte, 'Transferencia:') !== false || stripos($parte, 'Transf') !== false) {
                            $totalesPago['Transferencia'] += $monto;
                        } else {
                            $totalesPago['Otro'] += $monto;
                        }
                        $sumaPagosClasificados += $monto;
                        $restante -= $monto;
                    }
                }
            }
            // Si queda algún monto sin clasificar (por redondeos), contabilizarlo en 'Otro'
            if ($restante > 0.01) {
                $totalesPago['Otro'] += $restante;
            }
            // Registrar el impacto del cambio en efectivo: reducir el efectivo a entregar
            if (!isset($totalesCambio)) $totalesCambio = 0.0;
            $totalesCambio += $sumaCambioRegistrado;
        }
    }

    // Desglose de movimientos
    $apertura = 0; $egresos = 0;
    if (isset($movimientos) && is_array($movimientos)) {
        foreach ($movimientos as $mov) {
            if ($mov['Tipo'] === 'Apertura') $apertura += $mov['Monto'];
            elseif ($mov['Tipo'] === 'Egreso') $egresos += $mov['Monto'];
        }
    }
    // Obtener ingresos no ventas (manuales)
    require_once dirname(__DIR__, 2) . '/models/MovimientoModel.php';
    $movModel = new MovimientoModel();
    $ingresos = $movModel->obtenerIngresosNoVentas($fecha);
    // Restar el total de cambios entregados al efectivo a entregar
    $totalesCambio = $totalesCambio ?? 0.0;
    $efectivoEntregar = $apertura + $ingresos + $totalesPago['Efectivo'] - $egresos - $totalesCambio;
    ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">Resumen Monetario</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>Apertura de Caja</th><td class="text-end">C$ <?= number_format($apertura, 2) ?></td></tr>
                        <tr><th>Pedidos</th><td class="text-end">C$ <?= number_format($ingresos, 2) ?></td></tr>
                        <tr><th>Egresos</th><td class="text-end">C$ <?= number_format($egresos, 2) ?></td></tr>
                        <tr class="table-info"><th>Efectivo por Ventas</th><td class="text-end">C$ <?= number_format($totalesPago['Efectivo'], 2) ?></td></tr>
                        <tr class="table-info"><th>Cambio entregado</th><td class="text-end">C$ <?= number_format($totalesCambio, 2) ?></td></tr>
                        <tr class="table-success fw-bold"><th>Efectivo a Entregar</th><td class="text-end">C$ <?= number_format($efectivoEntregar, 2) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">Ventas por Método de Pago</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>Efectivo</th><td class="text-end">C$ <?= number_format($totalesPago['Efectivo'], 2) ?></td></tr>
                        <tr><th>Tarjeta</th><td class="text-end">C$ <?= number_format($totalesPago['Tarjeta'], 2) ?></td></tr>
                        <tr><th>Transferencia</th><td class="text-end">C$ <?= number_format($totalesPago['Transferencia'], 2) ?></td></tr>
                        <tr><th>Otro</th><td class="text-end">C$ <?= number_format($totalesPago['Otro'], 2) ?></td></tr>
                        <tr class="table-success fw-bold"><th>Total Ventas</th><td class="text-end">C$ <?= number_format($granFinal, 2) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <h5 class="mb-3">Detalle de Ventas</h5>
    <?php if ($ventas && count($ventas) > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th># Venta</th>
                    <th>Fecha/Hora</th>
                    <th>Usuario</th>
                    <th>Método de Pago</th>
                    <th>Total</th>
                    <th>Servicio</th>
                    <th>Total Final</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ventas as $venta): ?>
                <tr>
                    <td><?= (int)$venta['ID_Venta'] ?></td>
                    <td><?= htmlspecialchars($venta['Fecha_Hora']) ?></td>
                    <td><?= htmlspecialchars($venta['usuario']) ?></td>
                    <td><?= htmlspecialchars($venta['Metodo_Pago']) ?></td>
                    <td>C$ <?= number_format($venta['Total'], 2) ?></td>
                    <td>C$ <?= number_format($venta['Servicio'], 2) ?></td>
                    <td class="fw-bold">C$ <?= number_format($venta['TotalFinal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-success">
                    <th colspan="4" class="text-end">Totales:</th>
                    <th>C$ <?= number_format($granTotal, 2) ?></th>
                    <th>C$ <?= number_format($granServicio, 2) ?></th>
                    <th>C$ <?= number_format($granFinal, 2) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else: ?>
        <div class="alert alert-info">No hay ventas registradas para esta fecha.</div>
    <?php endif; ?>

    <h5 class="mb-3 mt-5">Detalle de Pedidos</h5>
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
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
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
                    <td>C$ <?= number_format($pedido['total_pedido'] ?? 0, 2) ?></td>
                    <td>C$ <?= number_format($pedido['pagado'] ?? 0, 2) ?></td>
                    <td class="<?= ($pedido['restante'] ?? 0) > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                        C$ <?= number_format($pedido['restante'] ?? 0, 2) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-success">
                    <th colspan="4" class="text-end">Totales:</th>
                    <th>C$ <?= number_format($granTotalPedidos, 2) ?></th>
                    <th>C$ <?= number_format($granPagadoPedidos, 2) ?></th>
                    <th class="<?= $granRestantePedidos > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                        C$ <?= number_format($granRestantePedidos, 2) ?>
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else: ?>
        <div class="alert alert-info">No hay pedidos registrados para esta fecha.</div>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>reportes" class="btn btn-secondary btn-sm mt-3">Volver a reportes</a>
    <a href="<?= BASE_URL ?>imprimir_ticket_cierre.php?fecha=<?= htmlspecialchars($fecha) ?>" class="btn btn-primary btn-sm mt-3">Imprimir Ticket Cierre</a>
    <a href="<?= BASE_URL ?>reportes/cierre_caja_export?fecha=<?= htmlspecialchars($fecha) ?>" class="btn btn-outline-secondary btn-sm mt-3 ms-2">Exportar cierre (HTML)</a>
</div>
