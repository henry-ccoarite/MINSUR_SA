<?php
// modules/minsur/estadisticas/index.php - Estadísticas completas MINSUR
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$pageTitle = 'Estadísticas - MINSUR';
$pg = 'minsur_estadisticas';
$db = getMongoDB();

$reportes = $db->selectCollection('reportes_guardia');
$personal = $db->selectCollection('personal_mina');

// ============================================================
// 1. KPIS PRINCIPALES
// ============================================================
$total_reportes = $reportes->countDocuments();
$total_personal = $personal->countDocuments(['activo' => true]);
$total_personal_total = $personal->countDocuments();

$total_incidencias = $reportes->aggregate([
    ['$unwind' => '$incidencias_generales'],
    ['$count' => 'total']
])->toArray();
$total_incidencias = $total_incidencias[0]['total'] ?? 0;

// Reportes con evidencias
$reportes_con_evidencia = $reportes->countDocuments([
    'imagenes' => ['$exists' => true, '$ne' => []]
]);
$porcentaje_evidencia = $total_reportes > 0 ? round(($reportes_con_evidencia / $total_reportes) * 100, 1) : 0;

// Evidencias totales
$total_evidencias = 0;
$todas_las_evidencias = [];
$evidencias_por_tipo = [];

$reportes_con_evidencias = $reportes->find(
    ['imagenes' => ['$exists' => true, '$ne' => []]],
    ['sort' => ['fecha' => -1]]
)->toArray();

foreach ($reportes_con_evidencias as $r) {
    if (!empty($r['imagenes'])) {
        foreach ($r['imagenes'] as $img) {
            $tipo_incidencia = 'Sin clasificar';
            if (!empty($r['incidencias_generales'])) {
                $tipo_incidencia = $r['incidencias_generales'][0]['tipo'] ?? 'Sin clasificar';
            }
            if (!isset($evidencias_por_tipo[$tipo_incidencia])) {
                $evidencias_por_tipo[$tipo_incidencia] = 0;
            }
            $evidencias_por_tipo[$tipo_incidencia]++;
            $todas_las_evidencias[] = [
                'imagen' => $img,
                'fecha' => $r['fecha'],
                'turno' => $r['turno'] ?? '',
                'supervisor' => $r['supervisor'] ?? '',
                'tipo_incidencia' => $tipo_incidencia,
                'reporte_id' => getId($r)
            ];
            $total_evidencias++;
        }
    }
}

// Reportes de hoy
$hoy = date('Y-m-d');
$reportes_hoy = $reportes->countDocuments([
    'fecha' => [
        '$gte' => new MongoDB\BSON\UTCDateTime(strtotime($hoy) * 1000),
        '$lte' => new MongoDB\BSON\UTCDateTime(strtotime($hoy . ' 23:59:59') * 1000)
    ]
]);

$reportes_semana = $reportes->countDocuments([
    'fecha' => [
        '$gte' => new MongoDB\BSON\UTCDateTime(strtotime(date('Y-m-d', strtotime('-7 days'))) * 1000)
    ]
]);

// ============================================================
// 2. INCIDENCIAS POR SUPERVISOR
// ============================================================
$incidencias_supervisor = $reportes->aggregate([
    ['$unwind' => '$incidencias_generales'],
    ['$group' => ['_id' => '$supervisor', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]],
    ['$limit' => 5]
])->toArray();

// ============================================================
// 3. INCIDENCIAS POR ÁREA
// ============================================================
$incidencias_area = $reportes->aggregate([
    ['$unwind' => '$incidencias_generales'],
    ['$group' => ['_id' => '$area', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]]
])->toArray();

// ============================================================
// 4. INCIDENCIAS POR DÍA DE LA SEMANA
// ============================================================
$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$incidencias_dia = [];
foreach ($dias_semana as $idx => $dia) {
    $count = $reportes->countDocuments([
        '$expr' => [
            '$eq' => [
                ['$dayOfWeek' => '$fecha'],
                $idx + 1
            ]
        ]
    ]);
    $incidencias_dia[] = ['dia' => $dia, 'count' => $count];
}

// ============================================================
// 5. INCIDENCIAS POR HORA
// ============================================================
$incidencias_hora = [];
for ($h = 0; $h < 24; $h++) {
    $incidencias_hora[$h] = 0;
}
foreach ($reportes->find()->toArray() as $r) {
    if (!empty($r['incidencias_generales'])) {
        $hora = (int)date('H', $r['fecha']->toDateTime()->getTimestamp());
        $incidencias_hora[$hora] += count($r['incidencias_generales']);
    }
}

// ============================================================
// 6. DÍAS SIN INCIDENTES CRÍTICOS
// ============================================================
$ultima_critica = $reportes->findOne(
    ['prioridad' => 'Crítica'],
    ['sort' => ['fecha' => -1]]
);
if ($ultima_critica) {
    $dias_sin_incidentes = floor((time() - $ultima_critica['fecha']->toDateTime()->getTimestamp()) / 86400);
} else {
    $dias_sin_incidentes = 'N/A (sin incidentes críticos)';
}

// ============================================================
// 7. ÍNDICE DE FRECUENCIA
// ============================================================
$indice_frecuencia = $total_reportes > 0 ? round(($total_incidencias / $total_reportes) * 100, 1) : 0;

// ============================================================
// 8. TOP TRABAJADORES CON MÁS INCIDENCIAS
// ============================================================
$top_incidencias = $reportes->aggregate([
    ['$unwind' => '$personal'],
    ['$group' => [
        '_id' => '$personal.nombre',
        'total_incidencias' => ['$sum' => ['$cond' => [
            ['$ne' => ['$personal.incidencias', 'Ninguna']],
            1, 0
        ]]],
        'total_reportes' => ['$sum' => 1],
        'cargo' => ['$first' => '$personal.cargo'],
        'area' => ['$first' => '$personal.area']
    ]],
    ['$match' => ['total_incidencias' => ['$gt' => 0]]],
    ['$sort' => ['total_incidencias' => -1]],
    ['$limit' => 5]
])->toArray();

// ============================================================
// 9. INCIDENCIAS POR GRAVEDAD
// ============================================================
$incidencias_gravedad = $reportes->aggregate([
    ['$unwind' => '$incidencias_generales'],
    ['$group' => ['_id' => '$incidencias_generales.gravedad', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]]
])->toArray();

// ============================================================
// 10. INCIDENCIAS POR TIPO
// ============================================================
$incidencias_tipo = $reportes->aggregate([
    ['$unwind' => '$incidencias_generales'],
    ['$group' => ['_id' => '$incidencias_generales.tipo', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]]
])->toArray();

// ============================================================
// 11. REPORTES POR TURNO
// ============================================================
$reportes_turno = $reportes->aggregate([
    ['$group' => ['_id' => '$turno', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]]
])->toArray();

// ============================================================
// 12. REPORTES POR MES
// ============================================================
$meses = [];
$reportes_mes = [];
for ($i = 5; $i >= 0; $i--) {
    $fecha = date('Y-m-01', strtotime("-$i months"));
    $fecha_fin = date('Y-m-t', strtotime("-$i months"));
    $meses[] = date('M Y', strtotime("-$i months"));
    $count = $reportes->countDocuments([
        'fecha' => [
            '$gte' => new MongoDB\BSON\UTCDateTime(strtotime($fecha) * 1000),
            '$lte' => new MongoDB\BSON\UTCDateTime(strtotime($fecha_fin . ' 23:59:59') * 1000)
        ]
    ]);
    $reportes_mes[] = $count;
}

// ============================================================
// 13. PERSONAL POR CARGO
// ============================================================
$personal_cargo = $personal->aggregate([
    ['$match' => ['activo' => true]],
    ['$group' => ['_id' => '$cargo', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]]
])->toArray();

// ============================================================
// 14. PERSONAL POR TURNO
// ============================================================
$personal_turno = $personal->aggregate([
    ['$match' => ['activo' => true]],
    ['$group' => ['_id' => '$turno_asignado', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]]
])->toArray();

// ============================================================
// 15. REPORTES POR SUPERVISOR
// ============================================================
$reportes_supervisor = $reportes->aggregate([
    ['$group' => ['_id' => '$supervisor', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]],
    ['$limit' => 5]
])->toArray();

// ============================================================
// 16. REPORTES POR ÁREA
// ============================================================
$reportes_area = $reportes->aggregate([
    ['$group' => ['_id' => '$area', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]]
])->toArray();

// ============================================================
// 17. ACTIVIDADES MÁS COMUNES
// ============================================================
$actividades_comunes = $reportes->aggregate([
    ['$unwind' => '$personal'],
    ['$group' => ['_id' => '$personal.actividad', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]],
    ['$limit' => 5]
])->toArray();

// ============================================================
// 18. ÚLTIMOS REPORTES
// ============================================================
$ultimos_reportes = $reportes->find(
    [],
    ['sort' => ['fecha' => -1], 'limit' => 5]
)->toArray();

// ============================================================
// 19. GRAVEDAD PROMEDIO (asignando valores numéricos)
// ============================================================
$gravedad_valores = ['Baja' => 1, 'Media' => 2, 'Alta' => 3, 'Crítica' => 4];
$suma_gravedad = 0;
$total_incidencias_gravedad = 0;

foreach ($incidencias_gravedad as $g) {
    $valor = $gravedad_valores[$g['_id']] ?? 0;
    $suma_gravedad += $valor * $g['count'];
    $total_incidencias_gravedad += $g['count'];
}
$gravedad_promedio = $total_incidencias_gravedad > 0 ? round($suma_gravedad / $total_incidencias_gravedad, 2) : 0;

// ============================================================
// 20. INCIDENCIAS RECURRENTES (descripciones similares)
// ============================================================
// Tomamos las incidencias más frecuentes por descripción
$incidencias_recurrentes = $reportes->aggregate([
    ['$unwind' => '$incidencias_generales'],
    ['$group' => ['_id' => '$incidencias_generales.descripcion', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]],
    ['$limit' => 3]
])->toArray();

require_once __DIR__ . '/../../../includes/layout.php';
$isMinsur = true;
?>

<style>
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px; }
    .kpi-card {
        background: var(--white); border-radius: var(--radius-lg); padding: 14px 16px;
        border: 1px solid var(--border); box-shadow: var(--shadow); text-align: center;
        transition: all 0.3s; position: relative; overflow: hidden;
    }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .kpi-card .kpi-icon { font-size: 22px; margin-bottom: 2px; color: var(--text-3); }
    .kpi-card .kpi-number { font-size: 26px; font-weight: 700; }
    .kpi-card .kpi-label { font-size: 11px; color: var(--text-2); margin-top: 2px; }
    .kpi-card .kpi-bg { position: absolute; bottom: -40px; right: -40px; width: 120px; height: 120px; border-radius: 50%; opacity: 0.04; }
    .kpi-card.blue .kpi-number { color: #1e40af; }
    .kpi-card.blue .kpi-bg { background: #1e40af; }
    .kpi-card.green .kpi-number { color: #16a34a; }
    .kpi-card.green .kpi-bg { background: #16a34a; }
    .kpi-card.orange .kpi-number { color: #ea580c; }
    .kpi-card.orange .kpi-bg { background: #ea580c; }
    .kpi-card.red .kpi-number { color: #dc2626; }
    .kpi-card.red .kpi-bg { background: #dc2626; }
    .kpi-card.purple .kpi-number { color: #7c3aed; }
    .kpi-card.purple .kpi-bg { background: #7c3aed; }
    .kpi-card.teal .kpi-number { color: #0d9488; }
    .kpi-card.teal .kpi-bg { background: #0d9488; }

    .stat-section-title {
        font-size: 16px; font-weight: 600; margin: 20px 0 12px 0;
        display: flex; align-items: center; gap: 10px;
    }
    .stat-section-title i { color: var(--text-3); }
    .stat-section-title .badge-count {
        background: var(--primary-l); color: var(--primary);
        font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 20px;
    }

    .two-col-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .three-col-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .four-col-grid { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px; }
    .chart-wrap { height: 220px; position: relative; }

    .top-list { list-style: none; padding: 0; margin: 0; }
    .top-list li {
        display: flex; align-items: center; padding: 6px 0;
        border-bottom: 1px solid var(--border-light);
    }
    .top-list li:last-child { border-bottom: none; }
    .top-list .rank {
        width: 26px; height: 26px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 11px; margin-right: 10px; flex-shrink: 0;
    }
    .top-list .rank.gold { background: #fef3c7; color: #ca8a04; }
    .top-list .rank.silver { background: #e5e7eb; color: #6b7280; }
    .top-list .rank.bronze { background: #fed7aa; color: #ea580c; }
    .top-list .rank.normal { background: var(--bg); color: var(--text-3); }
    .top-list .info { flex: 1; }
    .top-list .info .name { font-weight: 600; font-size: 13px; }
    .top-list .info .detail { font-size: 11px; color: var(--text-3); }
    .top-list .badge-count {
        background: var(--red); color: white;
        padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
    }
    .top-list .badge-count.green { background: #16a34a; }
    .top-list .badge-count.blue { background: #1e40af; }

    .evidencia-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 8px; margin-top: 8px;
    }
    .evidencia-item {
        position: relative; border-radius: 6px; overflow: hidden;
        border: 1px solid var(--border); background: var(--bg);
        aspect-ratio: 1; cursor: pointer; transition: all 0.2s;
    }
    .evidencia-item:hover { transform: scale(1.03); box-shadow: var(--shadow-md); }
    .evidencia-item img, .evidencia-item video {
        width: 100%; height: 100%; object-fit: cover;
    }
    .evidencia-item .badge-tipo {
        position: absolute; bottom: 3px; left: 3px;
        background: rgba(0,0,0,0.7); color: white;
        font-size: 8px; padding: 2px 6px; border-radius: 3px;
        font-weight: 600; max-width: 90%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .evidencia-item .badge-archivos {
        position: absolute; top: 3px; right: 3px;
        background: rgba(0,0,0,0.6); color: white;
        font-size: 8px; padding: 2px 6px; border-radius: 3px;
    }

    .empty-state { text-align: center; padding: 20px; color: var(--text-3); }
    .empty-state i { font-size: 30px; display: block; margin-bottom: 8px; opacity: 0.5; }

    .stat-mini-card {
        background: var(--bg); border-radius: 8px; padding: 12px 14px;
        text-align: center; border: 1px solid var(--border-light);
    }
    .stat-mini-card .num { font-size: 18px; font-weight: 700; }
    .stat-mini-card .lbl { font-size: 10px; color: var(--text-3); }
    .stat-mini-card.blue .num { color: #1e40af; }
    .stat-mini-card.green .num { color: #16a34a; }
    .stat-mini-card.orange .num { color: #ea580c; }
    .stat-mini-card.red .num { color: #dc2626; }
    .stat-mini-card.purple .num { color: #7c3aed; }

    .report-item {
        display: flex; align-items: center; padding: 6px 0;
        border-bottom: 1px solid var(--border-light); gap: 10px;
    }
    .report-item:last-child { border-bottom: none; }
    .report-item .dot {
        width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
    }
    .report-item .dot.verde { background: #16a34a; }
    .report-item .dot.amarillo { background: #ca8a04; }
    .report-item .dot.naranja { background: #ea580c; }
    .report-item .dot.rojo { background: #dc2626; }
    .report-item .content { flex: 1; }
    .report-item .content .title { font-weight: 500; font-size: 13px; }
    .report-item .content .meta { font-size: 11px; color: var(--text-3); }
    .report-item .time { font-size: 11px; color: var(--text-3); white-space: nowrap; }

    .modal-evidencias {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.8); z-index: 1000;
        display: none; align-items: center; justify-content: center; padding: 20px;
    }
    .modal-evidencias.active { display: flex; }
    .modal-evidencias .modal-content {
        max-width: 80%; max-height: 80%; background: white;
        border-radius: 12px; overflow: hidden; position: relative;
    }
    .modal-evidencias .modal-content img,
    .modal-evidencias .modal-content video {
        max-width: 100%; max-height: 70vh; display: block;
    }
    .modal-evidencias .close-modal {
        position: absolute; top: 10px; right: 10px;
        background: rgba(0,0,0,0.7); color: white; border: none;
        border-radius: 50%; width: 34px; height: 34px;
        font-size: 18px; cursor: pointer;
    }
    .modal-evidencias .info-modal {
        padding: 10px 16px; background: white; font-size: 13px;
    }

    @media (max-width: 992px) {
        .two-col-grid, .three-col-grid, .four-col-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 600px) {
        .two-col-grid, .three-col-grid, .four-col-grid { grid-template-columns: 1fr; }
        .kpi-grid { grid-template-columns: 1fr 1fr; }
    }
</style>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/minsur/index.php" style="font-size:13px;color:var(--text-2);">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
        <h1 style="margin-top:4px;"><i class="fas fa-chart-bar"></i> Estadísticas MINSUR</h1>
        <p><i class="fas fa-database"></i> Análisis completo de datos de la mina San Rafael</p>
    </div>
</div>

<!-- ============================================================ -->
<!-- 1. KPIS PRINCIPALES                                           -->
<!-- ============================================================ -->
<div class="kpi-grid">
    <div class="kpi-card blue">
        <div class="kpi-icon"><i class="fas fa-clipboard-list"></i></div>
        <div class="kpi-number"><?= $total_reportes ?></div>
        <div class="kpi-label">Total Reportes</div>
        <div class="kpi-bg"></div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-icon"><i class="fas fa-users"></i></div>
        <div class="kpi-number"><?= $total_personal ?></div>
        <div class="kpi-label">Personal Activo</div>
        <div class="kpi-bg"></div>
    </div>
    <div class="kpi-card purple">
        <div class="kpi-icon"><i class="fas fa-camera"></i></div>
        <div class="kpi-number"><?= $total_evidencias ?></div>
        <div class="kpi-label">Evidencias Subidas</div>
        <div class="kpi-bg"></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="kpi-number"><?= $total_incidencias ?></div>
        <div class="kpi-label">Total Incidencias</div>
        <div class="kpi-bg"></div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-icon"><i class="fas fa-calendar-day"></i></div>
        <div class="kpi-number"><?= $reportes_hoy ?></div>
        <div class="kpi-label">Reportes de Hoy</div>
        <div class="kpi-bg"></div>
    </div>
    <div class="kpi-card teal">
        <div class="kpi-icon"><i class="fas fa-calendar-week"></i></div>
        <div class="kpi-number"><?= $reportes_semana ?></div>
        <div class="kpi-label">Reportes (7 días)</div>
        <div class="kpi-bg"></div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 2. ESTADÍSTICAS DE SEGURIDAD Y EFICIENCIA                     -->
<!-- ============================================================ -->
<div class="four-col-grid">
    <div class="stat-mini-card blue">
        <div class="num"><?= $dias_sin_incidentes === 'N/A (sin incidentes críticos)' ? '∞' : $dias_sin_incidentes ?></div>
        <div class="lbl">Días sin incidentes críticos</div>
    </div>
    <div class="stat-mini-card orange">
        <div class="num"><?= $indice_frecuencia ?>%</div>
        <div class="lbl">Índice de frecuencia</div>
    </div>
    <div class="stat-mini-card green">
        <div class="num"><?= $porcentaje_evidencia ?>%</div>
        <div class="lbl">Reportes con evidencias</div>
    </div>
    <div class="stat-mini-card purple">
        <div class="num"><?= $gravedad_promedio ?></div>
        <div class="lbl">Gravedad promedio</div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 3. EVIDENCIAS POR TIPO                                        -->
<!-- ============================================================ -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-hd">
        <span class="card-title"><i class="fas fa-camera"></i> Evidencias por Tipo de Incidencia</span>
        <span style="font-size:12px;color:var(--text-3);"><?= $total_evidencias ?> archivos</span>
    </div>
    <div class="card-body">
        <?php if(!empty($evidencias_por_tipo)): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:8px;">
                <?php foreach($evidencias_por_tipo as $tipo => $cantidad): ?>
                    <div class="stat-mini-card blue">
                        <div class="num"><?= $cantidad ?></div>
                        <div class="lbl"><?= u($tipo) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><i class="fas fa-camera"></i><p>No hay evidencias subidas aún</p></div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================ -->
<!-- 4. GALERÍA DE EVIDENCIAS                                      -->
<!-- ============================================================ -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-hd">
        <span class="card-title"><i class="fas fa-images"></i> Galería de Evidencias</span>
        <span style="font-size:12px;color:var(--text-3);">Últimas <?= min(count($todas_las_evidencias), 12) ?></span>
    </div>
    <div class="card-body">
        <?php if(!empty($todas_las_evidencias)): ?>
            <div class="evidencia-grid">
                <?php $cont = 0; foreach($todas_las_evidencias as $ev): if($cont >= 12) break; $cont++;
                    $ext = pathinfo($ev['imagen'], PATHINFO_EXTENSION);
                    $is_video = in_array(strtolower($ext), ['mp4', 'webm', 'ogg', 'mov']);
                ?>
                    <div class="evidencia-item" onclick="verEvidencia('<?= BASE ?>/uploads/minsur/<?= u($ev['imagen']) ?>', '<?= addslashes($ev['tipo_incidencia']) ?>', '<?= formatDateOnly($ev['fecha']) ?>', '<?= addslashes($ev['supervisor']) ?>')">
                        <?php if($is_video): ?>
                            <video src="<?= BASE ?>/uploads/minsur/<?= u($ev['imagen']) ?>" muted></video>
                            <div class="badge-archivos"><i class="fas fa-video"></i></div>
                        <?php else: ?>
                            <img src="<?= BASE ?>/uploads/minsur/<?= u($ev['imagen']) ?>">
                            <div class="badge-archivos"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                        <div class="badge-tipo"><?= u($ev['tipo_incidencia']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if(count($todas_las_evidencias) > 12): ?>
                <p style="text-align:center;margin-top:8px;font-size:11px;color:var(--text-3);">
                    <i class="fas fa-plus-circle"></i> +<?= count($todas_las_evidencias) - 12 ?> evidencias más
                </p>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state"><i class="fas fa-images"></i><p>No hay evidencias disponibles</p></div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================ -->
<!-- 5. TOP TRABAJADORES + ÚLTIMOS REPORTES                       -->
<!-- ============================================================ -->
<div class="two-col-grid">
    <div class="card">
        <div class="card-hd">
            <span class="card-title"><i class="fas fa-trophy"></i> Top Trabajadores con Incidencias</span>
        </div>
        <div class="card-body">
            <?php if(!empty($top_incidencias)): ?>
                <ul class="top-list">
                    <?php foreach($top_incidencias as $idx => $t):
                        $rank_class = $idx === 0 ? 'gold' : ($idx === 1 ? 'silver' : ($idx === 2 ? 'bronze' : 'normal'));
                        $medal = $idx === 0 ? '🥇' : ($idx === 1 ? '🥈' : ($idx === 2 ? '🥉' : '#'.($idx+1)));
                    ?>
                        <li>
                            <div class="rank <?= $rank_class ?>"><?= $medal ?></div>
                            <div class="info">
                                <div class="name"><?= u($t['_id']) ?></div>
                                <div class="detail"><?= u($t['cargo'] ?? '—') ?> · <?= u($t['area'] ?? '—') ?></div>
                            </div>
                            <div class="badge-count"><?= $t['total_incidencias'] ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-check-circle"></i><p>¡Excelente! No hay trabajadores con incidencias.</p></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-hd">
            <span class="card-title"><i class="fas fa-clock"></i> Últimos Reportes</span>
            <a href="<?= BASE ?>/modules/minsur/reportes/" class="btn btn-outline btn-sm"><i class="fas fa-arrow-right"></i> Ver todos</a>
        </div>
        <div class="card-body" style="padding:8px 20px;">
            <?php if(!empty($ultimos_reportes)): ?>
                <?php foreach($ultimos_reportes as $r):
                    $dot_color = ($r['prioridad'] ?? 'Media') === 'Crítica' ? 'rojo' : (($r['prioridad'] ?? 'Media') === 'Alta' ? 'naranja' : (($r['prioridad'] ?? 'Media') === 'Media' ? 'amarillo' : 'verde'));
                ?>
                    <div class="report-item">
                        <div class="dot <?= $dot_color ?>"></div>
                        <div class="content">
                            <div class="title"><?= u($r['supervisor']) ?> · <?= u($r['turno']) ?></div>
                            <div class="meta"><i class="fas fa-user"></i> <?= count($r['personal'] ?? []) ?> personas · <i class="fas fa-camera"></i> <?= count($r['imagenes'] ?? []) ?> evidencias</div>
                        </div>
                        <div class="time"><?= formatDateOnly($r['fecha']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-inbox"></i><p>No hay reportes recientes</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 6. INCIDENCIAS POR SUPERVISOR                                 -->
<!-- ============================================================ -->
<div class="two-col-grid">
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-user-tie"></i> Incidencias por Supervisor</span></div>
        <div class="card-body">
            <?php if(!empty($incidencias_supervisor)): ?>
                <ul class="top-list">
                    <?php foreach($incidencias_supervisor as $idx => $s): ?>
                        <li>
                            <div class="rank normal">#<?= $idx + 1 ?></div>
                            <div class="info"><div class="name"><?= u($s['_id'] ?? '—') ?></div></div>
                            <div class="badge-count blue"><?= $s['count'] ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-inbox"></i><p>No hay datos</p></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-building"></i> Incidencias por Área</span></div>
        <div class="card-body">
            <?php if(!empty($incidencias_area)): ?>
                <ul class="top-list">
                    <?php foreach($incidencias_area as $idx => $a): ?>
                        <li>
                            <div class="rank normal">#<?= $idx + 1 ?></div>
                            <div class="info"><div class="name"><?= u($a['_id'] ?? '—') ?></div></div>
                            <div class="badge-count blue"><?= $a['count'] ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-inbox"></i><p>No hay datos</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 7. INCIDENCIAS POR DÍA Y HORA                                 -->
<!-- ============================================================ -->
<div class="two-col-grid">
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-calendar-day"></i> Incidencias por Día de la Semana</span></div>
        <div class="card-body">
            <div class="chart-wrap"><canvas id="chartIncidenciasDia"></canvas></div>
        </div>
    </div>
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-clock"></i> Incidencias por Hora</span></div>
        <div class="card-body">
            <div class="chart-wrap"><canvas id="chartIncidenciasHora"></canvas></div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 8. INCIDENCIAS RECURRENTES                                    -->
<!-- ============================================================ -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-hd"><span class="card-title"><i class="fas fa-redo"></i> Incidencias Recurrentes</span></div>
    <div class="card-body">
        <?php if(!empty($incidencias_recurrentes)): ?>
            <ul class="top-list">
                <?php foreach($incidencias_recurrentes as $idx => $ir): ?>
                    <li>
                        <div class="rank normal">#<?= $idx + 1 ?></div>
                        <div class="info">
                            <div class="name"><?= u(substr($ir['_id'] ?? '', 0, 60)) ?>...</div>
                            <div class="detail">Se repite <?= $ir['count'] ?> veces</div>
                        </div>
                        <div class="badge-count orange"><?= $ir['count'] ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-state"><i class="fas fa-check-circle"></i><p>No hay incidencias recurrentes</p></div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================ -->
<!-- 9. GRÁFICAS PRINCIPALES                                       -->
<!-- ============================================================ -->
<div class="two-col-grid">
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-chart-line"></i> Reportes por Mes</span></div>
        <div class="card-body"><div class="chart-wrap"><canvas id="chartReportesMes"></canvas></div></div>
    </div>
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-chart-pie"></i> Incidencias por Tipo</span></div>
        <div class="card-body"><div class="chart-wrap"><canvas id="chartIncidenciasTipo"></canvas></div></div>
    </div>
</div>

<div class="two-col-grid">
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-chart-pie"></i> Incidencias por Gravedad</span></div>
        <div class="card-body"><div class="chart-wrap"><canvas id="chartIncidenciasGravedad"></canvas></div></div>
    </div>
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-clock"></i> Reportes por Turno</span></div>
        <div class="card-body"><div class="chart-wrap"><canvas id="chartReportesTurno"></canvas></div></div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 10. PERSONAL Y REPORTES POR CATEGORÍA                         -->
<!-- ============================================================ -->
<div class="three-col-grid">
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-user-tie"></i> Personal por Cargo</span></div>
        <div class="card-body">
            <?php if(!empty($personal_cargo)): ?>
                <?php foreach($personal_cargo as $p): ?>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--border-light);">
                        <span><?= u($p['_id'] ?? '—') ?></span>
                        <span class="badge-count blue"><?= $p['count'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-inbox"></i></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-clock"></i> Personal por Turno</span></div>
        <div class="card-body">
            <?php if(!empty($personal_turno)): ?>
                <?php foreach($personal_turno as $p): ?>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--border-light);">
                        <span><?= u($p['_id'] ?? '—') ?></span>
                        <span class="badge-count green"><?= $p['count'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-inbox"></i></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-tasks"></i> Actividades más Comunes</span></div>
        <div class="card-body">
            <?php if(!empty($actividades_comunes)): ?>
                <?php foreach($actividades_comunes as $a): ?>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--border-light);">
                        <span><?= u($a['_id'] ?? '—') ?></span>
                        <span class="badge-count purple"><?= $a['count'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-inbox"></i></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 11. REPORTES POR SUPERVISOR Y ÁREA                            -->
<!-- ============================================================ -->
<div class="two-col-grid">
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-user-tie"></i> Reportes por Supervisor</span></div>
        <div class="card-body">
            <?php if(!empty($reportes_supervisor)): ?>
                <?php foreach($reportes_supervisor as $r): ?>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--border-light);">
                        <span><?= u($r['_id'] ?? '—') ?></span>
                        <span class="badge-count blue"><?= $r['count'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-inbox"></i></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-building"></i> Reportes por Área</span></div>
        <div class="card-body">
            <?php if(!empty($reportes_area)): ?>
                <?php foreach($reportes_area as $r): ?>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--border-light);">
                        <span><?= u($r['_id'] ?? '—') ?></span>
                        <span class="badge-count green"><?= $r['count'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-inbox"></i></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL EVIDENCIAS                                              -->
<!-- ============================================================ -->
<div class="modal-evidencias" id="modalEvidencia">
    <div class="modal-content">
        <button class="close-modal" onclick="cerrarModal()"><i class="fas fa-times"></i></button>
        <div id="modalEvidenciaContenido"></div>
        <div class="info-modal" id="modalEvidenciaInfo"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ============================================================
// FUNCIONES PARA EVIDENCIAS
// ============================================================
function verEvidencia(src, tipo, fecha, supervisor) {
    const modal = document.getElementById('modalEvidencia');
    const contenido = document.getElementById('modalEvidenciaContenido');
    const info = document.getElementById('modalEvidenciaInfo');
    const ext = src.split('.').pop().toLowerCase();
    const isVideo = ['mp4', 'webm', 'ogg', 'mov'].includes(ext);
    if (isVideo) {
        contenido.innerHTML = `<video src="${src}" controls autoplay style="max-width:100%;max-height:70vh;"></video>`;
    } else {
        contenido.innerHTML = `<img src="${src}" style="max-width:100%;max-height:70vh;">`;
    }
    info.innerHTML = `
        <strong><i class="fas fa-tag"></i> ${tipo}</strong>
        <span style="margin-left:15px;"><i class="fas fa-calendar"></i> ${fecha}</span>
        <span style="margin-left:15px;"><i class="fas fa-user"></i> ${supervisor}</span>
    `;
    modal.classList.add('active');
}

function cerrarModal() {
    document.getElementById('modalEvidencia').classList.remove('active');
    document.getElementById('modalEvidenciaContenido').innerHTML = '';
}

document.addEventListener('keydown', function(e) { if (e.key === 'Escape') cerrarModal(); });
document.getElementById('modalEvidencia').addEventListener('click', function(e) { if (e.target === this) cerrarModal(); });

// ============================================================
// GRÁFICAS
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const gridColor = 'rgba(0,0,0,0.04)';
    const colors = ['#1e40af', '#dc2626', '#ca8a04', '#16a34a', '#7c3aed', '#ea580c'];

    // 1. Reportes por Mes
    new Chart(document.getElementById('chartReportesMes'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($meses) ?>,
            datasets: [{
                label: 'Reportes',
                data: <?= json_encode($reportes_mes) ?>,
                backgroundColor: 'rgba(30,64,175,0.7)',
                borderColor: '#1e40af',
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: gridColor }, ticks: { stepSize: 1 } }, x: { grid: { display: false } } } }
    });

    // 2. Incidencias por Tipo
    new Chart(document.getElementById('chartIncidenciasTipo'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($incidencias_tipo, '_id')) ?>,
            datasets: [{ data: <?= json_encode(array_column($incidencias_tipo, 'count')) ?>, backgroundColor: colors }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true } } } }
    });

    // 3. Incidencias por Gravedad
    new Chart(document.getElementById('chartIncidenciasGravedad'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($incidencias_gravedad, '_id')) ?>,
            datasets: [{ data: <?= json_encode(array_column($incidencias_gravedad, 'count')) ?>, backgroundColor: ['#16a34a', '#ca8a04', '#ea580c', '#dc2626'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true } } } }
    });

    // 4. Reportes por Turno
    new Chart(document.getElementById('chartReportesTurno'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($reportes_turno, '_id')) ?>,
            datasets: [{
                label: 'Reportes por Turno',
                data: <?= json_encode(array_column($reportes_turno, 'count')) ?>,
                backgroundColor: ['rgba(30,64,175,0.7)', 'rgba(22,163,74,0.7)', 'rgba(220,38,38,0.7)'],
                borderColor: ['#1e40af', '#16a34a', '#dc2626'],
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: gridColor }, ticks: { stepSize: 1 } }, x: { grid: { display: false } } } }
    });

    // 5. Incidencias por Día de la Semana
    new Chart(document.getElementById('chartIncidenciasDia'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($incidencias_dia, 'dia')) ?>,
            datasets: [{
                label: 'Incidencias',
                data: <?= json_encode(array_column($incidencias_dia, 'count')) ?>,
                backgroundColor: 'rgba(124,58,237,0.7)',
                borderColor: '#7c3aed',
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: gridColor }, ticks: { stepSize: 1 } }, x: { grid: { display: false } } } }
    });

    // 6. Incidencias por Hora
    new Chart(document.getElementById('chartIncidenciasHora'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(range(0, 23)) ?>,
            datasets: [{
                label: 'Incidencias',
                data: <?= json_encode(array_values($incidencias_hora)) ?>,
                backgroundColor: 'rgba(220,38,38,0.6)',
                borderColor: '#dc2626',
                borderWidth: 1,
                borderRadius: 3
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: gridColor }, ticks: { stepSize: 1 } }, x: { grid: { display: false } } } }
    });
});
</script>

<?php require_once __DIR__ . '/../../../includes/layout_end.php'; ?>