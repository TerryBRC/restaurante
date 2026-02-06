<?php
// Helper para gestión de impresoras en Windows

// Cargar todas las dependencias de escpos-php directamente
$escposPath = __DIR__ . '/escpos-php/src/';

// Cargar interfaces y clases base primero
require_once $escposPath . 'Mike42/Escpos/PrintConnectors/PrintConnector.php';
require_once $escposPath . 'Mike42/Escpos/CodePage.php';
require_once $escposPath . 'Mike42/Escpos/PrintBuffers/PrintBuffer.php';
require_once $escposPath . 'Mike42/Escpos/PrintBuffers/EscposPrintBuffer.php';
require_once $escposPath . 'Mike42/Escpos/CapabilityProfile.php';

// Cargar conectores
require_once $escposPath . 'Mike42/Escpos/PrintConnectors/WindowsPrintConnector.php';

// Cargar Printer
require_once $escposPath . 'Mike42/Escpos/Printer.php';

class ImpresoraHelper {
    private static $logFile = null;
    
    /**
     * Inicializar archivo de log
     */
    private static function initLog() {
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        self::$logFile = $logDir . '/impresora_' . date('Ymd') . '.log';
    }
    
    /**
     * Escribir al log
     */
    private static function log($message, $level = 'INFO') {
        self::initLog();
        if (self::$logFile && is_writable(dirname(self::$logFile))) {
            $timestamp = date('Y-m-d H:i:s');
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
            $entry = "[$timestamp] [$level] [$ip] $message" . PHP_EOL;
            @file_put_contents(self::$logFile, $entry, FILE_APPEND);
        }
    }

    // Buscar impresoras instaladas en el sistema (Windows)
    public static function buscarImpresoras() {
        $impresoras = [];
        // Usar PowerShell para obtener impresoras instaladas
        @exec('powershell "Get-Printer | Select-Object -ExpandProperty Name"', $output, $resultCode);
        if ($resultCode !== 0 || empty($output)) {
            // Intentar con método alternativo
            @exec('wmic printer get name, $resultCode', $output22);
            if ($resultCode2 === 0 && !empty($output2)) {
                array_shift($output2); // Remover header
                foreach ($output2 as $line) {
                    $line = trim($line);
                    if ($line) {
                        $impresoras[] = $line;
                    }
                }
            }
            return $impresoras;
        }
        foreach ($output as $line) {
            $line = trim($line);
            if ($line) {
                $impresoras[] = $line;
            }
        }
        return $impresoras;
    }

    // Guardar impresora seleccionada en la tabla config
    public static function guardarImpresora($clave, $nombre) {
        require_once dirname(__DIR__, 1) . '/models/ConfigModel.php';
        $configModel = new ConfigModel();
        return $configModel->set($clave, $nombre);
    }

    // Obtener impresora configurada
    public static function obtenerImpresora($clave) {
        require_once dirname(__DIR__, 1) . '/models/ConfigModel.php';
        $configModel = new ConfigModel();
        return $configModel->get($clave);
    }

    /**
     * Envía texto a una impresora compatible con ESC/POS utilizando la librería escpos-php.
     *
     * @param string $clave Clave identificadora de la impresora.
     * @param string $contenido Texto que se desea imprimir.
     * @return array Retorna ['success' => bool, 'error' => string|null]
     */
    public static function imprimir($clave, $contenido) {
        $impresora = self::obtenerImpresora($clave);
        $usarClave = 'usar_' . $clave;
        require_once dirname(__DIR__, 1) . '/models/ConfigModel.php';
        $configModel = new ConfigModel();
        $usarImpresora = $configModel->get($usarClave);
        
        if ($usarImpresora === '0' || $usarImpresora === 0 || $usarImpresora === false || $usarImpresora === null) {
            self::log("Impresora '$clave' deshabilitada en configuración", 'DEBUG');
            return ['success' => false, 'error' => 'Impresora deshabilitada'];
        }
        
        if (empty($impresora)) {
            self::log("No hay impresora configurada para '$clave'", 'ERROR');
            return ['success' => false, 'error' => 'No hay impresora configurada'];
        }
        
        return self::imprimir_directo($impresora, $contenido, $clave);
    }
        
    /**
     * Imprimir directamente en una impresora por nombre
     * @param string $nombre Nombre de la impresora (local o de red)
     * @param string $contenido Contenido a imprimir
     * @param string|null $etiqueta Etiqueta para logging (identificador de tipo)
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function imprimir_directo($nombre, $contenido, $etiqueta = null) {
        if (!$nombre) {
            return ['success' => false, 'error' => 'Nombre de impresora vacío'];
        }
        
        $etiqueta = $etiqueta ?? 'directo';
        self::log("Iniciando impresión [$etiqueta]: '$nombre' - " . strlen($contenido) . ' bytes');
        
        try {
            self::log("Conectando a impresora: $nombre");
            $connector = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector($nombre);
            $profile = \Mike42\Escpos\CapabilityProfile::load("simple");
            $printer = new \Mike42\Escpos\Printer($connector, $profile);
            
            self::log("Enviando contenido a impresora");
            $printer->text($contenido);
            $printer->feed(3);
            $printer->cut();
            $printer->close();
            
            self::log("Impresión completada exitosamente [$etiqueta]", 'SUCCESS');
            return ['success' => true, 'error' => null];
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            $errorTrace = $e->getTraceAsString();
            
            self::log("ERROR en impresión [$etiqueta]: $errorMsg", 'ERROR');
            self::log("Trace: $errorTrace", 'ERROR');
            
            // Sugerencias para errores comunes
            $sugerencia = self::getSugerenciaError($errorMsg);
            
            return ['success' => false, 'error' => $errorMsg . ($sugerencia ? '. Sugerencia: ' . $sugerencia : '')];
        }
    }
    
    /**
     * Obtener sugerencia basada en el mensaje de error
     */
    private static function getSugerenciaError($errorMsg) {
        $errorLower = strtolower($errorMsg);
        
        if (strpos($errorLower, 'failed to read') !== false || 
            strpos($errorLower, 'net use') !== false ||
            strpos($errorLower, 'access is denied') !== false) {
            return "Verifica que la impresora esté compartida y que el usuario del servidor tenga permisos. Intenta con formato de red: \\\\PC-NAME\\PrinterName";
        }
        
        if (strpos($errorLower, 'not found') !== false || 
            strpos($errorLower, 'does not exist') !== false ||
            strpos($errorLower, 'no such file') !== false) {
            return "Verifica el nombre de la impresora. Abre 'Dispositivos e impresoras' en Windows y copia el nombre exacto.";
        }
        
        if (strpos($errorLower, 'unable to connect') !== false ||
            strpos($errorLower, 'connection refused') !== false) {
            return "La impresora no responde. Verifica que esté encendida, conectada y con papel.";
        }
        
        if (strpos($errorLower, 'timeout') !== false) {
            return "Timeout de conexión. Verifica la conexión de red o USB con la impresora.";
        }
        
        return null;
    }
    
    /**
     * Obtener ruta del archivo de log actual
     */
    public static function getLogFile() {
        self::initLog();
        return self::$logFile;
    }
    
    /**
     * Verificar si la conexión a printer funciona
     */
    public static function testConnection($nombre) {
        self::log("Testeando conexión a: $nombre");
        
        try {
            $connector = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector($nombre);
            self::log("Conexión exitosa a: $nombre", 'SUCCESS');
            return ['success' => true, 'error' => null];
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            self::log("Error de conexión: $errorMsg", 'ERROR');
            return ['success' => false, 'error' => $errorMsg];
        }
    }
}
