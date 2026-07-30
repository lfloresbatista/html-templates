<?php
/**
 * Migración idempotente de schema IT-form (MySQL y SQLite).
 *
 * Uso:
 *   php database/migrate.php
 *   ITFORM_AUTO_MIGRATE=1  (entrypoint Docker)
 *
 * No crea la base de datos MySQL ni el usuario (eso lo hace el sysadmin
 * o las env MYSQL_* de un MariaDB oficial). Solo tablas/seeds/índices.
 */
require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/database.php';

function itform_migrate(?PDO $db = null): array
{
    $db = $db ?: getDB();
    $driver = itform_db_driver();
    $log = [];

    if ($driver === 'sqlite') {
        $log = array_merge($log, itform_migrate_sqlite($db));
    } else {
        $log = array_merge($log, itform_migrate_mysql($db));
    }

    $log = array_merge($log, itform_migrate_seeds($db));
    return $log;
}

function itform_migrate_mysql(PDO $db): array
{
    $log = [];
    $db->exec("SET NAMES utf8mb4");

    $db->exec("
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
            tema_defecto VARCHAR(10) DEFAULT 'light',
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $log[] = 'mysql:configuracion';

    // columnas opcionales si schema viejo
    itform_mysql_add_column($db, 'configuracion', 'ruc', "VARCHAR(100) DEFAULT ''");

    $db->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            nombre_completo VARCHAR(255) NOT NULL,
            rol VARCHAR(20) DEFAULT 'tecnico',
            activo TINYINT(1) DEFAULT 1,
            ultimo_acceso DATETIME DEFAULT NULL,
            intentos_fallidos INT DEFAULT 0,
            bloqueado_hasta DATETIME DEFAULT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_activo (activo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $log[] = 'mysql:usuarios';

    $db->exec("
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
            estado VARCHAR(20) DEFAULT 'pendiente',
            pdf_generado TINYINT(1) DEFAULT 0,
            ruta_pdf VARCHAR(500) DEFAULT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_guardado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_numero_secuencia (numero_secuencia),
            INDEX idx_cliente (cliente),
            INDEX idx_fecha (fecha_servicio),
            INDEX idx_ticket (ticket),
            INDEX idx_estado (estado),
            INDEX idx_usuario (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $log[] = 'mysql:servicios';

    $db->exec("
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
            INDEX idx_usuario (usuario_id),
            INDEX idx_accion (accion),
            INDEX idx_fecha (fecha_registro)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $log[] = 'mysql:auditoria';

    $db->exec("
        CREATE TABLE IF NOT EXISTS sesiones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            token_sesion VARCHAR(255) NOT NULL UNIQUE,
            ip_origen VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(500) DEFAULT NULL,
            fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion DATETIME NOT NULL,
            activa TINYINT(1) DEFAULT 1,
            INDEX idx_token (token_sesion),
            INDEX idx_usuario (usuario_id),
            INDEX idx_activa (activa)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $log[] = 'mysql:sesiones';

    return $log;
}

function itform_mysql_add_column(PDO $db, string $table, string $column, string $definition): void
{
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
        $stmt->execute([$column]);
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    } catch (Throwable $e) {
        error_log("migrate add column {$table}.{$column}: " . $e->getMessage());
    }
}

function itform_migrate_sqlite(PDO $db): array
{
    $log = [];
    $db->exec('PRAGMA foreign_keys = ON');

    $db->exec("
        CREATE TABLE IF NOT EXISTS configuracion (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre_empresa TEXT NOT NULL DEFAULT 'ITS Panama',
            email_soporte TEXT DEFAULT 'soporte@itspanama.net',
            sitio_web TEXT DEFAULT 'www.itspanama.net',
            telefono TEXT DEFAULT '',
            direccion TEXT DEFAULT '',
            ruc TEXT DEFAULT '',
            logo_login TEXT DEFAULT 'logo.png',
            logo_footer TEXT DEFAULT 'logo2.png',
            color_primario TEXT DEFAULT '#001F3F',
            color_secundario TEXT DEFAULT '#4CAF50',
            tema_defecto TEXT DEFAULT 'light',
            fecha_actualizacion TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $log[] = 'sqlite:configuracion';

    $db->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            email TEXT NOT NULL,
            nombre_completo TEXT NOT NULL,
            rol TEXT DEFAULT 'tecnico',
            activo INTEGER DEFAULT 1,
            ultimo_acceso TEXT DEFAULT NULL,
            intentos_fallidos INTEGER DEFAULT 0,
            bloqueado_hasta TEXT DEFAULT NULL,
            fecha_creacion TEXT DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $log[] = 'sqlite:usuarios';

    $db->exec("
        CREATE TABLE IF NOT EXISTS servicios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            numero_secuencia TEXT NOT NULL UNIQUE,
            cliente TEXT NOT NULL,
            fecha_servicio TEXT NOT NULL,
            direccion TEXT NOT NULL,
            ticket TEXT NOT NULL,
            reporte_cliente TEXT NOT NULL,
            diagnostico_tecnico TEXT NOT NULL,
            trabajo_realizado TEXT NOT NULL,
            observaciones TEXT,
            recibido_conforme TEXT NOT NULL,
            firma_tecnico TEXT NOT NULL,
            usuario_id INTEGER NULL,
            estado TEXT DEFAULT 'pendiente',
            pdf_generado INTEGER DEFAULT 0,
            ruta_pdf TEXT DEFAULT NULL,
            fecha_creacion TEXT DEFAULT CURRENT_TIMESTAMP,
            fecha_guardado TEXT DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $log[] = 'sqlite:servicios';

    $db->exec("
        CREATE TABLE IF NOT EXISTS auditoria (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NULL,
            accion TEXT NOT NULL,
            tabla_afectada TEXT DEFAULT NULL,
            registro_id INTEGER DEFAULT NULL,
            descripcion TEXT,
            ip_origen TEXT DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            fecha_registro TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $log[] = 'sqlite:auditoria';

    $db->exec("
        CREATE TABLE IF NOT EXISTS sesiones (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            token_sesion TEXT NOT NULL UNIQUE,
            ip_origen TEXT DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            fecha_inicio TEXT DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion TEXT NOT NULL,
            activa INTEGER DEFAULT 1
        )
    ");
    $log[] = 'sqlite:sesiones';

    return $log;
}

function itform_migrate_seeds(PDO $db): array
{
    $log = [];
    $count = (int) $db->query('SELECT COUNT(*) FROM configuracion')->fetchColumn();
    if ($count === 0) {
        $db->prepare(
            'INSERT INTO configuracion (nombre_empresa, email_soporte, sitio_web, logo_login, logo_footer, color_primario, color_secundario, tema_defecto)
             VALUES (?,?,?,?,?,?,?,?)'
        )->execute([
            'ITS Panama', 'soporte@itspanama.net', 'www.itspanama.net',
            'logo.png', 'logo2.png', '#001F3F', '#4CAF50', 'light',
        ]);
        $log[] = 'seed:configuracion';
    }

    $admin = $db->prepare('SELECT id FROM usuarios WHERE username = ?');
    $admin->execute(['admin']);
    if (!$admin->fetch()) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->prepare(
            'INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo)
             VALUES (?,?,?,?,?,1)'
        )->execute(['admin', $hash, 'admin@itspanama.net', 'Administrador Principal', 'admin']);
        $log[] = 'seed:admin';
    }

    $tec = $db->prepare('SELECT id FROM usuarios WHERE username = ?');
    $tec->execute(['itspanama']);
    if (!$tec->fetch()) {
        $hash = password_hash('tecnico123', PASSWORD_DEFAULT);
        $db->prepare(
            'INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo)
             VALUES (?,?,?,?,?,1)'
        )->execute(['itspanama', $hash, 'soporte@itspanama.net', 'Técnico ITS', 'tecnico']);
        $log[] = 'seed:itspanama';
    }

    return $log;
}

// CLI
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    try {
        $log = itform_migrate();
        echo "IT-form migrate OK\n";
        foreach ($log as $line) {
            echo " - {$line}\n";
        }
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, 'MIGRATE_ERROR: ' . $e->getMessage() . "\n");
        exit(1);
    }
}
