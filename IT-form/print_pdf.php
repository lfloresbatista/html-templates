<?php
/**
 * Generador de PDF para Formulario de Servicio Técnico
 * Usa TCPDF para generar documentos PDF profesionales
 */

// Configuración de errores (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Cambiar a 0 en producción

// Incluye la clase TCPDF
require_once __DIR__ . '/tcpdf/tcpdf.php';

/**
 * Función para sanitizar entradas
 * @param string $data Datos a sanitizar
 * @return string Datos sanitizados
 */
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Función para validar que los datos requeridos existan
 * @param array $requiredFields Campos requeridos
 * @param array $postData Datos POST
 * @return bool True si todos los campos existen
 */
function validateRequiredFields($requiredFields, $postData) {
    foreach ($requiredFields as $field) {
        if (!isset($postData[$field]) || empty(trim($postData[$field]))) {
            return false;
        }
    }
    return true;
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Campos requeridos
$requiredFields = [
    'cliente', 'fecha', 'direccion', 'ticket', 
    'reporte', 'diagnostico', 'trabajoRealizado', 
    'observaciones', 'recibidoConforme', 'firmaTecnico'
];

// Validar campos requeridos
if (!validateRequiredFields($requiredFields, $_POST)) {
    http_response_code(400);
    echo json_encode(['error' => 'Campos requeridos incompletos']);
    exit;
}

// Obtener y sanitizar los datos del formulario
$data = [
    'cliente' => sanitizeInput($_POST['cliente']),
    'fecha' => sanitizeInput($_POST['fecha']),
    'direccion' => sanitizeInput($_POST['direccion']),
    'ticket' => sanitizeInput($_POST['ticket']),
    'reporteCliente' => sanitizeInput($_POST['reporte']),
    'diagnosticoTecnico' => sanitizeInput($_POST['diagnostico']),
    'trabajoRealizado' => sanitizeInput($_POST['trabajoRealizado']),
    'observaciones' => sanitizeInput($_POST['observaciones']),
    'recibidoConforme' => sanitizeInput($_POST['recibidoConforme']),
    'firmaTecnico' => sanitizeInput($_POST['firmaTecnico'])
];

// Crear una nueva instancia de TCPDF
$pdf = new TCPDF('P', 'mm', 'Letter', true, 'UTF-8', true);

// Configurar metadatos del PDF
$pdf->SetCreator('ITS Panama - Sistema de Servicio Técnico');
$pdf->SetAuthor('ITS Panama');
$pdf->SetTitle('Formulario de Servicio Técnico - Ticket ' . $data['ticket']);
$pdf->SetSubject('Reporte de Servicio Técnico');
$pdf->SetKeywords('Servicio Técnico, Reporte, Ticket');

// Configurar fuentes
$pdf->SetFont('helvetica', '', 11);

// Agregar una página al documento
$pdf->AddPage();

// Encabezado con logo (opcional)
/*
$logo = __DIR__ . '/logo.png';
if (file_exists($logo)) {
    $pdf->Image($logo, 10, 10, 30, 0, 'PNG');
}
*/

// Título centrado
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'FORMULARIO DE SERVICIO TÉCNICO', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Ln(5);

// Línea de separación
$pdf->Line(10, 35, $pdf->GetPageWidth() - 10, 35);
$pdf->Ln(10);

// Información del servicio
$contenidoY = 50;
$columnWidth = ($pdf->GetPageWidth() - 40) / 2;

// Cliente
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY(20, $contenidoY);
$pdf->Cell($columnWidth, 8, 'Cliente:', 0, 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell($columnWidth, 8, $data['cliente'], 0, 1);

// Fecha
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY(20 + $columnWidth, $contenidoY);
$pdf->Cell($columnWidth, 8, 'Fecha y Hora:', 0, 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell($columnWidth, 8, $data['fecha'], 0, 1);

// Dirección
$contenidoY += 10;
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY(20, $contenidoY);
$pdf->Cell($columnWidth, 8, 'Dirección:', 0, 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell($columnWidth, 8, $data['direccion'], 0, 1);

// Ticket
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY(20 + $columnWidth, $contenidoY);
$pdf->Cell($columnWidth, 8, 'Ticket No.:', 0, 0);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell($columnWidth, 8, $data['ticket'], 0, 1);

// Separador
$contenidoY += 15;
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(200, 220, 240);
$pdf->SetXY(20, $contenidoY);
$pdf->Cell($columnWidth * 2, 8, 'DETALLES DEL SERVICIO', 0, 1, 'C', true);

// Reporte del Cliente
$contenidoY += 12;
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY(20, $contenidoY);
$pdf->MultiCell($columnWidth, 6, "Reporte del Cliente:\n", 0, 'L', false, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(20, $contenidoY + 6);
$pdf->MultiCell($columnWidth, 5, $data['reporteCliente'], 0, 'J', false, 0);

// Diagnóstico Técnico
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY(20 + $columnWidth, $contenidoY);
$pdf->MultiCell($columnWidth, 6, "Diagnóstico Técnico:\n", 0, 'L', false, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(20 + $columnWidth, $contenidoY + 6);
$pdf->MultiCell($columnWidth, 5, $data['diagnosticoTecnico'], 0, 'J', false, 0);

// Trabajo Realizado
$contenidoY += 35;
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY(20, $contenidoY);
$pdf->MultiCell($columnWidth, 6, "Trabajo Realizado:\n", 0, 'L', false, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(20, $contenidoY + 6);
$pdf->MultiCell($columnWidth, 5, $data['trabajoRealizado'], 0, 'J', false, 0);

// Observaciones
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY(20 + $columnWidth, $contenidoY);
$pdf->MultiCell($columnWidth, 6, "Observaciones/Recomendaciones:\n", 0, 'L', false, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(20 + $columnWidth, $contenidoY + 6);
$pdf->MultiCell($columnWidth, 5, $data['observaciones'], 0, 'J', false, 0);

// Sección de firmas
$contenidoY += 40;
$pdf->Line(10, $contenidoY, $pdf->GetPageWidth() - 10, $contenidoY);
$contenidoY += 15;

// Firma Cliente
$pdf->SetXY(20, $contenidoY);
$pdf->Cell($columnWidth, 10, '_________________________', 0, 1, 'C');
$pdf->SetXY(20, $contenidoY + 7);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell($columnWidth, 8, 'Recibido Conforme', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($columnWidth, 6, $data['recibidoConforme'], 0, 1, 'C');

// Firma Técnico
$pdf->SetXY(20 + $columnWidth, $contenidoY);
$pdf->Cell($columnWidth, 10, '_________________________', 0, 1, 'C');
$pdf->SetXY(20 + $columnWidth, $contenidoY + 7);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell($columnWidth, 8, 'Firma Técnico', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($columnWidth, 6, $data['firmaTecnico'], 0, 1, 'C');

// Pie de página
$pdf->SetY(-25);
$pdf->SetFont('helvetica', 'I', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, 'ITS Panama | soporte@itspanama.net | www.itspanama.net', 0, 1, 'C');
$pdf->Cell(0, 5, 'Página ' . $pdf->getPage() . ' de ' . $pdf->getAliasNumPage(), 0, 1, 'C');

// Definir nombre del archivo seguro
$nombreArchivo = 'servicio_' . preg_replace('/[^A-Za-z0-9_]/', '_', $data['cliente']) . '_' . date('Ymd_His') . '.pdf';

// Salida del PDF (descarga el archivo)
$pdf->Output($nombreArchivo, 'D');
?>