<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'rest_bar');
define('DB_USER', 'root');
define('DB_PASS', 'root123');

// Configuración de la aplicación
define('APP_NAME', 'RestBar');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/restaurante');

// Configuración de sesión
define('SESSION_LIFETIME', 3600); // 1 hora en segundos
define('SESSION_NAME', 'RESTBAR_SESSION');

// Roles de usuario
define('ROL_ADMIN', 'Administrador');
define('ROL_MESERO', 'Mesero');
define('ROL_CAJERO', 'Cajero');

// Estados de mesas
define('MESA_LIBRE', 0);
define('MESA_OCUPADA', 1);

// Estados de ventas
define('VENTA_PENDIENTE', 'Pendiente');
define('VENTA_COMPLETADA', 'Completada');
define('VENTA_CANCELADA', 'Cancelada');

// Configuración de paginación
define('ITEMS_POR_PAGINA', 10);

// Configuración de zona horaria
date_default_timezone_set('America/Managua');

/* // Detectar entorno
$isProduction = ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1');
// Configuración de errores según entorno
if ($isProduction) {
    // Producción: no mostrar errores
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    // Desarrollo: mostrar errores
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} */

// Funciones de utilidad

/**
 * Redirecciona a una URL específica
 * @param string $url URL a la que se redireccionará
 */
function redirect($url) {
    header("Location: " . APP_URL . $url);
    exit();
}

/**
 * Verifica si el usuario tiene el rol requerido
 * @param string|array $roles Rol o array de roles permitidos
 * @return bool
 */
function checkRole($roles) {
    if (!isset($_SESSION['user_rol'])) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['user_rol'], $roles);
    }
    
    return $_SESSION['user_rol'] === $roles;
}

/**
 * Formatea un número como moneda
 * @param float $amount Cantidad a formatear
 * @return string
 */
function formatMoney($amount) {
    return '$' . number_format($amount, 2, '.', ',');
}

/**
 * Formatea una fecha en formato legible
 * @param string $date Fecha a formatear
 * @param bool $includeTime Incluir hora en el formato
 * @return string
 */
function formatDate($date, $includeTime = false) {
    $format = $includeTime ? 'd/m/Y H:i:s' : 'd/m/Y';
    return date($format, strtotime($date));
}

/**
 * Sanitiza una cadena de texto
 * @param string $str Cadena a sanitizar
 * @return string
 */
function sanitizeString($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

/**
 * Genera un mensaje de error o éxito
 * @param string $message Mensaje a mostrar
 * @param string $type Tipo de mensaje (success, danger, warning, info)
 * @return string
 */
function generateAlert($message, $type = 'success') {
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
}

/**
 * Verifica si una solicitud es AJAX
 * @return bool
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Envía una respuesta JSON
 * @param array $data Datos a enviar
 * @param int $statusCode Código de estado HTTP
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}