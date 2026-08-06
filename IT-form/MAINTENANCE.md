# IT-Form — Documentación de mantenimiento y soporte (desarrolladores)

| Campo | Valor |
|-------|--------|
| **Producto** | IT-Form (formulario de servicio técnico) |
| **Versión documentada** | v1.0.4 |
| **Repositorio** | https://github.com/lfloresbatista/html-templates |
| **Ruta en monorepo** | `IT-form/` |
| **Audiencia** | Desarrolladores, DevOps, soporte L2/L3 |
| **Documentos relacionados** | `README.md`, `INSTALACION.md`, release notes `v1.0.4` |

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
          login → Panel (servicios) → subir firmado → seguimiento de estados
[Admin]   login → dashboard / servicios / usuarios / configuración / auditoría
          servicios → Aprobar firmados → completado
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
│   ├── print_servicio.php    # Reimpresión PDF desde BD
│   ├── download_firmado.php  # Descarga PDF firmado (auth)
│   ├── layout_header.php / layout_footer.php / admin.css
│
├── api/                      # Endpoints JSON (misma origin, cookies)
│   ├── session.php           # CSRF + estado de sesión
│   ├── config.php            # Branding público (sin secretos)
│   ├── health.php            # Healthcheck (liveness + readiness BD)
│   └── guardar_servicio.php  # Alta de servicios (auth + CSRF)
│
├── config/                   # No servir por web
│   ├── version.php           # APP_VERSION + APP_LAST_UPDATE
│   ├── env.php               # Carga .env
│   ├── database.php          # PDO singleton mysql|sqlite
│   ├── security.php          # Sesión, CSRF, rate-limit, headers, escape, proxy-aware
│   ├── company.php           # Branding, upload logo, paths, JPEG conversion
│   └── pdf_report.php        # Generación central PDF TCPDF + helpers de ticket
│
├── database/
│   ├── init_database.sql     # Schema MySQL (itformdb)
│   ├── init_sqlite.php       # Bootstrap lab SQLite
│   └── migrate.php           # Migración idempotente (MySQL + SQLite)
│
├── docker/
│   ├── Dockerfile
│   ├── docker-compose.example.yml       # BYOD (sin DB)
│   ├── docker-compose.with-db.example.yml  # Lab con MariaDB oficial
│   ├── .env.example
│   └── docker-entrypoint.sh
│
├── scripts/                  # Entrypoint de Docker
│   └── docker-entrypoint.sh
│
├── storage/                  # Runtime (db sqlite, logs rate-limit, firmados)
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
| `TRUST_PROXIES` | `0` | Confiar headers de proxy (X-Forwarded-*) |
| `ALLOW_PUBLIC_SAVE` | `0` | `1` = guardar sin login (**no prod**) |
| `LOGIN_MAX_ATTEMPTS` | `5` | Fallos antes de lockout |
| `LOGIN_LOCK_MINUTES` | `15` | Duración bloqueo cuenta |
| `API_RATE_MAX` | `30` | Tope rate-limit API save |
| `API_RATE_WINDOW` | `60` | Ventana rate-limit (s) |
| `ITFORM_AUTO_MIGRATE` | `1` | Ejecutar migrate.php al arrancar contenedor |
| `ITFORM_MIGRATE_RETRIES` | `30` | Reintentos de conexión DB en migrate |

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
| `usuarios` | Cuentas, roles (`admin`\|`tecnico`\|`usuario`), lockout, bcrypt, **cargo** |
| `servicios` | Informes técnicos + `numero_secuencia` único + `ticket` (formato negocio) |
| `auditoria` | Log de acciones (LOGIN, CREATE, UPDATE, APPROVE, UPLOAD, DELETE_FIRMADO…) |
| `sesiones` | Tabla opcional de tokens (schema presente; auth actual usa sesión PHP) |

### 5.2 Numeración y tickets

- Número de secuencia: `YYYY-MM-NNNN` (ej. `2026-07-0001`) generado por `itform_next_sequence()`.
- Ticket de negocio: `INICIALES_CLIENTE_DDMMAAAA_HHMM` (ej. `LP_06082026_1950`) generado por `itform_generate_ticket()`.
- Ambos campos se persisten en `servicios` (`numero_secuencia` y `ticket`).
- El nombre del archivo PDF sigue el formato del ticket.

### 5.3 Migraciones

No hay framework de migraciones. Cambios de schema:

1. Actualizar `database/init_database.sql` y `database/migrate.php`.
2. `migrate.php` es idempotente (`CREATE TABLE IF NOT EXISTS` + `ALTER TABLE` condicional).
3. Para installs existentes: `php database/migrate.php` o `ITFORM_AUTO_MIGRATE=1` en Docker.

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
4. Sesión PHP: `logged_in`, `user_id`, `username`, `nombre`, `email`, `cargo`, `rol`, `login_time`, `last_activity`, `csrf_token` (regenerado).
5. Post-login redirect: admin → `admin/index.php`; técnico → `../index.php` (formulario).

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
| `itform_safe_internal_path()` | Validación anti open-redirect |
| `itform_default_post_login_redirect()` | Destino por rol |

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
| Subir / eliminar firmado | Sí (estado no completado) | Sí |
| Cambiar estado de servicio | No | Sí |
| Aprobar firmado | No | Sí |
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

### `GET api/health.php`

```json
{ "success": true, "status": "ok", "db": { "driver": "sqlite", "ok": true } }
```

Con `?ready=1`: exige BD OK; 503 si no.

### `POST api/guardar_servicio.php`

- Requiere sesión (si `ALLOW_PUBLIC_SAVE=0`) + CSRF + rate-limit.
- Campos: `cliente`, `fecha`, `direccion`, `reporte`, `diagnostico`, `trabajoRealizado`, `observaciones`, `recibidoConforme`, `firmaTecnico`, `csrf_token`.
- `ticket` se genera automáticamente (`itform_generate_ticket`); `firmaTecnico` se fuerza desde sesión.
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
    "ticket": "LP_06082026_1950"
  }
}
```

Errores típicos: `400` validación, `401` no auth, `403` CSRF, `429` rate-limit, `500` error genérico.

### `POST print_pdf.php`

- Auth + CSRF (+ rate-limit).
- Mismos campos de formulario.
- **Firma técnico en PDF:** nombre de sesión (`$_SESSION['nombre']`) + cargo (`$_SESSION['cargo']`).
- **Recibido conforme:** contacto del cliente (`recibidoConforme`).
- Respuesta: binario PDF (`Content-Disposition: attachment`).
- Branding leído de `configuracion`.

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
| `lastSave` | Payload `data` del API (incl. `numero_secuencia`, `ticket`) |
| `lastPdfBlob` | Blob PDF cacheado para share/print |
| `shareSupported` | `navigator.share` disponible |

### UX de botones

1. **Guardar** — siempre disponible (requiere login en backend).
2. Tras save OK → habilita **Imprimir** y **Compartir**.
3. Cualquier `input` posterior invalida `saved` (hay que volver a guardar).
4. **Imprimir** — abre blob PDF en pestaña (fallback download si popup bloqueado).
5. **Compartir** — Web Share con `File` PDF si `canShare({files})`; si no, descarga.

### PDF cliente vs servidor

- Share/print intentan **servidor** (`print_pdf.php`) si hay sesión; fallback **html2pdf** sobre el DOM del form.
- Formato preferido de negocio: servidor (carta, logo empresa, firmas con cargo).

---

## 9. Panel admin

| Ruta | Rol | Función |
|------|-----|---------|
| `admin/login.php` | público | Login con branding de empresa |
| `admin/index.php` | auth | Dashboard: total, pendientes, revisión, completados |
| `admin/servicios.php` | auth | Listado + subir/eliminar firmado + aprobar (admin) + cambio de estado (admin) |
| `admin/usuarios.php` | admin | CRUD usuarios (username, nombre, email, **cargo**, rol, activo) |
| `admin/configuracion.php` | admin | Empresa + color pickers + upload logos + Restaurar fábrica |
| `admin/auditoria.php` | admin | Lectura log |

Layouts compartidos: `layout_header.php` / `layout_footer.php` + `admin.css` (responsive <900px).  
Versión de la app visible en el sidebar debajo del nombre de empresa.

### Flujo de estados (v1.0.4)

```
Guardar → pendiente
Upload firmado → revision (automático)
Admin aprueba → completado
Delete firmado → pendiente (solo si no está completado)
```

- Solo **admin** cambia estados manualmente.
- Al subir firmado: se oculta "Subir" y aparece "📄 Firmado" + "🗑 Eliminar".
- Al aprobar: no se puede eliminar ni resubir.

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
| Proxy-aware | `TRUST_PROXIES`, `X-Forwarded-Proto/For`, `CF-Connecting-IP`, mod_remoteip |
| Safe redirects | Whitelist de paths post-login |
| Open redirect prevention | `itform_safe_internal_path()` |
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

**Principio BYOD:** no se buildea MySQL/MariaDB. Se usa motor compartido o SQLite.

```bash
cd IT-form
cp docker/.env.example .env
docker compose -f docker/docker-compose.example.yml --env-file .env up -d --build
```

| Perfil | Compose | DB |
|--------|---------|-----|
| Principal | `docker-compose.example.yml` | Externa o SQLite |
| Lab full | `docker-compose.with-db.example.yml` | `mariadb:11.4` oficial + healthcheck + `depends_on: healthy` |

| Pieza | Detalle |
|-------|---------|
| Imagen web | non-root `www-data`, listen **8080**, código en imagen |
| Persistencia | `itform_uploads`, `itform_storage` (+ `itform_db_data` solo en perfil lab) |
| Schema | `database/migrate.php` al arrancar si `ITFORM_AUTO_MIGRATE=1` |
| Proxy | `TRUST_PROXIES=1`, RemoteIP, `VIRTUAL_PORT=8080` |
| Health | `GET /api/health.php` |

### 11.3 MySQL compartido (sysadmin)

1. Crear DB/user/pass en el MySQL existente.  
2. Poner contenedor web en la misma red Docker.  
3. `DB_HOST=<servicio>` + credenciales.  
4. Arrancar solo la app; migrate crea tablas.

### 11.4 Lab `php -S`

```bash
php database/migrate.php
php -S 127.0.0.1:8080 router.php
```

**Siempre** usar `router.php` para aplicar deny-list (el server built-in no lee `.htaccess`).

### 11.5 Registry (GHCR)

Se puede publicar la imagen web en **GHCR** (`ghcr.io/<user>/it-form:<tag>`) y reemplazar `build:` por `image:` en compose.

---

## 12. Desarrollo local recomendado

1. Clonar monorepo `html-templates`.
2. Trabajar en `IT-form/`.
3. SQLite lab o Docker Compose.
4. No commitear: `.env`, `storage/db/*.sqlite`, `storage/logs/*`, `uploads/*` (excepto placeholders), `pdfs-test/`.
5. Probar:
   - Login admin/tecnico
   - Guardar servicio + ticket formateado
   - PDF contiene branding y firmas correctas (nombre + cargo)
   - Flujo de estados (pendiente → revision → completado)
   - Deny de paths sensibles
   - Técnico no entra a `usuarios.php` (403)
6. Cambios de schema: actualizar `migrate.php` + `init_database.sql`.
7. Estilo de commits sugerido: Conventional Commits (`feat:`, `fix:`, `security:`, `docs:`).

### Puntos de extensión frecuentes

| Necesidad | Archivos típicos |
|-----------|------------------|
| Nuevo campo en informe | `index.php`, `script.js`, `api/guardar_servicio.php`, `config/pdf_report.php`, schema `servicios` |
| Nuevo rol | `auth.php` RBAC + páginas admin |
| Nuevo branding | `config/company.php`, `admin/configuracion.php` |
| Otro formato PDF | `config/pdf_report.php` (función `itform_build_service_pdf`) |
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
| "Error de conexión a la base de datos" | `.env` mal, DB caída, user/pass | Verificar `DB_*`, `docker compose ps`, grants `itform_usr` |
| Login siempre incorrecto | Seeds no cargados / hash viejo | Reimportar init o reset password con `password_hash` |
| 401 al guardar | Sesión expirada / cookies blocked | Re-login; revisar `SESSION_IDLE_MINUTES` y HTTPS/Secure |
| 403 CSRF | Token viejo / multi-pestaña | Recargar página; un solo origen |
| PDF sin logo | PNG sin GD | Subir logo y generar companion JPEG; o instalar `gd` |
| PDF firmas incorrectas | No hay sesión al imprimir | Imprimir solo tras login; técnico sale de sesión |
| 403 en admin/config | Usuario no admin | Usar cuenta `admin` |
| Logo no se ve | Path uploads / permisos | `chown www-data uploads`; URL `uploads/...` |
| 500 en servicios | Schema desactualizado (falta columna `cargo`) | `php database/migrate.php` |
| Estado no cambia | El estado enviado no está en la whitelist | Solo admin; estados válidos: pendiente, revision, completado, cancelado |

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

- Tags del monorepo: `v1.0.4` documenta IT-Form endurecido.
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

## 17. Mapa rápido de "dónde toco X"

| Quiero… | Empiezo en… |
|---------|-------------|
| Cambiar textos del form | `index.php` |
| Lógica de botones/share | `script.js` |
| Estilos / móvil | `styles.css`, `admin/admin.css` |
| Reglas de guardado | `api/guardar_servicio.php` |
| Diseño PDF | `config/pdf_report.php` |
| Login / roles | `admin/auth.php` |
| Branding | `admin/configuracion.php`, `config/company.php` |
| Conexión BD | `config/database.php`, `.env` |
| Headers/CSRF/sesión | `config/security.php` |
| Schema | `database/migrate.php`, `database/init_database.sql` |
| Empaquetado | `docker/*` |
| Hardening web | `.htaccess`, `router.php` |
| Versión de la app | `config/version.php` |

---

## 18. Contacto y ownership

| Rol | Notas |
|-----|--------|
| Maintainer repo | `@lfloresbatista` (GitHub) |
| Issues | GitHub Issues del monorepo `html-templates` |
| Soporte producto (ejemplo seed) | Configurable en `configuracion.email_soporte` |

---

## 19. Bitácora de seguridad

| Fecha | Versión | Actividad | Resultado |
|-------|---------|-----------|-----------|
| 2026-07-29 | v1.0.1 | Auditoría inicial SecSDLC (H-01…H-15) | 15 hallazgos mitigados; auth bcrypt, CSRF, rate-limit, headers |
| 2026-07-29 | v1.0.1 | Purga de `secret` del historial git | `git-filter-repo`: 0 ocurrencias en todas las refs |
| 2026-07-30 | v1.0.2 | Auditoría hardening proxy/non-root | Docker non-root :8080, TRUST_PROXIES, mod_remoteip |
| 2026-07-31 | v1.0.3 | Login obligatorio exposición internet | Safe redirect whitelist; x-forwarded-proto para cookies seguras |
| 2026-08-05 | v1.0.4-rc | Scan estático pre-release (27 archivos PHP) | 0 críticas; 1 bajo (exec en conversión logo con escapeshellarg) |
| 2026-08-06 | v1.0.4 | Test funcional + revisión de seguridad final | Todos los flujos OK; CSRF, auth, prepared statements en endpoints POST |

---

## 20. Historial de este documento

| Fecha | Cambio |
|-------|--------|
| 2026-07-29 | Creación inicial alineada a **IT-Form v1.0.1** |
| 2026-08-06 | Actualización a **v1.0.4**: ticket formato, cargo, estados pendiente→revision→completado, sidebar version, bitácora de seguridad |

---

*Fin del documento de mantenimiento y soporte para desarrolladores.*
