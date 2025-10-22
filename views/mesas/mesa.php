<?php
// Solo HTML y variables recibidas del controlador
if (isset($mensaje)) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($mensaje) . '</div>';
    return;
}
?>
<?php
// Asegurar que $moneda esté disponible para los scripts que la necesitan
if (!isset($moneda)) {
    require_once dirname(__DIR__, 2) . '/models/ConfigModel.php';
    $configModel = new ConfigModel();
    $moneda = $configModel->get('moneda') ?: 'C$';
}
?>
<div class="row g-4">
    <!-- Modal Liberar Mesa SIEMPRE en el DOM -->
<div class="modal fade" id="modalLiberarMesa" tabindex="-1" aria-labelledby="modalLiberarMesaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalLiberarMesaLabel">Liberar Mesa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formLiberarMesaModal" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="id_mesa" value="<?php echo htmlspecialchars($idMesa); ?>">
                    <input type="hidden" name="id_venta" value="<?php echo htmlspecialchars($comanda['ID_Venta']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="mb-3">
                        <label for="motivoLiberacion" class="form-label">Motivo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="motivoLiberacion" name="motivo" maxlength="100" required placeholder="Ej: Mesa desocupada, error, etc.">
                    </div>
                    <div class="mb-3">
                        <label for="descripcionLiberacion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcionLiberacion" name="descripcion" rows="2" maxlength="255" placeholder="Detalles adicionales (opcional)"></textarea>
                    </div>
                    <div id="liberarMesaError" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Liberar Mesa</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal para ingresar cantidad de producto -->
<div class="modal fade" id="modalCantidadProducto" tabindex="-1" aria-labelledby="modalCantidadProductoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCantidadProductoLabel">Cantidad de producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formCantidadProducto">
                    <div class="mb-3">
                        <label for="inputCantidadProducto" class="form-label">¿Cuántos desea agregar?</label>
                        <input type="number" class="form-control" id="inputCantidadProducto" min="1" value="1" required>
                    </div>
                    <div class="mb-3" id="preparacionContainer" style="display:none;">
                        <label for="inputPreparacion" class="form-label">Preparación especial (opcional)</label>
                        <textarea class="form-control" id="inputPreparacion" rows="2" maxlength="200" placeholder="Ej: Sin cebolla, término medio, etc."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Agregar</button>
                </form>
            </div>
        </div>
    </div>
</div>
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><h5 class="mb-0">Menú de Productos</h5></div>
            <div class="card-body">
                <?php if ($mostrarCrearComanda) { ?>
                    <div class="alert alert-info">La mesa está libre. Debe crear una comanda para poder agregar productos.</div>
                    <form id="formCrearComanda" autocomplete="off">
                        <input type="hidden" name="id_mesa" value="<?php echo htmlspecialchars($idMesa); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <button type="submit" class="btn btn-primary w-100">Crear Comanda</button>
                    </form>
                <?php } else { ?>
                    <?php include dirname(__DIR__, 2) . '/views/shared/menu_productos.php'; ?>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><h5 class="mb-0">Agregar Productos</h5></div>
            <div class="card-body">
                <ul id="lista-productos-agregados" class="list-group mb-3"></ul>
                <?php if ($comanda): ?>
                <button class="btn btn-success w-100" onclick="enviarProductosComanda()">Enviar pedido</button>
                <?php else: ?>
                <button class="btn btn-success w-100" disabled>Enviar pedido</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><h5 class="mb-0">Productos en la Mesa</h5></div>
            <div class="card-body">
                <h2 id="detalle-mesa-titulo">Detalle de Mesa #<?php echo htmlspecialchars($mesa['Numero_Mesa']); ?></h2>
                <p>Capacidad: <?php echo htmlspecialchars($mesa['Capacidad']); ?> personas</p>
                <?php if ($comanda) {
                    if (!$detalles || count($detalles) === 0) { ?>
                                                <?php if ($userRole === 'Administrador' || $userRole === 'Cajero'): ?>
                                                <button type="button" class="btn btn-danger w-100" style="margin-bottom: 1em;" data-bs-toggle="modal" data-bs-target="#modalLiberarMesa">Liberar Mesa</button>
                                                <?php endif; ?>
</div>
                    <?php } else { ?>
                        <ul id="lista-detalles-mesa" class="list-group mb-3">
                            <?php $total = 0;
                            foreach ($detalles as $detalle) {
                                $total += $detalle['Subtotal']; ?>
                                <li id="detalle-<?php echo $detalle['ID_Detalle']; ?>" class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="fw-bold me-2"><?php echo htmlspecialchars($detalle['Nombre_Producto']); ?></span>
                                            <span class="badge bg-secondary me-2">x<?php echo $detalle['Cantidad']; ?></span>
                                            <span class="text-success fw-bold">$<?php echo number_format($detalle['Subtotal'], 2); ?></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <?php if ($detalle['Cantidad'] > 1): ?>
                                                <button type="button" class="btn btn-sm btn-warning" onclick="eliminarProductoComanda(<?php echo $detalle['ID_Detalle']; ?>, <?php echo $detalle['Cantidad']; ?>)">Eliminar</button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-warning" onclick="eliminarProductoComanda(<?php echo $detalle['ID_Detalle']; ?>, 1)">Eliminar</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (isset($detalle['Preparacion']) && trim($detalle['Preparacion']) !== ''): ?>
                                        <?php $prepTrim = trim($detalle['Preparacion']); ?>
                                        <div class="mt-2 ms-3 small text-muted fst-italic">Preparación: <span class="badge bg-secondary text-white ms-2"><?php echo htmlspecialchars($prepTrim); ?></span></div>
                                    <?php endif; ?>
                                </li>
                            <?php } ?>
                            <?php
                            // Mostrar los totales usando los valores persistidos en la BD si la comanda existe.
                            require_once dirname(__DIR__, 2) . '/models/ConfigModel.php';
                            $configModel = new ConfigModel();
                            $servicioPct = (float) ($configModel->get('servicio') ?? 0);
                            $moneda = $configModel->get('moneda') ?: 'C$';
                            // Si la venta/comanda viene del controlador, preferir sus valores (DB authoritative)
                            $dbTotal = isset($comanda['Total']) ? (float)$comanda['Total'] : $total;
                            $dbServicio = isset($comanda['Servicio']) ? (float)$comanda['Servicio'] : ($dbTotal * $servicioPct);
                            $displaySubtotal = $dbTotal;
                            $displayServicio = $dbServicio;
                            $totalConServicio = $displaySubtotal + $displayServicio;
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                <span>Subtotal</span><span id="subtotal-venta" class="text-primary"><?php echo htmlspecialchars($moneda) . number_format($displaySubtotal, 2); ?></span>
                            </li>
                            <?php if ($servicioPct > 0): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Servicio (<?php echo ($servicioPct * 100); ?>%)</span><span id="servicio-venta" class="text-success"><?php echo htmlspecialchars($moneda) . number_format($displayServicio, 2); ?></span>
                                </li>
                            <?php endif; ?>
                            <li class="list-group-item fw-bold d-flex justify-content-between align-items-center bg-white">
                                <span>TOTAL</span><span id="total-venta" class="text-primary"><?php echo htmlspecialchars($moneda) . number_format($totalConServicio, 2); ?></span>
                            </li>
                        </ul>
                        <!-- Dividir cuenta deshabilitado temporalmente -->
                        <button class="btn btn-outline-secondary mb-3" disabled title="Función deshabilitada"> 
                            <i class="bi bi-scissors"></i> Dividir cuenta (deshabilitado)
                        </button>
                        <a href="<?php echo BASE_URL; ?>imprimir_ticket_pre_factura.php?id_mesa=<?php echo $mesa['ID_Mesa']; ?>" class="btn btn-warning mb-3 ms-2">
                            <i class="bi bi-receipt"></i> Pre-Facturas
                        </a>
                        </ul>
                    <?php }
                } ?>
            </div>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__, 2) . '/config/base_url.php'; ?>
<script>
document.getElementById('formCrearComanda')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const idMesa = this.id_mesa.value;
    fetch('<?php echo BASE_URL; ?>comanda/crear', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: new URLSearchParams({
            id_mesa: idMesa,
            productos: JSON.stringify([]),
            csrf_token: this.csrf_token.value
        })
    })
    .then(response => response.json())
    .then(data => {
        window.location.reload();
    });
});
let productosAgregados = [];
function renderListaProductosAgregados() {
    const lista = document.getElementById('lista-productos-agregados');
    lista.innerHTML = '';
    for (let i = 0; i < productosAgregados.length; i++) {
        const prod = productosAgregados[i];
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center';
        let html = '<div><span class="fw-bold">' + prod.nombre + '</span> <span class="badge bg-secondary ms-2">x' + prod.cantidad + '</span>';
        if (prod.preparacion && prod.preparacion.trim() !== '') {
            html += '<br><span class="text-muted small">Preparación: ' + prod.preparacion + '</span>';
        }
        html += '</div>';
        html += '<button class="btn btn-sm btn-outline-danger ms-2" onclick="eliminarProductoAgregado(' + i + ')">Eliminar</button>';
        li.innerHTML = html;
        lista.appendChild(li);
    }
}
function agregarProducto(nombre, cantidad, id, preparacion = '') {
    // Obtener la categoría del botón seleccionado
    let categoria = '';
    const btns = document.querySelectorAll('.btn-agregar-producto');
    for (let btn of btns) {
        if (btn.dataset.id == id) {
            categoria = btn.dataset.categoria || '';
            // Leer el flag is_food desde el dataset (valores '1'|'0' o 'true'|'false')
            var isFoodRaw = btn.dataset.isFood;
            var isFoodVal = (isFoodRaw === '1' || isFoodRaw === 'true' || isFoodRaw === 1 || isFoodRaw === true) ? '1' : '0';
            break;
        }
    }
    productosAgregados.push({ nombre, cantidad, id, preparacion, categoria, is_food: isFoodVal });
    renderListaProductosAgregados();
}
function eliminarProductoAgregado(idx) {
    productosAgregados.splice(idx, 1);
    renderListaProductosAgregados();
}
function enviarProductosComanda() {
    if (productosAgregados.length === 0) return;
    // Agrupar productos por id y preparación
    const agrupados = [];
    for (let i = 0; i < productosAgregados.length; i++) {
        const prod = productosAgregados[i];
        // Buscar si ya existe uno igual (id y preparación)
        const idx = agrupados.findIndex(p => p.id === prod.id && (p.preparacion || '') === (prod.preparacion || ''));
        if (idx !== -1) {
            agrupados[idx].cantidad += prod.cantidad;
        } else {
            agrupados.push({ ...prod });
        }
    }
    // Separar productos para barra y cocina según flag is_food (preferido).
    // is_food === '1' => comida (cocina), '0' => bebida (barra)
    const categoriasBarra = ['Bebidas', 'Licores', 'Cockteles', 'Cervezas'];
    const productosBarra = agrupados.filter(p => {
        if (typeof p.is_food !== 'undefined') return String(p.is_food) === '0';
        return categoriasBarra.includes((p.categoria || '').trim());
    });
    const productosCocina = agrupados.filter(p => {
        if (typeof p.is_food !== 'undefined') return String(p.is_food) === '1';
        return !categoriasBarra.includes((p.categoria || '').trim());
    });
    let idMesaInput = document.querySelector('input[name="id_mesa"]');
    let csrfTokenInput = document.querySelector('input[name="csrf_token"]');
    let idMesa = idMesaInput ? idMesaInput.value : <?php echo json_encode($idMesa); ?>;
    let csrfToken = csrfTokenInput ? csrfTokenInput.value : <?php echo json_encode(Csrf::getToken()); ?>;
    fetch('<?php echo BASE_URL; ?>comanda/agregarProductos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: new URLSearchParams({
            id_mesa: idMesa,
            productos: JSON.stringify(agrupados),
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Intentar imprimir las comandas nuevas (barra / cocina) usando ComandaController::imprimirComanda
            // Enviamos los productos agrupados para que el controlador filtre por tipo si es necesario
            fetch('<?php echo BASE_URL; ?>comandas/imprimirComanda', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_mesa: idMesa, tipo: 'ambos', productos: agrupados })
            }).then(r => r.json()).then(res => {
                // No bloqueamos la recarga por fallos de impresión; mostramos mensaje si hubo error
                if (res && res.success) {
                    // opcional: puedes mostrar notificación de éxito
                } else {
                    console.warn('Error imprimiendo comanda:', res && res.error);
                    // opcional: alert('Error al imprimir comanda: ' + (res && res.error || 'desconocido'));
                }
            }).catch(err => {
                console.error('Error de red al imprimir comanda:', err);
            }).finally(() => {
                // Recargar la página para que la vista se actualice desde la BD
                window.location.reload();
            });
        } else {
            alert('Error al enviar productos: ' + (data.error || 'Error desconocido.'));
        }
    });
}
// Modal Bootstrap para eliminar productos del detalle
let eliminarProductoModal = null;
let eliminarProductoCallback = null;
function showEliminarProductoModal(idDetalle, cantidad) {
    if (!eliminarProductoModal) {
        const modalHtml = `
        <div class="modal fade" id="modalEliminarProducto" tabindex="-1" aria-labelledby="modalEliminarProductoLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalEliminarProductoLabel">Eliminar producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
              </div>
              <div class="modal-body">
                <form id="formEliminarProductoDetalle">
                  <div class="mb-3">
                    <label for="cantidadEliminar" class="form-label">¿Cuántos deseas eliminar?</label>
                    <input type="number" class="form-control" id="cantidadEliminar" min="1" max="${cantidad}" value="${cantidad}" autocomplete="off">
                    <div class="form-text">Deja vacío o pon el máximo para eliminar todos.</div>
                  </div>
                  <button type="submit" class="btn btn-danger w-100">Eliminar</button>
                </form>
              </div>
            </div>
          </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        eliminarProductoModal = new bootstrap.Modal(document.getElementById('modalEliminarProducto'));
        document.getElementById('formEliminarProductoDetalle').addEventListener('submit', function(e) {
            e.preventDefault();
            if (eliminarProductoCallback) eliminarProductoCallback();
        });
    } else {
        document.getElementById('cantidadEliminar').max = cantidad;
        document.getElementById('cantidadEliminar').value = cantidad;
    }
    eliminarProductoModal.show();
    return new Promise(resolve => {
        eliminarProductoCallback = () => {
            const cantPrompt = document.getElementById('cantidadEliminar').value.trim();
            eliminarProductoModal.hide();
            resolve(cantPrompt);
        };
    });
}
function eliminarProductoComanda(idDetalle, cantidad) {
    let csrfToken = '<?php echo htmlspecialchars($csrf_token); ?>';
    let body = 'id_detalle=' + idDetalle + '&csrf_token=' + encodeURIComponent(csrfToken);
    if (cantidad > 1) {
    showEliminarProductoModal(idDetalle, cantidad).then(cantPrompt => {
            if (cantPrompt === null) return;
            if (cantPrompt !== '' && !isNaN(cantPrompt)) {
                body += '&cantidad=' + encodeURIComponent(cantPrompt);
            }
            else {
                body += '&cantidad=' + cantidad;
            }
            fetch('<?php echo BASE_URL; ?>detalleventa/eliminarProducto', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refrescar la página para asegurar que todo el estado (botones, mensajes, etc.) quede consistente
                    setTimeout(() => { location.reload(); }, 300);
                } else {
                    alert('Error al eliminar producto');
                }
            })
            .catch(() => {
                alert('Error de comunicación con el servidor.');
            });
        });
    } else {
        body += '&cantidad=1';
        fetch('<?php echo BASE_URL; ?>detalleventa/eliminarProducto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => { location.reload(); }, 300);
            } else {
                alert('Error al eliminar producto');
            }
        })
        .catch(() => {
            alert('Error de comunicación con el servidor.');
        });
    }
}

/**
 * Renderiza la lista de detalles y los totales a partir de la estructura venta retornada por el servidor.
 * Espera venta.detalles = [{ ID_Detalle, Nombre_Producto, Cantidad, Subtotal, preparacion, Precio_Venta }, ...]
 */
function renderDetallesFromVenta(venta) {
    const lista = document.getElementById('lista-detalles-mesa');
    const moneda = <?php echo json_encode($moneda); ?>;
    if (!venta) return;
    // Limpiar lista
    if (lista) lista.innerHTML = '';
    const detalles = venta.detalles || [];
    let subtotalCalc = 0;
    // helper to escape text inserted into HTML
    const escapeHtml = (str) => {
        if (str === null || typeof str === 'undefined') return '';
        return String(str).replace(/[&<>"'`]/g, function (s) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '`': '&#96;'
            })[s];
        });
    };

    detalles.forEach(det => {
        subtotalCalc += Number(det.Subtotal || 0);
        // Normalize preparación key: backend may return 'Preparacion' or 'preparacion'
        det.preparacion = (typeof det.preparacion !== 'undefined' && det.preparacion !== null) ? det.preparacion : (typeof det.Preparacion !== 'undefined' ? det.Preparacion : '');
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.id = 'detalle-' + det.ID_Detalle;
        let inner = '<div class="d-flex justify-content-between align-items-center">';
        inner += '<div class="d-flex align-items-center"><span class="fw-bold me-2">' + (det.Nombre_Producto || '') + '</span>';
        inner += '<span class="badge bg-secondary me-2">x' + (det.Cantidad || 0) + '</span>';
        inner += '<span class="text-success fw-bold">' + moneda + Number(det.Subtotal || 0).toFixed(2) + '</span></div>';
        inner += '<div class="d-flex gap-2">';
        const cant = Number(det.Cantidad || 0);
        if (cant > 1) {
            inner += '<button type="button" class="btn btn-sm btn-warning" onclick="eliminarProductoComanda(' + det.ID_Detalle + ', ' + cant + ')">Eliminar</button>';
        } else {
            inner += '<button type="button" class="btn btn-sm btn-warning" onclick="eliminarProductoComanda(' + det.ID_Detalle + ', 1)">Eliminar</button>';
        }
    inner += '</div></div>';
    if (det.preparacion && String(det.preparacion).trim() !== '') inner += '<div class="mt-2 ms-3 small text-muted fst-italic">Preparación: <span class="badge bg-secondary text-white ms-2">' + escapeHtml(det.preparacion) + '</span></div>';
    li.innerHTML = inner;
        if (lista) lista.appendChild(li);
    });
    // Actualizar totales visibles usando valores oficiales si vienen en venta, si no usar el calculo local
    const subtotalEl = document.getElementById('subtotal-venta');
    const servicioEl = document.getElementById('servicio-venta');
    const totalEl = document.getElementById('total-venta');
    const subtotalVal = Number(venta.Total || subtotalCalc || 0).toFixed(2);
    const servicioVal = Number(venta.Servicio || 0).toFixed(2);
    const totalVal = (Number(subtotalVal) + Number(servicioVal)).toFixed(2);
    if (subtotalEl) subtotalEl.textContent = moneda + subtotalVal;
    if (servicioEl) servicioEl.textContent = moneda + servicioVal;
    if (totalEl) totalEl.textContent = moneda + totalVal;
}

// Modal Liberar Mesa - submit
document.getElementById('formLiberarMesaModal')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const idMesa = form.id_mesa.value;
    const idVenta = form.id_venta.value;
    const csrfToken = form.csrf_token.value;
    const motivo = form.motivo.value.trim();
    const descripcion = form.descripcion.value.trim();
    const errorDiv = document.getElementById('liberarMesaError');
    errorDiv.classList.add('d-none');
    errorDiv.textContent = '';
    if (!motivo) {
        errorDiv.textContent = 'El motivo es obligatorio.';
        errorDiv.classList.remove('d-none');
        return;
    }
    fetch('<?php echo BASE_URL; ?>comanda/liberar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: new URLSearchParams({
            id_mesa: idMesa,
            id_venta: idVenta,
            csrf_token: csrfToken,
            motivo: motivo,
            descripcion: descripcion
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cerrar modal y recargar
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalLiberarMesa'));
            modal.hide();
            window.location.href = '<?php echo BASE_URL; ?>mesas';
        } else {
            errorDiv.textContent = data.error || 'Error al liberar la mesa';
            errorDiv.classList.remove('d-none');
        }
    })
    .catch(() => {
        errorDiv.textContent = 'Error de comunicación con el servidor.';
        errorDiv.classList.remove('d-none');
    });
});



renderListaProductosAgregados();
// Interceptar clicks en botones de productos del menú (usado en menu_productos.php)
let productoSeleccionado = null;
let productoSeleccionadoCategoria = null;
let productoSeleccionadoPrecio = null;
function initProductButtons() {
    // Attach handlers to buttons
    document.querySelectorAll('.btn-agregar-producto').forEach(btn => {
        // Avoid attaching multiple times
        if (btn.__hasProductHandler) return;
        btn.__hasProductHandler = true;
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const nombre = this.getAttribute('data-nombre');
            const id = parseInt(this.getAttribute('data-id'));
            const categoria = this.getAttribute('data-categoria');
            const precio = parseFloat(this.getAttribute('data-precio'));
            // Leer is_food del dataset, propagarlo y controlar la visibilidad del campo Preparación
            const isFoodAttr = this.getAttribute('data-is-food');
            const isFood = (isFoodAttr === '1' || isFoodAttr === 'true') ? '1' : '0';
            productoSeleccionado = { nombre, id, is_food: isFood };
            productoSeleccionadoCategoria = categoria;
            productoSeleccionadoPrecio = precio;
            document.getElementById('inputCantidadProducto').value = 1;
            // Mostrar campo preparación si corresponde
            // Permitir preparación también para ciertas categorías de bebidas (ej. cockteles)
            const allowPrepCats = ['cockteles','coctel','cocktail','cocktails','Cockteles','Cocktail','Cocktails','Coctel'];
            const catLower = (categoria || '').toLowerCase().trim();
            const shouldShowPrep = (isFoodAttr !== null && (isFoodAttr === '1' || isFoodAttr === 'true')) || allowPrepCats.includes(catLower);
            if (shouldShowPrep) {
                document.getElementById('preparacionContainer').style.display = '';
            } else {
                document.getElementById('preparacionContainer').style.display = 'none';
                document.getElementById('inputPreparacion').value = '';
            }
            const modal = new bootstrap.Modal(document.getElementById('modalCantidadProducto'));
            modal.show();
        });
    });
    // Modal cantidad submit (attach once)
    const formCantidad = document.getElementById('formCantidadProducto');
    if (formCantidad && !formCantidad.__hasSubmitHandler) {
        formCantidad.__hasSubmitHandler = true;
        formCantidad.addEventListener('submit', function(e) {
            e.preventDefault();
            const cantidad = parseInt(document.getElementById('inputCantidadProducto').value, 10);
            const preparacion = document.getElementById('inputPreparacion').value.trim();
                if (productoSeleccionado && cantidad > 0) {
                // Si ya existe con la misma preparación, suma la cantidad
                let idx = productosAgregados.findIndex(p => p.id === productoSeleccionado.id && (p.preparacion || '') === preparacion);
                if (idx !== -1) {
                    productosAgregados[idx].cantidad += cantidad;
                } else {
                    productosAgregados.push({ nombre: productoSeleccionado.nombre, cantidad, id: productoSeleccionado.id, categoria: productoSeleccionadoCategoria, precio: productoSeleccionadoPrecio, preparacion, is_food: productoSeleccionado.is_food });
                }
                renderListaProductosAgregados();
                // Limpiar campo preparación y cantidad
                document.getElementById('inputPreparacion').value = '';
                document.getElementById('inputCantidadProducto').value = 1;
                // Quitar el foco del elemento activo antes de cerrar el modal
                if (document.activeElement) document.activeElement.blur();
                // Cerrar modal
                const modalEl = document.getElementById('modalCantidadProducto');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                // Agregar listener para mover el foco después del cierre
                const focusAfterClose = () => {
                    setTimeout(() => {
                        const btnEnviar = document.querySelector('.btn-success.w-100:not([disabled])');
                        if (btnEnviar) btnEnviar.focus();
                        modalEl.removeEventListener('hidden.bs.modal', focusAfterClose);
                    }, 30);
                };
                modalEl.addEventListener('hidden.bs.modal', focusAfterClose);
                modalInstance.hide();
            }
        });
    }
}

// Run init immediately if DOM is ready, otherwise wait for DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProductButtons);
} else {
    initProductButtons();
}

// Eliminado: lógica y referencias al modal de dividir cuenta y parciales

// --- Trasladar mesa: botón/modal/JS ---
// Insertar botón si hay comanda y usuario con permisos
    (() => {
    const userRole = <?php echo json_encode($userRole ?? ''); ?>;
    const comandaId = <?php echo json_encode($comanda ? $comanda['ID_Venta'] : null); ?>;
    const currentMesaId = <?php echo json_encode($mesa['ID_Mesa']); ?>;
    if (comandaId && (userRole === 'Administrador' || userRole === 'Mesero' || userRole === 'Cajero')) {
        // Target the specific Detalle de Mesa header to avoid ambiguous selectors
        const titulo = document.getElementById('detalle-mesa-titulo');
        const container = titulo ? titulo.parentElement : null;
        if (container) {
            const btn = document.createElement('button');
            btn.className = 'btn btn-outline-secondary mb-3 ms-2';
            btn.textContent = 'Trasladar mesa';
            btn.type = 'button';
            btn.addEventListener('click', () => {
                showTrasladarModal();
            });
            // Insert before the action links
            const actionLinks = container.querySelectorAll('a');
            if (actionLinks.length > 0) actionLinks[0].before(btn);
            else container.appendChild(btn);
        }
    }

    // Modal HTML (append once)
    function ensureModalExists() {
        if (document.getElementById('modalTrasladarMesa')) return;
        const html = `
        <div class="modal fade" id="modalTrasladarMesa" tabindex="-1" aria-labelledby="modalTrasladarMesaLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTrasladarMesaLabel">Trasladar mesa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
              </div>
              <form id="formTrasladarMesa">
              <div class="modal-body">
                <input type="hidden" name="id_venta" value="${comandaId}">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="mb-3">
                  <label for="selectMesaDestino" class="form-label">Mesa destino</label>
                  <select id="selectMesaDestino" name="id_mesa_destino" class="form-select" required>
                    <option value="">Cargando mesas libres...</option>
                  </select>
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" value="1" id="checkImprimirAvisos" name="imprimir_avisos">
                  <label class="form-check-label" for="checkImprimirAvisos">Imprimir avisos a cocina/barra</label>
                </div>
                <div id="trasladoError" class="alert alert-danger d-none"></div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Confirmar traslado</button>
              </div>
              </form>
            </div>
          </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', html);
        const form = document.getElementById('formTrasladarMesa');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitTrasladoForm(this);
        });
    }

    function showTrasladarModal() {
        ensureModalExists();
        const select = document.getElementById('selectMesaDestino');
        select.innerHTML = '<option value="">Cargando mesas libres...</option>';
        fetch('<?php echo BASE_URL; ?>mesas/listar_libres', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                select.innerHTML = '';
                if (!Array.isArray(data) || data.length === 0) {
                    select.innerHTML = '<option value="">No hay mesas libres</option>';
                    return;
                }
                data.forEach(m => {
                    // Excluir la mesa actual si aparece
                    if (String(m.ID_Mesa) === String(currentMesaId)) return;
                    const opt = document.createElement('option');
                    opt.value = m.ID_Mesa;
                    opt.textContent = 'Mesa ' + (m.Numero_Mesa ?? m.ID_Mesa) + (m.Capacidad ? ' — ' + m.Capacidad + ' pers.' : '');
                    select.appendChild(opt);
                });
            }).catch(() => {
                select.innerHTML = '<option value="">Error al cargar mesas</option>';
            }).finally(() => {
                const modal = new bootstrap.Modal(document.getElementById('modalTrasladarMesa'));
                modal.show();
            });
    }

    function submitTrasladoForm(form) {
        const idVenta = form.id_venta.value;
        const idMesaDestino = form.id_mesa_destino.value;
        const csrf = form.csrf_token.value;
        const imprimir = document.getElementById('checkImprimirAvisos').checked ? '1' : '0';
        const errorDiv = document.getElementById('trasladoError');
        errorDiv.classList.add('d-none');
        errorDiv.textContent = '';
        if (!idMesaDestino) {
            errorDiv.textContent = 'Seleccione una mesa destino.';
            errorDiv.classList.remove('d-none');
            return;
        }
        fetch('<?php echo BASE_URL; ?>mesas/trasladar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: new URLSearchParams({ id_venta: idVenta, id_mesa_destino: idMesaDestino, imprimir_avisos: imprimir, csrf_token: csrf })
        })
        .then(async res => {
            const txt = await res.text();
            // Try parse JSON if possible
            try {
                const data = JSON.parse(txt || '{}');
                if (!res.ok) throw new Error(data.error || ('HTTP ' + res.status));
                return data;
            } catch (e) {
                // Not JSON or parsing failed
                if (!res.ok) throw new Error('HTTP ' + res.status + ': ' + (txt || res.statusText));
                try {
                    return JSON.parse(txt);
                } catch (e2) {
                    return { success: false, error: txt || 'Respuesta inesperada' };
                }
            }
        })
        .then(data => {
            if (data && data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalTrasladarMesa'));
                modal.hide();
                window.location.href = '<?php echo BASE_URL; ?>mesas';
            } else {
                errorDiv.textContent = (data && (data.error || data.message)) || 'Error desconocido';
                errorDiv.classList.remove('d-none');
            }
        })
        .catch(err => {
            errorDiv.textContent = err.message || 'Error de comunicación con el servidor.';
            errorDiv.classList.remove('d-none');
            console.error('Traslado error:', err);
        });
    }
})();
</script>
