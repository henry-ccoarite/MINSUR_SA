<?php
// modules/minsur/consultas/index.php - Consultas avanzadas
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$pageTitle = 'Consultas - MINSUR';
$pg = 'minsur_consultas';
$db = getMongoDB();
$reportes = $db->selectCollection('reportes_guardia');

$filtros = [];
if (!empty($_GET['fecha_desde']) && !empty($_GET['fecha_hasta'])) {
    $filtros['fecha']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['fecha_desde']) * 1000);
    $filtros['fecha']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['fecha_hasta'] . ' 23:59:59') * 1000);
}
if (!empty($_GET['turno'])) $filtros['turno'] = $_GET['turno'];
if (!empty($_GET['supervisor'])) $filtros['supervisor'] = ['$regex' => $_GET['supervisor'], '$options' => 'i'];
if (!empty($_GET['prioridad'])) $filtros['prioridad'] = $_GET['prioridad'];
if (!empty($_GET['estado'])) $filtros['estado'] = $_GET['estado'];
if (!empty($_GET['buscar_texto'])) {
    $filtros['$or'] = [
        ['descripcion' => ['$regex' => $_GET['buscar_texto'], '$options' => 'i']],
        ['observaciones' => ['$regex' => $_GET['buscar_texto'], '$options' => 'i']]
    ];
}

$cursor = $reportes->find($filtros, ['sort' => ['fecha' => -1]]);
$resultados = $cursor->toArray();
$total = count($resultados);

// Estadísticas
$pipeline_prioridad = [];
if (!empty($filtros)) $pipeline_prioridad[] = ['$match' => $filtros];
$pipeline_prioridad[] = ['$group' => ['_id' => '$prioridad', 'count' => ['$sum' => 1]]];
$stats_prioridad = $reportes->aggregate($pipeline_prioridad)->toArray();

$turnos = ['06:00-14:00', '14:00-22:00', '22:00-06:00'];
$estados = ['Pendiente', 'Completado', 'Cancelado'];
$prioridades = ['Baja', 'Media', 'Alta', 'Crítica'];

require_once __DIR__ . '/../../../includes/layout.php';
$isMinsur = true;
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/minsur/index.php" style="font-size:13px;color:var(--text-2);">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
        <h1 style="margin-top:4px;"><i class="fas fa-search"></i> Consultas Avanzadas</h1>
        <p><i class="fas fa-database"></i> <?= $total ?> resultados</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= BASE ?>/modules/minsur/reportes/exportar.php?<?= http_build_query($_GET) ?>" class="btn btn-outline">
            <i class="fas fa-file-export"></i> Exportar
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:16px;">
        <form method="GET">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
                <div class="fg">
                    <label><i class="fas fa-calendar"></i> Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="fc" value="<?= u($_GET['fecha_desde'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label><i class="fas fa-calendar"></i> Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="fc" value="<?= u($_GET['fecha_hasta'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label><i class="fas fa-clock"></i> Turno</label>
                    <select name="turno" class="fc">
                        <option value="">Todos</option>
                        <?php foreach($turnos as $t): ?>
                            <option value="<?= $t ?>" <?= ($_GET['turno'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-user"></i> Supervisor</label>
                    <input type="text" name="supervisor" class="fc" placeholder="Nombre..." value="<?= u($_GET['supervisor'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label><i class="fas fa-flag"></i> Prioridad</label>
                    <select name="prioridad" class="fc">
                        <option value="">Todas</option>
                        <?php foreach($prioridades as $p): ?>
                            <option value="<?= $p ?>" <?= ($_GET['prioridad'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-circle"></i> Estado</label>
                    <select name="estado" class="fc">
                        <option value="">Todos</option>
                        <?php foreach($estados as $e): ?>
                            <option value="<?= $e ?>" <?= ($_GET['estado'] ?? '') === $e ? 'selected' : '' ?>><?= $e ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg span2">
                    <label><i class="fas fa-search"></i> Buscar en Descripción</label>
                    <input type="text" name="buscar_texto" class="fc" placeholder="Palabra clave..." value="<?= u($_GET['buscar_texto'] ?? '') ?>">
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:12px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="<?= BASE ?>/modules/minsur/consultas/" class="btn btn-outline">
                    <i class="fas fa-undo"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:16px;">
    <?php foreach($stats_prioridad as $s): ?>
        <div class="stat <?= $s['_id'] === 'Crítica' ? 'red' : ($s['_id'] === 'Alta' ? 'orange' : 'yellow') ?>">
            <div class="stat-val"><?= $s['count'] ?></div>
            <div class="stat-label"><i class="fas fa-flag"></i> <?= u($s['_id'] ?? 'Sin prioridad') ?></div>
        </div>
    <?php endforeach; ?>
    <div class="stat blue">
        <div class="stat-val"><?= $total ?></div>
        <div class="stat-label"><i class="fas fa-database"></i> Total Reportes</div>
    </div>
</div>

<!-- Resultados -->
<div class="card">
    <div class="card-hd">
        <span class="card-title"><i class="fas fa-list"></i> Resultados</span>
        <span style="font-size:13px;color:var(--text-2);"><i class="fas fa-database"></i> <?= $total ?> registros</span>
    </div>
    <div class="tbl-wrap">
        <table class="table-modern">
            <thead>
                <tr>
                    <th><i class="fas fa-calendar-day"></i> Fecha</th>
                    <th><i class="fas fa-clock"></i> Turno</th>
                    <th><i class="fas fa-user"></i> Supervisor</th>
                    <th><i class="fas fa-map-marker-alt"></i> Área</th>
                    <th><i class="fas fa-flag"></i> Prioridad</th>
                    <th><i class="fas fa-circle"></i> Estado</th>
                    <th><i class="fas fa-file-alt"></i> Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($resultados as $r):
                $prioridad_color = ['Baja'=>'bdg-green','Media'=>'bdg-yellow','Alta'=>'bdg-orange','Crítica'=>'bdg-red'][$r['prioridad']??'Media']??'bdg-gray';
            ?>
                <tr>
                    <td><?= formatDateOnly($r['fecha']) ?></td>
                    <td><?= u($r['turno']) ?></td>
                    <td><strong><?= u($r['supervisor']) ?></strong></td>
                    <td><?= u($r['area'] ?? '—') ?></td>
                    <td><span class="bdg <?= $prioridad_color ?>"><?= u($r['prioridad'] ?? 'Media') ?></span></td>
                    <td><span class="bdg <?= ($r['estado'] ?? 'Pendiente') === 'Completado' ? 'bdg-green' : 'bdg-yellow' ?>"><?= u($r['estado'] ?? 'Pendiente') ?></span></td>
                    <td>
                        <a href="<?= BASE ?>/modules/minsur/reportes/ver.php?id=<?= getId($r) ?>" class="btn btn-outline btn-sm btn-icon">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if($total === 0): ?>
                <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-3);">
                    <i class="fas fa-inbox"></i> No hay resultados
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/layout_end.php'; ?>