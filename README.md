# Sistema de Gestión de Restaurante

Sistema completo de punto de venta (POS) para restaurantes y bares desarrollado en PHP con arquitectura MVC.

## 📋 Descripción

Sistema integral para la gestión de restaurantes que incluye:
- Gestión de mesas y comandas
- Sistema de ventas y facturación
- Control de inventario
- Gestión de pedidos (delivery/local)
- Reportes y cierre de caja
- Impresión de tickets térmicos
- Control de usuarios y roles

## 🚀 Características Principales

### Gestión de Ventas
- Creación de ventas por mesa
- Múltiples métodos de pago (Efectivo, Tarjeta, Transferencia)
- Cálculo automático de servicio
- División de cuentas
- Traslado de ventas entre mesas

### Gestión de Pedidos
- Pedidos para delivery y consumo local
- Seguimiento de estado de pedidos
- Registro de pagos parciales
- Impresión de tickets de pedidos

### Control de Mesas
- Visualización del estado de mesas (libre/ocupada)
- Asignación de comandas a mesas
- Liberación de mesas con registro de motivo
- Traslado de ventas entre mesas

### Comandas y Cocina
- Separación de productos por cocina/barra
- Impresión automática en impresoras específicas
- Control de estado de preparación
- Notas de preparación personalizadas

### Reportes
- Cierre de caja diario con desglose de pagos
- Reporte de ventas por empleado
- Productos vendidos por fecha
- Inventario actual
- Exportación de reportes a HTML

### Caja
- Apertura y cierre de caja
- Registro de movimientos (ingresos/egresos)
- Control de efectivo a entregar
- Desglose por método de pago

## 🏗️ Arquitectura

### Estructura del Proyecto

```
restaurante/
├── config/              # Configuración
│   ├── database.php     # Conexión a BD
│   ├── routes.php       # Definición de rutas
│   ├── Router.php       # Enrutador
│   ├── Session.php      # Manejo de sesiones
│   └── config.php       # Configuración general
├── controllers/         # Controladores MVC
│   ├── AuthController.php
│   ├── VentaController.php
│   ├── PedidoController.php
│   ├── MesaController.php
│   ├── ReporteController.php
│   └── ...
├── models/             # Modelos de datos
│   ├── VentaModel.php
│   ├── PedidoModel.php
│   ├── ProductModel.php
│   └── ...
├── views/              # Vistas
│   ├── mesas/
│   ├── ventas/
│   ├── pedidos/
│   ├── reportes/
│   └── shared/         # Componentes compartidos
├── helpers/            # Utilidades
│   ├── TicketHelper.php
│   ├── ImpresoraHelper.php
│   └── escpos-php/     # Librería de impresión
├── assets/             # Recursos estáticos
│   ├── css/
│   ├── js/
│   └── img/
├── backups/            # Respaldos de BD
└── index.php           # Punto de entrada
```

### Patrón MVC

**Modelo (Model)**: Interactúa con la base de datos usando PDO
- Métodos para CRUD de entidades
- Lógica de negocio
- Validaciones de datos

**Vista (View)**: Archivos PHP con HTML
- Componentes reutilizables en `views/shared/`
- Uso de Bootstrap 5 para estilos
- JavaScript para interactividad

**Controlador (Controller)**: Procesa peticiones
- Hereda de `BaseController`
- Renderiza vistas con datos
- Maneja la lógica de aplicación

## 🗄️ Base de Datos

### Tablas Principales

#### Usuarios y Roles
- `usuarios`: Credenciales y roles
- `roles`: Administrador, Mesero, Cajero
- `empleados`: Información de empleados

#### Productos
- `productos`: Catálogo de productos
- `categorias`: Clasificación (con flag `is_food` para cocina/barra)

#### Ventas
- `ventas`: Cabecera de ventas
- `detalle_venta`: Productos vendidos
- `pagos`: Desglose de pagos por venta
- `parciales_venta`: División de cuentas

#### Pedidos
- `pedidos`: Cabecera de pedidos
- `pedido_detalles`: Productos del pedido
- `pagos_pedido`: Pagos de pedidos

#### Operaciones
- `mesas`: Estado de mesas
- `movimientos`: Apertura, cierre, ingresos, egresos
- `liberaciones_mesa`: Historial de liberaciones
- `clientes`: Registro de clientes
- `config`: Configuración del sistema

### Vistas de Base de Datos
- `pedidos_view`: Vista consolidada de pedidos con totales

## ⚙️ Configuración

### Requisitos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, PDO_MySQL, mbstring

### Instalación

1. **Clonar el repositorio**
```bash
git clone <repository-url>
cd restaurante
```

2. **Configurar base de datos**
```bash
# Importar el esquema de base de datos
mysql -u root -p < backups/rest_barDumb.sql
```

3. **Configurar conexión**

Editar `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'rest_bar');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/restaurante/');
```

4. **Configurar servidor web**

Para Apache, el archivo `.htaccess` ya está configurado.

5. **Acceder al sistema**
```
URL: http://localhost/restaurante/
Usuario: superadmin
Contraseña: (ver en la BD, hash bcrypt)
```

### Configuración de Impresoras

1. Ir a **Configuración** en el menú
2. Buscar impresoras disponibles
3. Asignar impresoras para:
   - Tickets de venta
   - Comandas de cocina
   - Comandas de barra
4. Probar impresión

## 📱 Módulos del Sistema

### 1. Dashboard
- Resumen de mesas ocupadas
- Ventas del día
- Órdenes activas

### 2. Mesas
- Grid visual de mesas
- Estados: Libre (verde) / Ocupada (rojo)
- Acciones: Ver comanda, Trasladar, Liberar

### 3. Ventas
- Crear nueva venta
- Agregar productos
- Registrar pagos múltiples
- Imprimir ticket
- Historial de ventas

### 4. Pedidos
- Crear pedido (delivery/local)
- Datos del cliente
- Registro de pagos
- Impresión de ticket

### 5. Productos
- Catálogo de productos
- Gestión de stock
- Precios de costo y venta
- Categorización

### 6. Comandas
- Vista de cocina
- Vista de barra
- Actualización de estados
- Impresión automática

### 7. Reportes
- Cierre de caja
- Ventas por empleado
- Productos vendidos
- Inventario
- Exportación

### 8. Caja
- Apertura de caja
- Registro de movimientos
- Cierre de caja

### 9. Configuración
- Datos del restaurante
- Impresoras
- Porcentaje de servicio
- IVA
- Backup de base de datos

## 🔐 Sistema de Roles

### Administrador
- Acceso completo al sistema
- Gestión de usuarios
- Configuración
- Reportes avanzados

### Mesero
- Gestión de mesas
- Crear ventas
- Ver comandas

### Cajero
- Registrar pagos
- Cierre de caja
- Reportes de ventas

## 🖨️ Sistema de Impresión

Utiliza la librería **escpos-php** para impresión térmica.

### Tipos de Tickets
1. **Ticket de Venta**: Factura para el cliente
2. **Ticket de Comanda**: Para cocina/barra
3. **Ticket de Pedido**: Para pedidos delivery
4. **Ticket de Cierre**: Resumen de caja

### Configuración
- Impresoras compatibles con ESC/POS
- Conexión por nombre de impresora Windows
- Configuración independiente por tipo

## 🔄 Flujo de Trabajo

### Flujo de Venta en Mesa

1. Cliente llega → Asignar mesa
2. Mesero crea comanda
3. Agregar productos
4. Imprimir comanda en cocina/barra
5. Cocina prepara pedido
6. Cliente solicita cuenta
7. Registrar pago (uno o múltiples métodos)
8. Imprimir ticket
9. Mesa queda libre

### Flujo de Pedido Delivery

1. Recibir pedido
2. Crear pedido con datos del cliente
3. Agregar productos
4. Registrar pago (puede ser parcial)
5. Imprimir ticket
6. Preparar y entregar

### Flujo de Cierre de Caja

1. Apertura de caja (monto inicial)
2. Ventas del día
3. Registro de ingresos/egresos
4. Cierre de caja
5. Generar reporte
6. Imprimir ticket de cierre
7. Verificar efectivo a entregar

## 🛠️ Tecnologías Utilizadas

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL 8.0
- **Frontend**: 
  - HTML5, CSS3, JavaScript
  - Bootstrap 5
  - jQuery
- **Impresión**: escpos-php
- **Arquitectura**: MVC personalizado
- **Seguridad**: 
  - Sesiones PHP
  - Contraseñas con bcrypt
  - CSRF tokens
  - Prepared statements (PDO)

## 📊 Características Técnicas

### Seguridad
- Autenticación basada en sesiones
- Protección CSRF
- Validación de entrada
- Consultas preparadas (prevención SQL injection)
- Control de acceso por roles

### Rendimiento
- Uso de vistas de BD para consultas complejas
- Índices en tablas principales
- Carga lazy de datos
- Caché de sesión

### Mantenibilidad
- Código organizado en capas
- Separación de responsabilidades
- Comentarios en código
- Nombres descriptivos
- Reutilización de componentes

## 📝 Notas de Desarrollo

### Agregar Nueva Ruta
```php
// En config/routes.php
$router->add('ruta/nueva', 'ControllerName', 'methodName');
```

### Crear Nuevo Controlador
```php
<?php
require_once 'BaseController.php';

class NuevoController extends BaseController {
    public function index() {
        $this->render('views/nuevo/index.php');
    }
}
```

### Crear Nuevo Modelo
```php
<?php
require_once __DIR__ . '/../config/database.php';

class NuevoModel {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    // Métodos del modelo
}
```

## 🐛 Solución de Problemas

### Error de Conexión a BD
- Verificar credenciales en `config/config.php`
- Verificar que MySQL esté corriendo
- Verificar permisos de usuario

### Impresoras no Detectadas
- Verificar que las impresoras estén instaladas en Windows
- Verificar nombres exactos de impresoras
- Probar impresión desde configuración

### Sesión Expirada
- Verificar configuración de sesión en `php.ini`
- Aumentar `session.gc_maxlifetime`

## 📄 Licencia

Este proyecto es de uso interno para gestión de restaurantes.

## 👥 Créditos

Desarrollado para la gestión eficiente de restaurantes y bares.

---

**Versión**: 1.0  
**Última actualización**: Enero 2026
