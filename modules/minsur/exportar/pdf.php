<?php
// modules/minsur/exportar/pdf.php - Exportar a PDF
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

// Usamos la librería TCPDF (debes instalarla con Composer)
require_once __DIR__ . '/../../../vendor/autoload.php';

use TCPDF;

$db = getMongoDB();
$reportes = $db->selectCollection('reportes_guardia');

// Obtener reportes según filtros
$filtros = [];
if (!empty($_GET['fecha_desde']) && !empty($_GET['fecha_hasta'])) {
    $filtros['fecha']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['fecha_desde']) * 1000);
    $filtros['fecha']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['fecha_hasta'] . ' 23:59:59') * 1000);
}

$lista = $reportes->find($filtros, ['sort' => ['fecha' => -1]])->toArray();

// Crear PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('MINSUR S.A.');
$pdf->SetAuthor('Sistema de Reportes');
$pdf->SetTitle('Reportes de Guardia - MINSUR');
$pdf->SetMargins(10, 10, 10);

$pdf->AddPage();

// Título
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'MINSUR S.A. - Reportes de Guardia', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Mina San Rafael', 0, 1, 'C');
$pdf->Cell(0, 6, 'Fecha: ' . date('d/m/Y H:i'), 0, 1, 'C');
$pdf->Ln(5);

// Encabezados de tabla
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(25, 7, 'Fecha', 1, 0, 'C');
$pdf->Cell(30, 7, 'Turno', 1, 0, 'C');
$pdf->Cell(35, 7, 'Supervisor', 1, 0, 'C');
$pdf->Cell(25, 7, 'Área', 1, 0, 'C');
$pdf->Cell(20, 7, 'Personal', 1, 0, 'C');
$pdf->Cell(20, 7, 'Incidencias', 1, 0, 'C');
$pdf->Cell(25, 7, 'Prioridad', 1, 1, 'C');

// Datos
$pdf->SetFont('helvetica', '', 9);
foreach ($lista as $r) {
    $fecha = formatDateOnly($r['fecha']);
    $turno = $r['turno'] ?? '';
    $supervisor = $r['supervisor'] ?? '';
    $area = $r['area'] ?? '';
    $personal = count($r['personal'] ?? []);
    $incidencias = count($r['incidencias_generales'] ?? []);
    $prioridad = $r['prioridad'] ?? 'Media';
    
    $pdf->Cell(25, 6, $fecha, 1, 0, 'C');
    $pdf->Cell(30, 6, $turno, 1, 0, 'C');
    $pdf->Cell(35, 6, $supervisor, 1, 0, 'C');
    $pdf->Cell(25, 6, $area, 1, 0, 'C');
    $pdf->Cell(20, 6, $personal, 1, 0, 'C');
    $pdf->Cell(20, 6, $incidencias, 1, 0, 'C');
    $pdf->Cell(25, 6, $prioridad, 1, 1, 'C');
}

// Total
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'Total: ' . count($lista) . ' reportes', 0, 1, 'R');

// Salida
$pdf->Output('reportes_minsur.pdf', 'I');
exit;
?>