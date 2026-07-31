<?php
// modules/minsur/reportes/exportar.php - Exportar PDF o Excel
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$db = getMongoDB();
$reportes = $db->selectCollection('reportes_guardia');

// Filtros para exportación
$filtros = [];
if (!empty($_GET['fecha_desde']) && !empty($_GET['fecha_hasta'])) {
    $filtros['fecha']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['fecha_desde']) * 1000);
    $filtros['fecha']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['fecha_hasta'] . ' 23:59:59') * 1000);
}
if (!empty($_GET['turno'])) $filtros['turno'] = $_GET['turno'];
if (!empty($_GET['prioridad'])) $filtros['prioridad'] = $_GET['prioridad'];

$cursor = $reportes->find($filtros, ['sort' => ['fecha' => -1]]);
$lista = $cursor->toArray();

$formato = $_GET['formato'] ?? '';

// ============================================
// EXPORTAR CSV / EXCEL
// ============================================
if ($formato === 'excel' || $formato === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reportes_minsur_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Fecha', 'Turno', 'Supervisor', 'Área', 'Personal', 'Incidencias', 'Prioridad', 'Estado']);
    foreach ($lista as $r) {
        fputcsv($output, [
            formatDateOnly($r['fecha']),
            $r['turno'] ?? '',
            $r['supervisor'] ?? '',
            $r['area'] ?? '',
            count($r['personal'] ?? []),
            count($r['incidencias_generales'] ?? []),
            $r['prioridad'] ?? 'Media',
            $r['estado'] ?? 'Pendiente'
        ]);
    }
    fclose($output);
    exit;
}

// ============================================
// EXPORTAR PDF (SIN TCPDF - USANDO HTML)
// ============================================
if ($formato === 'pdf') {
    // Usamos una solución simple con HTML para PDF
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename=reportes_minsur_' . date('Y-m-d') . '.html');
    
    echo '<!DOCTYPE html>';
    echo '<html><head><meta charset="UTF-8"><title>Reportes MINSUR</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #1e40af; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #1e40af; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border: 1px solid #ddd; }
        tr:nth-child(even) { background: #f9f9f9; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>';
    echo '</head><body>';
    echo '<h1>MINSUR S.A. - Reportes de Guardia</h1>';
    echo '<p style="text-align:center;">Fecha: ' . date('d/m/Y H:i') . '</p>';
    echo '<table>';
    echo '<tr><th>Fecha</th><th>Turno</th><th>Supervisor</th><th>Área</th><th>Personal</th><th>Incidencias</th><th>Prioridad</th><th>Estado</th></tr>';
    foreach ($lista as $r) {
        echo '<tr>';
        echo '<td>' . formatDateOnly($r['fecha']) . '</td>';
        echo '<td>' . ($r['turno'] ?? '') . '</td>';
        echo '<td>' . ($r['supervisor'] ?? '') . '</td>';
        echo '<td>' . ($r['area'] ?? '') . '</td>';
        echo '<td>' . count($r['personal'] ?? []) . '</td>';
        echo '<td>' . count($r['incidencias_generales'] ?? []) . '</td>';
        echo '<td>' . ($r['prioridad'] ?? 'Media') . '</td>';
        echo '<td>' . ($r['estado'] ?? 'Pendiente') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '<p class="footer">Total: ' . count($lista) . ' reportes</p>';
    echo '</body></html>';
    exit;
}

// ============================================
// MENÚ DE EXPORTACIÓN
// ============================================
?>
<!DOCTYPE html>
<html>
<head>
    <title>Exportar Reportes</title>
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { padding: 40px; font-family: 'Inter', sans-serif; background: var(--bg); }
        .export-container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 16px; box-shadow: var(--shadow); }
        .export-container h2 { margin-bottom: 8px; }
        .export-container p { color: var(--text-2); margin-bottom: 24px; }
        .export-buttons { display: flex; gap: 12px; flex-wrap: wrap; }
        .export-buttons .btn { flex: 1; min-width: 120px; justify-content: center; padding: 12px; }
    </style>
</head>
<body>
<div class="export-container">
    <h2><i class="fas fa-file-export"></i> Exportar Reportes</h2>
    <p>Selecciona el formato para exportar los reportes</p>
    <div class="export-buttons">
        <a href="?formato=pdf" class="btn btn-primary">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
        <a href="?formato=excel" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Excel
        </a>
        <a href="<?= BASE ?>/modules/minsur/reportes/" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>
</body>
</html>
<?php
?>