<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/company.php';

$redirectIn = itform_safe_internal_path($_GET['redirect'] ?? null) ?? '';

if (isAuthenticated()) {
    $dest = $redirectIn !== '' ? $redirectIn : itform_default_post_login_redirect();
    header('Location: ' . $dest);
    exit;
}

itform_send_security_headers();
$csrf = itform_csrf_token();
$cfg = itform_get_config();
$empresa = (string) ($cfg['nombre_empresa'] ?? 'IT-Form');
$logoUrl = itform_logo_url($cfg);
$colorPrimary = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($cfg['color_primario'] ?? ''))
    ? $cfg['color_primario'] : '#001F3F';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo itform_e($csrf); ?>">
    <meta name="robots" content="noindex, nofollow">
    <title>Iniciar Sesión | <?php echo itform_e($empresa); ?></title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        :root { --color-primary: <?php echo itform_e($colorPrimary); ?>; }
        body {
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; margin: 0;
            background: linear-gradient(135deg, var(--color-primary, #001F3F) 0%, #003366 100%);
        }
        .login-box {
            background: #fff; padding: 36px 32px; border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,.3); width: 100%; max-width: 420px;
            margin: 16px;
        }
        .login-header { text-align: center; margin-bottom: 22px; }
        .login-header img {
            max-width: 180px; max-height: 72px; height: auto; margin-bottom: 12px;
            background: #fff; padding: 6px; border-radius: 8px;
        }
        .login-header h1 { font-size: 1.35rem; margin: 0 0 6px; color: var(--color-primary, #001F3F); }
        .login-header p { margin: 0; color: #666; font-size: 0.95rem; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; }
        .form-group input {
            width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 6px; font-size: 1rem;
            box-sizing: border-box;
        }
        .btn-login {
            width: 100%; padding: 14px; background: var(--color-primary, #001F3F);
            color: #fff; border: none; border-radius: 6px; font-size: 1.05rem; font-weight: 600; cursor: pointer;
        }
        .btn-login:disabled { opacity: 0.7; cursor: wait; }
        .error-message, .success-message {
            display: none; padding: 12px; border-radius: 6px; margin-bottom: 16px;
        }
        .error-message { background: #ffe6e6; color: #c0392b; border-left: 4px solid #c0392b; }
        .success-message { background: #e6ffe6; color: #27ae60; border-left: 4px solid #27ae60; }
        .login-note { text-align: center; margin-top: 16px; font-size: 0.85rem; color: #888; }
        body.dark-mode .login-box { background: #2d2d44; color: #fff; }
        body.dark-mode .login-header h1 { color: #fff; }
        body.dark-mode .login-header p,
        body.dark-mode .login-note { color: #bbb; }
        body.dark-mode .form-group input { background: #1a1a2e; border-color: #444; color: #fff; }
        .theme-toggle {
            position: absolute; top: 16px; right: 16px;
            background: rgba(255,255,255,.15); color: #fff; border: none;
            width: 44px; height: 44px; border-radius: 50%; font-size: 1.2rem; cursor: pointer;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle" type="button" aria-label="Cambiar tema">🌙</button>
    <div class="login-box">
        <div class="login-header">
            <img src="../<?php echo itform_e($logoUrl); ?>" alt="<?php echo itform_e($empresa); ?>"
                 onerror="this.onerror=null;this.src='../logo.png';">
            <h1><?php echo itform_e($empresa); ?></h1>
            <p>Inicie sesión para usar el sistema</p>
        </div>
        <div class="error-message" id="errorMessage" role="alert"></div>
        <div class="success-message" id="successMessage" role="status"></div>
        <form id="loginForm">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" value="<?php echo itform_e($csrf); ?>">
            <?php if ($redirectIn !== ''): ?>
            <input type="hidden" name="redirect" value="<?php echo itform_e($redirectIn); ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required autocomplete="username" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login" id="loginBtn">Iniciar Sesión</button>
        </form>
        <p class="login-note">Acceso restringido. Se requiere autenticación.</p>
    </div>
    <script>
    (function () {
      const themeToggle = document.getElementById('themeToggle');
      const body = document.body;
      if (localStorage.getItem('adminTheme') === 'dark') {
        body.classList.add('dark-mode');
        themeToggle.textContent = '☀️';
      }
      themeToggle.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        const dark = body.classList.contains('dark-mode');
        themeToggle.textContent = dark ? '☀️' : '🌙';
        localStorage.setItem('adminTheme', dark ? 'dark' : 'light');
      });

      const form = document.getElementById('loginForm');
      const err = document.getElementById('errorMessage');
      const ok = document.getElementById('successMessage');
      const btn = document.getElementById('loginBtn');
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        err.style.display = 'none';
        ok.style.display = 'none';
        btn.disabled = true;
        btn.textContent = 'Verificando...';
        try {
          const res = await fetch('auth.php', { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
          const data = await res.json();
          if (data.success) {
            ok.textContent = data.message || 'OK';
            ok.style.display = 'block';
            setTimeout(() => { location.href = data.redirect || '../index.php'; }, 500);
          } else {
            err.textContent = data.error || 'Error';
            err.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Iniciar Sesión';
          }
        } catch (ex) {
          err.textContent = 'Error de conexión';
          err.style.display = 'block';
          btn.disabled = false;
          btn.textContent = 'Iniciar Sesión';
        }
      });
    })();
    </script>
</body>
</html>
