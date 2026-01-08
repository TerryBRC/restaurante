# Documentación de API y Rutas

## Sistema de Enrutamiento

El sistema utiliza un enrutador personalizado basado en el patrón Front Controller.

### Configuración de Rutas

Las rutas se definen en `config/routes.php`:

```php
$router->add('ruta', 'ControllerName', 'methodName');
```

## Rutas Disponibles

### Autenticación

#### `GET /login`
Muestra el formulario de inicio de sesión.

**Controller**: `AuthController::login`  
**Vista**: `views/login.php`  
**Acceso**: Público

#### `POST /login`
Procesa el inicio de sesión.

**Controller**: `AuthController::login`  
**Parámetros**:
- `username` (string): Nombre de usuario
- `password` (string): Contraseña

**Respuesta**: Redirección al dashboard o mensaje de error

#### `GET /logout`
Cierra la sesión del usuario.

**Controller**: `AuthController::logout`  
**Respuesta**: Redirección a `/login`

---

### Dashboard

#### `GET /`
Página principal del sistema.

**Controller**: `DashboardController::index`  
**Vista**: `views/dashboard.php`  
**Acceso**: Requiere autenticación

---

### Mesas

#### `GET /mesas`
Lista todas las mesas con su estado.

**Controller**: `MesaController::index`  
**Vista**: `views/mesas/index.php`

#### `GET /mesa?id={id}`
Detalle de una mesa específica con su comanda activa.

**Controller**: `MesaController::detalle`  
**Parámetros**:
- `id` (int): ID de la mesa

**Vista**: `views/mesas/mesa.php`

#### `POST /mesas/procesar_mesa`
Procesa acciones sobre una mesa (liberar, trasladar).

**Controller**: `MesaController::procesarMesa`  
**Parámetros**:
- `accion` (string): 'liberar' o 'trasladar'
- `id_mesa` (int): ID de la mesa
- `motivo` (string): Motivo de liberación (si aplica)
- `mesa_destino` (int): ID mesa destino (si es traslado)

#### `GET /mesas/listar_libres`
Lista mesas disponibles (AJAX).

**Controller**: `MesaController::listarLibres`  
**Respuesta**: JSON con mesas libres

```json
[
  {
    "ID_Mesa": 1,
    "Numero_Mesa": 1,
    "Capacidad": 4
  }
]
```

#### `POST /mesas/trasladar`
Traslada una venta entre mesas (AJAX).

**Controller**: `MesaController::trasladar`  
**Parámetros**:
- `id_venta` (int): ID de la venta
- `id_mesa_destino` (int): ID mesa destino

**Respuesta**: JSON
```json
{
  "success": true,
  "message": "Venta trasladada exitosamente"
}
```

---

### Ventas

#### `GET /ventas`
Lista de ventas.

**Controller**: `VentaController::index`  
**Vista**: `views/ventas/index.php`

#### `GET /ventas/dia?fecha={fecha}`
Ventas de un día específico.

**Controller**: `VentaController::ventas_dia`  
**Parámetros**:
- `fecha` (string): Fecha en formato YYYY-MM-DD

#### `POST /ventas/registrarPago`
Registra el pago de una venta.

**Controller**: `VentaController::registrarPago`  
**Parámetros**:
- `id_venta` (int): ID de la venta
- `pagos` (array): Array de pagos
  - `metodo` (string): Efectivo, Tarjeta, Transferencia
  - `monto` (float): Monto del pago

**Ejemplo**:
```json
{
  "id_venta": 123,
  "pagos": [
    {"metodo": "Efectivo", "monto": 500},
    {"metodo": "Tarjeta", "monto": 200}
  ]
}
```

#### `GET /ventas/ticket?id={id}`
Imprime el ticket de una venta.

**Controller**: `VentaController::ticket`  
**Parámetros**:
- `id` (int): ID de la venta

---

### Comandas

#### `GET /comandas`
Vista de comandas activas.

**Controller**: `ComandaController::index`  
**Vista**: `views/comandas/index.php`

#### `POST /comanda/crear`
Crea una nueva comanda (AJAX).

**Controller**: `ComandaAjaxController::crearComanda`  
**Parámetros**:
- `id_mesa` (int): ID de la mesa
- `id_cliente` (int): ID del cliente
- `id_empleado` (int): ID del empleado

**Respuesta**: JSON
```json
{
  "success": true,
  "id_venta": 123
}
```

#### `POST /comanda/agregarProductos`
Agrega productos a una comanda (AJAX).

**Controller**: `ComandaAjaxController::agregarProductos`  
**Parámetros**:
- `id_venta` (int): ID de la venta
- `productos` (array): Array de productos
  - `id_producto` (int)
  - `cantidad` (int)
  - `precio` (float)
  - `preparacion` (string, opcional)

**Ejemplo**:
```json
{
  "id_venta": 123,
  "productos": [
    {
      "id_producto": 5,
      "cantidad": 2,
      "precio": 60.00,
      "preparacion": "Sin cebolla"
    }
  ]
}
```

**Respuesta**: JSON
```json
{
  "success": true,
  "message": "Productos agregados correctamente"
}
```

#### `POST /comanda/liberar`
Libera una mesa (AJAX).

**Controller**: `ComandaAjaxController::liberarMesa`  
**Parámetros**:
- `id_mesa` (int): ID de la mesa
- `motivo` (string): Motivo de liberación

#### `POST /comandas/imprimirComanda`
Imprime la comanda en cocina/barra.

**Controller**: `ComandaController::imprimirComanda`  
**Parámetros**:
- `id_venta` (int): ID de la venta

---

### Detalle de Venta

#### `POST /detalleventa/actualizarEstado`
Actualiza el estado de un producto en la comanda.

**Controller**: `DetalleVentaController::actualizarEstado`  
**Parámetros**:
- `id_detalle` (int): ID del detalle
- `estado` (string): Nuevo estado

#### `POST /detalleventa/eliminarProducto`
Elimina un producto de la venta.

**Controller**: `DetalleVentaController::eliminarProducto`  
**Parámetros**:
- `id_detalle` (int): ID del detalle

---

### Pedidos

#### `GET /pedidos`
Lista de pedidos.

**Controller**: `PedidoController::index`  
**Vista**: `views/pedidos/index.php`

#### `GET /pedidos/ver?id={id}`
Detalle de un pedido.

**Controller**: `PedidoController::ver`  
**Parámetros**:
- `id` (int): ID del pedido

**Vista**: `views/pedidos/ver.php`

#### `GET /pedidos/nuevo`
Formulario para crear nuevo pedido.

**Controller**: `PedidoController::nuevo`  
**Vista**: `views/pedidos/create.php`

#### `POST /pedidos/crear`
Crea un nuevo pedido.

**Controller**: `PedidoController::crear`  
**Parámetros**:
- `tipo_entrega` (string): 'local' o 'delivery'
- `nombre_cliente` (string)
- `telefono` (string)
- `direccion` (string, opcional)
- `notas` (string, opcional)
- `productos` (array): Array de productos

**Ejemplo**:
```json
{
  "tipo_entrega": "delivery",
  "nombre_cliente": "Juan Pérez",
  "telefono": "88887777",
  "direccion": "Calle Principal #123",
  "productos": [
    {
      "id_producto": 5,
      "cantidad": 2,
      "precio": 60.00
    }
  ]
}
```

#### `POST /pedidos/pagar`
Registra un pago para un pedido.

**Controller**: `PedidoController::pagar`  
**Parámetros**:
- `id_pedido` (int): ID del pedido
- `metodo` (string): Método de pago
- `monto` (float): Monto del pago

---

### Productos

#### `GET /productos`
Lista de productos.

**Controller**: `ProductoController::index`  
**Vista**: `views/productos/index.php`

#### `POST /productos/procesar`
Crea, actualiza o elimina un producto.

**Controller**: `ProductoController::procesar`  
**Parámetros**:
- `accion` (string): 'crear', 'editar', 'eliminar'
- `id_producto` (int, para editar/eliminar)
- `nombre` (string)
- `precio_costo` (float)
- `precio_venta` (float)
- `id_categoria` (int)
- `stock` (int)

---

### Empleados

#### `GET /empleados`
Lista de empleados.

**Controller**: `EmpleadoController::index`  
**Vista**: `views/empleados/index.php`

#### `GET /empleados/get?id={id}`
Obtiene datos de un empleado (AJAX).

**Controller**: `EmpleadoController::getEmpleado`  
**Respuesta**: JSON con datos del empleado

#### `POST /empleados/update`
Actualiza un empleado.

**Controller**: `EmpleadoController::updateEmpleado`

#### `POST /empleados/delete`
Elimina un empleado.

**Controller**: `EmpleadoController::deleteEmpleado`

---

### Caja

#### `GET /caja/apertura`
Formulario de apertura de caja.

**Controller**: `CajaController::apertura`  
**Vista**: `views/caja/apertura.php`

#### `POST /caja/apertura`
Registra apertura de caja.

**Parámetros**:
- `monto` (float): Monto inicial

#### `GET /caja/cierre`
Formulario de cierre de caja.

**Controller**: `CajaController::cierre`  
**Vista**: `views/caja/cierre.php`

---

### Movimientos

#### `GET /movimientos`
Lista de movimientos de caja.

**Controller**: `MovimientoController::index`  
**Vista**: `views/movimientos/index.php`

#### `POST /movimientos/registrar`
Registra un movimiento de caja.

**Controller**: `MovimientoController::registrar`  
**Parámetros**:
- `tipo` (string): 'Ingreso' o 'Egreso'
- `monto` (float): Monto del movimiento
- `descripcion` (string): Descripción

---

### Reportes

#### `GET /reportes`
Índice de reportes.

**Controller**: `ReporteController::index`  
**Vista**: `views/reportes/index.php`

#### `GET /reportes/ventas_dia?fecha={fecha}`
Reporte de ventas por empleado.

**Controller**: `ReporteController::ventas_dia`  
**Parámetros**:
- `fecha` (string, opcional): Fecha en formato YYYY-MM-DD

**Vista**: `views/reportes/ventas_dia.php`

#### `GET /reportes/productos_vendidos?fecha={fecha}`
Reporte de productos vendidos.

**Controller**: `ReporteController::productos_vendidos`  
**Parámetros**:
- `fecha` (string, opcional): Fecha en formato YYYY-MM-DD

**Vista**: `views/reportes/productos_vendidos.php`

#### `GET /reportes/cierre_caja?fecha={fecha}`
Reporte de cierre de caja.

**Controller**: `ReporteController::cierre_caja`  
**Parámetros**:
- `fecha` (string, opcional): Fecha en formato YYYY-MM-DD

**Vista**: `views/reportes/cierre_caja.php`

**Datos incluidos**:
- Ventas del día con desglose de pagos
- Pedidos del día con estado de pago
- Movimientos de caja
- Efectivo a entregar

#### `GET /reportes/cierre_caja_export?fecha={fecha}`
Exporta el cierre de caja como HTML.

**Controller**: `ReporteController::cierre_caja_export`  
**Respuesta**: Archivo HTML descargable

#### `GET /reportes/inventario`
Reporte de inventario actual.

**Controller**: `ReporteController::inventario`  
**Vista**: `views/reportes/inventario.php`

#### `GET /reportes/inventario?exportar=excel`
Exporta inventario a Excel.

**Respuesta**: Archivo XLS descargable

#### `POST /reportes/imprimir_ticket_productos_vendidos`
Imprime ticket de productos vendidos.

**Controller**: `ReporteController::imprimir_ticket_productos_vendidos`  
**Parámetros**:
- `fecha` (string): Fecha del reporte

---

### Configuración

#### `GET /configuracion`
Página de configuración del sistema.

**Controller**: `ConfigController::index`  
**Vista**: `views/config/index.php`

#### `POST /configuracion/update`
Actualiza la configuración.

**Controller**: `ConfigController::update`  
**Parámetros**:
- `nombre_app` (string)
- `moneda` (string)
- `servicio` (float)
- `IVA` (float)
- `impresora_ticket` (string)
- `impresora_cocina` (string)
- `impresora_barra` (string)
- `usar_impresora_cocina` (int)
- `usar_impresora_barra` (int)

#### `GET /configuracion/buscarImpresoras`
Busca impresoras disponibles (AJAX).

**Controller**: `ConfigController::buscarImpresoras`  
**Respuesta**: JSON con lista de impresoras

```json
{
  "success": true,
  "impresoras": [
    "POSPrinter POS-80",
    "Microsoft Print to PDF"
  ]
}
```

#### `POST /configuracion/probarImpresora`
Prueba una impresora.

**Controller**: `ConfigController::probarImpresora`  
**Parámetros**:
- `nombre_impresora` (string): Nombre de la impresora

#### `POST /configuracion/backup`
Genera backup de la base de datos.

**Controller**: `ConfigController::backup`  
**Respuesta**: Archivo SQL descargable

---

### Perfil de Usuario

#### `GET /user/perfil`
Página de perfil del usuario.

**Controller**: `UserController::perfil`  
**Vista**: `views/perfil.php`

#### `POST /user/update`
Actualiza el perfil del usuario.

**Controller**: `UserController::actualizarPerfil`  
**Parámetros**:
- `nombre_completo` (string)
- `correo` (string)
- `telefono` (string)
- `password_actual` (string, opcional)
- `password_nuevo` (string, opcional)

---

### API CSRF

#### `GET /api/csrf_token`
Obtiene un token CSRF para formularios.

**Respuesta**: JSON
```json
{
  "token": "abc123def456..."
}
```

---

## Códigos de Respuesta HTTP

- `200 OK`: Operación exitosa
- `302 Found`: Redirección
- `400 Bad Request`: Parámetros inválidos
- `401 Unauthorized`: No autenticado
- `403 Forbidden`: Sin permisos
- `404 Not Found`: Recurso no encontrado
- `500 Internal Server Error`: Error del servidor

## Formato de Respuestas JSON

### Éxito
```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": { }
}
```

### Error
```json
{
  "success": false,
  "error": "Descripción del error"
}
```

## Autenticación

El sistema utiliza sesiones PHP para autenticación.

### Verificar Sesión
```php
Session::isLoggedIn(); // true/false
```

### Obtener Usuario Actual
```php
$userId = Session::get('user_id');
$username = Session::get('username');
$role = Session::get('role');
```

### Cerrar Sesión
```php
Session::destroy();
```

## Seguridad

### CSRF Protection
Todos los formularios deben incluir un token CSRF:

```php
<?php require_once 'helpers/Csrf.php'; ?>
<input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">
```

Validar en el controlador:
```php
if (!Csrf::validateToken($_POST['csrf_token'])) {
    die('Token CSRF inválido');
}
```

### Validación de Entrada
```php
require_once 'helpers/Validator.php';

$validator = new Validator();
$validator->required('campo', $_POST['campo']);
$validator->email('email', $_POST['email']);
$validator->numeric('monto', $_POST['monto']);

if (!$validator->isValid()) {
    $errors = $validator->getErrors();
}
```

## Ejemplos de Uso

### Crear una Venta con AJAX

```javascript
fetch('/comanda/crear', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        id_mesa: 1,
        id_cliente: 1,
        id_empleado: 1
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Venta creada:', data.id_venta);
    }
});
```

### Agregar Productos a Comanda

```javascript
fetch('/comanda/agregarProductos', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        id_venta: 123,
        productos: [
            {
                id_producto: 5,
                cantidad: 2,
                precio: 60.00,
                preparacion: 'Sin cebolla'
            }
        ]
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Productos agregados');
    }
});
```

### Registrar Pago

```javascript
fetch('/ventas/registrarPago', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        id_venta: 123,
        pagos: [
            { metodo: 'Efectivo', monto: 500 },
            { metodo: 'Tarjeta', monto: 200 }
        ]
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Pago registrado');
    }
});
```
