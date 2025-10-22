<?php
// helpers/TicketPedidoHelper.php
require_once __DIR__ . '/TicketHelper.php';

class TicketPedidoHelper {
    // Local copy of mb_str_pad (multibyte-safe) to avoid calling private helper
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

    /**
     * Genera el contenido de la comanda para printing de pedidos.
     * Opciones: ancho, incluir notas, encabezado personalizado.
     * Retorna array con claves 'cocina' y 'barra' con el texto respectivo.
     */
    public static function buildComandas(array $pedido, $width = 40, $includeNotas = true) {
        // Reuse logic from TicketHelper::generarComandaPedido by delegating
        $cocina = TicketHelper::generarComandaPedido($pedido, 'cocina', $width);
        $barra = TicketHelper::generarComandaPedido($pedido, 'barra', $width);
        return ['cocina' => $cocina, 'barra' => $barra];
    }

    /**
     * Genera comanda combinada (barra + cocina) si se quiere un solo payload
     */
    public static function buildCombined(array $pedido, $width = 40) {
        return TicketHelper::generarComandaPedido($pedido, 'ambos', $width);
    }

    /**
     * Genera el texto de una prefactura para un pedido.
     * @param array $pedido
     * @param int|string $ticketId
     * @param string $moneda
     * @param string $metodoPago
     * @param float $cambio
     * @param float $servicio
     * @return string
     */
    public static function generarPrefacturaPedido(array $pedido, $ticketId = 0, $moneda = '$', $metodoPago = 'Efectivo', $cambio = 0.0, $servicio = 0.0) {
        // preparar detalles para generarTicketVenta
        $detalles = [];
        $total = 0.0;
        foreach ($pedido['detalles'] ?? [] as $it) {
            $cantidad = isset($it['cantidad']) ? (int)$it['cantidad'] : (isset($it['Cantidad']) ? (int)$it['Cantidad'] : 1);
            $precio = isset($it['precio']) ? (float)$it['precio'] : (isset($it['Precio_Unitario']) ? (float)$it['Precio_Unitario'] : 0.0);
            $nombre = isset($it['nombre']) ? $it['nombre'] : (isset($it['Nombre_Producto']) ? $it['Nombre_Producto'] : 'Producto');
            $subtotal = $cantidad * $precio;
            $detalles[] = ['cantidad' => $cantidad, 'nombre' => $nombre, 'subtotal' => $subtotal, 'preparacion' => $it['preparacion'] ?? $it['Preparacion'] ?? ''];
            $total += $subtotal;
        }
        if (session_status() == PHP_SESSION_NONE) session_start();
        $empleado = isset($_SESSION['username']) ? $_SESSION['username'] : (isset($_SESSION['user']['Nombre_Completo']) ? $_SESSION['user']['Nombre_Completo'] : 'Desconocido');
        // Leer datos del restaurante desde la tabla config
        $restaurante = 'Restaurante';
        try {
            if (!class_exists('ConfigModel')) require_once dirname(__DIR__, 1) . '/models/ConfigModel.php';
            $cm = new ConfigModel();
            $conf = $cm->getAll();
            if (isset($conf['nombre_app']) && trim($conf['nombre_app']) !== '') $restaurante = $conf['nombre_app'];
            else if (isset($conf['app_name']) && trim($conf['app_name']) !== '') $restaurante = $conf['app_name'];
        } catch (Exception $e) {
            // fallback
        }
        $fecha = $pedido['Fecha_Hora'] ?? date('Y-m-d H:i:s');
        // reutilizar generarTicketVenta con prefactura=true
        return TicketHelper::generarTicketVenta($restaurante, 'Pedido', $fecha, $detalles, $total, $empleado, $ticketId, $moneda, $metodoPago, $cambio, $servicio, true);
    }

    /**
     * Genera el texto de factura para un pedido (similar a venta final con prefactura=false)
     */
    public static function generarFacturaPedido(array $pedido, $ticketId = 0, $moneda = '$', $metodoPago = 'Efectivo', $cambio = 0.0, $servicio = 0.0) {
        $detalles = [];
        $total = 0.0;
        foreach ($pedido['detalles'] ?? [] as $it) {
            $cantidad = isset($it['cantidad']) ? (int)$it['cantidad'] : (isset($it['Cantidad']) ? (int)$it['Cantidad'] : 1);
            $precio = isset($it['precio']) ? (float)$it['precio'] : (isset($it['Precio_Unitario']) ? (float)$it['Precio_Unitario'] : 0.0);
            $nombre = isset($it['nombre']) ? $it['nombre'] : (isset($it['Nombre_Producto']) ? $it['Nombre_Producto'] : 'Producto');
            $subtotal = $cantidad * $precio;
            $detalles[] = ['cantidad' => $cantidad, 'nombre' => $nombre, 'subtotal' => $subtotal, 'preparacion' => $it['preparacion'] ?? $it['Preparacion'] ?? ''];
            $total += $subtotal;
        }
        if (session_status() == PHP_SESSION_NONE) session_start();
        $empleado = isset($_SESSION['username']) ? $_SESSION['username'] : (isset($_SESSION['user']['Nombre_Completo']) ? $_SESSION['user']['Nombre_Completo'] : 'Desconocido');
        $restaurante = 'Restaurante';
        try {
            if (!class_exists('ConfigModel')) require_once dirname(__DIR__, 1) . '/models/ConfigModel.php';
            $cm = new ConfigModel();
            $conf = $cm->getAll();
            // Support Spanish keys used in DB backups: nombre_app, moneda, servicio
            if (isset($conf['nombre_app']) && trim($conf['nombre_app']) !== '') $restaurante = $conf['nombre_app'];
            else if (isset($conf['restaurant_name']) && trim($conf['restaurant_name']) !== '') $restaurante = $conf['restaurant_name'];
            else if (isset($conf['app_name']) && trim($conf['app_name']) !== '') $restaurante = $conf['app_name'];
        } catch (Exception $e) {
            // fallback
        }
        $fecha = $pedido['Fecha_Hora'] ?? date('Y-m-d H:i:s');
        return TicketHelper::generarTicketVenta($restaurante, 'Pedido', $fecha, $detalles, $total, $empleado, $ticketId, $moneda, $metodoPago, $cambio, $servicio, false);
    }
}

?>
