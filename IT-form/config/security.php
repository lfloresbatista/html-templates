<?php
/**
 * Sesión segura, CSRF y helpers de sanitización.
 */
require_once __DIR__ . '/env.php';

function itform_bootstrap_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (itform_env('FORCE_SECURE_COOKIE', '0') === '1');

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name(itform_env('SESSION_NAME', 'ITFORMSESSID'));
    session_start();

    // Idle timeout
    $idle = (int) itform_env('SESSION_IDLE_MINUTES', '60');
    if ($idle > 0 && !empty($_SESSION['logged_in'])) {
        $last = (int) ($_SESSION['last_activity'] ?? time());
        if ((time() - $last) > ($idle * 60)) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
            }
            session_regenerate_id(true);
        } else {
            $_SESSION['last_activity'] = time();
        }
    } elseif (!empty($_SESSION['logged_in'])) {
        $_SESSION['last_activity'] = time();
    }
}

function itform_csrf_token(): string
{
    itform_bootstrap_session();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function itform_csrf_validate(?string $token): bool
{
    itform_bootstrap_session();
    if ($token === null || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function itform_client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Rate limit simple por archivo JSON (IP + acción).
 */
function itform_rate_limit(string $action, int $maxAttempts, int $windowSeconds): bool
{
    $dir = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    $file = $dir . '/rate_limit.json';
    $ip = itform_client_ip();
    $now = time();
    $data = [];
    if (is_readable($file)) {
        $data = json_decode((string) file_get_contents($file), true) ?: [];
    }
    $key = $action . '|' . $ip;
    $bucket = $data[$key] ?? ['count' => 0, 'start' => $now];
    if (($now - (int) $bucket['start']) > $windowSeconds) {
        $bucket = ['count' => 0, 'start' => $now];
    }
    $bucket['count'] = (int) $bucket['count'] + 1;
    $data[$key] = $bucket;
    // cleanup old keys
    foreach ($data as $k => $v) {
        if (($now - (int) ($v['start'] ?? 0)) > 86400) {
            unset($data[$k]);
        }
    }
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return $bucket['count'] <= $maxAttempts;
}

function itform_sanitize_text($data): string
{
    if (!is_string($data)) {
        $data = (string) $data;
    }
    $data = trim($data);
    // No strip_tags agresivo: se escapa en salida HTML
    return $data;
}

function itform_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function itform_json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function itform_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; script-src 'self' 'unsafe-inline'; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'");
    // HSTS solo si HTTPS
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (itform_env('FORCE_SECURE_COOKIE', '0') === '1');
    if ($https) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
