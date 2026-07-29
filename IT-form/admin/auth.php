<?php
/**
 * Auth unificada (BD + bcrypt + CSRF + rate limit + lockout).
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';

itform_bootstrap_session();

function isAuthenticated(): bool
{
    return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function isAdmin(): bool
{
    return isAuthenticated() && (($_SESSION['rol'] ?? '') === 'admin');
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']))) {
            itform_json_response(['success' => false, 'error' => 'No autorizado'], 401);
        }
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireAuth();
    if (!isAdmin()) {
        http_response_code(403);
        die('Acceso denegado. Se requieren privilegios de administrador.');
    }
}

function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function logAudit(PDO $db, ?int $userId, string $action, ?string $table, ?int $recordId, string $description): void
{
    try {
        $stmt = $db->prepare(
            'INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, descripcion, ip_origen, user_agent)
             VALUES (:uid, :acc, :tab, :rid, :des, :ip, :ua)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':acc' => $action,
            ':tab' => $table,
            ':rid' => $recordId,
            ':des' => $description,
            ':ip' => itform_client_ip(),
            ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    } catch (Throwable $e) {
        error_log('audit error: ' . $e->getMessage());
    }
}

function processLogin(string $username, string $password): array
{
    $maxAttempts = (int) itform_env('LOGIN_MAX_ATTEMPTS', '5');
    $lockMinutes = (int) itform_env('LOGIN_LOCK_MINUTES', '15');

    if (!itform_rate_limit('login', $maxAttempts * 3, 300)) {
        return ['success' => false, 'error' => 'Demasiados intentos. Espere unos minutos.'];
    }

    try {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT id, username, password_hash, email, nombre_completo, rol, activo,
                    intentos_fallidos, bloqueado_hasta
             FROM usuarios WHERE username = :u LIMIT 1'
        );
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'error' => 'Credenciales incorrectas'];
        }

        if (!(int) $user['activo']) {
            return ['success' => false, 'error' => 'Cuenta desactivada. Contacte al administrador.'];
        }

        if (!empty($user['bloqueado_hasta'])) {
            $lockTs = strtotime($user['bloqueado_hasta']);
            if ($lockTs !== false && $lockTs > time()) {
                return ['success' => false, 'error' => "Cuenta bloqueada. Intente en {$lockMinutes} minutos."];
            }
        }

        if (!password_verify($password, $user['password_hash'])) {
            $fails = (int) $user['intentos_fallidos'] + 1;
            if ($fails >= $maxAttempts) {
                $until = date('Y-m-d H:i:s', time() + $lockMinutes * 60);
                $db->prepare('UPDATE usuarios SET intentos_fallidos = :f, bloqueado_hasta = :b WHERE id = :id')
                    ->execute([':f' => $fails, ':b' => $until, ':id' => $user['id']]);
                return ['success' => false, 'error' => "Demasiados intentos. Cuenta bloqueada {$lockMinutes} min."];
            }
            $db->prepare('UPDATE usuarios SET intentos_fallidos = :f WHERE id = :id')
                ->execute([':f' => $fails, ':id' => $user['id']]);
            return ['success' => false, 'error' => 'Credenciales incorrectas'];
        }

        $db->prepare('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = :n WHERE id = :id')
            ->execute([':n' => date('Y-m-d H:i:s'), ':id' => $user['id']]);

        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nombre'] = $user['nombre_completo'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['login_time'] = time();
        // renovar CSRF post-login
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        logAudit($db, (int) $user['id'], 'LOGIN', 'usuarios', (int) $user['id'], 'Inicio de sesión exitoso');

        return ['success' => true, 'message' => 'Login exitoso', 'redirect' => 'index.php'];
    } catch (Throwable $e) {
        error_log('login error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Error del sistema'];
    }
}

function logout(): void
{
    if (isAuthenticated()) {
        try {
            $db = getDB();
            logAudit($db, currentUserId(), 'LOGOUT', 'usuarios', currentUserId(), 'Cierre de sesión');
        } catch (Throwable $e) {
            // ignore
        }
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

// Handlers
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        header('Content-Type: application/json; charset=utf-8');
        $csrf = $_POST['csrf_token'] ?? '';
        if (!itform_csrf_validate($csrf)) {
            itform_json_response(['success' => false, 'error' => 'Token CSRF inválido'], 403);
        }
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            itform_json_response(['success' => false, 'error' => 'Usuario y contraseña son requeridos'], 400);
        }
        $result = processLogin($username, $password);
        itform_json_response($result, $result['success'] ? 200 : 401);
    }

    if ($action === 'logout') {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!itform_csrf_validate($csrf)) {
            http_response_code(403);
            die('CSRF inválido');
        }
        logout();
        header('Location: login.php');
        exit;
    }
}
