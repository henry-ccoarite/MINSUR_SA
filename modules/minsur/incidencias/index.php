<?php
// modules/minsur/incidencias/index.php - Lista de incidencias
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$pageTitle = 'Incidencias - MINSUR';
$pg = 'minsur_incidencias';
$db = getMongoDB();
$reportes = $db->selectCollection('reportes_guardia');

// Construir match condicional
$match = [];
if (!empty($_GET['gravedad'])) $match['incidencias_generales.gravedad'] = $_GET['gravedad'];
if (!empty($_GET['tipo'])) $match['incidencias_generales.tipo'] = $_GET['tipo'];
if (!empty($_GET['fecha_desde']) && !empty($_GET['fecha_hasta'])) {
    $match['fecha']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['fecha_desde']) * 1000);
    $match['fecha']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['fecha_hasta'] . ' 23:59:59') * 1000);
}

// Pipeline
$pipeline = [];
$pipeline[] = ['$unwind' => '$incidencias_generales'];
if (!empty($match)) $pipeline[] = ['$match' => $match];
$pipeline[] = [
    '$project' => [
        'fecha' => 1,
        'turno' => 1,
        'supervisor' => 1,
        'area' => 1,
        'tipo' => '$incidencias_generales.tipo',
        'descripcion' => '$incidencias_generales.descripcion',
        'gravedad' => '$incidencias_generales.gravedad',
        'accion' => '$incidencias_generales.accion',
        'reporte_id' => '$_id'
    ]
];
$pipeline[] = ['$sort' => ['fecha' => -1]];

$incidencias = $reportes->aggregate($pipeline)->toArray();

$tipos = ['Seguridad', 'Operacional', 'Ambiental', 'Mecánico', 'Personal', 'Otro'];
$gravedades = ['Baja', 'Media', 'Alta', 'Crítica'];

require_once __DIR__ . '/../../../includes/layout.php';
$isMinsur = true;
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/minsur/index.php" style="font-size:13px;color:var(--text-2);">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
        <h1 style="margin-top:4px;"><i class="fas fa-exclamation-triangle"></i> Incidencias</h1>
        <p><i class="fas fa-database"></i> <?= count($incidencias) ?> registros</p>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 18px;">
        <form method="GET" class="fbar">
            <div class="fg">
                <label><i class="fas fa-tag"></i> Tipo</label>
                <select name="tipo" class="fc">
                    <option value="">Todos</option>
                    <?php foreach($tipos as $t): ?>
                        <option value="<?= $t ?>" <?= ($_GET['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label><i class="fas fa-flag"></i> Gravedad</label>
                <select name="gravedad" class="fc">
                    <option value="">Todas</option>
                    <?php foreach($gravedades as $g): ?>
                        <option value="<?= $g ?>" <?= ($_GET['gravedad'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label><i class="fas fa-calendar"></i> Fecha Desde</label>
                <input type="date" name="fecha_desde" class="fc" value="<?= u($_GET['fecha_desde'] ?? '') ?>">
            </div>
            <div class="fg">
                <label><i class="fas fa-calendar"></i> Fecha Hasta</label>
                <input type="date" name="fecha_hasta" class="fc" value="<?= u($_GET['fecha_hasta'] ?? '') ?>">
            </div>
            <div style="display:flex;gap:6px;align-items:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="<?= BASE ?>/modules/minsur/incidencias/" class="btn btn-outline btn-sm">
                    <i class="fas fa-undo"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="tbl-wrap">
        <table class="table-modern">
            <thead>
                <tr>
                    <th><i class="fas fa-calendar-day"></i> Fecha</th>
                    <th><i class="fas fa-clock"></i> Turno</th>
                    <th><i class="fas fa-user"></i> Supervisor</th>
                    <th><i class="fas fa-tag"></i> Tipo</th>
                    <th><i class="fas fa-align-left"></i> Descripción</th>
                    <th><i class="fas fa-flag"></i> Gravedad</th>
                    <th><i class="fas fa-check-circle"></i> Acción</th>
                    <th><i class="fas fa-file-alt"></i> Reporte</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($incidencias as $i): ?>
                <tr>
                    <td><?= formatDateOnly($i['fecha']) ?></td>
                    <td><?= u($i['turno']) ?></td>
                    <td><strong><?= u($i['supervisor']) ?></strong></td>
                    <td>
                        <span class="bdg <?= $i['tipo'] === 'Seguridad' ? 'bdg-red' : 'bdg-yellow' ?>">
                            <?= u($i['tipo'] ?? '—') ?>
                        </span>
                    </td>
                    <td><?= u(substr($i['descripcion'] ?? '', 0, 50)) ?>...</td>
                    <td>
                        <span class="bdg <?= $i['gravedad'] === 'Crítica' || $i['gravedad'] === 'Alta' ? 'bdg-red' : 'bdg-yellow' ?>">
                            <?= u($i['gravedad'] ?? 'Media') ?>
                        </span>
                    </td>
                    <td><?= u(substr($i['accion'] ?? '', 0, 30)) ?>...</td>
                    <td>
                        <a href="<?= BASE ?>/modules/minsur/reportes/ver.php?id=<?= $i['reporte_id'] ?>" class="btn btn-outline btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(count($incidencias) === 0): ?>
                <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-3);">
                    <i class="fas fa-inbox"></i> No hay incidencias registradas
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/layout_end.php'; ?>