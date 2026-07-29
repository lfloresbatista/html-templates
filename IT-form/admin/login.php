<?php
require_once __DIR__ . '/auth.php';

if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

itform_send_security_headers();
$csrf = itform_csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo itform_e($csrf); ?>">
    <title>Iniciar Sesión - Panel Administrativo</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        body {
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--color-primary, #001F3F) 0%, #003366 100%);
        }
        .login-box {
            background: #fff; padding: 40px; border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,.3); width: 100%; max-width: 420px;
        }
        .login-header { text-align: center; margin-bottom: 24px; }
        .login-header img { max-width: 180px; height: auto; margin-bottom: 12px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; }
        .form-group input {
            width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 6px; font-size: 1rem;
        }
        .btn-login {
            width: 100%; padding: 14px; background: var(--color-primary, #001F3F);
            color: #fff; border: none; border-radius: 6px; font-size: 1.05rem; font-weight: 600; cursor: pointer;
        }
        .error-message, .success-message {
            display: none; padding: 12px; border-radius: 6px; margin-bottom: 16px;
        }
        .error-message { background: #ffe6e6; color: #c0392b; border-left: 4px solid #c0392b; }
        .success-message { background: #e6ffe6; color: #27ae60; border-left: 4px solid #27ae60; }
        .footer-links { text-align: center; margin-top: 18px; }
        body.dark-mode .login-box { background: #2d2d44; color: #fff; }
        body.dark-mode .form-group input { background: #1a1a2e; border-color: #444; color: #fff; }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle" style="position:absolute;top:20px;right:20px;" type="button">🌙</button>
    <div class="login-box">
        <div class="login-header">
            <img src="../logo.png" alt="ITS Panama">
            <h1>Panel Administrativo</h1>
            <p>Inicie sesión para continuar</p>
        </div>
        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>
        <form id="loginForm">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" value="<?php echo itform_e($csrf); ?>">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login" id="loginBtn">Iniciar Sesión</button>
        </form>
        <div class="footer-links"><a href="../index.php">← Volver al formulario</a></div>
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
            setTimeout(() => { location.href = data.redirect || 'index.php'; }, 600);
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
