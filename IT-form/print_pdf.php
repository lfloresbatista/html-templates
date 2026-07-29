<?php
/**
 * PDF servidor (TCPDF) — Carta 8.5x11", branding desde configuración de empresa.
 * Firmas: contacto del cliente + técnico de la sesión (o campo enviado).
 */
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/company.php';
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

require_once __DIR__ . '/tcpdf/tcpdf.php';

function pdf_plain($data): string
{
    $data = trim((string) $data);
    return str_replace(["\r\n", "\r"], "\n", $data);
}

$requiredFields = [
    'cliente', 'fecha', 'direccion', 'ticket',
    'reporte', 'diagnostico', 'trabajoRealizado',
    'observaciones', 'recibidoConforme',
];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Campos requeridos incompletos']);
        exit;
    }
}

// Técnico: preferir nombre de sesión (quien crea el informe)
$tecnicoNombre = '';
if (isAuthenticated()) {
    $tecnicoNombre = pdf_plain($_SESSION['nombre'] ?? $_SESSION['username'] ?? '');
}
if ($tecnicoNombre === '') {
    $tecnicoNombre = pdf_plain($_POST['firmaTecnico'] ?? '');
}
if ($tecnicoNombre === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Nombre del técnico requerido']);
    exit;
}

$data = [
    'cliente' => pdf_plain($_POST['cliente']),
    'fecha' => pdf_plain($_POST['fecha']),
    'direccion' => pdf_plain($_POST['direccion']),
    'ticket' => pdf_plain($_POST['ticket']),
    'reporte' => pdf_plain($_POST['reporte']),
    'diagnostico' => pdf_plain($_POST['diagnostico']),
    'trabajo' => pdf_plain($_POST['trabajoRealizado']),
    'observaciones' => pdf_plain($_POST['observaciones']),
    'recibido' => pdf_plain($_POST['recibidoConforme']), // contacto cliente
    'firma' => $tecnicoNombre, // técnico que crea el informe
];

$cfg = itform_get_config();
$empresa = pdf_plain($cfg['nombre_empresa'] ?? 'Empresa');
$email = pdf_plain($cfg['email_soporte'] ?? '');
$web = pdf_plain($cfg['sitio_web'] ?? '');
$tel = pdf_plain($cfg['telefono'] ?? '');
$ruc = pdf_plain($cfg['ruc'] ?? '');
$colorHex = preg_replace('/[^#A-Fa-f0-9]/', '', (string) ($cfg['color_primario'] ?? '#001F3F'));
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $colorHex)) {
    $colorHex = '#001F3F';
}
$cr = hexdec(substr($colorHex, 1, 2));
$cg = hexdec(substr($colorHex, 3, 2));
$cb = hexdec(substr($colorHex, 5, 2));

$logoFs = itform_logo_fs_path($cfg);
$logoJpeg = itform_ensure_jpeg_logo($logoFs);

$fechaMostrar = $data['fecha'];
$ts = strtotime(str_replace('T', ' ', $data['fecha']));
if ($ts !== false) {
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];
    $m = (int) date('n', $ts);
    $fechaMostrar = date('j', $ts) . ' de ' . $meses[$m] . ' de ' . date('Y', $ts)
        . ', ' . date('H:i', $ts);
}

class ITServicePDF extends TCPDF
{
    public string $ticketRef = '';
    public string $logoPath = '';
    public string $footerLine = '';
    /** @var array{0:int,1:int,2:int} */
    public array $brandRgb = [0, 31, 63];

    public function Header(): void
    {
        if ($this->logoPath !== '' && is_readable($this->logoPath)) {
            try {
                $this->Image($this->logoPath, 15, 10, 42, 0, '', '', '', false, 300);
            } catch (Throwable $e) {
                // ignore logo errors
            }
        }
        $this->SetFont('helvetica', 'I', 9);
        $this->SetTextColor(80, 80, 80);
        $this->SetXY(120, 12);
        $ref = $this->ticketRef !== '' ? '#' . $this->ticketRef : '';
        $this->Cell(75, 6, $ref, 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }

    public function Footer(): void
    {
        $this->SetY(-22);
        $this->SetDrawColor(180, 180, 180);
        $this->SetLineWidth(0.2);
        $this->Line(15, $this->GetY(), 201, $this->GetY());
        $this->Ln(2);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(70, 70, 70);
        $line = $this->footerLine !== '' ? $this->footerLine : 'Informe técnico';
        $this->Cell(0, 4, $line, 0, 1, 'C');
        $this->Cell(0, 4, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
        $this->SetTextColor(0, 0, 0);
    }
}

$footerParts = array_filter([
    $empresa,
    $ruc !== '' ? 'R.U.C. ' . $ruc : '',
    $tel !== '' ? 'Tel: ' . $tel : '',
    $email,
    $web,
]);

$pdf = new ITServicePDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
$pdf->ticketRef = preg_replace('/\s+/', '_', $data['ticket']);
$pdf->logoPath = $logoJpeg ?: ($logoFs ?: '');
$pdf->footerLine = implode(' | ', $footerParts);
$pdf->brandRgb = [$cr, $cg, $cb];
$pdf->SetCreator($empresa);
$pdf->SetAuthor($tecnicoNombre);
$pdf->SetTitle('Informe Técnico - ' . $data['ticket']);
$pdf->SetSubject('Servicio técnico - ' . $data['cliente']);

$pdf->SetMargins(19, 34, 19);
$pdf->SetHeaderMargin(8);
$pdf->SetFooterMargin(18);
$pdf->SetAutoPageBreak(true, 28);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetFont('helvetica', '', 11);
$pdf->AddPage();

$contentWidth = $pdf->getPageWidth() - 19 - 19;

// Empresa + título
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor($cr, $cg, $cb);
$pdf->Cell(0, 6, $empresa, 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'INFORME TÉCNICO', 0, 1, 'C');
$pdf->Ln(1);

$pdf->SetDrawColor($cr, $cg, $cb);
$pdf->SetLineWidth(0.5);
$y = $pdf->GetY();
$pdf->Line(19, $y, 19 + $contentWidth, $y);
$pdf->Ln(6);

$writeField = static function (ITServicePDF $pdf, string $label, string $value, float $contentWidth): void {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->MultiCell($contentWidth, 6, $label, 0, 'L', false, 1);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell($contentWidth, 5.5, $value, 0, 'L', false, 1);
    $pdf->Ln(2);
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.2);
    $y = $pdf->GetY();
    $pdf->Line(19, $y, 19 + $contentWidth, $y);
    $pdf->Ln(4);
};

$writeTwoCol = static function (ITServicePDF $pdf, string $l1, string $v1, string $l2, string $v2, float $contentWidth): void {
    $gap = 8.0;
    $col = ($contentWidth - $gap) / 2;
    $x0 = 19.0;
    $y0 = $pdf->GetY();

    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetXY($x0, $y0);
    $pdf->Cell($col, 6, $l1, 0, 0, 'L');
    $pdf->SetXY($x0 + $col + $gap, $y0);
    $pdf->Cell($col, 6, $l2, 0, 0, 'L');

    $yVal = $y0 + 6;
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetXY($x0, $yVal);
    $pdf->MultiCell($col, 5.5, $v1, 0, 'L', false, 1);
    $yAfterLeft = $pdf->GetY();
    $pdf->SetXY($x0 + $col + $gap, $yVal);
    $pdf->MultiCell($col, 5.5, $v2, 0, 'L', false, 1);
    $yAfterRight = $pdf->GetY();
    $pdf->SetY(max($yAfterLeft, $yAfterRight) + 2);

    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.2);
    $y = $pdf->GetY();
    $pdf->Line(19, $y, 19 + $contentWidth, $y);
    $pdf->Ln(4);
};

$writeTwoCol($pdf, 'Cliente:', $data['cliente'], 'Fecha:', $fechaMostrar, $contentWidth);
$writeTwoCol($pdf, 'Dirección:', $data['direccion'], 'Ticket No.:', $data['ticket'], $contentWidth);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor($cr, $cg, $cb);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell($contentWidth, 8, '  DETALLES DEL SERVICIO', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(4);

$writeField($pdf, 'Reporte del Cliente:', $data['reporte'], $contentWidth);
$writeField($pdf, 'Diagnóstico Técnico:', $data['diagnostico'], $contentWidth);
$writeField($pdf, 'Trabajo Realizado:', $data['trabajo'], $contentWidth);
$writeField($pdf, 'Observaciones / Recomendaciones:', $data['observaciones'], $contentWidth);

// Firmas al pie: contacto cliente | técnico creador
$signatureBlockHeight = 48;
$footerReserve = 28;
$pageH = $pdf->getPageHeight();
$yNow = $pdf->GetY();
$targetY = $pageH - $footerReserve - $signatureBlockHeight;
if ($yNow < $targetY) {
    $pdf->SetY($targetY);
} else {
    $pdf->AddPage();
    $pdf->SetY($pageH - $footerReserve - $signatureBlockHeight);
}

$pdf->SetDrawColor($cr, $cg, $cb);
$pdf->SetLineWidth(0.4);
$y = $pdf->GetY();
$pdf->Line(19, $y, 19 + $contentWidth, $y);
$pdf->Ln(6);

$col = ($contentWidth - 16) / 2;
$xLeft = 19;
$xRight = 19 + $col + 16;
$ySig = $pdf->GetY();

$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.3);
$pdf->Line($xLeft + 5, $ySig + 14, $xLeft + $col - 5, $ySig + 14);
$pdf->Line($xRight + 5, $ySig + 14, $xRight + $col - 5, $ySig + 14);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY($xLeft, $ySig + 16);
$pdf->Cell($col, 5, 'Recibido conforme (cliente)', 0, 0, 'C');
$pdf->SetXY($xRight, $ySig + 16);
$pdf->Cell($col, 5, 'Técnico del informe', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY($xLeft, $ySig + 22);
$pdf->Cell($col, 5, $data['recibido'], 0, 0, 'C');
$pdf->SetXY($xRight, $ySig + 22);
$pdf->Cell($col, 5, $data['firma'], 0, 1, 'C');

$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($xLeft, $ySig + 28);
$pdf->Cell($col, 4, 'Contacto de la empresa/cliente atendido', 0, 0, 'C');
$pdf->SetXY($xRight, $ySig + 28);
$pdf->Cell($col, 4, 'Usuario que elaboró el informe', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

$safeClient = preg_replace('/[^A-Za-z0-9_]/', '_', $data['cliente']);
$nombreArchivo = 'informe_' . $safeClient . '_' . date('Ymd_His') . '.pdf';
$pdf->Output($nombreArchivo, 'D');
