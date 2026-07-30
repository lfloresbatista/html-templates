<?php
/**
 * Reimprimir PDF de un servicio guardado (TCPDF formato proyecto).
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/pdf_report.php';
requireAuth();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!itform_csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('CSRF');
    }
    $id = (int) ($_POST['id'] ?? 0);
} else {
    $id = (int) ($_GET['id'] ?? 0);
}

if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido');
}

$db = getDB();
$stmt = $db->prepare('SELECT s.*, u.nombre_completo AS tecnico FROM servicios s LEFT JOIN usuarios u ON s.usuario_id = u.id WHERE s.id = :id');
$stmt->execute([':id' => $id]);
$s = $stmt->fetch();
if (!$s) {
    http_response_code(404);
    exit('Servicio no encontrado');
}

$tecnico = itform_pdf_plain($s['firma_tecnico'] ?: ($s['tecnico'] ?? 'Técnico'));
$data = [
    'cliente' => itform_pdf_plain($s['cliente']),
    'fecha' => itform_pdf_plain($s['fecha_servicio']),
    'direccion' => itform_pdf_plain($s['direccion']),
    'ticket' => itform_pdf_plain($s['numero_secuencia'] ?: $s['ticket']),
    'reporte' => itform_pdf_plain($s['reporte_cliente']),
    'diagnostico' => itform_pdf_plain($s['diagnostico_tecnico']),
    'trabajo' => itform_pdf_plain($s['trabajo_realizado']),
    'observaciones' => itform_pdf_plain($s['observaciones'] ?? ''),
    'recibido' => itform_pdf_plain($s['recibido_conforme']),
    'firma' => $tecnico,
];

try {
    $bytes = itform_build_service_pdf($data);
    $name = itform_pdf_filename([
        'cliente' => $data['cliente'],
        'ticket' => $s['numero_secuencia'] ?: $data['ticket'],
    ]);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . strlen($bytes));
    echo $bytes;
    logAudit($db, currentUserId(), 'PRINT', 'servicios', $id, 'Reimpresión PDF ' . ($s['numero_secuencia'] ?? ''));
    exit;
} catch (Throwable $e) {
    error_log('print_servicio: ' . $e->getMessage());
    http_response_code(500);
    exit('Error al generar PDF');
}
