<?php
/**
 * Healthcheck — liveness + opcional readiness de BD.
 * GET api/health.php
 * No expone secretos.
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';

itform_send_security_headers();

$payload = [
    'success' => true,
    'status' => 'ok',
    'app' => 'it-form',
    'time' => gmdate('c'),
    'php' => PHP_VERSION,
    'db' => [
        'driver' => null,
        'ok' => false,
    ],
];

try {
    $driver = itform_env('DB_DRIVER', 'mysql');
    $payload['db']['driver'] = $driver;
    $db = getDB();
    $db->query('SELECT 1');
    $payload['db']['ok'] = true;
} catch (Throwable $e) {
    $payload['success'] = false;
    $payload['status'] = 'degraded';
    $payload['db']['ok'] = false;
    // sin detalle interno
    http_response_code(503);
}

// Si ?ready=1 exige BD OK
if (isset($_GET['ready']) && !$payload['db']['ok']) {
    http_response_code(503);
    $payload['status'] = 'not_ready';
}

itform_json_response($payload, $payload['success'] ? 200 : 503);
