-- ============================================
-- Estructura de Base de Datos para IT-Form
-- Sistema de Gestión de Servicios Técnicos
-- ============================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS itspanama_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE itspanama_db;

-- ============================================
-- Tabla de Configuración de Empresa
-- ============================================
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(255) NOT NULL DEFAULT 'ITS Panama',
    email_soporte VARCHAR(255) DEFAULT 'soporte@itspanama.net',
    sitio_web VARCHAR(255) DEFAULT 'www.itspanama.net',
    telefono VARCHAR(50) DEFAULT '',
    direccion VARCHAR(500) DEFAULT '',
    logo_login VARCHAR(500) DEFAULT 'logo.png',
    logo_footer VARCHAR(500) DEFAULT 'logo2.png',
    color_primario VARCHAR(7) DEFAULT '#001F3F',
    color_secundario VARCHAR(7) DEFAULT '#4CAF50',
    tema_defecto ENUM('light', 'dark') DEFAULT 'light',
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_config (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración por defecto
INSERT INTO configuracion (
    nombre_empresa, 
    email_soporte, 
    sitio_web, 
    logo_login, 
    logo_footer,
    color_primario,
    color_secundario,
    tema_defecto
) VALUES (
    'ITS Panama',
    'soporte@itspanama.net',
    'www.itspanama.net',
    'logo.png',
    'logo2.png',
    '#001F3F',
    '#4CAF50',
    'light'
);

-- ============================================
-- Tabla de Usuarios
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'tecnico', 'usuario') DEFAULT 'tecnico',
    activo BOOLEAN DEFAULT TRUE,
    ultimo_acceso DATETIME DEFAULT NULL,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME DEFAULT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario admin por defecto (password: admin123)
-- El hash se genera con password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO usuarios (
    username, 
    password_hash, 
    email, 
    nombre_completo, 
    rol, 
    activo
) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin@itspanama.net',
    'Administrador Principal',
    'admin',
    TRUE
);

-- Insertar usuario técnico por defecto (password: tecnico123)
INSERT INTO usuarios (
    username, 
    password_hash, 
    email, 
    nombre_completo, 
    rol, 
    activo
) VALUES (
    'itspanama',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'soporte@itspanama.net',
    'Técnico ITS',
    'tecnico',
    TRUE
);

-- ============================================
-- Tabla de Servicios/Informes
-- ============================================
CREATE TABLE IF NOT EXISTS servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_secuencia VARCHAR(20) NOT NULL UNIQUE,
    cliente VARCHAR(255) NOT NULL,
    fecha_servicio DATETIME NOT NULL,
    direccion VARCHAR(500) NOT NULL,
    ticket VARCHAR(50) NOT NULL,
    reporte_cliente TEXT NOT NULL,
    diagnostico_tecnico TEXT NOT NULL,
    trabajo_realizado TEXT NOT NULL,
    observaciones TEXT,
    recibido_conforme VARCHAR(255) NOT NULL,
    firma_tecnico VARCHAR(255) NOT NULL,
    usuario_id INT,
    estado ENUM('pendiente', 'en_proceso', 'completado', 'cancelado') DEFAULT 'pendiente',
    pdf_generado BOOLEAN DEFAULT FALSE,
    ruta_pdf VARCHAR(500) DEFAULT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_guardado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_numero_secuencia (numero_secuencia),
    INDEX idx_cliente (cliente),
    INDEX idx_fecha (fecha_servicio),
    INDEX idx_ticket (ticket),
    INDEX idx_estado (estado),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla de Auditoría/Logs
-- ============================================
CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50) DEFAULT NULL,
    registro_id INT DEFAULT NULL,
    descripcion TEXT,
    ip_origen VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_fecha (fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla de Sesiones (opcional para gestión de sesiones)
-- ============================================
CREATE TABLE IF NOT EXISTS sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token_sesion VARCHAR(255) NOT NULL UNIQUE,
    ip_origen VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token_sesion),
    INDEX idx_usuario (usuario_id),
    INDEX idx_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Vista para resumen de servicios
-- ============================================
CREATE OR REPLACE VIEW vista_resumen_servicios AS
SELECT 
    s.id,
    s.numero_secuencia,
    s.cliente,
    s.fecha_servicio,
    s.ticket,
    s.estado,
    u.nombre_completo as tecnico_nombre,
    s.fecha_guardado
FROM servicios s
LEFT JOIN usuarios u ON s.usuario_id = u.id
ORDER BY s.fecha_guardado DESC;

-- ============================================
-- Trigger para generar número de secuencia automático
-- ============================================
DELIMITER //
CREATE TRIGGER before_insert_servicios
BEFORE INSERT ON servicios
FOR EACH ROW
BEGIN
    DECLARE anio_actual CHAR(4);
    DECLARE mes_actual CHAR(2);
    DECLARE consecutivo INT;
    DECLARE nuevo_numero VARCHAR(20);
    
    SET anio_actual = YEAR(NOW());
    SET mes_actual = LPAD(MONTH(NOW()), 2, '0');
    
    -- Obtener el último consecutivo del mes actual
    SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(numero_secuencia, '-', -1) AS UNSIGNED)), 0) + 1
    INTO consecutivo
    FROM servicios
    WHERE numero_secuencia LIKE CONCAT(anio_actual, '-', mes_actual, '-%');
    
    -- Generar nuevo número: AAAA-MM-NNNN
    SET nuevo_numero = CONCAT(anio_actual, '-', mes_actual, '-', LPAD(consecutivo, 4, '0'));
    SET NEW.numero_secuencia = nuevo_numero;
END//
DELIMITER ;

-- ============================================
-- Procedimiento almacenado para limpiar registros antiguos
-- ============================================
DELIMITER //
CREATE PROCEDURE sp_limpiar_sesiones_expiradas()
BEGIN
    DELETE FROM sesiones 
    WHERE fecha_expiracion < NOW() OR activa = FALSE;
END//
DELIMITER ;

-- ============================================
-- Inicializar datos de prueba (opcional)
-- ============================================
-- INSERT INTO servicios (
--     cliente, fecha_servicio, direccion, ticket,
--     reporte_cliente, diagnostico_tecnico, trabajo_realizado,
--     observaciones, recibido_conforme, firma_tecnico, usuario_id, estado
-- ) VALUES (
--     'Cliente Ejemplo',
--     NOW(),
--     'Calle Principal #123',
--     'TK-001',
--     'Equipo no enciende',
--     'Fuente de poder dañada',
--     'Reemplazo de fuente de poder',
--     'Se recomienda usar UPS',
--     'Juan Pérez',
--     'Carlos Técnico',
--     2,
--     'completado'
-- );

-- ============================================
-- Permisos y privilegios (ajustar según necesidad)
-- ============================================
-- CREATE USER IF NOT EXISTS 'its_user'@'localhost' IDENTIFIED BY 'secure_password_123';
-- GRANT SELECT, INSERT, UPDATE ON itspanama_db.servicios TO 'its_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON itspanama_db.usuarios TO 'its_user'@'localhost';
-- GRANT SELECT ON itspanama_db.configuracion TO 'its_user'@'localhost';
-- FLUSH PRIVILEGES;

-- ============================================
-- Fin del script de inicialización
-- ============================================
