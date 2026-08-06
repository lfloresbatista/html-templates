-- Crear base de datos
CREATE DATABASE IF NOT EXISTS itformdb
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE itformdb;

CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(255) NOT NULL DEFAULT 'ITS Panama',
    email_soporte VARCHAR(255) DEFAULT 'soporte@itspanama.net',
    sitio_web VARCHAR(255) DEFAULT 'www.itspanama.net',
    telefono VARCHAR(50) DEFAULT '',
    direccion VARCHAR(500) DEFAULT '',
    ruc VARCHAR(100) DEFAULT '',
    logo_login VARCHAR(500) DEFAULT 'logo.png',
    logo_footer VARCHAR(500) DEFAULT 'logo2.png',
    color_primario VARCHAR(7) DEFAULT '#001F3F',
    color_secundario VARCHAR(7) DEFAULT '#4CAF50',
    tema_defecto ENUM('light', 'dark') DEFAULT 'light',
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracion (nombre_empresa, email_soporte, sitio_web, logo_login, logo_footer, color_primario, color_secundario, tema_defecto)
SELECT 'ITS Panama', 'soporte@itspanama.net', 'www.itspanama.net', 'logo.png', 'logo2.png', '#001F3F', '#4CAF50', 'light'
WHERE NOT EXISTS (SELECT 1 FROM configuracion LIMIT 1);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    cargo VARCHAR(150) DEFAULT '',
    rol ENUM('admin', 'tecnico', 'usuario') DEFAULT 'tecnico',
    activo TINYINT(1) DEFAULT 1,
    ultimo_acceso DATETIME DEFAULT NULL,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME DEFAULT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- password_hash('admin123', PASSWORD_DEFAULT) / password_hash('tecnico123', PASSWORD_DEFAULT)
INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo)
SELECT 'admin', '$2y$12$c2Hbifg.8/aEJPbQWROlBORW5QDOg500UvPxPuZ6V0f9rYUG0vCs6', 'admin@itspanama.net', 'Administrador Principal', 'admin', 1
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE username = 'admin');

INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo)
SELECT 'itspanama', '$2y$12$uXJbciKfNMI3GCAWGyFVh.wNbLAPg0ChWkxD8gfMTJT/63DbSelZm', 'soporte@itspanama.net', 'Técnico ITS', 'tecnico', 1
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE username = 'itspanama');

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
    usuario_id INT NULL,
    estado ENUM('pendiente', 'en_proceso', 'completado', 'cancelado') DEFAULT 'pendiente',
    pdf_generado TINYINT(1) DEFAULT 0,
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

CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
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

CREATE TABLE IF NOT EXISTS sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token_sesion VARCHAR(255) NOT NULL UNIQUE,
    ip_origen VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    activa TINYINT(1) DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token_sesion),
    INDEX idx_usuario (usuario_id),
    INDEX idx_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TRIGGER IF EXISTS before_insert_servicios;
DELIMITER //
CREATE TRIGGER before_insert_servicios
BEFORE INSERT ON servicios
FOR EACH ROW
BEGIN
    DECLARE anio_actual CHAR(4);
    DECLARE mes_actual CHAR(2);
    DECLARE consecutivo INT;
    DECLARE nuevo_numero VARCHAR(20);

    IF NEW.numero_secuencia IS NULL OR NEW.numero_secuencia = '' THEN
        SET anio_actual = YEAR(NOW());
        SET mes_actual = LPAD(MONTH(NOW()), 2, '0');

        SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(numero_secuencia, '-', -1) AS UNSIGNED)), 0) + 1
        INTO consecutivo
        FROM servicios
        WHERE numero_secuencia LIKE CONCAT(anio_actual, '-', mes_actual, '-%');

        SET nuevo_numero = CONCAT(anio_actual, '-', mes_actual, '-', LPAD(consecutivo, 4, '0'));
        SET NEW.numero_secuencia = nuevo_numero;
    END IF;
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_limpiar_sesiones_expiradas;
DELIMITER //
CREATE PROCEDURE sp_limpiar_sesiones_expiradas()
BEGIN
    DELETE FROM sesiones
    WHERE fecha_expiracion < NOW() OR activa = 0;
END//
DELIMITER ;

-- Usuario de aplicación (ejecutar como root; ajustar host/password)
-- CREATE USER IF NOT EXISTS 'itform_usr'@'%' IDENTIFIED BY 'change_me_strong_password';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON itformdb.* TO 'itform_usr'@'%';
-- FLUSH PRIVILEGES;
