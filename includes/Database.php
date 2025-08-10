<?php
/**
 * Clase para manejar la base de datos SQLite del ISP Speed Test Server
 */
class Database {
    private $db;
    private $dbPath;
    
    public function __construct($dbPath = null) {
        if ($dbPath === null) {
            $this->dbPath = dirname(__DIR__) . '/database/speedserver.db';
        } else {
            $this->dbPath = $dbPath;
        }
        $this->initDatabase();
    }
    
    /**
     * Inicializa la base de datos y crea las tablas si no existen
     */
    private function initDatabase() {
        // Crear directorio si no existe
        $dbDir = dirname($this->dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        try {
            $this->db = new PDO('sqlite:' . $this->dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Ejecutar script de inicialización
            $initSqlPath = dirname(__DIR__) . '/database/init.sql';
            if (file_exists($initSqlPath)) {
                $initSql = file_get_contents($initSqlPath);
                $this->db->exec($initSql);
            }
            
        } catch (PDOException $e) {
            error_log("Error de base de datos: " . $e->getMessage());
            throw new Exception("No se pudo conectar a la base de datos");
        }
    }
    
    /**
     * Obtiene cliente por IP
     */
    public function getClienteByIP($ip) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM clientes WHERE ip_asignada = ? AND activo = 1");
            $stmt->execute([$ip]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo cliente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea una nueva sesión para un cliente
     */
    public function crearSesion($clienteId, $ipCliente, $userAgent) {
        try {
            $token = $this->generarToken();
            $stmt = $this->db->prepare("
                INSERT INTO sesiones (cliente_id, ip_cliente, user_agent, token_sesion) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$clienteId, $ipCliente, $userAgent, $token]);
            
            return [
                'id' => $this->db->lastInsertId(),
                'token' => $token
            ];
        } catch (PDOException $e) {
            error_log("Error creando sesión: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Valida una sesión por token
     */
    public function validarSesion($token) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, c.nombre, c.plan_contratado, c.velocidad_contratada_mbps 
                FROM sesiones s 
                JOIN clientes c ON s.cliente_id = c.id 
                WHERE s.token_sesion = ? AND s.fecha_fin IS NULL
            ");
            $stmt->execute([$token]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error validando sesión: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cierra una sesión
     */
    public function cerrarSesion($token) {
        try {
            $stmt = $this->db->prepare("UPDATE sesiones SET fecha_fin = CURRENT_TIMESTAMP WHERE token_sesion = ?");
            return $stmt->execute([$token]);
        } catch (PDOException $e) {
            error_log("Error cerrando sesión: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Guarda un test de velocidad
     */
    public function guardarTest($sesionId, $clienteId, $tipo, $velocidad, $latencia, $jitter, $tamanio, $duracion, $ipCliente) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO tests (sesion_id, cliente_id, tipo_test, velocidad_mbps, latencia_ms, jitter_ms, tamanio_datos_mb, duracion_test_ms, ip_cliente) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$sesionId, $clienteId, $tipo, $velocidad, $latencia, $jitter, $tamanio, $duracion, $ipCliente]);
        } catch (PDOException $e) {
            error_log("Error guardando test: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene historial de tests de un cliente
     */
    public function getHistorialCliente($clienteId, $limite = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, s.fecha_inicio 
                FROM tests t 
                JOIN sesiones s ON t.sesion_id = s.id 
                WHERE t.cliente_id = ? 
                ORDER BY t.fecha DESC 
                LIMIT ?
            ");
            $stmt->execute([$clienteId, $limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo historial: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene estadísticas de un cliente
     */
    public function getEstadisticasCliente($clienteId, $dias = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    tipo_test,
                    AVG(velocidad_mbps) as velocidad_promedio,
                    AVG(latencia_ms) as latencia_promedio,
                    COUNT(*) as total_tests,
                    MAX(velocidad_mbps) as velocidad_maxima,
                    MIN(velocidad_mbps) as velocidad_minima
                FROM tests 
                WHERE cliente_id = ? AND fecha >= datetime('now', '-{$dias} days')
                GROUP BY tipo_test
            ");
            $stmt->execute([$clienteId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene todos los clientes (para panel ISP)
     */
    public function getAllClientes() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM clientes WHERE activo = 1 ORDER BY nombre");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo clientes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene estadísticas generales del ISP
     */
    public function getEstadisticasISP() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT c.id) as total_clientes,
                    COUNT(t.id) as total_tests_hoy,
                    AVG(t.velocidad_mbps) as velocidad_promedio_general,
                    AVG(t.latencia_ms) as latencia_promedio_general
                FROM clientes c 
                LEFT JOIN tests t ON c.id = t.cliente_id AND t.fecha >= date('now')
                WHERE c.activo = 1
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas ISP: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Genera un token único para la sesión
     */
    private function generarToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Cierra la conexión
     */
    public function __destruct() {
        $this->db = null;
    }
}
?>
