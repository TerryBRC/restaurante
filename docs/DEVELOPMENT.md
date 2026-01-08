# Guía de Desarrollo

## Configuración del Entorno de Desarrollo

### Requisitos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Composer (opcional, para dependencias futuras)
- Editor de código (VS Code recomendado)
- Git para control de versiones

### Extensiones PHP Requeridas
```bash
php -m | grep -E 'pdo|pdo_mysql|mbstring|json'
```

Deben estar habilitadas:
- `pdo`
- `pdo_mysql`
- `mbstring`
- `json`

### Configuración de VS Code

Extensiones recomendadas:
- PHP Intelephense
- PHP Debug
- MySQL (cweijan.vscode-mysql-client2)
- GitLens

## Estructura del Proyecto

### Arquitectura MVC

```
Petición HTTP
    ↓
index.php (Front Controller)
    ↓
Router (config/Router.php)
    ↓
Controller (controllers/)
    ↓
Model (models/) ←→ Database
    ↓
View (views/)
    ↓
Respuesta HTML/JSON
```

### Flujo de una Petición

1. **index.php**: Punto de entrada
   - Inicializa sesión
   - Carga autoloader
   - Crea instancia del Router
   - Carga rutas desde `config/routes.php`
   - Despacha la petición

2. **Router**: Enrutamiento
   - Parsea la URL
   - Busca la ruta correspondiente
   - Instancia el controlador
   - Ejecuta el método

3. **Controller**: Lógica de aplicación
   - Valida entrada
   - Interactúa con modelos
   - Prepara datos
   - Renderiza vista

4. **Model**: Acceso a datos
   - Conexión a BD
   - Consultas SQL
   - Validación de datos
   - Retorna resultados

5. **View**: Presentación
   - Recibe datos del controlador
   - Genera HTML
   - Incluye componentes compartidos

## Crear Nuevos Módulos

### 1. Crear el Modelo

```php
<?php
// models/EjemploModel.php
require_once __DIR__ . '/../config/database.php';

class EjemploModel {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Obtiene todos los registros
     * @return array
     */
    public function getAll() {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM tabla ORDER BY id DESC');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('EjemploModel::getAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene un registro por ID
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM tabla WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('EjemploModel::getById error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crea un nuevo registro
     * @param array $data
     * @return int|false ID del registro creado o false
     */
    public function create($data) {
        try {
            $stmt = $this->conn->prepare(
                'INSERT INTO tabla (campo1, campo2) VALUES (?, ?)'
            );
            $stmt->execute([
                $data['campo1'],
                $data['campo2']
            ]);
            return (int)$this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log('EjemploModel::create error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza un registro
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        try {
            $stmt = $this->conn->prepare(
                'UPDATE tabla SET campo1 = ?, campo2 = ? WHERE id = ?'
            );
            return $stmt->execute([
                $data['campo1'],
                $data['campo2'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log('EjemploModel::update error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un registro
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        try {
            $stmt = $this->conn->prepare('DELETE FROM tabla WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log('EjemploModel::delete error: ' . $e->getMessage());
            return false;
        }
    }
}
```

### 2. Crear el Controlador

```php
<?php
// controllers/EjemploController.php
require_once 'BaseController.php';
require_once __DIR__ . '/../models/EjemploModel.php';

class EjemploController extends BaseController {
    
    /**
     * Lista todos los registros
     */
    public function index() {
        $model = new EjemploModel();
        $registros = $model->getAll();
        
        $this->render('views/ejemplo/index.php', compact('registros'));
    }
    
    /**
     * Muestra el formulario de creación
     */
    public function crear() {
        $this->render('views/ejemplo/crear.php');
    }
    
    /**
     * Procesa la creación de un registro
     */
    public function guardar() {
        // Validar CSRF
        require_once __DIR__ . '/../helpers/Csrf.php';
        if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
            die('Token CSRF inválido');
        }
        
        // Validar datos
        require_once __DIR__ . '/../helpers/Validator.php';
        $validator = new Validator();
        $validator->required('campo1', $_POST['campo1'] ?? '');
        $validator->required('campo2', $_POST['campo2'] ?? '');
        
        if (!$validator->isValid()) {
            Session::set('flash_error', 'Datos inválidos');
            header('Location: ' . BASE_URL . 'ejemplo/crear');
            exit;
        }
        
        // Guardar
        $model = new EjemploModel();
        $id = $model->create([
            'campo1' => $_POST['campo1'],
            'campo2' => $_POST['campo2']
        ]);
        
        if ($id) {
            Session::set('flash_success', 'Registro creado exitosamente');
            header('Location: ' . BASE_URL . 'ejemplo');
        } else {
            Session::set('flash_error', 'Error al crear el registro');
            header('Location: ' . BASE_URL . 'ejemplo/crear');
        }
        exit;
    }
    
    /**
     * Muestra el formulario de edición
     */
    public function editar() {
        $id = $_GET['id'] ?? 0;
        $model = new EjemploModel();
        $registro = $model->getById($id);
        
        if (!$registro) {
            Session::set('flash_error', 'Registro no encontrado');
            header('Location: ' . BASE_URL . 'ejemplo');
            exit;
        }
        
        $this->render('views/ejemplo/editar.php', compact('registro'));
    }
    
    /**
     * Procesa la actualización
     */
    public function actualizar() {
        // Similar a guardar() pero llamando a update()
    }
    
    /**
     * Elimina un registro
     */
    public function eliminar() {
        $id = $_POST['id'] ?? 0;
        $model = new EjemploModel();
        
        if ($model->delete($id)) {
            Session::set('flash_success', 'Registro eliminado');
        } else {
            Session::set('flash_error', 'Error al eliminar');
        }
        
        header('Location: ' . BASE_URL . 'ejemplo');
        exit;
    }
}
```

### 3. Crear las Vistas

```php
<?php
// views/ejemplo/index.php
require_once dirname(__DIR__, 2) . '/config/base_url.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ejemplos</title>
    <link href="<?= BASE_URL ?>assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include dirname(__DIR__) . '/shared/navbar.php'; ?>
    
    <div class="container py-4">
        <h2>Lista de Ejemplos</h2>
        
        <?php if (Session::has('flash_success')): ?>
            <div class="alert alert-success">
                <?= Session::get('flash_success') ?>
                <?php Session::delete('flash_success'); ?>
            </div>
        <?php endif; ?>
        
        <a href="<?= BASE_URL ?>ejemplo/crear" class="btn btn-primary mb-3">
            Nuevo Registro
        </a>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Campo 1</th>
                    <th>Campo 2</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $reg): ?>
                <tr>
                    <td><?= $reg['id'] ?></td>
                    <td><?= htmlspecialchars($reg['campo1']) ?></td>
                    <td><?= htmlspecialchars($reg['campo2']) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>ejemplo/editar?id=<?= $reg['id'] ?>" 
                           class="btn btn-sm btn-warning">Editar</a>
                        <form method="POST" action="<?= BASE_URL ?>ejemplo/eliminar" 
                              style="display:inline;">
                            <input type="hidden" name="id" value="<?= $reg['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('¿Eliminar?')">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

### 4. Registrar las Rutas

```php
// En config/routes.php
$router->add('ejemplo', 'EjemploController', 'index');
$router->add('ejemplo/crear', 'EjemploController', 'crear');
$router->add('ejemplo/guardar', 'EjemploController', 'guardar');
$router->add('ejemplo/editar', 'EjemploController', 'editar');
$router->add('ejemplo/actualizar', 'EjemploController', 'actualizar');
$router->add('ejemplo/eliminar', 'EjemploController', 'eliminar');
```

## Trabajar con AJAX

### Controlador AJAX

```php
<?php
// controllers/EjemploAjaxController.php
require_once 'BaseController.php';
require_once __DIR__ . '/../models/EjemploModel.php';

class EjemploAjaxController extends BaseController {
    
    public function listar() {
        header('Content-Type: application/json');
        
        $model = new EjemploModel();
        $registros = $model->getAll();
        
        echo json_encode([
            'success' => true,
            'data' => $registros
        ]);
        exit;
    }
    
    public function crear() {
        header('Content-Type: application/json');
        
        // Leer JSON del body
        $input = json_decode(file_get_contents('php://input'), true);
        
        $model = new EjemploModel();
        $id = $model->create($input);
        
        if ($id) {
            echo json_encode([
                'success' => true,
                'id' => $id,
                'message' => 'Creado exitosamente'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al crear'
            ]);
        }
        exit;
    }
}
```

### Cliente JavaScript

```javascript
// Listar registros
fetch('/ejemplo/ajax/listar')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log(data.data);
        }
    });

// Crear registro
fetch('/ejemplo/ajax/crear', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        campo1: 'valor1',
        campo2: 'valor2'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Creado con ID:', data.id);
    }
});
```

## Buenas Prácticas

### Seguridad

1. **Siempre usar Prepared Statements**
```php
// ❌ MAL
$sql = "SELECT * FROM users WHERE id = " . $_GET['id'];

// ✅ BIEN
$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_GET['id']]);
```

2. **Validar y Sanitizar Entrada**
```php
$nombre = htmlspecialchars($_POST['nombre']);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
```

3. **Usar CSRF Tokens**
```php
// En el formulario
<?php require_once 'helpers/Csrf.php'; ?>
<input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">

// En el controlador
if (!Csrf::validateToken($_POST['csrf_token'])) {
    die('Token inválido');
}
```

4. **Hashear Contraseñas**
```php
// Crear
$hash = password_hash($password, PASSWORD_BCRYPT);

// Verificar
if (password_verify($password, $hash)) {
    // Contraseña correcta
}
```

### Código Limpio

1. **Nombres Descriptivos**
```php
// ❌ MAL
function get($id) { }

// ✅ BIEN
function getProductById($id) { }
```

2. **Comentarios Útiles**
```php
/**
 * Calcula el total de una venta incluyendo servicio
 * @param float $subtotal Subtotal de productos
 * @param float $porcentajeServicio Porcentaje de servicio (0.05 = 5%)
 * @return float Total con servicio incluido
 */
function calcularTotalConServicio($subtotal, $porcentajeServicio) {
    return $subtotal * (1 + $porcentajeServicio);
}
```

3. **Manejo de Errores**
```php
try {
    $result = $model->create($data);
    if (!$result) {
        throw new Exception('Error al crear registro');
    }
} catch (Exception $e) {
    error_log('Error en crear: ' . $e->getMessage());
    Session::set('flash_error', 'Ocurrió un error');
}
```

### Base de Datos

1. **Usar Transacciones**
```php
try {
    $conn->beginTransaction();
    
    // Múltiples operaciones
    $stmt1->execute();
    $stmt2->execute();
    
    $conn->commit();
} catch (Exception $e) {
    $conn->rollBack();
    throw $e;
}
```

2. **Índices Apropiados**
```sql
-- Para búsquedas frecuentes
CREATE INDEX idx_fecha ON ventas(Fecha_Hora);
CREATE INDEX idx_estado ON mesas(Estado);
```

3. **Evitar SELECT ***
```php
// ❌ MAL
SELECT * FROM productos

// ✅ BIEN
SELECT ID_Producto, Nombre_Producto, Precio_Venta FROM productos
```

## Debugging

### Logs de Error

```php
// Habilitar logs en desarrollo
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Registrar errores
error_log('Mensaje de debug: ' . print_r($variable, true));
```

### Debugging de SQL

```php
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    error_log('SQL Error: ' . $e->getMessage());
    error_log('SQL: ' . $sql);
    error_log('Params: ' . print_r($params, true));
}
```

### Debugging de Sesiones

```php
// Ver contenido de sesión
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
```

## Testing

### Testing Manual

1. Probar cada ruta con diferentes parámetros
2. Verificar validaciones de formularios
3. Probar con datos inválidos
4. Verificar permisos por rol
5. Probar en diferentes navegadores

### Checklist de Testing

- [ ] Formularios validan correctamente
- [ ] Mensajes de error son claros
- [ ] Redirecciones funcionan
- [ ] CSRF tokens están presentes
- [ ] SQL injection protegido
- [ ] XSS protegido
- [ ] Sesiones funcionan correctamente
- [ ] Permisos por rol funcionan

## Deployment

### Preparación para Producción

1. **Deshabilitar errores en pantalla**
```php
// config/config.php
ini_set('display_errors', 0);
error_reporting(0);
```

2. **Configurar logs**
```php
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/logs/php-errors.log');
```

3. **Optimizar base de datos**
```sql
OPTIMIZE TABLE ventas;
ANALYZE TABLE productos;
```

4. **Backup antes de deploy**
```bash
mysqldump -u root -p rest_bar > backup_pre_deploy.sql
```

### Checklist de Deployment

- [ ] Backup de base de datos
- [ ] Backup de archivos
- [ ] Configuración de producción
- [ ] Logs habilitados
- [ ] Errores deshabilitados en pantalla
- [ ] Permisos de archivos correctos
- [ ] .htaccess configurado
- [ ] Pruebas en staging
- [ ] Documentación actualizada

## Recursos Adicionales

- [PHP Manual](https://www.php.net/manual/es/)
- [PDO Documentation](https://www.php.net/manual/es/book.pdo.php)
- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.0/)
- [MySQL Reference](https://dev.mysql.com/doc/)
