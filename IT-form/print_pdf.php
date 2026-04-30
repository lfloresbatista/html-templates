<?php
// Display errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluye la clase TCPDF
require_once('tcpdf/tcpdf.php');

// Obtén los datos del formulario
$cliente = $_POST['cliente'];
$fecha = $_POST['fecha'];
$direccion = $_POST['direccion'];
$ticket = $_POST['ticket'];
$reporteCliente = $_POST['reporte'];
$diagnosticoTecnico = $_POST['diagnostico'];
$trabajoRealizado = $_POST['trabajoRealizado'];
$observaciones = $_POST['observaciones'];
$recibidoConforme = $_POST['recibidoConforme'];
$firmaTecnico = $_POST['firmaTecnico'];

// Crea una nueva instancia de TCPDF
$pdf = new TCPDF('P', 'mm', 'Letter', true, 'UTF-8', TRUE);

// Configura el estilo del PDF
$pdf->SetFont('times', 'B', 12);

// Establece el tamaño de página
//$pdf->SetPageSize('letter');

// Agrega una página al documento
$pdf->AddPage();

// Agrega el logo
//$logo = 'logo.png';
//$logoWidth = 40;  // Ancho original del logo en milímetros
//$logoHeight = 40; // Altura original del logo en milímetros
//$pdf->Image($logo, 10, 10, $logoWidth * 0.35, $logoHeight * 0.35);

// Agrega el título
$pdf->SetX($pdf->GetPageWidth() - 60);
$pdf->Cell(60, 10, 'Formulario de Servicio Técnico', 0, 1, 'R');
$pdf->Ln(10);

// Dibuja la línea de separación
$pdf->Line(10, 60, $pdf->GetPageWidth() - 10, 60);

// Configura el contenido del formulario
$contenidoY = 70;

// Cliente
$pdf->SetXY(20, $contenidoY);
$pdf->Cell(0, 10, 'Cliente: ' . $cliente, 0, 1);

// Fecha
$pdf->SetXY($pdf->GetPageWidth() / 2 + 10, $contenidoY);
$pdf->Cell(0, 10, 'Fecha y Hora: ' . $fecha, 0, 1);

// Dirección
$pdf->SetXY(20, $contenidoY + 10);
$pdf->Cell(0, 10, 'Dirección: ' . $direccion, 0, 1);

// Ticket
$pdf->SetXY($pdf->GetPageWidth() / 2 + 10, $contenidoY + 10);
$pdf->Cell(0, 10, 'Ticket No.: ' . $ticket, 0, 1);

// Reporte del Cliente
$pdf->SetXY(20, $contenidoY + 20);
$pdf->Cell(0, 10, 'Reporte del Cliente: ' . $reporteCliente, 0, 1);

// Diagnóstico Técnico
$pdf->SetXY($pdf->GetPageWidth() / 2 + 10, $contenidoY + 20);
$pdf->Cell(0, 10, 'Diagnóstico Técnico: ' . $diagnosticoTecnico, 0, 1);

// Trabajo Realizado
$pdf->SetXY(20, $contenidoY + 30);
$pdf->Cell(0, 10, 'Trabajo Realizado: ' . $trabajoRealizado, 0, 1);

// Observaciones
$pdf->SetXY($pdf->GetPageWidth() / 2 + 10, $contenidoY + 30);
$pdf->Cell(0, 10, 'Observaciones/Recomendaciones: ' . $observaciones, 0, 1);

// Línea de firma 1
$pdf->SetXY(20, $contenidoY + 40);
$pdf->Cell(0, 10, '_________________________', 0, 1);

// Línea de firma 2
$pdf->SetXY($pdf->GetPageWidth() / 2 + 10, $contenidoY + 40);
$pdf->Cell(0, 10, '_________________________', 0, 1);

// Recibido Conforme
$pdf->SetXY(20, $contenidoY + 50);
$pdf->Cell(0, 10, 'Recibido Conforme: ' . $recibidoConforme, 0, 1);

// Firma Técnico
$pdf->SetXY($pdf->GetPageWidth() / 2 + 10, $contenidoY + 50);
$pdf->Cell(0, 10, 'Firma Técnico: ' . $firmaTecnico, 0, 1);

// Dibuja la línea de separación
$pdf->Line(10, $contenidoY + 60, $pdf->GetPageWidth() - 10, $contenidoY + 60);

/// Configura el pie de página
$pieTextY = $pdf->GetPageHeight() - 10;
$pdf->SetFont('times', '', 11);

// Texto a la izquierda
$pdf->Text(20, $pieTextY, 'ITS Panama | soporte@itspanama.net | www.itspanama.net');

// Texto a la derecha (número total de páginas)
$numeroTotalPaginas = $pdf->getNumPages();
$pdf->Text($pdf->GetPageWidth() - 80, $pieTextY, 'Página ' . $pdf->getPage() . ' de ' . $numeroTotalPaginas);

// Define el nombre del archivo de salida
$nombreArchivo = $cliente . '_' . str_replace([' ', ':'], '_', $fecha) . '.pdf';

// Salida del PDF (descarga el archivo)
$pdf->Output($nombreArchivo, 'D');
?>