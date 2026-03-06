
<?php
require_once 'config/Session.php';
Session::init();
require_once '../../models/MesaModel.php';
require_once '../../models/ProductModel.php';
require_once '../../models/VentaModel.php';
// ...existing code...
?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
<?php foreach ($productos as $producto): ?>
    <div class="col-md-4 mb-3">
        <div class="producto-card card h-100" data-producto='<?php echo json_encode($producto); ?>'>
            <div class="card-body">
                <h5 class="card-title"><?php echo $producto['Nombre_Producto']; ?></h5>
                <p>
                    Precio: $<?php echo number_format($producto['Precio_Venta'], 2); ?><br>
                    Stock: <?php echo $producto['Stock']; ?>
                </p>
            </div>
        </div>
    </div>
<?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Resumen de Productos a Agregar -->
                    <div class="col-md-4">
                        <div class="card comanda-preview">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Productos a Agregar</h5>
                            </div>
                            <div class="card-body">
                                <div id="items-comanda"></div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <h5>Total:</h5>
                                    <h5 id="total-comanda">$0.00</h5>
                                </div>
                                <button class="btn btn-primary w-100 mt-3" onclick="procesarProductos()">Agregar Productos</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php BASE_URL ?>assets/bootstrap/bootstrap-5.3.0-dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php BASE_URL ?>assets/js/agregar_productos.js"></script>
</body>
</html>

                    <!-- Resumen de Productos a Agregar -->
                    <div class="col-md-4">
                        <div class="card comanda-preview">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Productos a Agregar</h5>
                            </div>
                            <div class="card-body">
                                <div id="items-comanda"></div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <h5>Total:</h5>
                                    <h5 id="total-comanda">$0.00</h5>
                                </div>
                                <button class="btn btn-primary w-100 mt-3" onclick="procesarProductos()">Agregar Productos</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php BASE_URL ?>assets/bootstrap/bootstrap-5.3.0-dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php BASE_URL ?>assets/js/agregar_productos.js"></script>
    <script>
        // Inicialización de variables globales
        let comandaItems = {};
        let total = 0;

        // Agregar event listeners cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            
            // Agregar listeners a todas las tarjetas de productos
            cards.forEach(card => {
                // Usar mousedown en lugar de click para mejor respuesta
                card.addEventListener('mousedown', function(e) {
                    e.preventDefault(); // Prevenir comportamiento por defecto
                    e.stopPropagation(); // Detener propagación del evento

                    // Añadir clase para feedback visual
                    this.classList.add('clicked');
                    setTimeout(() => this.classList.remove('clicked'), 300);

                    try {
                        const productoData = this.dataset.producto;
                        const producto = JSON.parse(productoData);
                        agregarProducto(producto);
                    } catch (error) {
                        console.error('Error al procesar el producto:', error, 'Valor data-producto:', this.dataset.producto);
                        alert('Error al seleccionar el producto. Por favor, inténtelo de nuevo.');
                    }
                });

                // Prevenir el comportamiento por defecto en click también
                card.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });

            // Verificar que los datos de productos están correctamente almacenados
        });
        });

        function agregarProducto(producto) {
            if (!producto || !producto.ID_Producto) {
                return;
            }

            // Convertir campos numéricos a número
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

        function procesarProductos() {
            if (Object.keys(comandaItems).length === 0) {
                alert('Agregue al menos un producto');
                return;
            }

            const items = Object.values(comandaItems).map(item => ({
                id_producto: item.ID_Producto,
                cantidad: item.cantidad,
                precio_venta: item.Precio_Venta
            }));

            $.post('agregar_productos_comanda.php', {
                id_mesa: <?php echo $idMesa; ?>,
                id_venta: <?php echo $ventaActiva['ID_Venta']; ?>,
                items: JSON.stringify(items)
            }, function(response) {
                if (response.success) {
                    alert('Productos agregados exitosamente');
                    window.location.href = '../mesas/';
                } else {
                    alert('Error al agregar productos: ' + response.message);
                }
            }, 'json');
        }
    </script>
</body>
</html>