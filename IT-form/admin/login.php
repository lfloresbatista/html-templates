<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Panel Administrativo</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--color-primary) 0%, #003366 100%);
        }
        
        .login-box {
            background: var(--color-white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header img {
            max-width: 180px;
            height: auto;
            margin-bottom: 20px;
        }
        
        .login-header h1 {
            color: var(--color-primary);
            font-size: 1.8rem;
            margin: 0;
        }
        
        .login-header p {
            color: #666;
            margin-top: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--color-secondary);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: #003366;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 31, 63, 0.3);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .error-message {
            background: #ffe6e6;
            color: #c0392b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
            border-left: 4px solid #c0392b;
        }
        
        .success-message {
            background: #e6ffe6;
            color: #27ae60;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
            border-left: 4px solid #27ae60;
        }
        
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            color: white;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .footer-links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .footer-links a {
            color: var(--color-primary);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        /* Dark mode */
        body.dark-mode {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }
        
        body.dark-mode .login-box {
            background: #2d2d44;
        }
        
        body.dark-mode .login-header h1 {
            color: #fff;
        }
        
        body.dark-mode .login-header p {
            color: #aaa;
        }
        
        body.dark-mode .form-group label {
            color: #fff;
        }
        
        body.dark-mode .form-group input {
            background: #1a1a2e;
            border-color: #444;
            color: #fff;
        }
        
        body.dark-mode .form-group input:focus {
            border-color: var(--color-secondary);
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle" title="Cambiar tema">🌙</button>
    
    <div class="login-box">
        <div class="login-header">
            <img src="../logo.png" alt="ITS Panama Logo" id="loginLogo">
            <h1>Panel Administrativo</h1>
            <p>Inicie sesión para continuar</p>
        </div>
        
        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>
        
        <form id="loginForm">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required 
                       autocomplete="username" placeholder="Ingrese su usuario">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required 
                       autocomplete="current-password" placeholder="Ingrese su contraseña">
            </div>
            
            <button type="submit" class="btn-login" id="loginBtn">
                Iniciar Sesión
            </button>
        </form>
        
        <div class="footer-links">
            <a href="../index.html">← Volver al formulario</a>
        </div>
    </div>
    
    <script>
        // Toggle tema claro/oscuro
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        
        // Verificar preferencia guardada
        const savedTheme = localStorage.getItem('adminTheme');
        if (savedTheme === 'dark') {
            body.classList.add('dark-mode');
            themeToggle.textContent = '☀️';
        }
        
        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const isDark = body.classList.contains('dark-mode');
            themeToggle.textContent = isDark ? '☀️' : '🌙';
            localStorage.setItem('adminTheme', isDark ? 'dark' : 'light');
        });
        
        // Manejar login
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');
        const loginBtn = document.getElementById('loginBtn');
        
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Resetear mensajes
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
            
            // Deshabilitar botón
            loginBtn.disabled = true;
            loginBtn.textContent = '⏳ Verificando...';
            
            try {
                const formData = new FormData(loginForm);
                
                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    successMessage.textContent = '✅ ' + result.message;
                    successMessage.style.display = 'block';
                    
                    setTimeout(() => {
                        window.location.href = result.redirect || 'index.php';
                    }, 1000);
                } else {
                    errorMessage.textContent = '❌ ' + result.error;
                    errorMessage.style.display = 'block';
                    loginBtn.disabled = false;
                    loginBtn.textContent = 'Iniciar Sesión';
                }
            } catch (error) {
                errorMessage.textContent = '❌ Error de conexión. Intente nuevamente.';
                errorMessage.style.display = 'block';
                loginBtn.disabled = false;
                loginBtn.textContent = 'Iniciar Sesión';
            }
        });
    </script>
</body>
</html>
