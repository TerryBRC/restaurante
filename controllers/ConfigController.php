<?php
require_once 'BaseController.php';
require_once dirname(__DIR__, 1) . '/models/ConfigModel.php';
require_once dirname(__DIR__, 1) . '/helpers/Csrf.php';

require_once dirname(__DIR__, 1) . '/helpers/ImpresoraHelper.php';

class ConfigController extends BaseController {
    public function index() {
        Session::init();
        $userRole = Session::getUserRole();
        if ($userRole !== 'Administrador') {
            http_response_code(403);
            echo 'Acceso Denegado. No tiene permisos para acceder a esta sección.';
            exit;
        }
        $configModel = new ConfigModel();
        $config = $configModel->getAll();
        $mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
        unset($_SESSION['mensaje']);
        $this->render('views/config/index.php', compact('config', 'mensaje'));
    }

    public function update() {
        Session::init();
        $userRole = Session::getUserRole();
        if ($userRole !== 'Administrador') {
            http_response_code(403);
            echo 'Acceso Denegado. No tiene permisos para acceder a esta sección.';
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['mensaje'] = 'Token CSRF inválido.';
                header('Location: ' . BASE_URL . 'configuracion');
                exit;
            }
            $configModel = new ConfigModel();
            $configModel->set('nombre_app', $_POST['nombre_app'] ?? '');
            $configModel->set('ruc', $_POST['ruc'] ?? '');
            $configModel->set('telefono', $_POST['telefono'] ?? '');
            $configModel->set('direccion', $_POST['direccion'] ?? '');
            $configModel->set('usar_impresora_cocina', isset($_POST['usar_impresora_cocina']) ? '1' : '0');
            $configModel->set('impresora_cocina', $_POST['impresora_cocina'] ?? '');
            $configModel->set('usar_impresora_barra', isset($_POST['usar_impresora_barra']) ? '1' : '0');
            $configModel->set('impresora_barra', $_POST['impresora_barra'] ?? '');
            $configModel->set('moneda', $_POST['moneda'] ?? 'C$');
            $configModel->set('IVA', $_POST['iva'] ?? '0');
            $configModel->set('simbolo_moneda', $_POST['simbolo_moneda'] ?? 'C$');
            $configModel->set('impresora_ticket', $_POST['impresora_ticket'] ?? '');
            $configModel->set('servicio', $_POST['servicio'] ?? '0');
            $_SESSION['mensaje'] = 'Configuración actualizada correctamente.';
            header('Location: ' . BASE_URL . 'configuracion');
            exit;
        }
    }

    public function buscarImpresoras() {
        Session::init();
        $userRole = Session::getUserRole();
        header('Content-Type: application/json');
        if ($userRole !== 'Administrador') {
            echo json_encode(['impresoras' => [], 'error' => 'Acceso denegado']);
            exit;
        }
        // Solo POST por seguridad
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['impresoras' => [], 'error' => 'Método no permitido']);
            exit;
        }
        $impresoras = ImpresoraHelper::buscarImpresoras();
        echo json_encode(['impresoras' => $impresoras]);
        exit;
    }

    public function getAll() {
        $configModel = new ConfigModel();
        return $configModel->getAll();
    }

    public function backup() {
        Session::init();
        $userRole = Session::getUserRole();
        if ($userRole !== 'Administrador') {
            http_response_code(403);
            echo 'Acceso Denegado. No tiene permisos para acceder a esta sección.';
            exit;
        }
        // Aquí puedes implementar el respaldo usando mysqldump y forzar descarga
        // Ejemplo básico:
        $dbHost = DB_HOST;
        $dbUser = DB_USER;
        $dbPass = DB_PASS;
        $dbName = DB_NAME;
        $filename = 'backup_' . date('Ymd_His') . '.sql';
        $command = "mysqldump -h$dbHost -u$dbUser -p$dbPass $dbName > $filename";
        system($command);
        // Descargar el archivo
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename=' . $filename);
        readfile($filename);
        unlink($filename);
        exit;
    }
        
    // Endpoint para probar impresora
    public function probarImpresora() {
        Session::init();
        $userRole = Session::getUserRole();
        header('Content-Type: application/json');
        if ($userRole !== 'Administrador') {
            echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        $tipo = $_POST['tipo'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        if (!$nombre) {
            echo json_encode(['success' => false, 'error' => 'Nombre de impresora vacío.']);
            exit;
        }
        require_once dirname(__DIR__, 1) . '/helpers/ImpresoraHelper.php';
        $mensaje = "PRUEBA DE IMPRESORA ($tipo)\n" . date('d/m/Y H:i:s') . "\n----------------------\n";
        $resultado = ImpresoraHelper::imprimir_directo($nombre, $mensaje, 'test');
        if ($resultado['success']) {
            echo json_encode(['success' => true]);
        } else {
            $error = $resultado['error'] ?: 'No se pudo imprimir en la impresora seleccionada.';
            // Incluir ruta del log para debugging
            $logFile = ImpresoraHelper::getLogFile();
            echo json_encode([
                'success' => false, 
                'error' => $error,
                'logFile' => $logFile
            ]);
        }
        exit;
    }
    
    // Nuevo endpoint para ver logs de impresión
    public function verLogsImpresora() {
        Session::init();
        $userRole = Session::getUserRole();
        if ($userRole !== 'Administrador') {
            http_response_code(403);
            echo 'Acceso Denegado.';
            exit;
        }
        
        $logFile = ImpresoraHelper::getLogFile();
        if (file_exists($logFile)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="impresora.log"');
            readfile($logFile);
        } else {
            echo "No hay logs de impresión disponibles.";
        }
        exit;
    }
}
