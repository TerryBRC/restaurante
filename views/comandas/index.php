<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">Comandas por Mesa</h1>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Actualizar
                </button>
            </div>
            <div class="comandas-grid">
                <?php if (!empty($comandasUnificadas)): ?>
                    <?php foreach ($comandasUnificadas as $idMesa => $comanda): ?>
                        <div class="comanda-card card mb-3">
                            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                <span class="mesa-numero">Mesa <?php echo htmlspecialchars($comanda['numero_mesa']); ?></span>
                                <small class="tiempo-espera">
                                    <?php 
                                    $tiempo = new DateTime($comanda['fecha_hora']);
                                    $ahora = new DateTime();
                                    $diferencia = $ahora->diff($tiempo);
                                    if ($diferencia->h > 0) {
                                        echo $diferencia->format('%h h %i min');
                                    } else {
                                        echo $diferencia->format('%i min');
                                    }
                                    ?>
                                </small>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($comanda['items'] as $item): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                                            <div>
                                                <span class="item-cantidad"><?php echo $item['Cantidad']; ?>x</span>
                                                <?php echo htmlspecialchars($item['Nombre_Producto']); ?>
                                                <?php if (isset($item['Categoria'])): ?>
                                                    <span class="badge bg-<?php echo $item['Categoria'] == 1 ? 'success' : 'primary'; ?> ms-2">
                                                        <?php echo $item['Categoria'] == 1 ? 'Barra' : 'Cocina'; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (isset($item['preparacion']) && trim($item['preparacion']) !== ''): ?>
                                                <div class="mt-2 ms-2 small text-muted fst-italic">Preparación: <span class="badge bg-secondary text-white ms-2"><?php echo htmlspecialchars(trim($item['preparacion'])); ?></span></div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="mt-3 d-flex gap-2">
                                    <button class="btn btn-outline-success" onclick="imprimirComandaPorTipo(<?php echo $idMesa; ?>, 1)"><i class="bi bi-cup"></i> Imprimir Barra</button>
                                    <button class="btn btn-outline-primary" onclick="imprimirComandaPorTipo(<?php echo $idMesa; ?>, 0)"><i class="bi bi-egg-fried"></i> Imprimir Cocina</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle"></i> No hay comandas pendientes en este momento.
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
            <?php require_once dirname(__DIR__, 2) . '/config/base_url.php'; ?>
            <script>
function imprimirComandaPorTipo(idMesa, tipo) {
    // tipo: 1 = Barra, 0 = Cocina
    const tipoStr = tipo == 1 ? 'barra' : 'cocina';
    fetch('<?php echo BASE_URL; ?>comandas/imprimirComanda', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_mesa: idMesa, tipo: tipoStr })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Comanda enviada a imprimir correctamente.');
        } else {
            alert('Error al imprimir: ' + (data.error || 'Error desconocido.'));
        }
    })
    .catch(() => {
        alert('Error de red al intentar imprimir la comanda.');
    });
}
</script>