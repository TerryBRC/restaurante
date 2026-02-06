<?php
// Helper para generar el contenido de tickets y comandas
class TicketHelper {
    /**
     * Obtener configuración dinámica desde la base de datos
     */
    private static function getConfig() {
        static $config = null;
        if ($config === null) {
            $config = [
                'ruc' => '',
                'telefono' => '',
                'direccion' => ''
            ];
            try {
                if (!class_exists('ConfigModel')) {
                    require_once dirname(__DIR__, 1) . '/models/ConfigModel.php';
                }
                $model = new ConfigModel();
                $dbConfig = $model->getAll();
                if (isset($dbConfig['ruc'])) $config['ruc'] = $dbConfig['ruc'];
                if (isset($dbConfig['telefono'])) $config['telefono'] = $dbConfig['telefono'];
                if (isset($dbConfig['direccion'])) $config['direccion'] = $dbConfig['direccion'];
            } catch (Exception $e) {
                // Mantener valores por defecto
            }
        }
        return $config;
    }

    /**
     * Multibyte-safe str_pad replacement.
     * Pads $input to $length characters using $pad_string and $type (STR_PAD_RIGHT|STR_PAD_LEFT|STR_PAD_BOTH)
     */
    private static function mb_str_pad($input, $length, $pad_string = ' ', $type = STR_PAD_RIGHT) {
        $input = (string)$input;
        $pad_string = (string)$pad_string;
        $inputLen = mb_strlen($input);
        if ($length <= 0 || $inputLen >= $length) return $input;
        $padLen = $length - $inputLen;
        switch ($type) {
            case STR_PAD_LEFT:
                return str_repeat($pad_string, (int)ceil($padLen / mb_strlen($pad_string))) . $input;
            case STR_PAD_BOTH:
                $left = (int)floor($padLen / 2);
                $right = $padLen - $left;
                return str_repeat($pad_string, (int)ceil($left / mb_strlen($pad_string))) . $input . str_repeat($pad_string, (int)ceil($right / mb_strlen($pad_string)));
            case STR_PAD_RIGHT:
            default:
                return $input . str_repeat($pad_string, (int)ceil($padLen / mb_strlen($pad_string)));
        }
    }
    public static function generarTicketVenta($restaurante, $mesa, $fechaHora, $detalles, $total, $empleado, $ticketId, $moneda, $metodoPago, $cambio, $servicio, $prefactura = false) {
        // Obtener configuración dinámica
        $config = self::getConfig();
        
        // Ancho máximo de línea para ticket térmico estándar 80mm
        $maxWidth = 35;
        $out  = str_pad($restaurante, $maxWidth, ' ', STR_PAD_BOTH) . "\n";
        if ($prefactura) {
            $out .= str_pad('*** PRE-FACTURA ***', $maxWidth, ' ', STR_PAD_BOTH) . "\n";
        }
        if (!empty($config['ruc'])) {
            $out .= "RUC: " . $config['ruc'] . "\n";
        }
        if (!empty($config['telefono'])) {
            $out .= "Tel: " . $config['telefono'] . "\n";
        }
        if (!empty($config['direccion'])) {
            // Dividir dirección larga en múltiples líneas si es necesario
            $direccionLines = wordwrap($config['direccion'], $maxWidth, "\n", true);
            $out .= "Dirección: " . $direccionLines . "\n";
        }
        $out .= str_repeat('=', $maxWidth) . "\n";
        $out .= "Mesa: $mesa\n";
        $out .= "Fecha: $fechaHora\n";
        $out .= str_repeat('-', $maxWidth) . "\n";
    $out .= self::mb_str_pad('Cant', 4) . self::mb_str_pad('Producto', 22) . self::mb_str_pad('Subtotal', 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_repeat('-', $maxWidth) . "\n";
        foreach ($detalles as $item) {
            $cant = self::mb_str_pad($item['cantidad'] . 'x', 4);
            $nombre = mb_strimwidth(strtoupper($item['nombre']), 0, 22, '');
            $nombre = self::mb_str_pad($nombre, 22);
            $subtotal = self::mb_str_pad($moneda . number_format($item['subtotal'], 2), 8, ' ', STR_PAD_LEFT);
            $out .= "$cant$nombre$subtotal\n";
            // Si hay preparación, imprimirla en la línea siguiente con indent
            $prep = '';
            if (isset($item['preparacion'])) $prep = $item['preparacion'];
            if (isset($item['Preparacion'])) $prep = $prep ?: $item['Preparacion'];
            if ($prep !== null && trim($prep) !== '') {
                $prepWrapped = wordwrap(trim($prep), $maxWidth - 4, "\n", true);
                $lines = explode("\n", $prepWrapped);
                foreach ($lines as $l) {
                    $out .= '   ' . self::mb_str_pad('> ' . $l, $maxWidth) . "\n";
                }
            }
        }
        $out .= str_repeat('-', $maxWidth) . "\n";
        $out .= str_repeat('-', $maxWidth) . "\n";
        $out .= self::mb_str_pad('SUBTOTAL:', 26) . self::mb_str_pad($moneda . number_format($total, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        if($servicio>0){
            $out .= self::mb_str_pad('Servicio:', 26) . self::mb_str_pad($moneda . number_format($servicio, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        }
        $out .= self::mb_str_pad('TOTAL:', 26) . self::mb_str_pad($moneda . number_format($total+$servicio, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        if (!$prefactura) {
            // Método de pago: esperar que la cadena venga ya en el formato deseado
            $mpago = $metodoPago;
            if (mb_strlen($mpago) > $maxWidth - 15) {
                $out .= "Método de pago:\n";
                $out .= wordwrap($mpago, $maxWidth, "\n", true) . "\n";
            } else {
                $out .= "Método de pago: $mpago\n";
            }
            if ($cambio > 0) {
                $out .= "Cambio: $moneda " . number_format($cambio, 2) . "\n";
            }
        }
        $out .= "¡Gracias por su visita!\n";
        $emp = "$empleado";
        if (mb_strlen($emp) > $maxWidth - 15) {
            $out .= "Atendido por:\n";
            $out .= wordwrap($emp, $maxWidth, "\n", true) . "\n";
        } else {
            $out .= "Atendido por: $emp\n";
        }
        $out .= "Ticket: #" . str_pad($ticketId, 6, '0', STR_PAD_LEFT) . "\n";
        //despues de todo esto guarda el $out en un archivo .txt
        /* $filePath = __DIR__ . '/../tickets/ticket_' . $ticketId . '.txt';
         file_put_contents($filePath, $out); */
        return $out;
    }


    /**
     * Genera una comanda/ticket escalonado específico para delivery.
     * @param array $venta Venta con detalle (debe incluir 'detalles')
     * @param string $tipo 'cocina'|'barra'|'ambos'
     * @param int $width ancho en caracteres (ej. 40)
     * @return string Contenido listo para imprimir
     */
    public static function generarComandaPedido($pedido, $tipo = 'ambos', $width = 40) {
        // Intentar cargar categorías desde la base de datos para identificar las que van a barra (is_food = 0)
        $categoriasBarra = ['Bebidas', 'Licores', 'Cockteles', 'Cervezas'];
        try {
            if (!class_exists('ProductModel')) require_once dirname(__DIR__, 1) . '/models/ProductModel.php';
            $pm = new ProductModel();
            $cats = $pm->getAllCategories();
            if (is_array($cats) && count($cats) > 0) {
                $categoriasBarra = [];
                foreach ($cats as $c) {
                    if (isset($c['is_food']) && intval($c['is_food']) === 0) {
                        $categoriasBarra[] = $c['Nombre_Categoria'];
                    }
                }
                if (empty($categoriasBarra)) $categoriasBarra = ['Bebidas', 'Licores', 'Cockteles', 'Cervezas'];
            }
        } catch (Exception $e) {
            $categoriasBarra = ['Bebidas', 'Licores', 'Cockteles', 'Cervezas'];
        }
        
        $detalles = $pedido['detalles'] ?? [];
        $barra = [];
        $cocina = [];
        
        foreach ($detalles as $d) {
            // Priorizar campo is_food (1=true comida -> cocina, 0=false bebida -> barra)
            if (isset($d['is_food'])) {
                $isFood = $d['is_food'];
                // Normalizar valores comunes (string '1','0', boolean, int)
                $flag = false;
                if (is_bool($isFood)) $flag = $isFood;
                else if (is_numeric($isFood)) $flag = intval($isFood) === 1;
                else $flag = in_array(strtolower((string)$isFood), ['1','true','yes','si'], true);
                if ($flag) $cocina[] = $d; else $barra[] = $d;
                continue;
            }

            // Fallback a categoría cuando no exista is_food
            $cat = isset($d['Nombre_Categoria']) ? trim($d['Nombre_Categoria']) : (isset($d['categoria']) ? trim($d['categoria']) : '');
            if ($cat !== '' && in_array($cat, $categoriasBarra)) $barra[] = $d; else $cocina[] = $d;
        }

        if ($tipo === 'barra' && empty($barra)) return '';
        if ($tipo === 'cocina' && empty($cocina)) return '';

        if (session_status() == PHP_SESSION_NONE) session_start();
        $usuario = isset($_SESSION['username']) ? $_SESSION['username'] : (isset($_SESSION['user']['Nombre_Completo']) ? $_SESSION['user']['Nombre_Completo'] : 'Desconocido');
        $cliente = $pedido['Nombre_Cliente'] ?? ($pedido['nombre_cliente'] ?? 'Cliente');
        $idPedido = $pedido['ID_Pedido'] ?? ($pedido['id'] ?? '0');
        $fecha = $pedido['Fecha_Hora'] ?? date('Y-m-d H:i:s');
        $tipoEntrega = $pedido['tipo_entrega'] ?? 'local';
        $notas = $pedido['notas'] ?? '';

        $out = "\n";
        $buildSection = function($items, $sectionName) use ($width, $usuario, $cliente, $idPedido, $fecha, $tipoEntrega, $notas) {
            $s = "";
            $s .= self::mb_str_pad("====== COMANDA $sectionName ======", $width, ' ', STR_PAD_BOTH) . "\n";
            $s .= "Usuario: " . $usuario . "\n";
            $s .= "Pedido ID: " . $idPedido . "\n";
            $s .= "Cliente: " . $cliente . "\n";
            $s .= "Tipo: " . strtoupper($tipoEntrega) . "\n";
            $s .= "Hora: " . $fecha . "\n";
            if (!empty($notas)) {
                $s .= "Notas: " . wordwrap($notas, $width - 7, "\n       ", true) . "\n";
            }
            $s .= str_repeat('-', $width) . "\n";
            foreach ($items as $item) {
                $qty = isset($item['Cantidad']) ? $item['Cantidad'] : (isset($item['cantidad']) ? $item['cantidad'] : 1);
                $name = isset($item['Nombre_Producto']) ? $item['Nombre_Producto'] : (isset($item['nombre']) ? $item['nombre'] : 'Producto');
                $prep = isset($item['Preparacion']) ? $item['Preparacion'] : (isset($item['preparacion']) ? $item['preparacion'] : '');
                // Truncar el nombre de forma multibyte para evitar roturas con wordwrap
                $nameClean = mb_strimwidth(mb_strtoupper($name), 0, max(0, $width - 6), '');
                $line = $qty . ' x ' . $nameClean;
                $wrapped = wordwrap($line, $width, "\n", true);
                $s .= $wrapped . "\n";
                if (!empty($prep)) {
                    $prepWrapped = wordwrap($prep, $width - 4, "\n", true);
                    $lines = explode("\n", $prepWrapped);
                    foreach ($lines as $l) {
                        $s .= '   > ' . $l . "\n";
                    }
                }
            }
            $s .= str_repeat('-', $width) . "\n\n";
            return $s;
        };

        if ($tipo === 'barra') {
            $out .= $buildSection($barra, 'BARRA');
        } elseif ($tipo === 'cocina') {
            $out .= $buildSection($cocina, 'COCINA');
        } else {
            if (!empty($barra)) $out .= $buildSection($barra, 'BARRA');
            if (!empty($cocina)) $out .= $buildSection($cocina, 'COCINA');
        }

        return $out;
    }

    /**
     * Genera una comanda/aviso específica para traslado de mesa.
     * Incluye mesa origen y destino y separa items por cocina/barra como en delivery.
     * @param array $venta
     * @param int|null $origen
     * @param int|null $destino
     * @param string $tipo 'cocina'|'barra'|'ambos'
     * @param int $width
     * @return string
     */
    public static function generarComandaTraslado($venta, $origen = null, $destino = null, $tipo = 'ambos', $width = 40) {
        // Intentar cargar categorías desde la base de datos para identificar las que van a barra (is_food = 0)
        $categoriasBarra = ['Bebidas', 'Licores', 'Cockteles', 'Cervezas'];
        try {
            if (!class_exists('ProductModel')) require_once dirname(__DIR__, 1) . '/models/ProductModel.php';
            $pm = new ProductModel();
            $cats = $pm->getAllCategories();
            if (is_array($cats) && count($cats) > 0) {
                $categoriasBarra = [];
                foreach ($cats as $c) {
                    if (isset($c['is_food']) && intval($c['is_food']) === 0) {
                        $categoriasBarra[] = $c['Nombre_Categoria'];
                    }
                }
                if (empty($categoriasBarra)) $categoriasBarra = ['Bebidas', 'Licores', 'Cockteles', 'Cervezas'];
            }
        } catch (Exception $e) {
            $categoriasBarra = ['Bebidas', 'Licores', 'Cockteles', 'Cervezas'];
        }
        $detalles = $venta['detalles'] ?? [];
        $barra = [];
        $cocina = [];
        foreach ($detalles as $d) {
            // Priorizar campo is_food (1=true comida -> cocina, 0=false bebida -> barra)
            if (isset($d['is_food'])) {
                $isFood = $d['is_food'];
                // Normalizar valores comunes (string '1','0', boolean, int)
                $flag = false;
                if (is_bool($isFood)) $flag = $isFood;
                else if (is_numeric($isFood)) $flag = intval($isFood) === 1;
                else $flag = in_array(strtolower((string)$isFood), ['1','true','yes','si'], true);
                if ($flag) $cocina[] = $d; else $barra[] = $d;
                continue;
            }

            // Fallback a categoría cuando no exista is_food
            $cat = isset($d['Nombre_Categoria']) ? trim($d['Nombre_Categoria']) : (isset($d['categoria']) ? trim($d['categoria']) : '');
            if ($cat !== '' && in_array($cat, $categoriasBarra)) $barra[] = $d; else $cocina[] = $d;
        }

        if ($tipo === 'barra' && empty($barra)) return '';
        if ($tipo === 'cocina' && empty($cocina)) return '';

        if (session_status() == PHP_SESSION_NONE) session_start();
        $usuario = isset($_SESSION['username']) ? $_SESSION['username'] : (isset($_SESSION['user']['Nombre_Completo']) ? $_SESSION['user']['Nombre_Completo'] : 'Desconocido');
        $cliente = $venta['Nombre_Cliente'] ?? ($venta['cliente'] ?? 'Cliente');
        $idVenta = $venta['ID_Venta'] ?? ($venta['id'] ?? '0');
        $fecha = $venta['Fecha_Hora'] ?? date('Y-m-d H:i:s');

        $out = "\n";
        $buildSection = function($items, $sectionName) use ($width, $usuario, $cliente, $idVenta, $fecha, $origen, $destino) {
            $s = "";
            $s .= self::mb_str_pad("====== AVISO TRASLADO $sectionName ======", $width, ' ', STR_PAD_BOTH) . "\n";
            $s .= "Usuario: " . $usuario . "\n";
            $s .= "Venta ID: " . $idVenta . "\n";
            $s .= "Cliente: " . $cliente . "\n";
            $s .= "Hora: " . $fecha . "\n";
            $s .= "Origen: " . ($origen ?? 'N/A') . "  -> Destino: " . ($destino ?? 'N/A') . "\n";
            $s .= str_repeat('-', $width) . "\n";
            foreach ($items as $item) {
                $qty = isset($item['Cantidad']) ? $item['Cantidad'] : (isset($item['cantidad']) ? $item['cantidad'] : 1);
                $name = isset($item['Nombre_Producto']) ? $item['Nombre_Producto'] : (isset($item['nombre']) ? $item['nombre'] : 'Producto');
                $prep = isset($item['Preparacion']) ? $item['Preparacion'] : (isset($item['preparacion']) ? $item['preparacion'] : '');
                // Truncar el nombre de forma multibyte para evitar roturas con wordwrap
                $nameClean = mb_strimwidth(mb_strtoupper($name), 0, max(0, $width - 6), '');
                $line = $qty . ' x ' . $nameClean;
                $wrapped = wordwrap($line, $width, "\n", true);
                $s .= $wrapped . "\n";
                if (!empty($prep)) {
                    $prepWrapped = wordwrap($prep, $width - 4, "\n", true);
                    $lines = explode("\n", $prepWrapped);
                    foreach ($lines as $l) {
                        $s .= '   > ' . $l . "\n";
                    }
                }
            }
            $s .= str_repeat('-', $width) . "\n\n";
            return $s;
        };

        if ($tipo === 'barra') {
            $out .= $buildSection($barra, 'BARRA');
        } elseif ($tipo === 'cocina') {
            $out .= $buildSection($cocina, 'COCINA');
        } else {
            if (!empty($barra)) $out .= $buildSection($barra, 'BARRA');
            if (!empty($cocina)) $out .= $buildSection($cocina, 'COCINA');
        }

        return $out;
    }
    // Generar ticket de cierre de caja - ventas diarias
    public static function generarTicketCierreCaja($restaurante, $fechaInicio, $fechaFin, $totalEfectivo, $totalTarjeta, $totalVentas, $totalServicio, $totalGeneral, $empleado, $ticketId, $moneda) {
        // Obtener configuración dinámica
        $config = self::getConfig();
        
        $maxWidth = 35;
        $out  = str_pad($restaurante, $maxWidth, ' ', STR_PAD_BOTH) . "\n";
        $out .= str_pad('*** CIERRE DE CAJA ***', $maxWidth, ' ', STR_PAD_BOTH) . "\n";
        if (!empty($config['ruc'])) {
            $out .= "RUC: " . $config['ruc'] . "\n";
        }
        if (!empty($config['telefono'])) {
            $out .= "Tel: " . $config['telefono'] . "\n";
        }
        if (!empty($config['direccion'])) {
            $direccionLines = wordwrap($config['direccion'], $maxWidth, "\n", true);
            $out .= "Dirección: " . $direccionLines . "\n";
        }
        $out .= str_repeat('=', $maxWidth) . "\n";
        $out .= "Desde: $fechaInicio\n";
        $out .= "Hasta: $fechaFin\n";
        $out .= str_repeat('-', $maxWidth) . "\n";
        $out .= str_pad('Total Efectivo:', 26) . str_pad($moneda . number_format($totalEfectivo, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_pad('Total Tarjeta:', 26) . str_pad($moneda . number_format($totalTarjeta, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        if($totalServicio>0){
            $out .= str_pad('Total Servicio:', 26) . str_pad($moneda . number_format($totalServicio, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        }
        $out .= str_repeat('-', $maxWidth) . "\n";
        $out .= str_pad('Total Ventas:', 26) . str_pad($moneda . number_format($totalVentas, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_repeat('=', $maxWidth) . "\n";
        $out .= str_pad('TOTAL GENERAL:', 26) . str_pad($moneda . number_format($totalGeneral, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_repeat('-', $maxWidth) . "\n";
        $emp = "$empleado";
        if (mb_strlen($emp) > $maxWidth - 15) {
            $out .= "Cerrado por:\n";
            $out .= wordwrap($emp, $maxWidth, "\n", true) . "\n";
        } else {
            $out .= "Cerrado por: $emp\n";
        }
        $out .= "Ticket: #" . str_pad($ticketId, 6, '0', STR_PAD_LEFT) . "\n";
        $out .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
        $out .= "¡Gracias por su visita!\n";
        $out .= str_repeat('-', $maxWidth) . "\n";
        return $out;
    }

        /**
     * Genera el texto de un ticket para reporte de productos vendidos por fecha.
     * @param string $restaurante Nombre del restaurante
     * @param string $fecha Fecha del reporte (formato d/m/Y)
     * @param array $productos Array de productos: [ ['nombre'=>..., 'cantidad'=>..., 'total'=>...] ]
     * @param float $granTotal Suma total de todos los productos
     * @param string $moneda Símbolo de moneda
     * @param string $empleado Nombre del usuario/empleado que imprime
     * @return string Texto listo para impresión térmica
     */
    public static function generarTicketProductosVendidos($restaurante, $fecha, $productos, $granTotal, $moneda, $empleado) {
        $maxWidth = 35;
        $out  = str_pad($restaurante, $maxWidth, ' ', STR_PAD_BOTH) . "\n";
        $out .= str_pad('*** REPORTE DE PRODUCTOS ***', $maxWidth, ' ', STR_PAD_BOTH) . "\n";
        $out .= "Fecha: $fecha\n";
        $out .= str_repeat('=', $maxWidth) . "\n";

        // Agrupar productos por categoría
        $porCategoria = [];
        foreach ($productos as $item) {
            $cat = isset($item['categoria']) ? $item['categoria'] : 'SIN CATEGORIA';
            if (!isset($porCategoria[$cat])) {
                $porCategoria[$cat] = [];
            }
            $porCategoria[$cat][] = $item;
        }

        foreach ($porCategoria as $categoria => $items) {
            $out .= str_pad(strtoupper($categoria), $maxWidth, ' ', STR_PAD_BOTH) . "\n";
            $out .= str_pad('Cant', 4) . str_pad('Producto', 22) . str_pad('Total', 8, ' ', STR_PAD_LEFT) . "\n";
            $out .= str_repeat('-', $maxWidth) . "\n";
            $subtotal = 0;
            foreach ($items as $item) {
                $cant = str_pad($item['cantidad'], 4);
                $nombre = mb_strimwidth(strtoupper($item['nombre']), 0, 22, '');
                $nombre = str_pad($nombre, 22);
                $total = str_pad($moneda . number_format($item['total'], 2), 8, ' ', STR_PAD_LEFT);
                $out .= "$cant$nombre$total\n";
                $subtotal += $item['total'];
            }
            $out .= str_repeat('-', $maxWidth) . "\n";
            $out .= str_pad('Subtotal:', 26) . str_pad($moneda . number_format($subtotal, 2), 8, ' ', STR_PAD_LEFT) . "\n";
            $out .= str_repeat('=', $maxWidth) . "\n";
        }

        $out .= str_pad('GRAN TOTAL:', 26) . str_pad($moneda . number_format($granTotal, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_repeat('=', $maxWidth) . "\n";
        $emp = "$empleado";
        if (mb_strlen($emp) > $maxWidth - 15) {
            $out .= "Generado por:\n";
            $out .= wordwrap($emp, $maxWidth, "\n", true) . "\n";
        } else {
            $out .= "Generado por: $emp\n";
        }
        $out .= "Fecha/Hora: " . date('d/m/Y H:i') . "\n";
        $out .= "\n";
        $out .= str_pad('FIN DEL REPORTE', $maxWidth, ' ', STR_PAD_BOTH) . "\n";
        return $out;
    }

    /**
     * Genera ticket de PRE-CIERRE (resumen acumulado sin cerrar caja)
     * Muestra lo que lleva acumulado el día sin registrar el cierre
     * @param array $datos Datos del reporte de cierre (de ReporteModel::obtenerReporteCierreCaja)
     * @return string Texto listo para impresión térmica
     */
    public static function generarTicketPreCierreCaja($datos) {
        $config = self::getConfig();
        $maxWidth = 35;
        $out  = str_pad($config['ruc'] ? $config['ruc'] : 'RESTAURANTE', $maxWidth, ' ', STR_PAD_BOTH) . "\n";
        $out .= str_pad('*** PRE-CIERRE DE CAJA ***', $maxWidth, ' ', STR_PAD_BOTH) . "\n";
        if (!empty($config['telefono'])) {
            $out .= "Tel: " . $config['telefono'] . "\n";
        }
        $out .= str_repeat('=', $maxWidth) . "\n";
        $out .= "Fecha: " . date('d/m/Y H:i:s') . "\n";
        $out .= str_repeat('-', $maxWidth) . "\n";
        $out .= str_pad('MONTO APERTURA:', 26) . str_pad('C$ ' . number_format($datos['monto_apertura'] ?? 0, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_repeat('-', $maxWidth) . "\n";
        $out .= "RESUMEN DE VENTAS:\n";
        $out .= str_pad('Total Efectivo:', 26) . str_pad('C$ ' . number_format($datos['total_efectivo'] ?? 0, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_pad('Total Tarjeta:', 26) . str_pad('C$ ' . number_format($datos['total_tarjeta'] ?? 0, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_pad('Total Ventas:', 26) . str_pad('C$ ' . number_format($datos['total_ventas'] ?? 0, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_repeat('-', $maxWidth) . "\n";
        $out .= str_pad('INGRESOS:', 26) . str_pad('C$ ' . number_format($datos['total_ingresos'] ?? 0, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_pad('EGRESOS:', 26) . str_pad('C$ ' . number_format($datos['total_egresos'] ?? 0, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_repeat('=', $maxWidth) . "\n";
        $saldo = ($datos['monto_apertura'] ?? 0) + ($datos['total_ventas'] ?? 0) + ($datos['total_ingresos'] ?? 0) - ($datos['total_egresos'] ?? 0);
        $out .= str_pad('SALDO ACTUAL:', 26) . str_pad('C$ ' . number_format($saldo, 2), 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_repeat('=', $maxWidth) . "\n";
        $out .= "Este es un PRE-CIERRE.\n";
        $out .= "La caja aún está abierta.\n";
        $out .= "Use el botón CERRAR CAJA\n";
        $out .= "para registrar el cierre oficial.\n";
        $out .= str_repeat('-', $maxWidth) . "\n";
        $out .= "Pre-cierre generado por:\n";
        $out .= ($_SESSION['username'] ?? ($_SESSION['user']['Nombre_Completo'] ?? 'Usuario')) . "\n";
        $out .= date('d/m/Y H:i:s') . "\n";
        return $out;
    }

    /**
     * Genera ticket de CIERRE de caja (versión simplificada para impresión directa)
     * @param array $datos ['monto_cierre' => float, 'fecha_cierre' => string, 'usuario' => string]
     * @return string Texto listo para impresión térmica
     */
    public static function generarTicketCierreCajaSimplificado($datos) {
        $config = self::getConfig();
        $maxWidth = 35;
        $out  = str_pad($config['ruc'] ? $config['ruc'] : 'RESTAURANTE', $maxWidth, ' ', STR_PAD_BOTH) . "\n";
        $out .= str_pad('*** CIERRE DE CAJA ***', $maxWidth, ' ', STR_PAD_BOTH) . "\n";
        if (!empty($config['telefono'])) {
            $out .= "Tel: " . $config['telefono'] . "\n";
        }
        $out .= str_repeat('=', $maxWidth) . "\n";
        $out .= "Fecha Cierre: " . date('d/m/Y H:i:s', strtotime($datos['fecha_cierre'])) . "\n";
        $out .= str_repeat('-', $maxWidth) . "\n";
        $out .= str_pad('MONTO CIERRE:', 26) . str_pad('C$ ' . number_format($datos['monto_cierre'], 2), 8, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_repeat('=', $maxWidth) . "\n";
        $out .= "Cerrado por: " . $datos['usuario'] . "\n";
        $out .= str_repeat('-', $maxWidth) . "\n";
        $out .= "*** CIERRE REGISTRADO ***\n";
        return $out;
    }
}
