<?php
/**
 * API: guardar servicio técnico.
 * Requiere sesión autenticada (salvo ALLOW_PUBLIC_SAVE=1) + CSRF + rate limit.
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/auth.php';

itform_send_security_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    itform_json_response(['success' => false, 'error' => 'Método no permitido'], 405);
}

$max = (int) itform_env('API_RATE_MAX', '30');
$win = (int) itform_env('API_RATE_WINDOW', '60');
if (!itform_rate_limit('api_save', $max, $win)) {
    itform_json_response(['success' => false, 'error' => 'Rate limit excedido'], 429);
}

$csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!itform_csrf_validate(is_string($csrf) ? $csrf : null)) {
    itform_json_response(['success' => false, 'error' => 'Token CSRF inválido'], 403);
}

$allowPublic = itform_env('ALLOW_PUBLIC_SAVE', '0') === '1';
if (!$allowPublic && !isAuthenticated()) {
    itform_json_response(['success' => false, 'error' => 'No autorizado. Inicie sesión para guardar.'], 401);
}

try {
    $db = getDB();

    $cliente = itform_sanitize_text($_POST['cliente'] ?? '');
    $fechaServicio = itform_sanitize_text($_POST['fecha'] ?? date('Y-m-d H:i:s'));
    // datetime-local → SQL
    $fechaServicio = str_replace('T', ' ', $fechaServicio);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $fechaServicio)) {
        $fechaServicio .= ':00';
    }
    $direccion = itform_sanitize_text($_POST['direccion'] ?? '');
    $ticket = itform_sanitize_text($_POST['ticket'] ?? '');
    $reporteCliente = itform_sanitize_text($_POST['reporte'] ?? '');
    $diagnostico = itform_sanitize_text($_POST['diagnostico'] ?? '');
    $trabajoRealizado = itform_sanitize_text($_POST['trabajoRealizado'] ?? '');
    $observaciones = itform_sanitize_text($_POST['observaciones'] ?? '');
    $recibidoConforme = itform_sanitize_text($_POST['recibidoConforme'] ?? '');
    $firmaTecnico = itform_sanitize_text($_POST['firmaTecnico'] ?? '');

    $required = [
        'cliente' => $cliente,
        'fecha' => $fechaServicio,
        'direccion' => $direccion,
        'ticket' => $ticket,
        'reporte' => $reporteCliente,
        'diagnostico' => $diagnostico,
        'trabajoRealizado' => $trabajoRealizado,
        'recibidoConforme' => $recibidoConforme,
        'firmaTecnico' => $firmaTecnico,
    ];
    foreach ($required as $field => $value) {
        if ($value === '') {
            throw new InvalidArgumentException("El campo '{$field}' es requerido");
        }
    }

    $usuarioId = currentUserId();
    $numero = itform_next_sequence($db);

    $now = date('Y-m-d H:i:s');
    $sql = 'INSERT INTO servicios (
                numero_secuencia, cliente, fecha_servicio, direccion, ticket,
                reporte_cliente, diagnostico_tecnico, trabajo_realizado,
                observaciones, recibido_conforme, firma_tecnico,
                usuario_id, estado, pdf_generado, fecha_guardado
            ) VALUES (
                :num, :cliente, :fecha, :direccion, :ticket,
                :reporte, :diagnostico, :trabajo,
                :observaciones, :recibido, :firma,
                :usuario_id, :estado, 0, :guardado
            )';

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':num' => $numero,
        ':cliente' => $cliente,
        ':fecha' => $fechaServicio,
        ':direccion' => $direccion,
        ':ticket' => $ticket,
        ':reporte' => $reporteCliente,
        ':diagnostico' => $diagnostico,
        ':trabajo' => $trabajoRealizado,
        ':observaciones' => $observaciones,
        ':recibido' => $recibidoConforme,
        ':firma' => $firmaTecnico,
        ':usuario_id' => $usuarioId,
        ':estado' => 'completado',
        ':guardado' => $now,
    ]);

    $servicioId = (int) $db->lastInsertId();
    $stmtSelect = $db->prepare('SELECT numero_secuencia, fecha_guardado FROM servicios WHERE id = :id');
    $stmtSelect->execute([':id' => $servicioId]);
    $servicio = $stmtSelect->fetch();

    if ($usuarioId) {
        logAudit(
            $db,
            $usuarioId,
            'CREATE',
            'servicios',
            $servicioId,
            'Servicio ' . ($servicio['numero_secuencia'] ?? '') . " cliente {$cliente}"
        );
    }

    itform_json_response([
        'success' => true,
        'message' => 'Servicio guardado exitosamente',
        'data' => [
            'id' => $servicioId,
            'numero_secuencia' => $servicio['numero_secuencia'] ?? $numero,
            'fecha_guardado' => $servicio['fecha_guardado'] ?? $now,
            'cliente' => $cliente,
            'ticket' => $ticket,
        ],
    ]);
} catch (InvalidArgumentException $e) {
    itform_json_response(['success' => false, 'error' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('guardar_servicio: ' . $e->getMessage());
    itform_json_response(['success' => false, 'error' => 'Error al guardar en la base de datos'], 500);
}
