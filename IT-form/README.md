# IT-form — Formulario de Servicio Técnico

Aplicación web PHP para registrar servicios técnicos, branding de empresa, panel admin y PDF (cliente/servidor).

## Documentación

| Doc | Contenido |
|-----|-----------|
| [README.md](./README.md) | Overview, quickstart, bitácora de cambios |
| [INSTALACION.md](./INSTALACION.md) | On-premise y Docker paso a paso |
| [MAINTENANCE.md](./MAINTENANCE.md) | **Mantenimiento y soporte técnico (desarrolladores)** |

## Características

- Formulario responsive (móvil/desktop)
- Guardar en BD con numeración `AAAA-MM-NNNN`
- Tras guardar: **Imprimir** + **Compartir** (Web Share en móvil) o **Descargar**
- Panel admin: dashboard, servicios, usuarios, configuración (logo/datos), auditoría
- PDF carta 8.5×11" con logo y firmas (contacto cliente + técnico de sesión)
- Auth bcrypt, CSRF, rate-limit, sesión segura
- Instalación **on-premise** o **Docker**

## Requisitos

- PHP 8.1+
- MySQL/MariaDB → DB **`itformdb`**, usuario **`itform_usr`**
- o SQLite (solo lab)

## Inicio rápido

### Docker

```bash
cd IT-form
cp docker/.env.example .env
# editar secretos
docker compose -f docker/docker-compose.example.yml --env-file .env up -d --build
# http://localhost:8088/
```

### On-premise

Ver [INSTALACION.md](./INSTALACION.md).

### Lab local SQLite

```bash
cp .env.example .env   # o usar SQLite en .env
php database/init_sqlite.php
php -S 127.0.0.1:8080 router.php
```

## Credenciales por defecto (cambiar en producción)

| Usuario | Password | Rol |
|---------|----------|-----|
| admin | admin123 | admin |
| itspanama | tecnico123 | tecnico |

## Estructura

```
IT-form/
├── index.php              # Frontend formulario
├── script.js / styles.css
├── admin/                 # Panel
├── api/                   # session, config, guardar_servicio
├── config/                # env, db, security, company
├── database/              # init MySQL + SQLite
├── docker/                # Dockerfile, compose example, .env.example
├── uploads/               # logos subidos
├── storage/               # sqlite + logs
├── print_pdf.php          # PDF servidor (TCPDF)
├── router.php             # php -S + deny list
└── INSTALACION.md
```

## Seguridad (resumen)

- Sin archivo `secret` en plaintext
- Auth unificada BD + bcrypt
- CSRF en mutaciones
- Rate limit login/API
- Idle timeout de sesión
- Headers: CSP, nosniff, frame-options, permissions-policy
- Paths sensibles denegados (`.env`, `config/`, `database/`, `storage/`, `docker/`)
- API guardar requiere login (`ALLOW_PUBLIC_SAVE=0`)

## Licencia

MIT (excepto TCPDF: LGPL).

---

## Bitácora de cambios (SecSDLC / endurecimiento)

### 2026-07-29 — Baseline y remediación completa

1. **Workspace y política**
   - Clonado repo en `Proyectos_GHLF/html-templates`
   - Push a GitHub solo con pruebas OK + comando `Listo (Y)`

2. **Hallazgos iniciales y mitigación**
   - Eliminado `secret` plaintext trackeado; auth unificada admin/BD
   - Seeds de password corregidos (`admin123` / `tecnico123` con bcrypt real)
   - API `guardar_servicio` con auth + CSRF + rate limit; sin leak PDO
   - Módulos admin completos: servicios, usuarios, configuración, auditoría
   - `.htaccess` / `router.php` bloquean config, storage, database, `.env`
   - Sesión: cookie flags antes de `session_start`, SameSite, idle timeout
   - Eliminados zips basura (`master.zip`, `tcpdf/main.zip`)

3. **PDF profesional**
   - Formato Carta 8.5×11", márgenes ~0.75"
   - Logo, título INFORME TÉCNICO, etiquetas en negrita, separadores
   - Firmas al pie: contacto del cliente + técnico creador (sesión)
   - Alineado a referencia `ejemplo.pdf`

4. **Branding de empresa**
   - Admin → Configuración: nombre, RUC, email, web, tel, dirección, colores
   - Upload de logo principal/footer → `uploads/`
   - Branding reflejado en frontend y PDF
   - Prueba **Okami-Pruebas** + `logo-test.png` validada

5. **UX frontend**
   - Responsive (botones 44px, barra sticky en móvil, admin adaptable)
   - Flujo: **Guardar** → habilita **Imprimir** y **Compartir**
   - Compartir: Web Share API (archivos) en móvil; fallback **Descargar**

6. **Instalación y ops**
   - BD por defecto: **`itformdb`** / usuario **`itform_usr`**
   - `INSTALACION.md`: on-premise + Docker
   - Carpeta `docker/`: `Dockerfile`, `docker-compose.example.yml`, `.env.example`, entrypoint
   - Headers de seguridad ampliados (CSP, Permissions-Policy, HSTS condicional)
   - Eliminado `shell_exec` de descubrimiento de binarios en conversión de logo

7. **No incluido en release (intencional)**
   - Contenido de `pdfs-test/` y `.env` real (gitignore)
   - Uploads de clientes runtime (gitignore salvo placeholders)

### Pendiente del release manager

- Asignar tag/versión (ej. `v1.0.0`)
- Confirmar mensaje de commit/release
- Emitir `Listo (Y)` para push a GitHub
