<?php
/**
 * Configuración pública de empresa (frontend branding).
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/company.php';

itform_send_security_headers();

try {
    $cfg = itform_get_config();
    itform_json_response([
        'success' => true,
        'data' => [
            'nombre_empresa' => $cfg['nombre_empresa'] ?? '',
            'email_soporte' => $cfg['email_soporte'] ?? '',
            'sitio_web' => $cfg['sitio_web'] ?? '',
            'telefono' => $cfg['telefono'] ?? '',
            'direccion' => $cfg['direccion'] ?? '',
            'ruc' => $cfg['ruc'] ?? '',
            'logo_url' => itform_logo_url($cfg),
            'logo_footer_url' => itform_footer_logo_url($cfg),
            'color_primario' => $cfg['color_primario'] ?? '#001F3F',
            'color_secundario' => $cfg['color_secundario'] ?? '#4CAF50',
            'tema_defecto' => $cfg['tema_defecto'] ?? 'light',
        ],
    ]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    itform_json_response(['success' => false, 'error' => 'No se pudo cargar configuración'], 500);
}
