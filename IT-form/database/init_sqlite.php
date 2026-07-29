<?php
/**
 * Inicializa esquema SQLite (dev/test) con usuarios seed correctos.
 * Uso: php database/init_sqlite.php
 */
require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/database.php';

putenv('DB_DRIVER=sqlite');
$_ENV['DB_DRIVER'] = 'sqlite';

$path = itform_env('DB_PATH', dirname(__DIR__) . '/storage/db/itform.sqlite');
if (file_exists($path)) {
    unlink($path);
}
Database::resetInstance();

$db = getDB();

$db->exec("
CREATE TABLE configuracion (
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
);

CREATE TABLE usuarios (
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
);

CREATE TABLE servicios (
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
    fecha_actualizacion TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE auditoria (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NULL,
    accion TEXT NOT NULL,
    tabla_afectada TEXT DEFAULT NULL,
    registro_id INTEGER DEFAULT NULL,
    descripcion TEXT,
    ip_origen TEXT DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    fecha_registro TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE sesiones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    token_sesion TEXT NOT NULL UNIQUE,
    ip_origen TEXT DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    fecha_inicio TEXT DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion TEXT NOT NULL,
    activa INTEGER DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
");

$adminHash = password_hash('admin123', PASSWORD_DEFAULT);
$tecHash = password_hash('tecnico123', PASSWORD_DEFAULT);

$db->prepare('INSERT INTO configuracion (nombre_empresa) VALUES (?)')->execute(['ITS Panama']);
$db->prepare('INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo) VALUES (?,?,?,?,?,1)')
    ->execute(['admin', $adminHash, 'admin@itspanama.net', 'Administrador Principal', 'admin']);
$db->prepare('INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo) VALUES (?,?,?,?,?,1)')
    ->execute(['itspanama', $tecHash, 'soporte@itspanama.net', 'Técnico ITS', 'tecnico']);

echo "SQLite OK: {$path}\n";
echo "Users: admin/admin123 , itspanama/tecnico123\n";
// verify
echo 'admin verify: ', password_verify('admin123', $adminHash) ? 'OK' : 'FAIL', "\n";
echo 'tec verify: ', password_verify('tecnico123', $tecHash) ? 'OK' : 'FAIL', "\n";
