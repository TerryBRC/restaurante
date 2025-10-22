<?php
// views/pedidos/create.php
require_once dirname(__DIR__, 2) . '/config/base_url.php';
require_once dirname(__DIR__, 2) . '/helpers/Csrf.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Nuevo Pedido</h1>
    <a href="<?= BASE_URL ?>pedidos" class="btn btn-secondary">Volver</a>
</div>

<div class="row">
    <div class="col-md-5">
        <?php include dirname(__DIR__, 2) . '/views/shared/menu_productos.php'; ?>
    </div>
    <div class="col-md-7">
        <form id="formPedido">
            <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">
            <div class="mb-2">
                <label class="form-label">Tipo de Entrega</label>
                <select name="tipo_entrega" id="tipo_entrega" class="form-select">
                    <option value="local">Local</option>
                    <option value="para_llevar">Para llevar</option>
                    <option value="recoger">Recoger</option>
                    <option value="delivery">Delivery</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">Nombre Cliente</label>
                <input type="text" name="nombre_cliente" id="nombre_cliente" class="form-control">
            </div>
            <div class="mb-2">
                <label class="form-label">Teléfono</label>
                <input type="text" name="telefono" id="telefono" class="form-control">
            </div>
            <div class="mb-2">
                <label class="form-label">Dirección</label>
                <input type="text" name="direccion" id="direccion" class="form-control">
            </div>
            <div class="mb-2">
                <label class="form-label">Notas</label>
                <textarea name="notas" id="notas" class="form-control" rows="2"></textarea>
            </div>

            <h5>Items</h5>
            <ul id="lista-items" class="list-group mb-3"></ul>

            <div id="resumen-carrito" class="mb-3">
                <div class="d-flex justify-content-between"><strong>Total pedido</strong><span id="totalAmount">0.00</span></div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" id="btnGuardarPedido" class="btn btn-primary">Guardar Pedido</button>
                <a href="<?= BASE_URL ?>pedidos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
let items = [];
let currentProduct = null; // kept for modal
let currentEditIndex = null; // if editing an existing item

function formatMoney(v){
    return parseFloat(v).toFixed(2);
}

function renderItems(){
    const ul = document.getElementById('lista-items');
    ul.innerHTML = '';
    items.forEach((it, idx) => {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        const itemTotal = (parseFloat(it.precio) * parseInt(it.cantidad || 0)) || 0;
        li.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${it.nombre}</strong>
                    <div class="small">Precio: ${formatMoney(it.precio)} x ${it.cantidad}</div>
                    ${it.preparacion?'<div class="small fst-italic">'+it.preparacion+'</div>':''}
                </div>
                <div class="text-end">
                    <div class="small">Subtotal</div>
                    <div><strong>${formatMoney(itemTotal)}</strong></div>
                    <div class="btn-group mt-2">
                        <button class="btn btn-sm btn-outline-secondary" onclick="editItem(${idx})">Editar</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeItem(${idx})">Eliminar</button>
                    </div>
                </div>
            </div>
        `;
        ul.appendChild(li);
    });
    renderTotals();
}
// Modal flow for adding/editing product
function openAddProductModal(nombre, id, precio) {
    currentProduct = {nombre, id, precio};
    currentEditIndex = null; // adding
    const modalNameEl = document.getElementById('modalProductName');
    if (modalNameEl) modalNameEl.textContent = nombre;
    const cantidadEl = document.getElementById('inputCantidadModal');
    if (cantidadEl) cantidadEl.value = 1;
    const prepEl = document.getElementById('inputPreparacionModal');
    if (prepEl) prepEl.value = '';
    const labelEl = document.getElementById('modalAddProductLabel');
    if (labelEl) labelEl.textContent = 'Agregar Producto';
    var modal = new bootstrap.Modal(document.getElementById('modalAddProduct'));
    modal.show();
}

function editItem(index){
    const it = items[index];
    currentProduct = {nombre: it.nombre, id: it.id_producto, precio: it.precio};
    currentEditIndex = index;
    const modalNameEl2 = document.getElementById('modalProductName');
    if (modalNameEl2) modalNameEl2.textContent = it.nombre;
    const cantidadEl2 = document.getElementById('inputCantidadModal');
    if (cantidadEl2) cantidadEl2.value = it.cantidad;
    const prepEl2 = document.getElementById('inputPreparacionModal');
    if (prepEl2) prepEl2.value = it.preparacion || '';
    const labelEl2 = document.getElementById('modalAddProductLabel');
    if (labelEl2) labelEl2.textContent = 'Editar Item';
    var modal = new bootstrap.Modal(document.getElementById('modalAddProduct'));
    modal.show();
}

function confirmAddProductModal(){
    try {
        const cantidad = Math.max(1, parseInt(document.getElementById('inputCantidadModal').value) || 1);
        const prep = document.getElementById('inputPreparacionModal').value.trim();
        const itemData = {id_producto: currentProduct.id, nombre: currentProduct.nombre, cantidad: cantidad, precio: parseFloat(currentProduct.precio), preparacion: prep};
        if (currentEditIndex !== null && typeof currentEditIndex !== 'undefined'){
            // update existing
            items[currentEditIndex] = itemData;
        } else {
            // add new
            items.push(itemData);
        }
        // ensure UI updates
        renderItems();
    } catch (err) {
        console.error('confirmAddProductModal error', err);
        // fallback: try to salvage item data from modal fields
        try {
            const modalName = document.getElementById('modalProductName');
            const fallbackName = modalName ? modalName.textContent : 'Producto';
            const cantidad2 = Math.max(1, parseInt(document.getElementById('inputCantidadModal').value) || 1);
            const prep2 = document.getElementById('inputPreparacionModal').value.trim();
            const fallbackPrice = currentProduct && currentProduct.precio ? parseFloat(currentProduct.precio) : 0;
            const fallbackId = currentProduct && currentProduct.id ? currentProduct.id : null;
            const itemData2 = {id_producto: fallbackId, nombre: fallbackName, cantidad: cantidad2, precio: fallbackPrice, preparacion: prep2};
            if (currentEditIndex !== null && typeof currentEditIndex !== 'undefined') items[currentEditIndex] = itemData2; else items.push(itemData2);
            renderItems();
        } catch (err2) {
            console.error('confirmAddProductModal fallback failed', err2);
            alert('Error al procesar el item: ' + (err.message || err));
        }
    } finally {
        // reset edit index and hide modal safely
        currentEditIndex = null;
        var modalEl = document.getElementById('modalAddProduct');
        try {
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            if (!modal && typeof bootstrap.Modal.getInstance === 'function') modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        } catch (e) {
            console.warn('No se pudo ocultar modal con getOrCreateInstance, intentando remove backdrop', e);
            try { if (modalEl) modalEl.classList.remove('show'); } catch(e2){}
        }
    }
}

function removeItem(i){ items.splice(i,1); renderItems(); }

function renderTotals(){
    const totalEl = document.getElementById('totalAmount');
    let total = 0;
    items.forEach(it => { total += (parseFloat(it.precio) || 0) * (parseInt(it.cantidad) || 0); });
    if (totalEl) totalEl.textContent = formatMoney(total); // no taxes by default
}

document.querySelectorAll('.btn-agregar-producto').forEach(btn => {
    btn.addEventListener('click', function(){
        const id = this.dataset.id;
        const nombre = this.dataset.nombre;
        const precio = this.dataset.precio;
        openAddProductModal(nombre, id, precio);
    });
});

document.getElementById('btnGuardarPedido').addEventListener('click', function(){
    if (items.length === 0) { alert('Agrega al menos un producto'); return; }
    // populate confirmation modal
    const lista = document.getElementById('confirm-list');
    lista.innerHTML = '';
    items.forEach(it => {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between';
        const total = ((parseFloat(it.precio)||0) * (parseInt(it.cantidad)||0)).toFixed(2);
        li.innerHTML = `<div>${it.nombre} <small class="text-muted">x ${it.cantidad}</small></div><div><strong>${total}</strong></div>`;
        lista.appendChild(li);
    });
    const totalEl = document.getElementById('confirmTotal');
    if (totalEl) totalEl.textContent = document.getElementById('totalAmount').textContent || '0.00';
    var modal = new bootstrap.Modal(document.getElementById('modalConfirmPedido'));
    modal.show();
});

function confirmSendPedido(){
    // send the pedido to server
    const form = document.getElementById('formPedido');
    const data = new FormData(form);
    data.append('detalles', JSON.stringify(items));
    fetch('<?= BASE_URL ?>pedidos/crear', {
        method: 'POST',
        body: data,
        credentials: 'same-origin'
    }).then(r => r.json()).then(j => {
        if (j.success) {
            window.location.href = '<?= BASE_URL ?>pedidos';
        } else {
            alert('Error: ' + (j.error || 'No se pudo crear pedido'));
        }
    }).catch(e => { alert('Error: ' + e.message); });
}
</script>

<!-- Modal for adding product -->
<div class="modal fade" id="modalAddProduct" tabindex="-1" aria-labelledby="modalAddProductLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddProductLabel">Agregar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p><strong id="modalProductName"></strong></p>
                <div class="mb-3">
                    <label class="form-label">Cantidad</label>
                    <input type="number" id="inputCantidadModal" class="form-control" min="1" value="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Preparación (opcional)</label>
                    <textarea id="inputPreparacionModal" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmAddProductModal()">Agregar</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation modal before sending pedido -->
<div class="modal fade" id="modalConfirmPedido" tabindex="-1" aria-labelledby="modalConfirmPedidoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmPedidoLabel">Confirmar Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>Revise los items y el total antes de confirmar:</p>
                <ul id="confirm-list" class="list-group mb-3"></ul>
                <div class="d-flex justify-content-between"><strong>Total pedido</strong><span id="confirmTotal">0.00</span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmSendPedido()">Confirmar y Enviar</button>
            </div>
        </div>
    </div>
</div>
