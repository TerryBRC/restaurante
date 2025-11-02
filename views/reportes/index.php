<div class="container py-4">
    <h2 class="mb-4">Reportes</h2>
    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <a href="<?= BASE_URL ?>reportes/ventas_dia" class="card text-decoration-none h-100 shadow-sm border-0">
                <div class="card-body text-center">
                    <i class="bi bi-bar-chart display-4 text-primary mb-3"></i>
                    <h5 class="card-title">Ventas por Día</h5>
                    <p class="card-text small">Reporte de ventas diarias por empleado.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="<?= BASE_URL ?>reportes/productos_vendidos" class="card text-decoration-none h-100 shadow-sm border-0">
                <div class="card-body text-center">
                    <i class="bi bi-box display-4 text-success mb-3"></i>
                    <h5 class="card-title">Productos Vendidos</h5>
                    <p class="card-text small">Productos vendidos por fecha.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="<?= BASE_URL ?>reportes/cierre_caja" class="card text-decoration-none h-100 shadow-sm border-0">
                <div class="card-body text-center">
                    <i class="bi bi-cash-coin display-4 text-warning mb-3"></i>
                    <h5 class="card-title">Cierre de Caja</h5>
                    <p class="card-text small">Conciliación y resumen de caja diaria.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="<?= BASE_URL ?>reportes/cierre_ventas_egresos" class="card text-decoration-none h-100 shadow-sm border-0">
                <div class="card-body text-center">
                    <i class="bi bi-file-earmark-text display-4 text-danger mb-3"></i>
                    <h5 class="card-title">Cierre Ventas & Egresos</h5>
                    <p class="card-text small">Reporte combinado: ventas y movimientos de egreso.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="<?= BASE_URL ?>reportes/inventario" class="card text-decoration-none h-100 shadow-sm border-0">
                <div class="card-body text-center">
                    <i class="bi bi-clipboard-data display-4 text-info mb-3"></i>
                    <h5 class="card-title">Inventario</h5>
                    <p class="card-text small">Stock y costos de productos.</p>
                </div>
            </a>
        </div>
    </div>
</div>
