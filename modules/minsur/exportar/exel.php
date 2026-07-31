<?php
// modules/minsur/exportar/excel.php - Exportar a Excel
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$db = getMongoDB();
$reportes = $db->selectCollection('reportes_guardia');

// Obtener reportes según filtros
$filtros = [];
if (!empty($_GET['fecha_desde']) && !empty($_GET['fecha_hasta'])) {
    $filtros['fecha']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['fecha_desde']) * 1000);
    $filtros['fecha']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['fecha_hasta'] . ' 23:59:59') * 1000);
}

$lista = $reportes->find($filtros, ['sort' => ['fecha' => -1]])->toArray();

// Cabeceras CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=reportes_minsur_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Fecha', 'Turno', 'Supervisor', 'Área', 'Personal', 'Incidencias', 'Prioridad', 'Estado', 'Descripción']);

foreach ($lista as $r) {
    fputcsv($output, [
        formatDateOnly($r['fecha']),
        $r['turno'] ?? '',
        $r['supervisor'] ?? '',
        $r['area'] ?? '',
        count($r['personal'] ?? []),
        count($r['incidencias_generales'] ?? []),
        $r['prioridad'] ?? 'Media',
        $r['estado'] ?? 'Pendiente',
        substr($r['descripcion'] ?? '', 0, 100)
    ]);
}

fclose($output);
exit;
?>