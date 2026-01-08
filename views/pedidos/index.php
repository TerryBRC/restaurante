<?php
// views/pedidos/index.php
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Pedidos</h1>
    <div>
        <form method="get" class="d-flex align-items-center">
            <label class="me-2 mb-0">Fecha:</label>
            <input type="date" name="fecha" value="<?= htmlspecialchars($_GET['fecha'] ?? date('Y-m-d')) ?>" class="form-control form-control-sm me-2">
            <button class="btn btn-sm btn-outline-primary" type="submit">Ver</button>
        </form>
    </div>
    <a href="<?= BASE_URL ?>pedidos/nuevo" class="btn btn-sm btn-primary ms-2">Nuevo Pedido</a>
</div>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Tipo</th>
            <th>Cliente</th>
            <th>Teléfono</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Pagado</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pedidos as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['ID_Pedido']) ?></td>
            <td><?= htmlspecialchars($p['tipo_entrega']) ?></td>
            <td><?= htmlspecialchars($p['nombre_cliente'] ?? '-') ?></td>
            <td><?= htmlspecialchars($p['telefono'] ?? '-') ?></td>
            <td><?= htmlspecialchars($p['fecha_creado']) ?></td>
            <td><?= htmlspecialchars(number_format($p['total_pedido'],2)) ?></td>
            <td>
                <?php
                    $rest = isset($p['restante']) ? floatval($p['restante']) : (isset($p['total_pedido']) ? floatval($p['total_pedido']) : 0);
                    if ($rest <= 0.0001) echo '<span class="badge bg-success">Sí</span>'; else echo '<span class="badge bg-danger">No</span>';
                ?>
            </td>
            <td><a href="<?= BASE_URL ?>pedidos/ver?id=<?= $p['ID_Pedido'] ?>" class="btn btn-sm btn-outline-primary">Ver</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
