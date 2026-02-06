<?php
/**
 * Script de Diagnóstico para Impresión
 * Ejecutar este script desde el navegador para diagnosticar problemas de impresión
 */

// Cargar autoloader de escpos-php
require_once __DIR__ . '/helpers/escpos-autoloader.php';

echo "<h1> Diagnóstico de Impresión - Epson POSON PR 350 WF</h1>";
echo "<hr>";

// 1. Verificar extensiones PHP necesarias
echo "<h2>1. Extensiones PHP</h2>";
$extensiones = ['gd', 'mbstring', 'pdo', 'pdo_mysql'];
foreach ($extensiones as $ext) {
    $status = extension_loaded($ext) ? 'OK' : 'FALTA';
    echo "<p>$ext: $status</p>";
}

// 2. Verificar permisos de escritura
echo "<h2>2. Permisos de Escritura</h2>";
$logsPath = __DIR__ . '/logs';
if (!is_dir($logsPath)) {
    @mkdir($logsPath, 0777, true);
}
if (is_dir($logsPath) && is_writable($logsPath)) {
    echo "<p>OK Directorio logs/ escribible</p>";
} else {
    echo "<p>FALTA Directorio logs/ no escribible</p>";
}

// 3. Verificar configuración de impresoras
echo "<h2>3. Configuración de Impresoras</h2>";
require_once __DIR__ . '/models/ConfigModel.php';
$configModel = new ConfigModel();

$impresoras = [
    'cocina' => [
        'nombre' => 'impresora_cocina',
        'usar' => 'usar_impresora_cocina'
    ],
    'barra' => [
        'nombre' => 'impresora_barra', 
        'usar' => 'usar_impresora_barra'
    ],
    'ticket' => [
        'nombre' => 'impresora_ticket',
        'usar' => 'usar_impresora_ticket'
    ]
];

foreach ($impresoras as $tipo => $cfg) {
    $nombre = $configModel->get($cfg['nombre']);
    $usar = $configModel->get($cfg['usar']);
    echo "<p><strong>" . strtoupper($tipo) . ":</strong><br>";
    echo "  Nombre: " . ($nombre ?: 'No configurada') . "<br>";
    echo "  Habilitada: " . ($usar === '1' ? 'Si' : 'No') . "</p>";
}

// 4. Verificar conexión con impresoras
echo "<h2>4. Pruebas de Conexión</h2>";

$testPrinter = $configModel->get('impresora_cocina') ?: $configModel->get('impresora_barra') ?: $configModel->get('impresora_ticket');

if ($testPrinter) {
    echo "<p>Impresora a probar: <strong>$testPrinter</strong></p>";
    
    // Listar impresoras disponibles
    echo "<p>Buscando impresoras disponibles...</p>";
    @exec('powershell "Get-Printer | Select-Object -ExpandProperty Name"', $output, $result);
    if (!empty($output)) {
        echo "<p>Impresoras encontradas:</p>";
        echo "<ul>";
        foreach ($output as $line) {
            $line = trim($line);
            if ($line) {
                $match = (stripos($line, $testPrinter) !== false) ? '👉' : '  ';
                echo "<li>$match $line</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>No se encontraron impresoras</p>";
    }
} else {
    echo "<p>No hay impresoras configuradas</p>";
}

// 5. Test de impresión directo usando ImpresoraHelper
echo "<h2>5. Test de Impresión</h2>";
if ($testPrinter && isset($_GET['test'])) {
    require_once __DIR__ . '/helpers/ImpresoraHelper.php';
    
    $testContent = "================================\n";
    $testContent .= "     TEST DE IMPRESION\n";
    $testContent .= "     " . date('d/m/Y H:i:s') . "\n";
    $testContent .= "================================\n";
    $testContent .= "Impresora: $testPrinter\n";
    $testContent .= "================================\n";
    
    echo "<p>Enviando test a: $testPrinter...</p>";
    echo "<p>Usando ImpresoraHelper::imprimir_directo()</p>";
    
    $resultado = ImpresoraHelper::imprimir_directo($testPrinter, $testContent, 'test');
    
    if ($resultado['success']) {
        echo "<p>IMPRESION EXITOSA</p>";
        echo "<p>Revisa si el ticket salio de la impresora.</p>";
    } else {
        $error = $resultado['error'] ?: 'Error desconocido';
        echo "<p>ERROR DE IMPRESION</p>";
        echo "<p>Error: $error</p>";
        echo "<p>Ver log: " . ImpresoraHelper::getLogFile() . "</p>";
    }
    
    echo "<p><a href='?test=1' class='btn btn-primary'>Reintentar Test</a></p>";
    
} else if ($testPrinter) {
    echo "<p><a href='?test=1' class='btn btn-success'>EJECUTAR TEST DE IMPRESION</a></p>";
} else {
    echo "<p>Configura una impresora primero para poder hacer el test.</p>";
}

// 6. Información del sistema
echo "<h2>6. Información del Sistema</h2>";
echo "<p>Sistema: " . PHP_OS . "</p>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Usuario del servidor: " . @exec('whoami') . "</p>";
echo "<p>Servidor web: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido') . "</p>";

echo "<hr>";
echo "<p><em>Logs en: <code>logs/impresora_*.log</code></em></p>";
?>
<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
h1 { color: #198754; }
h2 { color: #0d6efd; margin-top: 20px; }
p { margin: 5px 0; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
.btn { display: inline-block; padding: 10px 20px; background: #198754; color: white; text-decoration: none; border-radius: 5px; }
.btn-success { background: #198754; }
</style>
