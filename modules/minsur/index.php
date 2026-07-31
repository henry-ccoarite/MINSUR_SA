<?php
// modules/minsur/index.php - Dashboard MINSUR (Simplificado)
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();
requireMinsurAccess();

$pageTitle = 'Dashboard - MINSUR';
$pg = 'minsur';
$db = getMongoDB();

// ============================================
// ESTADÍSTICAS PRINCIPALES
// ============================================

$reportes = $db->selectCollection('reportes_guardia');
$personal = $db->selectCollection('personal_mina');

$total_reportes = $reportes->countDocuments();
$total_personal = $personal->countDocuments();
$personal_activo = $personal->countDocuments(['activo' => true]);

// Incidencias totales
$incidencias = $reportes->aggregate([
    ['$unwind' => '$incidencias_generales'],
    ['$count' => 'total']
])->toArray();
$total_incidencias = $incidencias[0]['total'] ?? 0;

// Personal por cargo (Top 4)
$cargos = $personal->aggregate([
    ['$match' => ['activo' => true]],
    ['$group' => ['_id' => '$cargo', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]],
    ['$limit' => 4]
])->toArray();

// Personal por área (Top 4)
$areas = $personal->aggregate([
    ['$match' => ['activo' => true]],
    ['$group' => ['_id' => '$area', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]],
    ['$limit' => 4]
])->toArray();

// Incidencias por gravedad
$incidencias_gravedad = $reportes->aggregate([
    ['$unwind' => '$incidencias_generales'],
    ['$group' => ['_id' => '$incidencias_generales.gravedad', 'count' => ['$sum' => 1]]]
])->toArray();

// Últimos 5 reportes
$ultimos_reportes = $reportes->find(
    [],
    ['sort' => ['fecha' => -1], 'limit' => 5]
)->toArray();

require_once __DIR__ . '/../../includes/layout.php';
$isMinsur = true;
$flash = getFlash();
?>

<style>
    .dash-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        padding: 20px;
        box-shadow: var(--shadow);
        text-align: center;
    }
    .dash-card .number {
        font-size: 28px;
        font-weight: 700;
        color: var(--primary);
    }
    .dash-card .label {
        font-size: 12px;
        color: var(--text-2);
        margin-top: 2px;
    }
    .dash-card .icon {
        font-size: 24px;
        margin-bottom: 4px;
    }
    .dash-card.blue .number { color: #1e40af; }
    .dash-card.green .number { color: #16a34a; }
    .dash-card.orange .number { color: #ea580c; }
    .dash-card.red .number { color: #dc2626; }
    .dash-card.purple .number { color: #7c3aed; }

    .dash-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    .section-title {
        font-size: 15px;
        font-weight: 600;
        margin: 16px 0 10px 0;
    }
</style>

<div class="ph">
    <div>
        <h1>⛏️ Dashboard MINSUR</h1>
        <p>Resumen de operaciones - Mina San Rafael</p>
    </div>
</div>

<?php if($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 's' : 'e' ?>"><?= u($flash['msg']) ?></div>
<?php endif; ?>

<!-- ============================================ -->
<!-- TARJETAS PRINCIPALES                          -->
<!-- ============================================ -->
<div class="dash-grid">
    <div class="dash-card blue">
        <div class="icon">📋</div>
        <div class="number"><?= $total_reportes ?></div>
        <div class="label">Total Reportes</div>
    </div>
    <div class="dash-card green">
        <div class="icon">👷</div>
        <div class="number"><?= $total_personal ?></div>
        <div class="label">Total Personal</div>
    </div>
    <div class="dash-card purple">
        <div class="icon">✅</div>
        <div class="number"><?= $personal_activo ?></div>
        <div class="label">Personal Activo</div>
    </div>
    <div class="dash-card red">
        <div class="icon">⚠️</div>
        <div class="number"><?= $total_incidencias ?></div>
        <div class="label">Total Incidencias</div>
    </div>
</div>

<!-- ============================================ -->
<!-- PERSONAL POR CARGO Y ÁREA                    -->
<!-- ============================================ -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    <div class="card">
        <div class="card-hd"><span class="card-title">👔 Personal por Cargo</span></div>
        <div class="card-body">
            <?php foreach($cargos as $c): ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light);">
                    <span><?= u($c['_id'] ?? '—') ?></span>
                    <span class="bdg bdg-blue"><?= $c['count'] ?></span>
                </div>
            <?php endforeach; ?>
            <?php if(empty($cargos)): ?>
                <p style="color:var(--text-3);text-align:center;">Sin datos</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-hd"><span class="card-title">🏗️ Personal por Área</span></div>
        <div class="card-body">
            <?php foreach($areas as $a): ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light);">
                    <span><?= u($a['_id'] ?? '—') ?></span>
                    <span class="bdg bdg-orange"><?= $a['count'] ?></span>
                </div>
            <?php endforeach; ?>
            <?php if(empty($areas)): ?>
                <p style="color:var(--text-3);text-align:center;">Sin datos</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- INCIDENCIAS POR GRAVEDAD                     -->
<!-- ============================================ -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-hd"><span class="card-title">⚠️ Incidencias por Gravedad</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;">
            <?php foreach($incidencias_gravedad as $i): ?>
                <div style="text-align:center;padding:10px;background:var(--bg);border-radius:8px;">
                    <div style="font-size:22px;font-weight:700;color:<?= $i['_id'] === 'Crítica' ? '#dc2626' : ($i['_id'] === 'Alta' ? '#ea580c' : ($i['_id'] === 'Media' ? '#ca8a04' : '#16a34a')) ?>;">
                        <?= $i['count'] ?>
                    </div>
                    <div style="font-size:12px;color:var(--text-2);"><?= u($i['_id'] ?? 'Sin gravedad') ?></div>
                </div>
            <?php endforeach; ?>
            <?php if(empty($incidencias_gravedad)): ?>
                <p style="color:var(--text-3);text-align:center;">Sin incidencias</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ÚLTIMOS REPORTES                             -->
<!-- ============================================ -->
<div class="card">
    <div class="card-hd">
        <span class="card-title">📄 Últimos Reportes</span>
        <a href="<?= BASE ?>/modules/minsur/reportes/" class="btn btn-outline btn-sm">Ver todos</a>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Turno</th>
                    <th>Supervisor</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($ultimos_reportes as $r): ?>
                <tr>
                    <td><?= formatDateOnly($r['fecha']) ?></td>
                    <td><?= u($r['turno']) ?></td>
                    <td><strong><?= u($r['supervisor']) ?></strong></td>
                    <td>
                        <span class="bdg <?= ($r['prioridad'] ?? 'Media') === 'Crítica' ? 'bdg-red' : (($r['prioridad'] ?? 'Media') === 'Alta' ? 'bdg-orange' : 'bdg-yellow') ?>">
                            <?= u($r['prioridad'] ?? 'Media') ?>
                        </span>
                    </td>
                    <td>
                        <span class="bdg <?= ($r['estado'] ?? 'Pendiente') === 'Completado' ? 'bdg-green' : 'bdg-yellow' ?>">
                            <?= u($r['estado'] ?? 'Pendiente') ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= BASE ?>/modules/minsur/reportes/ver.php?id=<?= getId($r) ?>" class="btn btn-outline btn-sm btn-icon">👁️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($ultimos_reportes)): ?>
                <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-3);">No hay reportes</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>