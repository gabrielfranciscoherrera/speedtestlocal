<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../includes/Database.php';

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $db = new Database();
    
    // Obtener IP del cliente con mejor manejo de errores
    $ipCliente = '';
    $ipSources = [];
    
    // Intentar diferentes métodos para obtener IP
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipCliente = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $ipSources[] = 'HTTP_X_FORWARDED_FOR';
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ipCliente = $_SERVER['HTTP_CLIENT_IP'];
        $ipSources[] = 'HTTP_CLIENT_IP';
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ipCliente = $_SERVER['REMOTE_ADDR'];
        $ipSources[] = 'REMOTE_ADDR';
    }
    
    // Log para debugging
    error_log("API Debug - IP obtenida: '$ipCliente' desde fuentes: " . implode(', ', $ipSources));
    error_log("API Debug - REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'NO_DEFINIDO'));
    error_log("API Debug - HTTP_X_FORWARDED_FOR: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'NO_DEFINIDO'));
    error_log("API Debug - HTTP_CLIENT_IP: " . ($_SERVER['HTTP_CLIENT_IP'] ?? 'NO_DEFINIDO'));
    
    // Validar que la IP sea válida
    if (empty($ipCliente) || !filter_var($ipCliente, FILTER_VALIDATE_IP)) {
        error_log("API Error - IP inválida o no obtenida: '$ipCliente'");
        http_response_code(400);
        echo json_encode([
            'error' => 'No se pudo obtener una IP válida del cliente',
            'debug_info' => [
                'ip_obtenida' => $ipCliente,
                'fuentes_intentadas' => $ipSources,
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'NO_DEFINIDO',
                'x_forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'NO_DEFINIDO',
                'client_ip' => $_SERVER['HTTP_CLIENT_IP'] ?? 'NO_DEFINIDO'
            ]
        ]);
        exit();
    }
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'iniciar_sesion':
                    // Verificar si se envió la IP del cliente
                    $clientIP = $input['client_ip'] ?? $ipCliente;
                    
                    error_log("API Debug - iniciar_sesion - IP del servidor: '$ipCliente', IP del cliente: '$clientIP'");
                    
                    $cliente = $db->getClienteByIP($clientIP);
                    if (!$cliente) {
                        error_log("API Error - Cliente no encontrado para IP: '$clientIP'");
                        http_response_code(404);
                        echo json_encode([
                            'error' => 'Cliente no encontrado para IP: ' . $clientIP,
                            'debug_info' => [
                                'ip_buscada' => $clientIP,
                                'ip_servidor' => $ipCliente,
                                'sugerencia' => 'Verifica que la IP esté registrada en la base de datos'
                            ]
                        ]);
                        exit();
                    }
                    
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                    $sesion = $db->crearSesion($cliente['id'], $clientIP, $userAgent);
                    
                    if ($sesion) {
                        echo json_encode([
                            'success' => true,
                            'token' => $sesion['token'],
                            'cliente' => [
                                'nombre' => $cliente['nombre'],
                                'plan' => $cliente['plan_contratado'],
                                'velocidad_contratada' => $cliente['velocidad_contratada_mbps']
                            ]
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Error creando sesión']);
                    }
                    break;
                    
                case 'guardar_test':
                    $token = $input['token'] ?? '';
                    $sesion = $db->validarSesion($token);
                    
                    if (!$sesion) {
                        http_response_code(401);
                        echo json_encode(['error' => 'Sesión inválida']);
                        exit();
                    }
                    
                    $testData = $input['test_data'] ?? [];
                    $guardado = $db->guardarTest(
                        $sesion['id'],
                        $sesion['cliente_id'],
                        $testData['tipo'],
                        $testData['velocidad'],
                        $testData['latencia'],
                        $testData['jitter'] ?? 0,
                        $testData['tamanio'],
                        $testData['duracion'],
                        $ipCliente
                    );
                    
                    if ($guardado) {
                        echo json_encode(['success' => true, 'message' => 'Test guardado correctamente']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Error guardando test']);
                    }
                    break;
                    
                case 'cerrar_sesion':
                    $token = $input['token'] ?? '';
                    $cerrado = $db->cerrarSesion($token);
                    
                    if ($cerrado) {
                        echo json_encode(['success' => true, 'message' => 'Sesión cerrada']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Error cerrando sesión']);
                    }
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Acción no válida']);
                    break;
            }
            break;
            
        case 'GET':
            $action = $_GET['action'] ?? '';
            
            switch ($action) {
                case 'historial':
                    $token = $_GET['token'] ?? '';
                    $sesion = $db->validarSesion($token);
                    
                    if (!$sesion) {
                        http_response_code(401);
                        echo json_encode(['error' => 'Sesión inválida']);
                        exit();
                    }
                    
                    $historial = $db->getHistorialCliente($sesion['cliente_id']);
                    echo json_encode(['success' => true, 'historial' => $historial]);
                    break;
                    
                case 'estadisticas':
                    $token = $_GET['token'] ?? '';
                    $sesion = $db->validarSesion($token);
                    
                    if (!$sesion) {
                        http_response_code(401);
                        echo json_encode(['error' => 'Sesión inválida']);
                        exit();
                    }
                    
                    $dias = intval($_GET['dias'] ?? 30);
                    $estadisticas = $db->getEstadisticasCliente($sesion['cliente_id'], $dias);
                    echo json_encode(['success' => true, 'estadisticas' => $estadisticas]);
                    break;
                    
                case 'cliente_info':
                    $cliente = $db->getClienteByIP($ipCliente);
                    if ($cliente) {
                        echo json_encode(['success' => true, 'cliente' => $cliente]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'Cliente no encontrado']);
                    }
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Acción no válida']);
                    break;
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>
