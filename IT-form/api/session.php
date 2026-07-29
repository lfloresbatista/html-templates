<?php
/**
 * Emite CSRF + estado de sesión para el formulario público.
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../admin/auth.php';

itform_send_security_headers();
itform_json_response([
    'success' => true,
    'csrf_token' => itform_csrf_token(),
    'authenticated' => isAuthenticated(),
    'user' => isAuthenticated() ? [
        'username' => $_SESSION['username'] ?? null,
        'nombre' => $_SESSION['nombre'] ?? null,
        'rol' => $_SESSION['rol'] ?? null,
    ] : null,
    'allow_public_save' => itform_env('ALLOW_PUBLIC_SAVE', '0') === '1',
]);
