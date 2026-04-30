# Guía de Instalación y Configuración

## Requisitos del Sistema

- **Servidor Web**: Apache 2.4+ o Nginx
- **PHP**: 7.4 o superior
- **Base de Datos**: MySQL 5.7+ o MariaDB 10.3+
- **Extensiones PHP requeridas**: PDO, pdo_mysql, json

## Instalación Paso a Paso

### 1. Clonar o Copiar Archivos

```bash
# Copiar archivos al directorio del servidor web
cp -r IT-form /var/www/html/
```

### 2. Configurar Base de Datos

#### Opción A: Usando MySQL/MariaDB CLI

```bash
# Ingresar a MySQL
mysql -u root -p

# Ejecutar script de inicialización
source /var/www/html/IT-form/database/init_database.sql;

# Salir de MySQL
exit;
```

#### Opción B: Usando phpMyAdmin

1. Abrir phpMyAdmin en el navegador
2. Ir a la pestaña "Importar"
3. Seleccionar el archivo `database/init_database.sql`
4. Hacer clic en "Continuar"

### 3. Configurar Conexión a la Base de Datos

Editar el archivo `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'itspanama_db');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_CHARSET', 'utf8mb4');
```

### 4. Establecer Permisos

```bash
# Establecer permisos correctos
chmod -R 755 /var/www/html/IT-form
chown -R www-data:www-data /var/www/html/IT-form

# El archivo .login_attempts debe ser escribible
touch /var/www/html/IT-form/.login_attempts
chmod 666 /var/www/html/IT-form/.login_attempts
```

### 5. Configurar Apache (Opcional)

Crear archivo `.htaccess` en la raíz:

```apache
RewriteEngine On

# Redirigir al login si no está autenticado
RewriteCond %{REQUEST_URI} ^/admin/
RewriteRule ^$ admin/login.php [L]

# Proteger archivos de configuración
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "\.(sql|log|ini)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

## Usuarios por Defecto

### Administrador
- **Usuario**: `admin`
- **Contraseña**: `admin123`
- **Rol**: Administrador (acceso completo)

### Técnico
- **Usuario**: `itspanama`
- **Contraseña**: `tecnico123`
- **Rol**: Técnico

**⚠️ IMPORTANTE**: Cambie las contraseñas inmediatamente después de la instalación!

## Estructura de la Base de Datos

### Tablas Principales

1. **configuracion** - Configuración de empresa (logo, colores, etc.)
2. **usuarios** - Usuarios del sistema con roles y permisos
3. **servicios** - Informes de servicio técnico con numeración secuencial
4. **auditoria** - Logs de auditoría de todas las acciones
5. **sesiones** - Gestión de sesiones de usuario

### Numeración Secuencial

Los servicios se numeran automáticamente con el formato: `AAAA-MM-NNNN`
- `AAAA`: Año actual
- `MM`: Mes actual (01-12)
- `NNNN`: Consecutivo mensual (0001-9999)

Ejemplo: `2025-04-0001`

## Características del Sistema

### Frontend (Formulario Público)
- ✅ Formulario responsive y accesible
- ✅ Modo claro/oscuro (toggle)
- ✅ Generación de PDFs
- ✅ Guardado en base de datos con numeración automática
- ✅ Validación en tiempo real
- ✅ Enlace al panel administrativo

### Panel Administrativo
- ✅ Dashboard con estadísticas
- ✅ Autenticación segura con hash de contraseñas
- ✅ Protección contra fuerza bruta (bloqueo temporal)
- ✅ Gestión de usuarios (CRUD)
- ✅ Configuración de empresa (logos, colores)
- ✅ Listado de servicios con filtros
- ✅ Auditoría completa de acciones
- ✅ Modo claro/oscuro

### Seguridad Implementada
- ✅ Hash de contraseñas con bcrypt
- ✅ Protección CSRF (sesiones PHP)
- ✅ Prepared statements (previene SQL Injection)
- ✅ Rate limiting para login
- ✅ Bloqueo temporal de cuentas
- ✅ Sanitización de entradas
- ✅ Escape de salidas (XSS prevention)
- ✅ Cookies seguras (HttpOnly)

## Personalización

### Cambiar Logos

1. Reemplazar `logo.png` para el encabezado
2. Reemplazar `logo2.png` para el footer
3. O usar el panel administrativo → Configuración

### Cambiar Colores

Desde el panel administrativo:
1. Iniciar sesión como admin
2. Ir a Configuración
3. Modificar colores primario/secundario
4. Guardar cambios

O editar directamente en `styles.css`:

```css
:root {
    --color-primary: #001F3F;
    --color-secondary: #4CAF50;
}
```

### Agregar Más Usuarios

Desde phpMyAdmin o MySQL:

```sql
INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo)
VALUES (
    'nuevo_usuario',
    '$2y$10$' || password_hash('contraseña_segura', PASSWORD_DEFAULT),
    'email@empresa.com',
    'Nombre Completo',
    'tecnico',
    TRUE
);
```

O desde PHP:

```php
<?php
require_once 'config/database.php';
$db = getDB();

$passwordHash = password_hash('contraseña_segura', PASSWORD_DEFAULT);

$sql = "INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol) 
        VALUES (:user, :pass, :email, :nombre, :rol)";
$stmt = $db->prepare($sql);
$stmt->execute([
    ':user' => 'nuevo_usuario',
    ':pass' => $passwordHash,
    ':email' => 'email@empresa.com',
    ':nombre' => 'Nombre Completo',
    ':rol' => 'tecnico'
]);
?>
```

## Solución de Problemas

### Error de Conexión a la BD

1. Verificar que MySQL/MariaDB esté corriendo
2. Confirmar credenciales en `config/database.php`
3. Asegurar que la base de datos fue creada
4. Verificar permisos del usuario de BD

### Los PDFs no se generan

1. Verificar que JavaScript esté habilitado
2. Revisar consola del navegador para errores
3. Confirmar que los archivos en `dist/` existen

### Login no funciona

1. Verificar que las tablas fueron creadas
2. Confirmar que hay usuarios en la BD
3. Revisar logs de error de PHP
4. Verificar permisos de escritura para `.login_attempts`

### Modo Oscuro no persiste

1. Verificar que localStorage esté habilitado
2. Limpiar caché del navegador
3. Verificar que JavaScript esté habilitado

## Mantenimiento

### Limpieza de Sesiones Expiradas

Ejecutar periódicamente:

```sql
CALL sp_limpiar_sesiones_expiradas();
```

O configurar un cron job:

```bash
# Ejecutar diariamente a las 2 AM
0 2 * * * mysql -u root -p itspanama_db -e "CALL sp_limpiar_sesiones_expiradas();"
```

### Backup de la Base de Datos

```bash
mysqldump -u root -p itspanama_db > backup_$(date +%Y%m%d).sql
```

### Restaurar Backup

```bash
mysql -u root -p itspanama_db < backup_20250430.sql
```

## Soporte

Para soporte técnico o reportar errores:
- Email: soporte@itspanama.net
- Web: www.itspanama.net

## Licencia

Ver archivo LICENSE para más detalles.
