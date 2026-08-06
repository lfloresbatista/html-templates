<?php
/**
 * Generación central del PDF de informe técnico (TCPDF, carta).
 * Usado por print_pdf.php y admin/print_servicio.php
 */
require_once __DIR__ . '/../tcpdf/tcpdf.php';
require_once __DIR__ . '/company.php';

function itform_pdf_plain($data): string
{
    $data = trim((string) $data);
    return str_replace(["\r\n", "\r"], "\n", $data);
}

/**
 * @param array{
 *   cliente:string,fecha:string,direccion:string,ticket:string,
 *   reporte:string,diagnostico:string,trabajo:string,observaciones:string,
 *   recibido:string,firma:string,cargo:string
 * } $data
 * @return string bytes PDF
 */
function itform_build_service_pdf(array $data, ?array $cfg = null): string
{
    $cfg = $cfg ?: itform_get_config();
    $empresa = itform_pdf_plain($cfg['nombre_empresa'] ?? 'Empresa');
    $email = itform_pdf_plain($cfg['email_soporte'] ?? '');
    $web = itform_pdf_plain($cfg['sitio_web'] ?? '');
    $tel = itform_pdf_plain($cfg['telefono'] ?? '');
    $ruc = itform_pdf_plain($cfg['ruc'] ?? '');

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

    if (!class_exists('ITServicePDF', false)) {
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
                        // ignore
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
    $pdf->logoPath = ($logoJpeg && is_readable($logoJpeg)) ? $logoJpeg : '';
    $pdf->footerLine = implode(' | ', $footerParts);
    $pdf->brandRgb = [$cr, $cg, $cb];
    $pdf->SetCreator($empresa);
    $pdf->SetAuthor($data['firma']);
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

    // Firma justo debajo de Observaciones/Recomendaciones.
    // Si no cabe en la página actual (≈12 mm), salta a nueva y sigue desde ahí.
    $sigNeeded = 50; // mm aproximados para el bloque de firmas
    $sigLineGap = 3;
    $footerReserve = 28;
    $pageH = $pdf->getPageHeight();
    $yNow = $pdf->GetY();
    if (($yNow + $sigNeeded) > ($pageH - $footerReserve)) {
        $pdf->AddPage();
    } else {
        $pdf->Ln(2);
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
    // línea de firma cliente
    $pdf->Line($xLeft + 5, $ySig + 14, $xLeft + $col - 5, $ySig + 14);
    // línea de firma técnico
    $pdf->Line($xRight + 5, $ySig + 14, $xRight + $col - 5, $ySig + 14);

    // Contacto cliente
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetXY($xLeft, $ySig + 16);
    $pdf->Cell($col, 4, 'Recibido conforme', 0, 0, 'C');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY($xLeft, $ySig + 21);
    $pdf->Cell($col, 4, $data['recibido'], 0, 0, 'C');

    // Técnico
    $cargoLine = (string) ($data['cargo'] ?? '');
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetXY($xRight, $ySig + 16);
    $pdf->Cell($col, 4, ($cargoLine !== '' ? 'Técnico responsable' : 'Técnico del informe'), 0, 0, 'C');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY($xRight, $ySig + 21);
    $pdf->Cell($col, 4, $data['firma'], 0, 0, 'C');
    if ($cargoLine !== '') {
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(70, 70, 70);
        $pdf->SetXY($xRight, $ySig + 26);
        $pdf->Cell($col, 4, $cargoLine, 0, 0, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }

    return $pdf->Output('', 'S');
}



function itform_client_initials(string $nombre): string
{
    $nombre = trim($nombre);
    // quitar sufijos SA/SRL etc
    $nombre = preg_replace('/\s+(S\.?A\.?|SRL|INC|LLC)\s*$/i', '', $nombre);
    $words = preg_split('/\s+/', $nombre);
    $init = '';
    foreach ($words as $w) {
        if ($w !== '') {
            $init .= mb_strtoupper(mb_substr($w, 0, 1));
        }
    }
    if ($init === '') {
        $init = 'CL';
    }
    return mb_substr($init, 0, 6);
}

function itform_pdf_basename(string $cliente, string $fechaGuardado): string
{
    $ini = itform_client_initials($cliente);
    $ts = strtotime($fechaGuardado);
    if ($ts === false) {
        $ts = time();
    }
    $stamp = date('dmY_Hi', $ts);
    return $ini . '_' . $stamp . '.pdf';
}



function itform_pdf_filename(array $data): string
{
    // Prefer ticket ya formateado (INICIALES_DDMMAAAA_HHMM)
    $ticket = trim((string) ($data['ticket'] ?? ''));
    if ($ticket !== '' && preg_match('/^[A-Z0-9]{2,8}_\d{8}_\d{4}$/i', $ticket)) {
        return strtoupper(preg_replace('/\.pdf$/i', '', $ticket)) . '.pdf';
    }
    $fecha = (string) ($data['fecha'] ?? $data['fecha_guardado'] ?? date('Y-m-d H:i:s'));
    $fecha = str_replace('T', ' ', $fecha);
    return itform_pdf_basename($data['cliente'] ?? 'cliente', $fecha);
}

/** Genera ticket de negocio: INICIALES_DDMMAAAA_HHMM (sin .pdf) */
function itform_generate_ticket(string $cliente, ?string $fecha = null): string
{
    $fecha = $fecha ?: date('Y-m-d H:i:s');
    $fecha = str_replace('T', ' ', $fecha);
    $base = itform_pdf_basename($cliente, $fecha);
    return preg_replace('/\.pdf$/i', '', $base);
}

/** Nombre informe firmado: INICIALES_DDMMAAAA_HHMM-FIRMADO.pdf */
function itform_signed_pdf_basename(array $servicio): string
{
    $base = itform_pdf_basename(
        $servicio['cliente'] ?? 'cliente',
        $servicio['fecha_guardado'] ?? date('Y-m-d H:i:s')
    );
    // quitar .pdf si viene
    $base = preg_replace('/\.pdf$/i', '', $base);
    return $base . '-FIRMADO.pdf';
}

