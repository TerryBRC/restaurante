<?php 
require_once dirname(__DIR__, 2) . '/config/base_url.php'; 
require_once dirname(__DIR__, 2) . '/config/Session.php';
Session::init();
if ($msg = Session::get('flash_success')) {
    echo '<div class="alert alert-success">' . htmlspecialchars($msg) . '</div>';
    Session::set('flash_success', null);
}
if ($msg = Session::get('flash_error')) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($msg) . '</div>';
    Session::set('flash_error', null);
}
?>
<div class="container py-4">
    <h2 class="mb-4">Reporte de Productos Vendidos por Fecha</h2>
    <form class="row g-3 mb-4" method="get" action="<?= BASE_URL ?>reportes/productos_vendidos">
        <div class="col-auto">
            <label for="fecha" class="col-form-label">Fecha:</label>
        </div>
        <div class="col-auto">
            <input type="date" id="fecha" name="fecha" class="form-control" value="<?= htmlspecialchars($fecha) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>
    <?php if ($ventas && count($ventas) > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Cantidad Vendida</th>
                    <th>Total Vendido</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ventas as $venta): ?>
                <tr>
                    <td><?= htmlspecialchars($venta['Nombre_Producto']) ?></td>
                    <td><?= htmlspecialchars($venta['Nombre_Categoria']) ?></td>
                    <td><?= (int)$venta['Cantidad'] ?></td>
                    <td>C$ <?= number_format($venta['TotalVendido'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="<?= BASE_URL ?>reportes/imprimir_ticket_productos_vendidos" class="mt-3">
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-printer"></i> Imprimir ticket
        </button>
    </form>
    <?php else: ?>
        <div class="alert alert-info">No hay ventas registradas para esta fecha.</div>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>reportes" class="btn btn-secondary btn-sm mt-3">Volver a reportes</a>
</div>
