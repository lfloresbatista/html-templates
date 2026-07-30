# Instalación IT-form

Modos: **on-premise**, **Docker BYOD** (MySQL compartido o SQLite), **Docker lab con MariaDB oficial**.

## Requisitos

| Componente | Versión |
|------------|---------|
| PHP | 8.1+ (`pdo`, `pdo_mysql` o `pdo_sqlite`, `session`, `json`, `mbstring`, `fileinfo`; `gd` recomendado) |
| Base de datos | MySQL/MariaDB compartido (**itformdb** / **itform_usr**) **o** SQLite |
| Servidor web | Apache 2.4+ o contenedor app (non-root :8080) |
| Proxy opcional | nginx-proxy, Traefik, Cloudflare Tunnel |

---

## Opción A — On-premise

### 1. Copiar aplicación

```bash
cp -r IT-form /var/www/html/itform
cd /var/www/html/itform
```

### 2. Base de datos (sysadmin)

```bash
# En el MySQL existente:
CREATE DATABASE itformdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'itform_usr'@'%' IDENTIFIED BY 'CLAVE_SEGURA';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX ON itformdb.* TO 'itform_usr'@'%';
FLUSH PRIVILEGES;
```

Tablas:

```bash
cp .env.example .env
# DB_DRIVER=mysql DB_HOST=... DB_NAME=itformdb DB_USER=itform_usr DB_PASS=...
php database/migrate.php
```

(Alternativa: importar `database/init_database.sql` como root y luego solo DML al user de la app.)

### 3. Permisos y Apache

Ver checklist de seguridad más abajo. DocumentRoot = carpeta IT-form, `AllowOverride All`.

### 4. Usuarios seed

| Usuario | Password | Rol |
|---------|----------|-----|
| admin | admin123 | admin |
| itspanama | tecnico123 | tecnico |

**Cambiar en producción.**

---

## Opción B — Docker BYOD (recomendado en homelab)

**Principio:** no se construye ni se levanta MySQL por app. Se usa el motor compartido o SQLite.

### B1. SQLite (máxima simplicidad)

```bash
cd IT-form
cp docker/.env.example .env
# DB_DRIVER=sqlite  (default en example)
docker compose -f docker/docker-compose.example.yml --env-file .env up -d --build
# http://localhost:8088/
```

La app (non-root, puerto **8080** interno) crea tablas al arrancar (`ITFORM_AUTO_MIGRATE=1`).

### B2. MySQL/MariaDB ya corriendo en otra stack

1. En MySQL: crear `itformdb` + `itform_usr` + password (como en Opción A §2).  
2. Poner el contenedor **web** en la **misma red Docker** que MySQL:

```yaml
# al final de un override o descomentar en compose
networks:
  default:
    name: kd-prod_net   # red de tu stack
    external: true
```

3. `.env`:

```env
DB_DRIVER=mysql
DB_HOST=mysql          # nombre DNS del servicio MySQL en esa red
DB_NAME=itformdb
DB_USER=itform_usr
DB_PASS=...
TRUST_PROXIES=1
```

4. `docker compose ... up -d --build`

Volúmenes **nombrados** (no binds de código): `itform_uploads`, `itform_storage`.  
Código = **dentro de la imagen**.

### Proxy (nginx-proxy / Traefik / Cloudflare)

```env
TRUST_PROXIES=1
VIRTUAL_HOST=itform.example.com
VIRTUAL_PORT=8080
HTTPS_METHOD=noredirect
# LETSENCRYPT_HOST=itform.example.com
```

- Contenedor escucha **8080** como non-root (compatible con `VIRTUAL_PORT`).
- Cookies Secure / HSTS respetan `X-Forwarded-Proto` si `TRUST_PROXIES=1`.
- Traefik: label `traefik.http.services.itform.loadbalancer.server.port=8080`.

### Imagen preconstruida (GHCR)

```bash
docker build -f docker/Dockerfile -t ghcr.io/USER/it-form:v1.0.1 .
docker push ghcr.io/USER/it-form:v1.0.1
# compose: image: ghcr.io/USER/it-form:v1.0.1  (sin build)
```

---

## Opción C — Docker lab con MariaDB oficial (opcional)

Solo si **no** tienes MySQL compartido. **No** se buildea MariaDB: imagen oficial + env.

```bash
docker compose -f docker/docker-compose.with-db.example.yml --env-file .env up -d --build
```

- `db`: `mariadb:11.4` + healthcheck + `MYSQL_DATABASE/USER/PASSWORD`
- `web`: build app, `depends_on: service_healthy`, migrate al arrancar

---

## Migraciones

```bash
php database/migrate.php
# o en contenedor:
docker exec <web> php database/migrate.php
```

Idempotente. Crea tablas/seeds; **no** crea el DATABASE/USER de MySQL.

---

## Health

- `GET /api/health.php` — liveness  
- `GET /api/health.php?ready=1` — exige BD OK (503 si no)

---

## Checklist seguridad producción

- [ ] HTTPS (proxy) + `TRUST_PROXIES=1` solo detrás de proxy confiable  
- [ ] Passwords seed rotadas  
- [ ] `ALLOW_PUBLIC_SAVE=0`  
- [ ] User BD least-privilege (si migrate auto: necesita CREATE/ALTER iniciales)  
- [ ] Backups `itformdb` + volumen `uploads`  
- [ ] Contenedor non-root (UID www-data, puerto 8080)

---

## Flujo de uso

1. Admin → configuración empresa/logo  
2. Técnico login → formulario → **Guardar**  
3. **Imprimir** / **Compartir|Descargar**
