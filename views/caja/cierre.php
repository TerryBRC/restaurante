
    <h2>Cierre de Caja</h2>
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-success"> <?= htmlspecialchars($mensaje) ?> </div>
    <?php endif; ?>
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($cajaAbierta): ?>
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div class="col-md-4">
            <label class="form-label">Saldo actual</label>
            <input type="text" class="form-control" value="C$ <?= number_format($saldo, 2) ?>" readonly>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-danger">Cerrar Caja</button>
            <a href="<?= BASE_URL ?>caja/imprimir_pre_cierre" class="btn btn-warning" onclick="return confirm('¿Imprimir pre-cierre de lo que lleva acumulado?')">
                <i class="bi bi-printer"></i> Imprimir Pre-Cierre
            </a>
        </div>
    </form>
    <?php elseif ($cierreHoy && $ultimoCierre): ?>
    <div class="alert alert-success">
        <h5>¡Cierre de caja registrado hoy!</h5>
        <p><strong>Monto del cierre:</strong> C$ <?= number_format($ultimoCierre['Monto'] ?? 0, 2) ?></p>
        <p><strong>Fecha:</strong> <?= date('d/m/Y H:i:s', strtotime($ultimoCierre['Fecha_Hora'] ?? 'now')) ?></p>
    </div>
    <a href="<?= BASE_URL ?>caja/imprimir_cierre" class="btn btn-primary" onclick="return confirm('¿Imprimir ticket del último cierre?')">
        <i class="bi bi-printer"></i> Imprimir Último Cierre
    </a>
    <?php else: ?>
        <div class="alert alert-info">No hay caja abierta para cerrar.</div>
    <?php endif; ?>
