<?php
// Definir las rutas de la aplicación
// El método add() del router toma tres argumentos:
// 1. La ruta (la URL que el usuario visitará)
// 2. El nombre del controlador (sin el sufijo 'Controller')
// 3. El método en el controlador que se ejecutará

$router->add('/', 'DashboardController', 'index');
$router->add('login', 'AuthController', 'login');
$router->add('logout', 'AuthController', 'logout');
$router->add('empleados', 'EmpleadoController', 'index');
$router->add('empleados/get', 'EmpleadoController', 'getEmpleado');
$router->add('empleados/update', 'EmpleadoController', 'updateEmpleado');
$router->add('empleados/delete', 'EmpleadoController', 'deleteEmpleado');
$router->add('productos', 'ProductoController', 'index');
$router->add('mesas', 'MesaController', 'index');
$router->add('comandas', 'ComandaController', 'index');
$router->add('comandas/imprimirComanda', 'ComandaController', 'imprimirComanda');
$router->add('mesa', 'MesaController', 'detalle');
$router->add('ventas', 'VentaController', 'index');

$router->add('ventas/registrarPago', 'VentaController', 'registrarPago');
$router->add('ventas/ticket', 'VentaController', 'ticket');

// Puedes añadir más rutas aquí para otras acciones como crear, editar, eliminar, etc.
$router->add('configuracion', 'ConfigController', 'index');
$router->add('configuracion/update', 'ConfigController', 'update');
$router->add('configuracion/buscarImpresoras', 'ConfigController', 'buscarImpresoras');
$router->add('configuracion/backup', 'ConfigController', 'backup');
$router->add('configuracion/probarImpresora', 'ConfigController', 'probarImpresora');

$router->add('productos/procesar', 'ProductoController', 'procesar');
// Ruta para AJAX de agregar productos a la comanda
$router->add('detalleventa/actualizarEstado', 'DetalleVentaController', 'actualizarEstado');
$router->add('detalleventa/eliminarProducto', 'DetalleVentaController', 'eliminarProducto');
$router->add('comanda/agregarProductos', 'ComandaAjaxController', 'agregarProductos');
$router->add('comanda/crear', 'ComandaAjaxController', 'crearComanda');
$router->add('comanda/liberar', 'ComandaAjaxController', 'liberarMesa');
$router->add('user/perfil', 'UserController', 'perfil');
$router->add('user/update', 'UserController', 'actualizarPerfil');

// Ruta deshabilitada: dividir cuenta temporalmente desactivada. Para re-habilitar, descomentar.
// $router->add('mesas/dividir_cuenta', 'MesaController', 'dividirCuenta');

$router->add('mesas/procesar_mesa', 'MesaController', 'procesarMesa');
// Endpoints added for trasladar feature
$router->add('mesas/listar_libres', 'MesaController', 'listarLibres');
$router->add('mesas/trasladar', 'MesaController', 'trasladar');

$router->add('caja/apertura', 'CajaController', 'apertura');
$router->add('caja/cierre', 'CajaController', 'cierre');

$router->add('movimientos', 'MovimientoController', 'index');
$router->add('movimientos/registrar', 'MovimientoController', 'registrar');

// Pedidos (lista y ver)
$router->add('pedidos', 'PedidoController', 'index');
$router->add('pedidos/ver', 'PedidoController', 'ver');
// Crear nuevos pedidos
$router->add('pedidos/nuevo', 'PedidoController', 'nuevo');
$router->add('pedidos/crear', 'PedidoController', 'crear');
// Endpoint para registrar pagos de pedidos
$router->add('pedidos/pagar', 'PedidoController', 'pagar');

$router->add('reportes', 'ReporteController', 'index');
$router->add('reportes/ventas_dia', 'ReporteController', 'ventas_dia');
$router->add('reportes/productos_vendidos', 'ReporteController', 'productos_vendidos');
$router->add('reportes/cierre_caja', 'ReporteController', 'cierre_caja');
$router->add('reportes/cierre_caja_export', 'ReporteController', 'cierre_caja_export');
$router->add('reportes/inventario', 'ReporteController', 'inventario');
$router->add('reportes/imprimir_ticket_productos_vendidos', 'ReporteController', 'imprimir_ticket_productos_vendidos');

// Ventas del día
$router->add('ventas/dia', 'VentaController', 'ventas_dia');

// Ejemplo:
// $router->add('productos/crear', 'ProductoController', 'create');
// $router->add('productos/editar', 'ProductoController', 'edit');
