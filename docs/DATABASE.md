# Documentación de la Base de Datos

## Diagrama de Relaciones

```
usuarios ──┬── empleados
           │
roles ─────┘

categorias ──── productos

clientes ──┬── ventas ──┬── detalle_venta ──── productos
           │            ├── pagos
           │            └── parciales_venta
           │
           └── pedidos ──┬── pedido_detalles ──── productos
                         └── pagos_pedido

mesas ──┬── ventas
        └── liberaciones_mesa ──── usuarios

movimientos ──┬── usuarios
              └── ventas

config (tabla de configuración)
```

## Tablas Detalladas

### Usuarios y Autenticación

#### `usuarios`
Almacena las credenciales de acceso al sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_usuario | INT (PK) | Identificador único |
| Nombre_Usuario | VARCHAR(30) | Nombre de usuario (único) |
| Contrasenia | VARCHAR(255) | Hash bcrypt de la contraseña |
| ID_Rol | INT (FK) | Referencia a roles |
| Estado | TINYINT(1) | 1=Activo, 0=Inactivo |

#### `roles`
Define los roles del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Rol | INT (PK) | Identificador único |
| Nombre_Rol | VARCHAR(50) | Administrador, Mesero, Cajero |

**Roles predefinidos:**
- 1: Administrador (acceso completo)
- 2: Mesero (ventas y mesas)
- 3: Cajero (pagos y reportes)

#### `empleados`
Información personal de los empleados.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Empleado | INT (PK) | Identificador único |
| Nombre_Completo | VARCHAR(100) | Nombre completo |
| Correo | VARCHAR(100) | Email (único) |
| Telefono | VARCHAR(15) | Teléfono de contacto |
| Fecha_Contratacion | DATE | Fecha de ingreso |
| ID_Usuario | INT (FK) | Referencia a usuarios |

### Productos y Categorías

#### `categorias`
Clasificación de productos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Categoria | INT (PK) | Identificador único |
| Nombre_Categoria | VARCHAR(50) | Nombre de la categoría |
| is_food | TINYINT(1) | 1=Cocina, 0=Barra |

**Uso de `is_food`:**
- `is_food = 1`: Productos que se preparan en cocina
- `is_food = 0`: Bebidas que se preparan en barra

#### `productos`
Catálogo de productos disponibles.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Producto | INT (PK) | Identificador único |
| Nombre_Producto | VARCHAR(100) | Nombre del producto |
| Precio_Costo | DECIMAL(10,2) | Costo de adquisición |
| Precio_Venta | DECIMAL(10,2) | Precio de venta |
| ID_Categoria | INT (FK) | Referencia a categorías |
| Stock | INT | Cantidad disponible |

### Ventas

#### `ventas`
Cabecera de las ventas realizadas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Venta | INT (PK) | Identificador único |
| ID_Cliente | INT (FK) | Referencia a clientes |
| ID_Mesa | INT (FK) | Mesa asignada (NULL para delivery) |
| Fecha_Hora | DATETIME | Fecha y hora de la venta |
| Total | DECIMAL(10,2) | Total sin servicio |
| Metodo_Pago | VARCHAR(200) | Métodos de pago (legacy) |
| ID_Empleado | INT (FK) | Empleado que atendió |
| Estado | VARCHAR(20) | Pendiente, Pagada |
| Servicio | DECIMAL(10,2) | Monto del servicio |

**Estados:**
- `Pendiente`: Venta creada, sin pagar
- `Pagada`: Venta completada

#### `detalle_venta`
Productos incluidos en cada venta.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Detalle | INT (PK) | Identificador único |
| ID_Venta | INT (FK) | Referencia a ventas |
| ID_Producto | INT (FK) | Producto vendido |
| Precio_Venta | DECIMAL(10,2) | Precio unitario |
| Cantidad | INT | Cantidad vendida |
| Subtotal | DECIMAL(10,2) | Cantidad × Precio |
| ID_Parcial | INT (FK) | Para división de cuenta |
| Preparacion | TEXT | Notas especiales |

#### `pagos`
Desglose de pagos por venta (sistema nuevo).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Pago | INT (PK) | Identificador único |
| ID_Venta | INT (FK) | Referencia a ventas |
| Metodo | VARCHAR(255) | Efectivo, Tarjeta, Transferencia |
| Monto | DECIMAL(12,2) | Monto pagado |
| Es_Cambio | TINYINT(1) | 1=Es cambio devuelto, 0=Pago |
| Fecha_Hora | DATETIME | Momento del pago |

**Importante:** 
- Los pagos con `Es_Cambio = 1` representan dinero devuelto al cliente
- Se restan del efectivo a entregar en el cierre de caja

#### `parciales_venta`
División de cuentas (funcionalidad deshabilitada).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Parcial | INT (PK) | Identificador único |
| ID_Venta | INT (FK) | Referencia a ventas |
| nombre_cliente | VARCHAR(100) | Nombre del comensal |
| pagado | TINYINT(1) | Estado de pago |

### Pedidos

#### `pedidos`
Cabecera de pedidos (delivery/local).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Pedido | INT (PK) | Identificador único |
| tipo_entrega | VARCHAR(20) | local, delivery |
| nombre_cliente | VARCHAR(100) | Nombre del cliente |
| telefono | VARCHAR(20) | Teléfono de contacto |
| direccion | TEXT | Dirección de entrega |
| notas | TEXT | Notas adicionales |
| fecha_creado | DATETIME | Fecha de creación |

#### `pedido_detalles`
Productos incluidos en cada pedido.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Detalle | INT (PK) | Identificador único |
| ID_Pedido | INT (FK) | Referencia a pedidos |
| ID_Producto | INT (FK) | Producto pedido |
| Cantidad | INT | Cantidad solicitada |
| Precio_Unitario | DECIMAL(10,2) | Precio unitario |
| Subtotal | DECIMAL(10,2) | Cantidad × Precio |
| preparacion | TEXT | Notas de preparación |

#### `pagos_pedido`
Pagos realizados para pedidos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Pago | INT (PK) | Identificador único |
| ID_Pedido | INT (FK) | Referencia a pedidos |
| Metodo | VARCHAR(50) | Método de pago |
| Monto | DECIMAL(10,2) | Monto pagado |
| Es_Cambio | TINYINT(1) | 1=Cambio, 0=Pago |
| Fecha_Hora | DATETIME | Momento del pago |

### Mesas y Operaciones

#### `mesas`
Estado de las mesas del restaurante.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Mesa | INT (PK) | Identificador único |
| Numero_Mesa | INT | Número visible de la mesa |
| Capacidad | INT | Número de comensales |
| Estado | TINYINT(1) | 0=Libre, 1=Ocupada |

#### `liberaciones_mesa`
Historial de liberaciones manuales de mesas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Liberacion | INT (PK) | Identificador único |
| ID_Mesa | INT (FK) | Mesa liberada |
| ID_Usuario | INT (FK) | Usuario que liberó |
| Motivo | VARCHAR(100) | Razón de liberación |
| Descripcion | TEXT | Detalles adicionales |
| Fecha_Hora | DATETIME | Momento de liberación |

#### `clientes`
Registro de clientes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Cliente | INT (PK) | Identificador único |
| Nombre_Cliente | VARCHAR(100) | Nombre del cliente |
| RUC | VARCHAR(100) | RUC/NIT (opcional) |
| Telefono | VARCHAR(15) | Teléfono de contacto |

**Clientes predefinidos:**
- ID 1: "C/F" (Consumidor Final)
- ID 2: "Pedidos YA" (Plataforma delivery)

### Movimientos de Caja

#### `movimientos`
Registro de todos los movimientos de caja.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| ID_Movimiento | INT (PK) | Identificador único |
| Fecha_Hora | DATETIME | Momento del movimiento |
| Tipo | VARCHAR(30) | Apertura, Ingreso, Egreso, Cortesia, Traslado |
| Monto | DECIMAL(10,2) | Monto del movimiento |
| Descripcion | VARCHAR(255) | Descripción del movimiento |
| ID_Usuario | INT (FK) | Usuario responsable |
| ID_Venta | INT (FK) | Venta relacionada (si aplica) |

**Tipos de movimientos:**
- `Apertura`: Monto inicial de caja
- `Ingreso`: Pagos de ventas, ingresos manuales
- `Egreso`: Gastos, retiros
- `Cortesia`: Descuentos o cortesías
- `Traslado`: Traslado de venta entre mesas (monto 0)

### Configuración

#### `config`
Configuración del sistema (clave-valor).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT (PK) | Identificador único |
| clave | VARCHAR(50) | Nombre de la configuración (único) |
| valor | VARCHAR(255) | Valor de la configuración |

**Configuraciones principales:**
- `nombre_app`: Nombre del restaurante
- `moneda`: Símbolo de moneda (C$)
- `servicio`: Porcentaje de servicio (0.05 = 5%)
- `IVA`: Porcentaje de IVA
- `impresora_ticket`: Nombre de impresora de tickets
- `impresora_cocina`: Nombre de impresora de cocina
- `impresora_barra`: Nombre de impresora de barra
- `usar_impresora_cocina`: 1=Activado, 0=Desactivado
- `usar_impresora_barra`: 1=Activado, 0=Desactivado

## Vistas de Base de Datos

### `pedidos_view`
Vista consolidada de pedidos con totales calculados.

```sql
CREATE VIEW pedidos_view AS
SELECT 
    p.*,
    SUM(pd.Subtotal) as total_pedido,
    COUNT(pd.ID_Detalle) as num_items
FROM pedidos p
LEFT JOIN pedido_detalles pd ON p.ID_Pedido = pd.ID_Pedido
GROUP BY p.ID_Pedido;
```

## Procedimientos Almacenados

### Productos
- `sp_AddProduct`: Agregar nuevo producto
- `sp_GetAllProducts`: Listar todos los productos

### Ventas
- `sp_CreateSale`: Crear nueva venta
- `sp_AddSaleDetail`: Agregar producto a venta
- `sp_GetDailySales`: Obtener ventas del día

### Usuarios
- `sp_CreateUserWithEmployee`: Crear usuario con empleado
- `sp_CheckUserExists`: Verificar si usuario existe
- `sp_DeleteEmployee`: Eliminar empleado y usuario

### Otros
- `sp_GetAllCategories`: Listar categorías
- `sp_GetAllTables`: Listar mesas
- `sp_GetAllRols`: Listar roles
- `sp_GetAllEmployees`: Listar empleados
- `sp_GetDashboardStats`: Estadísticas del dashboard

## Índices Importantes

### Índices de Rendimiento
```sql
-- Ventas por fecha
CREATE INDEX idx_ventas_fecha ON ventas(Fecha_Hora);

-- Pagos por venta
CREATE INDEX idx_pago_venta ON pagos(ID_Venta);

-- Detalles por venta
CREATE INDEX idx_detalle_venta ON detalle_venta(ID_Venta);

-- Productos por categoría
CREATE INDEX idx_producto_categoria ON productos(ID_Categoria);
```

## Relaciones y Restricciones

### Claves Foráneas Principales

```sql
-- Empleados → Usuarios
ALTER TABLE empleados 
ADD CONSTRAINT empleados_ibfk_1 
FOREIGN KEY (ID_Usuario) REFERENCES usuarios(ID_usuario);

-- Ventas → Clientes, Mesas, Empleados
ALTER TABLE ventas 
ADD CONSTRAINT ventas_ibfk_1 
FOREIGN KEY (ID_Cliente) REFERENCES clientes(ID_Cliente);

ALTER TABLE ventas 
ADD CONSTRAINT ventas_ibfk_2 
FOREIGN KEY (ID_Mesa) REFERENCES mesas(ID_Mesa);

ALTER TABLE ventas 
ADD CONSTRAINT ventas_ibfk_3 
FOREIGN KEY (ID_Empleado) REFERENCES empleados(ID_Empleado);

-- Pagos → Ventas (con CASCADE)
ALTER TABLE pagos 
ADD CONSTRAINT fk_pagos_venta 
FOREIGN KEY (ID_Venta) REFERENCES ventas(ID_Venta) 
ON DELETE CASCADE;
```

## Migraciones

Las migraciones se encuentran en la carpeta `backups/`:

- `20251016_add_is_food_to_categorias.sql`: Agregar campo `is_food`
- `20251019_create_table_pagos.sql`: Crear tabla de pagos
- `20251021_create_table_pagos_pedido.sql`: Crear tabla de pagos de pedidos

## Respaldos

### Archivos de Respaldo
- `rest_barDumb.sql`: Respaldo completo con datos
- `rest_barDumb05102025.sql`: Respaldo con pedidos
- `equeleto.sql`: Esquema sin datos

### Crear Respaldo
```bash
mysqldump -u root -p rest_bar > backup_$(date +%Y%m%d).sql
```

### Restaurar Respaldo
```bash
mysql -u root -p rest_bar < backup_20260107.sql
```

## Consideraciones de Diseño

### Normalización
- Base de datos normalizada en 3FN
- Evita redundancia de datos
- Facilita mantenimiento

### Integridad Referencial
- Uso de claves foráneas
- Restricciones CASCADE donde apropiado
- Validaciones a nivel de BD

### Auditoría
- Campos de fecha/hora en operaciones
- Registro de usuario en movimientos
- Historial de liberaciones de mesa

### Escalabilidad
- Índices en campos de búsqueda frecuente
- Vistas para consultas complejas
- Procedimientos almacenados para operaciones comunes

## Consultas Útiles

### Ventas del Día
```sql
SELECT v.*, e.Nombre_Completo, c.Nombre_Cliente
FROM ventas v
JOIN empleados e ON v.ID_Empleado = e.ID_Empleado
JOIN clientes c ON v.ID_Cliente = c.ID_Cliente
WHERE DATE(v.Fecha_Hora) = CURDATE();
```

### Productos Más Vendidos
```sql
SELECT p.Nombre_Producto, SUM(dv.Cantidad) as total_vendido
FROM detalle_venta dv
JOIN productos p ON dv.ID_Producto = p.ID_Producto
JOIN ventas v ON dv.ID_Venta = v.ID_Venta
WHERE DATE(v.Fecha_Hora) = CURDATE()
GROUP BY p.ID_Producto
ORDER BY total_vendido DESC;
```

### Cierre de Caja
```sql
SELECT 
    SUM(CASE WHEN Tipo = 'Apertura' THEN Monto ELSE 0 END) as apertura,
    SUM(CASE WHEN Tipo = 'Ingreso' THEN Monto ELSE 0 END) as ingresos,
    SUM(CASE WHEN Tipo = 'Egreso' THEN Monto ELSE 0 END) as egresos
FROM movimientos
WHERE DATE(Fecha_Hora) = CURDATE();
```
