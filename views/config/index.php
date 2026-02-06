<div class="container mt-4">
    <h2>Configuración de la Aplicación</h2>
    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <form method="POST" action="<?php echo BASE_URL; ?>configuracion/update">
        <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">
        <div class="mb-3">
            <label for="nombre_app" class="form-label">Nombre del Restaurante</label>
            <input type="text" class="form-control" id="nombre_app" name="nombre_app" value="<?php echo htmlspecialchars($config['nombre_app'] ?? ''); ?>" required>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="ruc" class="form-label">RUC</label>
                <input type="text" class="form-control" id="ruc" name="ruc" value="<?php echo htmlspecialchars($config['ruc'] ?? ''); ?>" placeholder="Ej: 0810509961001U">
            </div>
            <div class="col-md-4 mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($config['telefono'] ?? ''); ?>" placeholder="Ej: 8882-3804">
            </div>
            <div class="col-md-4 mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo htmlspecialchars($config['direccion'] ?? ''); ?>" placeholder="Dirección del restaurante">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Impresora de Cocina</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="usar_impresora_cocina" name="usar_impresora_cocina" value="1" <?php echo ($config['usar_impresora_cocina'] ?? '0') == '1' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="usar_impresora_cocina">Usar impresora de cocina</label>
            </div>
            <div class="input-group mt-2">
                <input type="text" class="form-control" id="impresora_cocina" name="impresora_cocina" placeholder="Nombre o puerto" value="<?php echo htmlspecialchars($config['impresora_cocina'] ?? ''); ?>">
                <button type="button" class="btn btn-outline-secondary" onclick="buscarImpresoras('cocina')">Buscar impresoras</button>
                <button type="button" class="btn btn-outline-primary ms-2" onclick="probarImpresora('cocina')">Probar impresora</button>
            </div>
            <div id="listaImpresorasCocina" class="mt-2"></div>
        </div>
        <div class="mb-3">
            <label class="form-label">Impresora de Barra</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="usar_impresora_barra" name="usar_impresora_barra" value="1" <?php echo ($config['usar_impresora_barra'] ?? '0') == '1' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="usar_impresora_barra">Usar impresora de barra</label>
            </div>
            <div class="input-group mt-2">
                <input type="text" class="form-control" id="impresora_barra" name="impresora_barra" placeholder="Nombre o puerto" value="<?php echo htmlspecialchars($config['impresora_barra'] ?? ''); ?>">
                <button type="button" class="btn btn-outline-secondary" onclick="buscarImpresoras('barra')">Buscar impresoras</button>
                <button type="button" class="btn btn-outline-primary ms-2" onclick="probarImpresora('barra')">Probar impresora</button>
            </div>
            <div id="listaImpresorasBarra" class="mt-2"></div>
        </div>
        <div class="mb-3">
            <label class="form-label">Impresora de ticket</label>
            <div class="input-group mt-2">
                <input type="text" class="form-control" id="impresora_ticket" name="impresora_ticket" placeholder="Nombre o puerto" value="<?php echo htmlspecialchars($config['impresora_ticket'] ?? ''); ?>">
                <button type="button" class="btn btn-outline-secondary" onclick="buscarImpresoras('ticket')">Buscar impresoras</button>
                <button type="button" class="btn btn-outline-primary ms-2" onclick="probarImpresora('ticket')">Probar impresora</button>
            </div>
<script>
function probarImpresora(tipo) {
    let nombre = '';
    if (tipo === 'cocina') nombre = document.getElementById('impresora_cocina').value;
    if (tipo === 'barra') nombre = document.getElementById('impresora_barra').value;
    if (tipo === 'ticket') nombre = document.getElementById('impresora_ticket').value;
    if (!nombre) {
        alert('Debes ingresar el nombre de la impresora a probar.');
        return;
    }
    fetch('<?php echo BASE_URL; ?>configuracion/probarImpresora', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ tipo, nombre })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Impresora funcionando correctamente.');
        } else {
            alert('Error: ' + (data.error || 'No se pudo imprimir.'));
        }
    })
    .catch(() => alert('Error de comunicación con el servidor.'));
}
</script>
            <div id="listaImpresorasTicket" class="mt-2"></div>
        </div>
        <!-- <div class="mb-3">
            <label for="moneda" class="form-label">Símbolo de la moneda</label>
            <input type="text" class="form-control" id="moneda" name="moneda" value="<php echo htmlspecialchars($config['moneda'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="iva" class="form-label">Porcentaje de IVA (%)</label>
            <input type="number" step="0.01" class="form-control" id="iva" name="iva" value="<php echo htmlspecialchars($config['iva'] ?? ''); ?>" required>
        </div> -->
        <div class="mb-3">
            <label for="servicio" class="form-label">Porcentaje de Servicio (<?php echo htmlspecialchars($config['servicio']*100 ?? ''); ?>%)</label>
            <input type="number" step="0.01" class="form-control" id="servicio" name="servicio" value="<?php echo htmlspecialchars($config['servicio'] ?? ''); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Guardar configuración</button>
        <a href="<?php echo BASE_URL; ?>config/backup" class="btn btn-success ms-2">Respaldar Base de Datos</a>
    </form>
</div>
<script>
function buscarImpresoras(tipo) {
    fetch('<?php echo BASE_URL; ?>configuracion/buscarImpresoras', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tipo })
    })
    .then(res => res.json())
    .then(data => {
        let lista = '';
        if (Array.isArray(data.impresoras) && data.impresoras.length > 0) {
            lista = '<ul class="list-group">' + data.impresoras.map(function(nombre) {
                return `<li class="list-group-item list-group-item-action" onclick=\"seleccionarImpresora('${tipo}', '${nombre.replace(/'/g, "\\'")}')\">${nombre}</li>`;
            }).join('') + '</ul>';
        } else if (data.error) {
            lista = `<div class="alert alert-danger">Error: ${data.error}</div>`;
        } else {
            lista = '<div class="alert alert-warning">No se encontraron impresoras.</div>';
        }
        if (tipo === 'cocina') {
            document.getElementById('listaImpresorasCocina').innerHTML = lista;
        } else if (tipo === 'barra') {
            document.getElementById('listaImpresorasBarra').innerHTML = lista;
        }else if (tipo === 'ticket') {
            document.getElementById('listaImpresorasTicket').innerHTML = lista;
        }
    })
    .catch(err => {
        let msg = `<div class="alert alert-danger">Error al buscar impresoras: ${err}</div>`;
        if (tipo === 'cocina') {
            document.getElementById('listaImpresorasCocina').innerHTML = msg;
        } else {
            document.getElementById('listaImpresorasBarra').innerHTML = msg;
        }
    });
}
function seleccionarImpresora(tipo, nombre) {
    if (tipo === 'cocina') {
        document.getElementById('impresora_cocina').value = nombre;
        document.getElementById('listaImpresorasCocina').innerHTML = '';
    } else if (tipo === 'barra') {
        document.getElementById('impresora_barra').value = nombre;
        document.getElementById('listaImpresorasBarra').innerHTML = '';
    } else if (tipo === 'ticket') {
        document.getElementById('impresora_ticket').value = nombre;
        document.getElementById('listaImpresorasTicket').innerHTML = '';
    }
}
</script>
