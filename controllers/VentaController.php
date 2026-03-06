<?php
require_once 'BaseController.php';


class VentaController extends BaseController {
    public function index() {
        $this->render('views/ventas/index.php');
    }

    public function registrarPago() {
        require_once __DIR__ . '/../config/Session.php';
        require_once __DIR__ . '/../models/VentaModel.php';
        require_once __DIR__ . '/../models/MovimientoModel.php';
        Session::init();
        Session::checkRole(['Administrador', 'Cajero']);
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        require_once __DIR__ . '/../helpers/Csrf.php';
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            echo json_encode(['success' => false, 'error' => 'CSRF token inválido']);
            exit;
        }
        $idVenta = isset($_POST['id_venta']) ? (int)$_POST['id_venta'] : 0;
        $metodoPago = isset($_POST['metodo_pago']) ? substr(trim($_POST['metodo_pago']), 0, 200) : '';
    $monto = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
    $incluirServicioFlag = isset($_POST['incluir_servicio']) ? ($_POST['incluir_servicio'] === '1' || $_POST['incluir_servicio'] === 'true' ? true : false) : true;
        // Parse payment methods server-side. Prefer structured JSON 'metodos_json' if present (safer), else fallback to comma-separated 'metodo_pago'.
        $totalPagado = 0.0;
        $hasEfectivo = false;
        $efectivoMonto = 0.0;
        $metodosStructured = [];
        if (!empty($_POST['metodos_json'])) {
            $raw = $_POST['metodos_json'];
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $entry) {
                    $mname = isset($entry['metodo']) ? trim($entry['metodo']) : '';
                    $montoEntry = isset($entry['monto']) ? floatval($entry['monto']) : 0.0;
                    $metodosStructured[] = ['metodo' => $mname, 'monto' => $montoEntry];
                    $totalPagado += $montoEntry;
                    if (stripos($mname, 'efectivo') !== false) {
                        $hasEfectivo = true;
                        $efectivoMonto += $montoEntry;
                    }
                }
            }
        } else {
            // Fallback to legacy parsing
            $metodosArr = array_filter(array_map('trim', explode(',', $metodoPago)));
            foreach ($metodosArr as $m) {
                $parts = explode(':', $m);
                if (isset($parts[1])) {
                    $amt = floatval(trim($parts[1]));
                    $totalPagado += $amt;
                }
                if (stripos($m, 'efectivo') !== false) {
                    $hasEfectivo = true;
                    // try to extract amount
                    $parts = explode(':', $m);
                    $amt = isset($parts[1]) ? floatval(trim($parts[1])) : 0.0;
                    $efectivoMonto += $amt;
                }
            }
        }
        // Basic server-side validation for payment input
        if (!$idVenta) {
            echo json_encode(['success' => false, 'error' => 'ID de venta inválido']);
            exit;
        }
        if ($totalPagado <= 0) {
            echo json_encode(['success' => false, 'error' => 'El monto total pagado debe ser mayor a 0']);
            exit;
        }
        // Validate structured methods if provided
        if (!empty($metodosStructured)) {
            foreach ($metodosStructured as $idx => $entry) {
                $mname = isset($entry['metodo']) ? trim($entry['metodo']) : '';
                $montoEntry = isset($entry['monto']) ? floatval($entry['monto']) : 0.0;
                if ($mname === '' || $montoEntry <= 0) {
                    echo json_encode(['success' => false, 'error' => "Método de pago inválido en la posición " . ($idx+1)]);
                    exit;
                }
            }
        }
    $ventaModel = new VentaModel();
    $movimientoModel = new MovimientoModel();
    require_once __DIR__ . '/../models/PagoModel.php';
    $pagoModel = new PagoModel();
    $conn = (new Database())->connect();
        try {
            // Asegurar que el campo Total en la tabla ventas esté actualizado con la suma de detalle_venta
            $ventaModel->actualizarTotal($idVenta);
            $stmt = $conn->prepare('SELECT Total, Servicio FROM ventas WHERE ID_Venta = ?');
            $stmt->execute([$idVenta]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$venta) throw new Exception('Venta no encontrada');
            $total = (float)$venta['Total'];
            // Use Servicio persisted in DB (do not trust client-side value)
            $servicio = isset($venta['Servicio']) ? (float)$venta['Servicio'] : 0.0;
            // If the cashier chose NOT to include service, persist that change so prints/reports reflect it
            if (!$incluirServicioFlag && $servicio > 0) {
                try {
                    $stmtUpd = $conn->prepare('UPDATE ventas SET Servicio = 0 WHERE ID_Venta = ?');
                    $stmtUpd->execute([$idVenta]);
                    // reflect change locally for calculations
                    $servicio = 0.0;
                } catch (Exception $e) {
                    // Non-fatal: continue
                }
            }
            // determinar si en esta operacion se cobrará el servicio (checkbox del cajero)
            $servicioCobradoEnEstaOperacion = $incluirServicioFlag ? $servicio : 0.0;
            $totalFactura = $total + $servicioCobradoEnEstaOperacion;
            // Use small epsilon to avoid floating point rounding issues
            $epsilon = 0.005;
            // Validar con el total pagado extraido de metodo_pago
            if ($totalPagado > $totalFactura + $epsilon) {
                $neededChange = round($totalPagado - $totalFactura, 2);
                if ($hasEfectivo) {
                    // ensure efectivo provided covers the needed change
                    if ($efectivoMonto + $epsilon < $neededChange) {
                        throw new Exception('No hay suficiente efectivo para cubrir el cambio. Efectivo: ' . number_format($efectivoMonto,2,'.','') . ', cambio necesario: ' . number_format($neededChange,2,'.',''));
                    }
                    $cambio = $neededChange;
                } else {
                    throw new Exception('El monto pagado excede el total de la venta y no hay efectivo para devolver cambio');
                }
            }
            if ($totalFactura > $totalPagado + $epsilon) {
                throw new Exception('El monto pagado es menor al total de la venta');
            }
            // Si la diferencia es menor a epsilon, igualamos para evitar discrepancias de redondeo
            if (abs($totalPagado - $totalFactura) <= $epsilon) {
                $totalPagado = $totalFactura;
            }

            // Si se usó metodosStructured pero metodoPago (legacy) está vacío, crear una representación legible para guardarla en ventas.Metodo_Pago
            if (empty($metodoPago) && !empty($metodosStructured)) {
                $pairs = [];
                foreach ($metodosStructured as $entry) {
                    $mname = $entry['metodo'] ?? '';
                    $montoEntry = isset($entry['monto']) ? floatval($entry['monto']) : 0.0;
                    $pairs[] = $mname . ':' . number_format($montoEntry, 2, '.', '');
                }
                $metodoPago = implode(',', $pairs);
            }
            // Registrar el pago y marcar como pagada (no sobrescribimos Servicio desde el cliente)
            $stmtUp = $conn->prepare('UPDATE ventas SET Metodo_Pago = ?, Estado = "Pagada" WHERE ID_Venta = ?');
            $stmtUp->execute([$metodoPago, $idVenta]);
            // Liberar la mesa
            $stmtMesa = $conn->prepare("UPDATE mesas SET Estado = 0 WHERE ID_Mesa = (SELECT ID_Mesa FROM ventas WHERE ID_Venta = ?)");
            $stmtMesa->execute([$idVenta]);

            // Registrar movimiento de caja
            $idUsuario = null;
            if (isset($_SESSION['user_id'])) {
                $idUsuario = $_SESSION['user_id'];
            } elseif (isset($_SESSION['user']['ID_usuario'])) {
                $idUsuario = $_SESSION['user']['ID_usuario'];
            }
            $descripcion = 'Pago de venta ID ' . $idVenta . ' (' . $metodoPago . ')';
            // Registrar el ingreso correspondiente al total final (incluye servicio). Si hay sobrepago, el cambio se maneja aparte.
            // Registrar ingreso por el monto efectivamente cobrado en esta operación (respeta incluir_servicio)
            $movimientoModel->registrarMovimiento('Ingreso', $totalFactura, $descripcion, $idUsuario, $idVenta);

            // Registrar cada metodo de pago en la tabla pagos
            try {
                if (!empty($metodosStructured)) {
                    foreach ($metodosStructured as $entry) {
                        $mname = $entry['metodo'] ?? '';
                        $montoEntry = floatval($entry['monto'] ?? 0);
                        if ($montoEntry > 0) {
                            $pagoModel->registrarPago($idVenta, $mname, $montoEntry, false);
                        }
                    }
                } else {
                    // Legacy: parse metodoPago string like "Efectivo:100,Card:50"
                    $metodosArr = array_filter(array_map('trim', explode(',', $metodoPago)));
                    foreach ($metodosArr as $m) {
                        $parts = explode(':', $m);
                        $mname = trim($parts[0]);
                        $montoEntry = isset($parts[1]) ? floatval(trim($parts[1])) : 0.0;
                        if ($montoEntry > 0) {
                            $pagoModel->registrarPago($idVenta, $mname, $montoEntry, false);
                        }
                    }
                }
            } catch (Exception $e) {
                // Non-fatal: continue
            }

            // Llamar a imprimir_ticket.php en segundo plano
            // Forzar la ruta de php.exe para evitar problemas con PHP_BINARY bajo Apache
            $phpPath = 'C:/xampp/php/php.exe';
            $scriptPath = realpath(__DIR__ . '/../imprimir_ticket.php');
            if ($scriptPath) {
                $cmd = "$phpPath \"$scriptPath\" id=$idVenta > NUL 2>&1 &";
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Windows: usar start para background
                    $cmd = 'start /B ' . $cmd;
                }
                exec($cmd);
            }

            // Si hay cambio y se pagó en efectivo, registrar cambio como pago con Es_Cambio=1 (monto positivo siendo entregado como cambio)
            if (isset($cambio) && $cambio > 0 && $hasEfectivo) {
                try {
                    $pagoModel->registrarPago($idVenta, 'Cambio', $cambio, true);
                } catch (Exception $e) {
                }
            }

            $response = ['success' => true, 'total_pagado' => $totalPagado, 'total_factura' => $totalFactura, 'metodo_pago' => $metodoPago];
            if (isset($cambio)) $response['cambio'] = $cambio;
            echo json_encode($response);
        } catch (Exception $e) {
            // If the exception is about overpayment, include difference info when possible
            $msg = $e->getMessage();
            if (isset($totalFactura) && isset($totalPagado) && strpos($msg, 'excede') !== false) {
                $diff = round($totalPagado - $totalFactura, 2);
                $msg .= ' (diferencia: ' . number_format($diff, 2, '.', '') . ')';
            }
            echo json_encode(['success' => false, 'error' => $msg]);
        }
    }
    // ticket
    public function ticket() {
        // El método ticket solo debe preparar datos o delegar a la vista según el router
        // Si usas un sistema de vistas, deberías hacer algo como:
        $this->render('views/ventas/ticket.php');
    }

    public function ventas_dia() {
        // Mostrar ventas filtradas por fecha (por defecto: hoy)
        require_once __DIR__ . '/../models/VentaModel.php';
        require_once __DIR__ . '/../models/ConfigModel.php';
        $fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : date('Y-m-d');
        $ventaModel = new VentaModel();
        $conn = (new Database())->connect();
        try {
            $stmt = $conn->prepare('SELECT v.*, m.Numero_Mesa FROM ventas v LEFT JOIN mesas m ON v.ID_Mesa = m.ID_Mesa WHERE DATE(v.Fecha_Hora) = ? ORDER BY v.Fecha_Hora DESC');
            $stmt->execute([$fecha]);
            $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $ventas = [];
        }
        $this->render('views/ventas/ventas_dia.php', compact('ventas', 'fecha'));
    }
}
