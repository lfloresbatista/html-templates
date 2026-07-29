# Instalación IT-form

Dos modos soportados: **on-premise** (LAMP/LEMP) y **Docker**.

## Requisitos

| Componente | Versión |
|------------|---------|
| PHP | 8.1+ (`pdo`, `pdo_mysql` o `pdo_sqlite`, `session`, `json`, `mbstring`, `fileinfo`; `gd` recomendado) |
| Base de datos | MySQL 8 / MariaDB 10.4+ (**nombre:** `itformdb`, **usuario:** `itform_usr`) |
| Servidor web | Apache 2.4+ (mod_rewrite) o Nginx + PHP-FPM |

---

## Opción A — On-premise

### 1. Copiar aplicación

```bash
cp -r IT-form /var/www/html/itform
cd /var/www/html/itform
```

### 2. Base de datos MySQL/MariaDB

```bash
sudo mysql -u root -p <<'SQL'
CREATE DATABASE IF NOT EXISTS itformdb
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'itform_usr'@'localhost' IDENTIFIED BY 'CLAVE_SEGURA';
GRANT SELECT, INSERT, UPDATE, DELETE ON itformdb.* TO 'itform_usr'@'localhost';
FLUSH PRIVILEGES;
SQL

mysql -u root -p itformdb < database/init_database.sql
```

> El script `database/init_database.sql` crea tablas en `itformdb`.  
> Si el usuario `itform_usr` no puede crear DB, ejecute el SQL como root y otorgue permisos DML.

### 3. Entorno

```bash
cp .env.example .env
# Editar:
# DB_DRIVER=mysql
# DB_HOST=127.0.0.1
# DB_NAME=itformdb
# DB_USER=itform_usr
# DB_PASS=CLAVE_SEGURA
# ALLOW_PUBLIC_SAVE=0
```

### 4. Permisos

```bash
sudo chown -R www-data:www-data /var/www/html/itform
sudo find /var/www/html/itform -type d -exec chmod 755 {} \;
sudo find /var/www/html/itform -type f -exec chmod 644 {} \;
sudo chmod 640 /var/www/html/itform/.env
sudo mkdir -p storage/db storage/logs uploads
sudo chown -R www-data:www-data storage uploads
sudo chmod 750 storage storage/db storage/logs uploads
```

### 5. Apache (ejemplo)

```apache
<VirtualHost *:80>
    ServerName itform.local
    DocumentRoot /var/www/html/itform
    <Directory /var/www/html/itform>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Habilite HTTPS (Let’s Encrypt) y `FORCE_SECURE_COOKIE=1` en producción.

### 6. Usuarios iniciales

| Usuario | Password | Rol |
|---------|----------|-----|
| admin | admin123 | admin |
| itspanama | tecnico123 | tecnico |

**Cambiar contraseñas de inmediato** (Admin → Usuarios).

### 7. Checklist seguridad producción

- [ ] HTTPS + cookies seguras
- [ ] `.env` no legible vía web
- [ ] `ALLOW_PUBLIC_SAVE=0`
- [ ] Usuario BD least-privilege (`itform_usr`)
- [ ] Backups de `itformdb` y `uploads/`
- [ ] Rotar passwords seed

---

## Opción B — Docker

Archivos en `docker/`:

- `Dockerfile`
- `docker-compose.example.yml`
- `.env.example`
- `docker-entrypoint.sh`

### Pasos

```bash
cd IT-form
cp docker/.env.example .env
# Editar DB_PASS y MYSQL_ROOT_PASSWORD

docker compose -f docker/docker-compose.example.yml --env-file .env up -d --build
```

- App: `http://localhost:8088/` (o `APP_PORT`)
- DB: servicio `db`, database **`itformdb`**, user **`itform_usr`**
- Init SQL se carga en el primer arranque del volumen

Volúmenes de datos:

- `itform_db_data` (MySQL)
- código montado en `/var/www/html` (uploads/storage persisten en el host)

Detener:

```bash
docker compose -f docker/docker-compose.example.yml --env-file .env down
```

---

## Flujo de uso

1. Admin configura empresa/logo: `admin/configuracion.php`
2. Técnico inicia sesión
3. Completa formulario → **Guardar**
4. Tras guardar se habilitan **Imprimir** y **Compartir** (en móvil usa Web Share API si está disponible; si no, **Descargar**)

---

## Lab SQLite (solo desarrollo)

```bash
# .env
DB_DRIVER=sqlite
DB_PATH=/ruta/absoluta/storage/db/itform.sqlite

php database/init_sqlite.php
php -S 127.0.0.1:8080 router.php
```

---

## Soporte de rutas sensibles

El router/`\.htaccess` bloquea: `.env`, `config/`, `database/`, `storage/`, `docker/`, `*.sql`.  
Los logos públicos viven en `uploads/`.
