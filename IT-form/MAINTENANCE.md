# IT-Form — Documentación de mantenimiento y soporte (desarrolladores)

| Campo | Valor |
|-------|--------|
| **Producto** | IT-Form (formulario de servicio técnico) |
| **Versión documentada** | v1.0.1 |
| **Repositorio** | https://github.com/lfloresbatista/html-templates |
| **Ruta en monorepo** | `IT-form/` |
| **Audiencia** | Desarrolladores, DevOps, soporte L2/L3 |
| **Documentos relacionados** | `README.md`, `INSTALACION.md`, release notes `v1.0.1` |

Este documento describe la arquitectura, convenciones, seguridad, operaciones y procedimientos de soporte de la aplicación. Para instalar desde cero ver **INSTALACION.md**. Para overview de usuario/dev rápido ver **README.md**.

---

## 1. Propósito del sistema

IT-Form permite:

1. Registrar informes de servicio técnico (cliente, ticket, diagnóstico, trabajo, firmas).
2. Persistirlos en base de datos con numeración secuencial `AAAA-MM-NNNN`.
3. Generar PDF profesional (servidor TCPDF y/o cliente html2pdf).
4. Administrar usuarios, servicios, branding de empresa y auditoría.

Flujos de usuario:

```
[Técnico] login → formulario → Guardar → Imprimir / Compartir|Descargar
[Admin]   login → dashboard / servicios / usuarios / configuración / auditoría
```

---

## 2. Stack técnico

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8.1+ (probado 8.2 / 8.5) |
| Frontend | HTML5, CSS3, JS vanilla (sin bundler de app) |
| PDF cliente | html2pdf.js + FileSaver (`dist/`) |
| PDF servidor | TCPDF (`tcpdf/`) |
| BD producción | MySQL 8 / MariaDB 10.4+ — DB **`itformdb`**, user **`itform_usr`** |
| BD lab | SQLite (`storage/db/itform.sqlite`) vía `DB_DRIVER=sqlite` |
| Web server | Apache 2.4 (`mod_rewrite`, `mod_headers`) o `php -S` + `router.php` |
| Contenedores | Docker: `docker/Dockerfile` + `docker/docker-compose.example.yml` |
| Config | Variables de entorno (`.env` / entorno del contenedor) |

Extensiones PHP requeridas: `pdo`, `pdo_mysql` **o** `pdo_sqlite`, `session`, `json`, `mbstring`, `fileinfo`.  
Recomendadas: `gd` (logos PNG en TCPDF sin conversión externa).

---

## 3. Estructura del código

```
IT-form/
├── index.php                 # Frontend principal (branding + formulario)
├── index.html / login.html   # Redirects de compatibilidad
├── script.js                 # UX: validación, guardar, imprimir, share/download
├── styles.css                # Estilos formulario + responsive
├── print_pdf.php             # Generación PDF servidor (TCPDF)
├── procesar_login.php        # Redirect → admin/login.php (legacy)
├── router.php                # Front controller deny-list para php -S
├── .htaccess                 # Deny paths + headers (Apache)
├── .env.example              # Plantilla env (no secretos reales)
│
├── admin/                    # Panel administrativo (sesión PHP)
│   ├── auth.php              # Login/logout JSON, RBAC helpers, lockout
│   ├── login.php / index.php
│   ├── servicios.php / usuarios.php / configuracion.php / auditoria.php
│   ├── layout_header.php / layout_footer.php / admin.css
│
├── api/                      # Endpoints JSON (misma origin, cookies)
│   ├── session.php           # CSRF + estado de sesión
│   ├── config.php            # Branding público (sin secretos)
│   └── guardar_servicio.php  # Alta de servicios (auth + CSRF)
│
├── config/                   # No servir por web
│   ├── env.php               # Carga .env
│   ├── database.php          # PDO singleton mysql|sqlite
│   ├── security.php          # Sesión, CSRF, rate-limit, headers, escape
│   └── company.php           # Branding, upload logo, paths
│
├── database/
│   ├── init_database.sql     # Schema MySQL (itformdb)
│   └── init_sqlite.php       # Bootstrap lab SQLite
│
├── docker/
│   ├── Dockerfile
│   ├── docker-compose.example.yml
│   ├── .env.example
│   └── docker-entrypoint.sh
│
├── storage/                  # Runtime (db sqlite, logs rate-limit) — no público
├── uploads/                  # Logos subidos (solo imágenes vía web)
├── dist/                     # Vendors JS minificados (versionados)
└── tcpdf/                    # Vendor PDF (examples/tools denegados en web)
```

### Convenciones

- Prefijo de helpers PHP: `itform_*`.
- Escape HTML de salida: `itform_e()` / `htmlspecialchars` ENT_QUOTES UTF-8.
- Entradas mutadoras: validar **CSRF** (`csrf_token` o header si se añade).
- No devolver mensajes PDO/`Exception` al cliente; solo `error_log`.
- Archivos sensibles con `.htaccess` `Require all denied` en directorio.

---

## 4. Configuración (variables de entorno)

Fuente: `config/env.php` lee `IT-form/.env` si existe; **no sobrescribe** variables ya definidas en el entorno del proceso/contenedor.

| Variable | Default | Descripción |
|----------|---------|-------------|
| `DB_DRIVER` | `mysql` | `mysql` \| `sqlite` |
| `DB_HOST` | `127.0.0.1` | Host MySQL |
| `DB_PORT` | `3306` | Puerto |
| `DB_NAME` | `itformdb` | Nombre de base |
| `DB_USER` | `itform_usr` | Usuario least-privilege |
| `DB_PASS` | _(vacío)_ | Password BD |
| `DB_CHARSET` | `utf8mb4` | Charset PDO |
| `DB_PATH` | `storage/db/itform.sqlite` | Solo SQLite |
| `SESSION_NAME` | `ITFORMSESSID` | Nombre cookie sesión |
| `SESSION_IDLE_MINUTES` | `60` | Timeout inactividad |
| `FORCE_SECURE_COOKIE` | `0` | Forzar Secure/HSTS-like cookie |
| `ALLOW_PUBLIC_SAVE` | `0` | `1` = guardar sin login (**no prod**) |
| `LOGIN_MAX_ATTEMPTS` | `5` | Fallos antes de lockout |
| `LOGIN_LOCK_MINUTES` | `15` | Duración bloqueo cuenta |
| `API_RATE_MAX` | `30` | Tope rate-limit API save |
| `API_RATE_WINDOW` | `60` | Ventana rate-limit (s) |

Plantillas:

- On-prem / genérico: `.env.example`
- Docker: `docker/.env.example` → copiar a `IT-form/.env`

**Nunca** commitear `.env` real (está en `.gitignore` del monorepo).

---

## 5. Modelo de datos

### 5.1 Tablas

| Tabla | Uso |
|-------|-----|
| `configuracion` | Branding: empresa, RUC, email, web, tel, dirección, logos, colores, tema |
| `usuarios` | Cuentas, roles (`admin`\|`tecnico`\|`usuario`), lockout, bcrypt |
| `servicios` | Informes técnicos + `numero_secuencia` único |
| `auditoria` | Log de acciones (LOGIN, CREATE, UPDATE, …) |
| `sesiones` | Tabla opcional de tokens (schema presente; auth actual usa sesión PHP) |

### 5.2 Numeración de servicios

- Formato: `YYYY-MM-NNNN` (ej. `2026-07-0001`).
- **MySQL:** trigger `before_insert_servicios` si `numero_secuencia` vacío.
- **SQLite:** generación en aplicación (`itform_next_sequence()` en `config/database.php`).

### 5.3 Migraciones

No hay framework de migraciones. Cambios de schema:

1. Actualizar `database/init_database.sql` y `database/init_sqlite.php`.
2. Para installs existentes: script SQL/ALTER documentado en el PR (ej. columna `ruc` se auto-añade en `admin/configuracion.php` si falta).

Seeds de instalación (cambiar en producción):

| Usuario | Password seed | Rol |
|---------|---------------|-----|
| `admin` | `admin123` | admin |
| `itspanama` | `tecnico123` | tecnico |

Hashes con `password_hash(..., PASSWORD_DEFAULT)` (bcrypt/argon según PHP).

---

## 6. Autenticación y autorización

### 6.1 Flujo login

1. `GET admin/login.php` → emite CSRF en meta/hidden.
2. `POST admin/auth.php` `action=login` + `username`, `password`, `csrf_token`.
3. Respuesta JSON: `{ success, message|error, redirect }`.
4. Sesión PHP: `logged_in`, `user_id`, `username`, `nombre`, `email`, `rol`, `login_time`, `last_activity`, `csrf_token` (regenerado).

### 6.2 Helpers (`admin/auth.php`)

| Función | Uso |
|---------|-----|
| `isAuthenticated()` | Sesión válida |
| `isAdmin()` | Rol `admin` |
| `requireAuth()` | Redirect login o 401 JSON |
| `requireAdmin()` | 403 si no admin |
| `currentUserId()` | ID o null |
| `logAudit(...)` | Inserta en `auditoria` |
| `processLogin` / `logout` | Núcleo auth |

### 6.3 Controles de fuerza bruta

- Contador `usuarios.intentos_fallidos`.
- `bloqueado_hasta` tras `LOGIN_MAX_ATTEMPTS`.
- Rate-limit adicional por IP/acción en `storage/logs/rate_limit.json` (`itform_rate_limit`).

### 6.4 RBAC efectivo

| Recurso | tecnico | admin |
|---------|---------|-------|
| Formulario + guardar servicio | Sí | Sí |
| PDF servidor | Sí (auth) | Sí |
| Dashboard / listado servicios | Sí | Sí |
| Usuarios / configuración / auditoría | No (403) | Sí |

### 6.5 Legacy

- `login.html` y `procesar_login.php` solo redirigen a `admin/login.php`.
- **No** existe archivo `secret` de contraseñas (eliminado en v1.0.1 del árbol e historial).

---

## 7. APIs y contratos

Todas same-origin; cookies de sesión; `Content-Type` JSON en respuestas API.

### `GET api/session.php`

```json
{
  "success": true,
  "csrf_token": "<hex 64>",
  "authenticated": true,
  "user": { "username": "...", "nombre": "...", "rol": "tecnico" },
  "allow_public_save": false
}
```

### `GET api/config.php`

Branding público para UI (sin credenciales):

```json
{
  "success": true,
  "data": {
    "nombre_empresa": "...",
    "email_soporte": "...",
    "sitio_web": "...",
    "telefono": "...",
    "direccion": "...",
    "ruc": "...",
    "logo_url": "uploads/logo_company.png",
    "logo_footer_url": "logo2.png",
    "color_primario": "#001F3F",
    "color_secundario": "#4CAF50",
    "tema_defecto": "light"
  }
}
```

### `POST api/guardar_servicio.php`

- Requiere sesión (si `ALLOW_PUBLIC_SAVE=0`) + CSRF + rate-limit.
- Campos: `cliente`, `fecha`, `direccion`, `ticket`, `reporte`, `diagnostico`, `trabajoRealizado`, `observaciones`, `recibidoConforme`, `firmaTecnico`, `csrf_token`.
- Éxito:

```json
{
  "success": true,
  "message": "Servicio guardado exitosamente",
  "data": {
    "id": 1,
    "numero_secuencia": "2026-07-0001",
    "fecha_guardado": "...",
    "cliente": "...",
    "ticket": "..."
  }
}
```

Errores típicos: `400` validación, `401` no auth, `403` CSRF, `429` rate-limit, `500` error genérico.

### `POST print_pdf.php`

- Auth + CSRF (+ rate-limit).
- Mismos campos de formulario.
- **Firma técnico en PDF:** nombre de sesión (`$_SESSION['nombre']`) prioritario sobre POST.
- **Recibido conforme:** contacto del cliente (`recibidoConforme`).
- Respuesta: binario PDF (`Content-Disposition: attachment` vía TCPDF `Output(..., 'D')`).
- Branding leído de `configuracion` (logo → JPEG companion en `uploads/` si hace falta).

### `POST admin/auth.php`

- `action=login` → JSON.
- `action=logout` → redirect `login.php` (CSRF required).

---

## 8. Frontend (formulario)

### Archivos clave

| Archivo | Responsabilidad |
|---------|-----------------|
| `index.php` | SSR branding, CSRF inicial, labels, botones |
| `script.js` | Estado de guardado, PDF blob, share API |
| `styles.css` | Layout + sticky actions en móvil |

### Estado JS relevante (`script.js`)

| Estado | Significado |
|--------|-------------|
| `saved` | Último save OK → habilita Imprimir/Compartir |
| `lastSave` | Payload `data` del API (incl. `numero_secuencia`) |
| `lastPdfBlob` | Blob PDF cacheado para share/print |
| `shareSupported` | `navigator.share` disponible |

### UX de botones

1. **Guardar** — siempre disponible (requiere login en backend).
2. Tras save OK → habilita **Imprimir** y **Compartir**.
3. Cualquier `input` posterior invalida `saved` (hay que volver a guardar).
4. **Imprimir** — abre blob PDF en pestaña (fallback download si popup bloqueado).
5. **Compartir** — Web Share con `File` PDF si `canShare({files})`; si no, descarga y el label pasa a “Descargar”.

### PDF cliente vs servidor

- Share/print intentan **servidor** (`print_pdf.php`) si hay sesión; fallback **html2pdf** sobre el DOM del form.
- Formato preferido de negocio: servidor (carta, logo empresa, firmas fijas).

---

## 9. Panel admin

| Ruta | Rol | Función |
|------|-----|---------|
| `admin/login.php` | público | Login |
| `admin/index.php` | auth | Dashboard métricas |
| `admin/servicios.php` | auth | Listado + cambio de estado |
| `admin/usuarios.php` | admin | CRUD usuarios (soft-delete = desactivar) |
| `admin/configuracion.php` | admin | Empresa + upload logos (`multipart`) |
| `admin/auditoria.php` | admin | Lectura log |

Layouts compartidos: `layout_header.php` / `layout_footer.php` + `admin.css` (responsive &lt;900px menú horizontal).

Upload de logo:

- MIME allowlist: png/jpeg/webp/gif.
- Máx. 2 MB.
- Destino: `uploads/logo_company.<ext>`.
- Companion JPEG: `uploads/logo_company_pdf.jpg` para TCPDF sin GD.

---

## 10. Seguridad (diseño y checklist)

### Controles implementados

| Control | Dónde |
|---------|--------|
| Password hashing | `password_hash` / `password_verify` |
| CSRF | `itform_csrf_token` / `itform_csrf_validate` |
| Session hardening | httponly, samesite=Lax, strict mode, regenerate on login |
| Idle timeout | `SESSION_IDLE_MINUTES` |
| Rate limiting | archivo JSON en `storage/logs/` |
| Account lockout | columnas usuario |
| XSS output encoding | `itform_e` |
| SQL injection | PDO prepared statements |
| Path traversal logos | sanitiza `..`, basename uploads |
| Security headers | CSP, nosniff, frame-options, referrer, permissions-policy, HSTS si HTTPS |
| Surface reduction | deny `.env`, `config/`, `database/`, `storage/`, `docker/`, tcpdf examples/tools |

### Superficie denegada (Apache / router)

- Exact: `/.env`, `/.env.example`, `/secret`, `/router.php`, …
- Prefijos: `/config/`, `/storage/`, `/database/`, `/docker/`, `/tcpdf/examples/`, `/tcpdf/tools/`
- Uploads: solo imágenes; no `.php`

### Checklist soporte seguridad

- [ ] HTTPS en producción + `FORCE_SECURE_COOKIE=1`
- [ ] Passwords seed rotadas
- [ ] `ALLOW_PUBLIC_SAVE=0`
- [ ] Backups de `itformdb` + `uploads/`
- [ ] Permisos: `.env` 640; `storage`/`uploads` escribibles solo por www-data
- [ ] Revisar `auditoria` ante incidentes
- [ ] No exponer phpinfo ni listados de directorio

### Historial sensible

En v1.0.1 se eliminó del historial git el archivo `IT-form/secret` (credencial legacy). Clones antiguos deben re-clonar o resetear a `origin/main`.

---

## 11. Despliegue y entornos

### 11.1 On-premise

Ver `INSTALACION.md` § Opción A.

Puntos de mantenimiento:

- Rotar logs de Apache/PHP.
- Cron opcional: `CALL sp_limpiar_sesiones_expiradas();` (MySQL).
- Backup: `mysqldump itformdb` + tar de `uploads/`.

### 11.2 Docker

```bash
cd IT-form
cp docker/.env.example .env
# editar secretos
docker compose -f docker/docker-compose.example.yml --env-file .env up -d --build
```

Servicios:

| Service | Imagen / build | Puerto |
|---------|----------------|--------|
| `web` | build `docker/Dockerfile` | `APP_PORT`→80 (default 8088) |
| `db` | `mariadb:11.4` | interno; init via `init_database.sql` |

Volúmenes: datos MySQL nombrados; código montado en `/var/www/html` (uploads/storage persisten en host).

### 11.3 Lab `php -S`

```bash
php database/init_sqlite.php   # si SQLite
php -S 127.0.0.1:8080 router.php
```

**Siempre** usar `router.php` para aplicar deny-list (el server built-in no lee `.htaccess`).

### 11.4 Registry (opcional futuro)

Se puede publicar la imagen web en **GHCR** (`ghcr.io/<user>/it-form:<tag>`) y reemplazar `build:` por `image:` en compose. Ver notas de arquitectura en conversaciones de release; no es obligatorio en v1.0.1.

---

## 12. Desarrollo local recomendado

1. Clonar monorepo `html-templates`.
2. Trabajar en `IT-form/`.
3. SQLite lab o Docker Compose.
4. No commitear: `.env`, `storage/db/*.sqlite`, `storage/logs/*`, `uploads/*` (excepto placeholders), `pdfs-test/`.
5. Probar:
   - Login admin/tecnico
   - Guardar servicio + secuencia
   - PDF contiene branding y firmas correctas
   - Deny de paths sensibles
   - Técnico no entra a `usuarios.php` (403)
6. Cambios de schema: actualizar ambos inits + notas de upgrade.
7. Estilo de commits sugerido: Conventional Commits (`feat:`, `fix:`, `security:`, `docs:`).

### Puntos de extensión frecuentes

| Necesidad | Archivos típicos |
|-----------|------------------|
| Nuevo campo en informe | `index.php`, `script.js`, `api/guardar_servicio.php`, `print_pdf.php`, schema `servicios` |
| Nuevo rol | `auth.php` RBAC + páginas admin |
| Nuevo branding | `config/company.php`, `admin/configuracion.php` |
| Otro formato PDF | `print_pdf.php` (clase `ITServicePDF`) |
| i18n | Hoy strings en ES embebidos; no hay capa i18n |

---

## 13. Observabilidad y troubleshooting

### Logs

| Fuente | Ubicación |
|--------|-----------|
| PHP `error_log` | Config del host / Docker logs del contenedor `web` |
| Rate-limit | `storage/logs/rate_limit.json` |
| Auditoría de negocio | tabla `auditoria` |
| Apache/nginx | logs del virtual host |

### Síntomas comunes

| Síntoma | Causas probables | Acción |
|---------|------------------|--------|
| “Error de conexión a la base de datos” | `.env` mal, DB caída, user/pass | Verificar `DB_*`, `docker compose ps`, grants `itform_usr` |
| Login siempre incorrecto | Seeds no cargados / hash viejo | Reimportar init o reset password con `password_hash` |
| 401 al guardar | Sesión expirada / cookies blocked | Re-login; revisar `SESSION_IDLE_MINUTES` y HTTPS/Secure |
| 403 CSRF | Token viejo / multi-pestaña | Recargar página; un solo origen |
| PDF sin logo / error Imagick-GD | PNG sin GD | Subir logo y generar companion JPEG; o instalar `gd` |
| PDF firmas incorrectas | No hay sesión al imprimir | Imprimir solo tras login; técnico sale de sesión |
| 403 en admin/config | Usuario no admin | Usar cuenta `admin` |
| Logo no se ve | Path uploads / permisos | `chown www-data uploads`; URL `uploads/...` |
| Secuencia duplicada (raro MySQL) | Trigger ausente | Reaplicar trigger del init SQL |

### Comandos útiles

```bash
# Salud contenedores
docker compose -f docker/docker-compose.example.yml --env-file .env ps
docker compose ... logs -f web

# PHP lint
find . -name '*.php' ! -path './tcpdf/*' -print0 | xargs -0 -n1 php -l

# Verificar que secret no reapareció
test ! -f secret && git log --all -- IT-form/secret | head

# Reset lab SQLite
php database/init_sqlite.php
```

---

## 14. Procedimientos de soporte

### L1 (operaciones básicas)

- Reset de password de usuario (admin → Usuarios o SQL con `password_hash`).
- Desbloquear cuenta: `intentos_fallidos=0`, `bloqueado_hasta=NULL`.
- Verificar espacio disco en `uploads/` y logs.

### L2 (aplicación)

- Revisar `auditoria` y logs PHP.
- Validar `.env` y conectividad BD.
- Regenerar logo PDF companion si branding roto.
- Confirmar versión desplegada (`git describe --tags` / imagen Docker tag).

### L3 (desarrollo)

- Parches de seguridad/schema.
- Cambios de PDF o flujos JS.
- Release: tag SemVer, notas, (opcional) imagen GHCR.

### Escalamiento de incidentes de seguridad

1. Revocar credenciales comprometidas (BD, admin, tokens).
2. Revisar `auditoria` y accesos web.
3. Rotar secrets; no commitear evidencia sensible.
4. Si hubo leak en git: history rewrite + force-push controlado + avisar clones.

---

## 15. Versionado y release

- Tags del monorepo: `v1.0.1` documenta IT-Form endurecido.
- Mensajes de release deben listar features + mitigaciones de seguridad.
- Tras rewrite de historial: comunicar force-push a colaboradores.

Artefactos de release típicos:

- Código en `main` + tag
- GitHub Release notes
- (Opcional) imagen `ghcr.io/<org>/it-form:vX.Y.Z`

---

## 16. Dependencias de terceros

| Componente | Licencia aprox. | Notas |
|------------|-----------------|-------|
| TCPDF | LGPL v3 | Vendor completo en repo; no servir examples |
| html2pdf.js | según upstream | `dist/` versionado para deploy sin npm |
| FileSaver.js | según upstream | idem |
| Código app | MIT (ver monorepo) | |

Al actualizar TCPDF/html2pdf: probar PDF servidor y cliente; mantener deny de examples.

---

## 17. Mapa rápido de “dónde toco X”

| Quiero… | Empiezo en… |
|---------|-------------|
| Cambiar textos del form | `index.php` |
| Lógica de botones/share | `script.js` |
| Estilos / móvil | `styles.css`, `admin/admin.css` |
| Reglas de guardado | `api/guardar_servicio.php` |
| Diseño PDF | `print_pdf.php` |
| Login / roles | `admin/auth.php` |
| Branding | `admin/configuracion.php`, `config/company.php` |
| Conexión BD | `config/database.php`, `.env` |
| Headers/CSRF/sesión | `config/security.php` |
| Schema | `database/init_*.sql/php` |
| Empaquetado | `docker/*` |
| Hardening web | `.htaccess`, `router.php` |

---

## 18. Contacto y ownership

| Rol | Notas |
|-----|--------|
| Maintainer repo | `@lfloresbatista` (GitHub) |
| Issues | GitHub Issues del monorepo `html-templates` |
| Soporte producto (ejemplo seed) | Configurable en `configuracion.email_soporte` |

---

## 19. Historial de este documento

| Fecha | Cambio |
|-------|--------|
| 2026-07-29 | Creación inicial alineada a **IT-Form v1.0.1** (post SecSDLC, branding, Docker, purge secret) |

---

*Fin del documento de mantenimiento y soporte para desarrolladores.*
