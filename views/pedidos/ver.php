<?php
// views/pedidos/ver.php
// calcular total y pagos aplicados para mostrar en cabecera
$detalles = $detalles ?? [];
$pagos = $pagos ?? [];
$total = 0.0;
foreach ($detalles as $dt) {
    $total += isset($dt['Subtotal']) ? floatval($dt['Subtotal']) : 0.0;
}
$pagosAplicados = 0.0;
foreach ($pagos as $pv) {
    if (isset($pv['Es_Cambio']) && intval($pv['Es_Cambio']) === 0) {
        $pagosAplicados += floatval($pv['Monto']);
    }
}
$restante = max(0.0, $total - $pagosAplicados);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Pedido #<?= htmlspecialchars($id) ?></h1>
    <div>
        <button id="btnPrefactura" class="btn btn-outline-primary me-2">Imprimir Prefactura</button>
        <button id="btnFactura" class="btn btn-outline-success me-2">Imprimir Factura</button>
<!-- agrega una condicion si el restante es 0 deshabilita el boton registrar pago -->
        <button id="btnRegistrarPago" class="btn btn-outline-warning me-2" <?= $restante <= 0 ? 'disabled' : '' ?>>Registrar Pago</button>
        <a href="<?= BASE_URL ?>pedidos" class="btn btn-secondary">Volver</a>
    </div>
</div>

<div class="mb-3">
    <strong>Total:</strong> <?= number_format($total,2) ?>
    &nbsp;&nbsp; <strong>Pagado:</strong> <?= number_format($pagosAplicados,2) ?>
    &nbsp;&nbsp; <strong>Restante:</strong> <?= number_format($restante,2) ?>
    <div class="form-text">El cambio se guardará automáticamente si el monto recibido excede el restante.</div>
</div>

<?php if (!empty($pedido)): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>Tipo Entrega</strong><div><?= htmlspecialchars($pedido['tipo_entrega'] ?? '') ?></div></div>
                <div class="col-md-4"><strong>Cliente</strong><div><?= htmlspecialchars($pedido['nombre_cliente'] ?? '') ?></div></div>
                <div class="col-md-4"><strong>Teléfono</strong><div><?= htmlspecialchars($pedido['telefono'] ?? '') ?></div></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6"><strong>Dirección</strong><div><?= htmlspecialchars($pedido['direccion'] ?? '') ?></div></div>
                <div class="col-md-6"><strong>Fecha</strong><div><?= htmlspecialchars($pedido['fecha_creado'] ?? $pedido['Fecha_Hora'] ?? '') ?></div></div>
            </div>
            <div class="row mt-2">
                <div class="col-12"><strong>Notas</strong><div><?= nl2br(htmlspecialchars($pedido['notas'] ?? '')) ?></div></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<table class="table">
    <thead>
        <tr>
            <th>Producto</th>
            <th>Preparación</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($detalles)) foreach ($detalles as $d): ?>
        <tr>
            <td><?= htmlspecialchars($d['Nombre_Producto'] ?? $d['ID_Producto']) ?></td>
            <td><?= htmlspecialchars($d['preparacion'] ?? '') ?></td>
            <td><?= (int)$d['Cantidad'] ?></td>
            <td><?= number_format($d['Precio_Unitario'],2) ?></td>
            <td><?= number_format($d['Subtotal'],2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" class="text-end"><strong>Total</strong></td>
            <td><strong><?= number_format($total,2) ?></strong></td>
        </tr>
    </tfoot>
</table>

<?php if (!empty($pagos)): ?>
        <h5>Pagos registrados</h5>
        <table class="table table-sm">
                <thead><tr><th>Método</th><th>Monto</th><th>Es Cambio</th><th>Fecha</th></tr></thead>
                <tbody>
                        <?php foreach ($pagos as $p): ?>
                                <tr>
                                        <td><?= htmlspecialchars($p['Metodo']) ?></td>
                                        <td><?= number_format($p['Monto'],2) ?></td>
                                        <td><?= $p['Es_Cambio'] ? 'Sí' : 'No' ?></td>
                                        <td><?= htmlspecialchars($p['Fecha_Hora']) ?></td>
                                </tr>
                        <?php endforeach; ?>
                </tbody>
        </table>
<?php endif; ?>

<!-- Modal Registrar Pago -->
<div class="modal fade" id="modalPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Pago para Pedido #<?= htmlspecialchars($id) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formPago">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Csrf::getToken()) ?>">
                    <input type="hidden" name="ID_Pedido" value="<?= htmlspecialchars($id) ?>">
                                <div class="mb-3">
                                    <label class="form-label">Método</label>
                                    <input class="form-control" name="Metodo" value="Efectivo" maxlength="50" placeholder="Efectivo, Tarjeta, Transferencia...">
                                    <div class="form-text">Máx. 50 caracteres. Sólo letras, números, espacios, guiones y comas.</div>
                                </div>
                    <div class="mb-3">
                        <div><strong>Total:</strong> <?= number_format($total,2) ?></div>
                        <div><strong>Restante:</strong> <?= number_format($restante,2) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="Monto" required value="<?= number_format($restante,2,'.','') ?>">
                        <div class="form-text">Por defecto se usa el restante del pedido.</div>
                    </div>
                    <!-- El cambio se guarda automáticamente cuando aplica; no es necesario indicar Es_Cambio -->
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button id="btnEnviarPago" type="button" class="btn btn-primary">Registrar pago</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('btnPrefactura').addEventListener('click', function(){
        const id = <?= json_encode($id) ?>;
        fetch('<?= BASE_URL ?>pedidos/prefactura?id=' + encodeURIComponent(id), {credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.json()).then(j => {
                if (j && j.success) {
                    alert('Prefactura enviada a impresora');
                } else {
                    alert('Error imprimiendo prefactura: ' + (j.error || 'Desconocido'));
                }
            }).catch(err => { alert('Error imprimiendo prefactura: ' + err.message); });
    });

    document.getElementById('btnFactura').addEventListener('click', function(){
        const id = <?= json_encode($id) ?>;
        fetch('<?= BASE_URL ?>pedidos/factura?id=' + encodeURIComponent(id), {credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.json()).then(j => {
                if (j && j.success) {
                    alert('Factura enviada a impresora');
                } else {
                    alert('Error imprimiendo factura: ' + (j.error || 'Desconocido'));
                }
            }).catch(err => { alert('Error imprimiendo factura: ' + err.message); });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // Pago modal handling
    var btn = document.getElementById('btnRegistrarPago');
    var modalEl = document.getElementById('modalPago');
    if (!btn || !modalEl) return;
    var modal = new bootstrap.Modal(modalEl, {});
    btn.addEventListener('click', function(){ modal.show(); });

    document.getElementById('btnEnviarPago').addEventListener('click', function(){
        var form = document.getElementById('formPago');
        var formData = new FormData(form);
        // normalize checkbox Es_Cambio
        if (!formData.has('Es_Cambio')) formData.append('Es_Cambio', '0');

        fetch('<?= BASE_URL ?>pedidos/pagar', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        }).then(function(resp){
            var ct = resp.headers.get('content-type') || '';
            if (ct.indexOf('application/json') !== -1) return resp.json();
            return resp.text().then(function(txt){ throw new Error('Non-JSON response:\n' + txt); });
        }).then(j => {
            if (j && j.success) {
                modal.hide();
                var msg = '';
                if (j.cambio && parseFloat(j.cambio) > 0) msg += 'Cambio: ' + parseFloat(j.cambio).toFixed(2) + '. ';
                msg += 'Restante: ' + parseFloat(j.restante).toFixed(2) + '. Total: ' + parseFloat(j.total).toFixed(2);
                // show a simple alert then reload
                alert(msg);
                // reload to show new pago
                window.location.href = '<?= BASE_URL ?>pedidos/ver?id=' + encodeURIComponent(<?= json_encode($id) ?>);
            } else {
                alert('Error guardando pago: ' + (j.error || 'Desconocido'));
            }
        }).catch(err => {
            alert('Error al registrar pago: ' + err.message);
        });
    });
});
</script>
