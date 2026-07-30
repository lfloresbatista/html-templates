<?php
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/company.php';
require_once __DIR__ . '/admin/auth.php';
itform_send_security_headers();
$csrf = itform_csrf_token();
$auth = isAuthenticated();
$userLabel = $auth ? (string) ($_SESSION['nombre'] ?? $_SESSION['username'] ?? '') : '';
$cfg = itform_get_config();
$empresa = (string) ($cfg['nombre_empresa'] ?? 'ITS Panama');
$logoUrl = itform_logo_url($cfg);
$logoFooter = itform_footer_logo_url($cfg);
$email = (string) ($cfg['email_soporte'] ?? '');
$web = (string) ($cfg['sitio_web'] ?? '');
$tel = (string) ($cfg['telefono'] ?? '');
$ruc = (string) ($cfg['ruc'] ?? '');
$colorPrimary = (string) ($cfg['color_primario'] ?? '#001F3F');
$colorSecondary = (string) ($cfg['color_secundario'] ?? '#4CAF50');
$footerBits = array_filter([$empresa, $email, $web, $tel !== '' ? 'Tel: ' . $tel : '']);
$footerLine = implode(' | ', $footerBits);
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Formulario de Servicio Técnico - <?php echo itform_e($empresa); ?>">
  <meta name="csrf-token" content="<?php echo itform_e($csrf); ?>">
  <title>Formulario de Servicio Técnico | <?php echo itform_e($empresa); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=AbeeZee:ital@0;1&family=Arial&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <script src="dist/html2pdf.bundle.min.js" defer></script>
  <script src="dist/FileSaver.min.js" defer></script>
  <link rel="icon" type="image/x-icon" href="data:image/x-icon;,">
  <style>
    :root {
      --color-primary: <?php echo itform_e($colorPrimary); ?>;
      --color-secondary: <?php echo itform_e($colorSecondary); ?>;
    }
    body.dark-mode {
      --color-background: #1a1a2e;
      --color-white: #2d2d44;
      --color-gray: #aaa;
      --color-border: #444;
      --shadow: 0 0 10px rgba(0, 0, 0, 0.3);
    }
    body.dark-mode label { color: #fff; }
    body.dark-mode input[type="text"],
    body.dark-mode input[type="datetime-local"],
    body.dark-mode textarea {
      background: #1a1a2e; color: #fff; border-color: #444;
    }
    body.dark-mode fieldset { border-color: #444; }
    body.dark-mode legend { color: #fff; }
    body.dark-mode .help-text { color: #888; }
    body.dark-mode #encabezado,
    body.dark-mode #footer { background-color: var(--color-background); }
    .theme-toggle-container { position: fixed; top: 12px; right: 12px; z-index: 1001; }
    .theme-toggle-btn {
      background: var(--color-primary); color: white; border: none;
      padding: 10px 14px; border-radius: 25px; cursor: pointer; font-size: 1.1rem;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); transition: all 0.3s ease;
      display: flex; align-items: center; gap: 6px;
    }
    .admin-link {
      position: fixed; bottom: 20px; right: 20px; background: var(--color-primary);
      color: white; padding: 12px 18px; border-radius: 25px; text-decoration: none;
      font-weight: 600; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); z-index: 1001;
    }
    .session-bar {
      max-width: 800px; margin: 10px auto 0; padding: 10px 16px;
      background: #e8f4ff; border-radius: 8px; font-size: .95rem;
    }
    body.dark-mode .session-bar { background: #2d2d44; color: #ddd; }
    .btn-save { background-color: var(--color-primary, #001F3F); color: white; }
    .btn-save:hover:not(:disabled) { filter: brightness(1.1); }
    .company-meta { color: #555; font-size: .95rem; margin: 6px 0 0; }
    body.dark-mode .company-meta { color: #bbb; }

    /* Móvil: Panel arriba-izq; barra de acciones 2x2 abajo (no tapa el form) */
    @media (max-width: 768px) {
      .theme-toggle-container { top: 10px; right: 10px; }
      .theme-toggle-btn {
        padding: 8px 10px;
        font-size: 1rem;
        max-width: 46vw;
      }
      .theme-toggle-btn #themeText { display: none; } /* solo icono en móvil */
      .admin-link {
        bottom: auto;
        top: 10px;
        left: 10px;
        right: auto;
        padding: 8px 12px;
        font-size: 0.85rem;
        border-radius: 20px;
      }
    }
    @media (max-width: 380px) {
      .theme-toggle-btn { padding: 8px; }
      .admin-link { padding: 8px 10px; font-size: 0.8rem; }
    }
  </style>
</head>
<body>
  <div class="theme-toggle-container">
    <button class="theme-toggle-btn" id="themeToggle" title="Cambiar tema" type="button">
      <span id="themeIcon">🌙</span>
      <span id="themeText">Modo Oscuro</span>
    </button>
  </div>

  <a href="admin/login.php" class="admin-link" id="adminLink"><?php echo $auth ? '🔐 Panel' : '🔐 Login'; ?></a>

  <header id="encabezado" role="banner">
    <img src="<?php echo itform_e($logoUrl); ?>" alt="<?php echo itform_e($empresa); ?> Logo" id="logo">
    <h1 id="titulo">Formulario de Servicio Técnico</h1>
    <p class="company-meta" id="companyMeta">
      <strong id="companyName"><?php echo itform_e($empresa); ?></strong>
      <?php if ($ruc !== ''): ?> · RUC: <span id="companyRuc"><?php echo itform_e($ruc); ?></span><?php endif; ?>
      <?php if ($tel !== ''): ?> · <span id="companyTel"><?php echo itform_e($tel); ?></span><?php endif; ?>
    </p>
  </header>

  <div class="session-bar" id="sessionBar">
    <?php if ($auth): ?>
      Sesión: <strong><?php echo itform_e($userLabel); ?></strong> — puede guardar en BD. El PDF firmará con este técnico.
    <?php else: ?>
      No ha iniciado sesión. Puede generar PDF cliente; para <strong>Guardar</strong> / PDF servidor use
      <a href="admin/login.php">Login</a>.
    <?php endif; ?>
  </div>

  <main role="main">
    <form id="serviceForm" novalidate>
      <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo itform_e($csrf); ?>">
      <fieldset>
        <legend>Datos del Servicio</legend>
        <div class="fila">
          <div class="columna">
            <label for="cliente">Cliente / Empresa atendida:
              <input type="text" id="cliente" name="cliente" required autocomplete="organization" placeholder="Nombre de la empresa o cliente" aria-required="true">
            </label>
          </div>
          <div class="columna">
            <label for="fecha">Fecha y Hora:
              <input type="datetime-local" id="fecha" name="fecha" required aria-required="true">
            </label>
          </div>
        </div>
        <div class="fila">
          <div class="columna">
            <label for="direccion">Dirección:
              <input type="text" id="direccion" name="direccion" required autocomplete="street-address" placeholder="Dirección completa" aria-required="true">
            </label>
          </div>
          <div class="columna">
            <label for="ticket">Ticket No.:
              <input type="text" id="ticket" name="ticket" required pattern="[A-Za-z0-9\-]+" placeholder="Ej: TK-001" aria-required="true" aria-describedby="ticketHelp">
            </label>
            <small id="ticketHelp" class="help-text">Solo letras, números y guiones</small>
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>Detalles del Servicio</legend>
        <div class="fila">
          <div class="columna columna-full">
            <label for="reporte">Reporte del Cliente:
              <textarea id="reporte" name="reporte" rows="4" required placeholder="Descripción del problema reportado" aria-required="true"></textarea>
            </label>
          </div>
        </div>
        <div class="fila">
          <div class="columna">
            <label for="diagnostico">Diagnóstico Técnico:
              <textarea id="diagnostico" name="diagnostico" rows="4" required placeholder="Diagnóstico realizado por el técnico" aria-required="true"></textarea>
            </label>
          </div>
          <div class="columna">
            <label for="trabajoRealizado">Trabajo Realizado:
              <textarea id="trabajoRealizado" name="trabajoRealizado" rows="4" required placeholder="Descripción del trabajo ejecutado" aria-required="true"></textarea>
            </label>
          </div>
        </div>
        <div class="fila">
          <div class="columna columna-full">
            <label for="observaciones">Observaciones/Recomendaciones:
              <textarea id="observaciones" name="observaciones" rows="4" required placeholder="Observaciones adicionales y recomendaciones" aria-required="true"></textarea>
            </label>
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>Firmas del informe</legend>
        <div class="fila">
          <div class="columna">
            <label for="recibidoConforme">Contacto del cliente (quien recibe):
              <input type="text" id="recibidoConforme" name="recibidoConforme" required autocomplete="name" placeholder="Nombre del contacto atendido" aria-required="true">
            </label>
          </div>
          <div class="columna">
            <label for="firmaTecnico">Técnico que elabora el informe:
              <input type="text" id="firmaTecnico" name="firmaTecnico" required autocomplete="name" placeholder="Nombre del técnico"
                     value="<?php echo $auth ? itform_e($userLabel) : ''; ?>"
                     <?php echo $auth ? 'readonly' : ''; ?>
                     aria-required="true">
            </label>
            <small class="help-text"><?php echo $auth ? 'Se toma de su sesión (usuario logueado).' : 'Inicie sesión para fijar el técnico automáticamente.'; ?></small>
          </div>
        </div>
      </fieldset>
    </form>

    <div id="mensaje" class="mensaje" role="alert" aria-live="polite"></div>

    <div class="acciones" id="accionesBar">
      <button type="button" id="btnGuardar" class="btn btn-save" aria-label="Guardar en base de datos">
        💾 Guardar
      </button>
      <button type="button" id="btnImprimir" class="btn btn-pdf" disabled aria-label="Imprimir o generar PDF">
        🖨 Imprimir
      </button>
      <button type="button" id="btnCompartir" class="btn btn-share" disabled aria-label="Compartir o descargar PDF">
        📤 Compartir
      </button>
      <button type="button" id="btnLimpiar" class="btn btn-secondary" aria-label="Limpiar formulario">
        🗑️ Limpiar
      </button>
    </div>
    <p class="acciones-hint" id="accionesHint">Guarde el servicio para habilitar Imprimir y Compartir/Descargar.</p>
  </main>

  <footer id="footer" role="contentinfo">
    <p id="poweredByText">Powered by</p>
    <div id="poweredByContainer">
      <img src="<?php echo itform_e($logoFooter); ?>" alt="Logo footer" id="poweredByLogo">
    </div>
    <p class="footer-info" id="footerInfo"><?php echo itform_e($footerLine); ?></p>
  </footer>

  <script src="script.js" defer></script>
</body>
</html>
