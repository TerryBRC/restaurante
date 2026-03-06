// JS para procesar nueva comanda
let comandaItems = {};
let total = 0;

document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.producto-card');
    cards.forEach(card => {
        card.addEventListener('click', function() {
            try {
                const producto = JSON.parse(this.dataset.producto);
                agregarProducto(producto);
            } catch (error) {
                console.error('Error al procesar el producto:', error, 'Valor data-producto:', this.dataset.producto);
                alert('Error al seleccionar el producto. Por favor, inténtelo de nuevo.');
            }
        });
        card.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    cards.forEach(card => {
        console.log('Datos almacenados en tarjeta:', card.dataset.producto);
    });
});

function agregarProducto(producto) {
    if (!producto || !producto.ID_Producto) {
        console.error('Producto inválido:', producto);
        return;
    }
    producto.Precio_Venta = Number(producto.Precio_Venta);
    producto.Precio_Costo = Number(producto.Precio_Costo);
    producto.Stock = Number(producto.Stock);
    producto.ID_Producto = Number(producto.ID_Producto);
    producto.ID_Categoria = Number(producto.ID_Categoria);
    const id = producto.ID_Producto;
    if (!comandaItems[id]) {
        comandaItems[id] = {
            ...producto,
            cantidad: 0
        };
    }
    comandaItems[id].cantidad++;
    actualizarComanda();
}

function eliminarProducto(id) {
    if (comandaItems[id].cantidad > 1) {
        comandaItems[id].cantidad--;
    } else {
        delete comandaItems[id];
    }
    actualizarComanda();
}

function actualizarComanda() {
    const container = document.getElementById('items-comanda');
    container.innerHTML = '';
    total = 0;
    Object.values(comandaItems).forEach(item => {
        const subtotal = item.cantidad * item.Precio_Venta;
        total += subtotal;
        container.innerHTML += `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="mb-0">${item.Nombre_Producto}</h6>
                    <small class="text-muted">
                        ${item.cantidad} x $${item.Precio_Venta.toFixed(2)}
                    </small>
                </div>
                <div>
                    <span class="me-2">$${subtotal.toFixed(2)}</span>
                    <button class="btn btn-sm btn-danger" onclick="eliminarProducto(${item.ID_Producto})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    document.getElementById('total-comanda').textContent = `$${total.toFixed(2)}`;
}

function procesarComanda() {
    if (Object.keys(comandaItems).length === 0) {
        alert('Agregue al menos un producto a la comanda');
        return;
    }
    const items = Object.values(comandaItems).map(item => ({
        id_producto: item.ID_Producto,
        cantidad: item.cantidad,
        precio_venta: item.Precio_Venta
    }));
    $.post(window.BASE_URL + 'views/comandas/procesar_comanda.php', {
        id_mesa: window.idMesa,
        items: JSON.stringify(items)
    }, function(response) {
        if (response.success) {
            alert('Comanda procesada exitosamente');
            window.location.href = window.BASE_URL + 'views/mesas/';
        } else {
            alert('Error al procesar la comanda: ' + response.message);
        }
    }, 'json');
}

// Verificar el estado del procedimiento almacenado al cargar la página
$(document).ready(function() {
    $.get(window.BASE_URL + 'views/comandas/verificar_procedimiento.php', { nombre: 'sp_CreateSale' }, function(data) {
        if (data.existe) {
        } else {
            console.warn('El procedimiento almacenado sp_CreateSale NO existe.');
        }
    }, 'json');
});
