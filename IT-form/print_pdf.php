<?php
/**
 * PDF servidor (TCPDF) — usa config/pdf_report.php
 */
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/pdf_report.php';
require_once __DIR__ . '/admin/auth.php';

error_reporting(E_ALL);
ini_set('display_errors', '0');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (!itform_csrf_validate($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'CSRF inválido']);
    exit;
}

$allowPublic = itform_env('ALLOW_PUBLIC_SAVE', '0') === '1';
if (!$allowPublic && !isAuthenticated()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (!itform_rate_limit('pdf_server', 20, 60)) {
    http_response_code(429);
    exit('Rate limit');
}

$requiredFields = [
    'cliente', 'fecha', 'direccion',
    'reporte', 'diagnostico', 'trabajoRealizado',
    'observaciones', 'recibidoConforme',
];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Campos requeridos incompletos: ' . $field]);
        exit;
    }
}

// Técnico SIEMPRE de sesión (no se puede falsificar desde el formulario)
$tecnicoNombre = '';
if (isAuthenticated()) {
    $tecnicoNombre = itform_pdf_plain($_SESSION['nombre'] ?? $_SESSION['username'] ?? '');
}
if ($tecnicoNombre === '') {
    $tecnicoNombre = itform_pdf_plain($_POST['firmaTecnico'] ?? '');
}
if ($tecnicoNombre === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Nombre del técnico requerido']);
    exit;
}

$cliente = itform_pdf_plain($_POST['cliente']);
$fecha = itform_pdf_plain($_POST['fecha']);
$ticket = itform_pdf_plain($_POST['ticket'] ?? '');
// Si vacío o legado AAAA-MM-NNNN → generar formato negocio
if ($ticket === '' || preg_match('/^\d{4}-\d{2}-\d{4}$/', $ticket)) {
    $ticket = itform_generate_ticket($cliente, str_replace('T', ' ', $fecha));
}

$data = [
    'cliente' => $cliente,
    'fecha' => $fecha,
    'direccion' => itform_pdf_plain($_POST['direccion']),
    'ticket' => $ticket,
    'reporte' => itform_pdf_plain($_POST['reporte']),
    'diagnostico' => itform_pdf_plain($_POST['diagnostico']),
    'trabajo' => itform_pdf_plain($_POST['trabajoRealizado']),
    'observaciones' => itform_pdf_plain($_POST['observaciones']),
    'recibido' => itform_pdf_plain($_POST['recibidoConforme']),
    'firma' => $tecnicoNombre,
    'cargo' => itform_pdf_plain($_SESSION['cargo'] ?? ''),
];

try {
    $bytes = itform_build_service_pdf($data);
    $nombreArchivo = itform_pdf_filename($data);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo $bytes;
    exit;
} catch (Throwable $e) {
    error_log('print_pdf: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No se pudo generar el PDF']);
    exit;
}
