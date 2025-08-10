-- Base de datos para ISP Speed Test Server
-- Inicialización de tablas

-- Tabla de clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre VARCHAR(100) NOT NULL,
    ip_asignada VARCHAR(45) UNIQUE NOT NULL,
    plan_contratado VARCHAR(50) NOT NULL,
    velocidad_contratada_mbps DECIMAL(10,2) NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo INTEGER DEFAULT 1
);

-- Tabla de sesiones de clientes
CREATE TABLE IF NOT EXISTS sesiones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cliente_id INTEGER NOT NULL,
    ip_cliente VARCHAR(45) NOT NULL,
    user_agent TEXT,
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME,
    token_sesion VARCHAR(255) UNIQUE NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- Tabla de tests de velocidad
CREATE TABLE IF NOT EXISTS tests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cliente_id INTEGER NOT NULL,
    sesion_id INTEGER NOT NULL,
    tipo_test TEXT CHECK(tipo_test IN ('download', 'upload', 'ping')) NOT NULL,
    velocidad_mbps DECIMAL(10,2),
    latencia_ms DECIMAL(10,2),
    jitter_ms DECIMAL(10,2),
    tamanio_datos_mb DECIMAL(10,2),
    duracion_test_ms INTEGER,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_cliente VARCHAR(45) NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (sesion_id) REFERENCES sesiones(id)
);

-- Tabla de configuración del sistema
CREATE TABLE IF NOT EXISTS configuracion (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT NOT NULL,
    descripcion TEXT,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_tests_cliente_id ON tests(cliente_id);
CREATE INDEX IF NOT EXISTS idx_tests_fecha ON tests(fecha);
CREATE INDEX IF NOT EXISTS idx_sesiones_token ON sesiones(token_sesion);
CREATE INDEX IF NOT EXISTS idx_clientes_ip ON clientes(ip_asignada);

-- Insertar configuración inicial
INSERT OR IGNORE INTO configuracion (clave, valor, descripcion) VALUES
('max_tests_por_sesion', '100', 'Máximo número de tests por sesión'),
('tiempo_expiracion_sesion', '3600', 'Tiempo de expiración de sesión en segundos'),
('tamanio_test_download', '10', 'Tamaño de test de descarga en MB'),
('tamanio_test_upload', '5', 'Tamaño de test de subida en MB'),
('umbral_velocidad_baja', '80', 'Porcentaje del plan contratado para alertas');

-- Insertar cliente de prueba
INSERT OR IGNORE INTO clientes (nombre, ip_asignada, plan_contratado, velocidad_contratada_mbps) VALUES
('Cliente Demo', '192.168.1.100', 'Plan Básico', 50.0),
('Cliente Premium', '192.168.1.101', 'Plan Premium', 100.0);
