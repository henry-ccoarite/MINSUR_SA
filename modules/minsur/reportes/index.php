<?php
// modules/minsur/reportes/index.php - Lista de reportes con filtros
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$pageTitle = 'Reportes de Guardia';
$pg = 'minsur_reportes';
$db = getMongoDB();
$reportes = $db->selectCollection('reportes_guardia');

// ============================================
// FILTROS
// ============================================
$filtros = [];

if (!empty($_GET['fecha_desde']) && !empty($_GET['fecha_hasta'])) {
    $filtros['fecha']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['fecha_desde']) * 1000);
    $filtros['fecha']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['fecha_hasta'] . ' 23:59:59') * 1000);
}
if (!empty($_GET['turno'])) $filtros['turno'] = $_GET['turno'];
if (!empty($_GET['supervisor'])) $filtros['supervisor'] = ['$regex' => $_GET['supervisor'], '$options' => 'i'];
if (!empty($_GET['prioridad'])) $filtros['prioridad'] = $_GET['prioridad'];
if (!empty($_GET['estado'])) $filtros['estado'] = $_GET['estado'];
if (!empty($_GET['buscar'])) {
    $filtros['$or'] = [
        ['descripcion' => ['$regex' => $_GET['buscar'], '$options' => 'i']],
        ['observaciones' => ['$regex' => $_GET['buscar'], '$options' => 'i']]
    ];
}

// ============================================
// EJECUTAR CONSULTA
// ============================================
$cursor = $reportes->find($filtros, ['sort' => ['fecha' => -1]]);
$lista = $cursor->toArray();
$total = count($lista);

// ============================================
// VALORES PARA FILTROS
// ============================================
$turnos = ['06:00-14:00', '14:00-22:00', '22:00-06:00'];
$estados = ['Pendiente', 'Completado', 'Cancelado'];
$prioridades = ['Baja', 'Media', 'Alta', 'Crítica'];

require_once __DIR__ . '/../../../includes/layout.php';
$isMinsur = true;
$flash = getFlash();
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/minsur/index.php" style="font-size:13px;color:var(--text-2);">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
        <h1 style="margin-top:4px;"><i class="fas fa-clipboard-list"></i> Reportes de Guardia</h1>
        <p><i class="fas fa-database"></i> <?= $total ?> registros encontrados</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= BASE ?>/modules/minsur/reportes/nuevo.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo
        </a>
        <a href="<?= BASE ?>/modules/minsur/reportes/exportar.php" class="btn btn-outline">
            <i class="fas fa-file-export"></i> Exportar
        </a>
    </div>
</div>

<?php if($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 's' : 'e' ?>">
        <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
        <?= u($flash['msg']) ?>
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- FILTROS + BÚSQUEDA                           -->
<!-- ============================================ -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 18px;">
        <form method="GET" class="fbar">
            <div class="fg">
                <label><i class="fas fa-search"></i> Buscar</label>
                <input type="text" name="buscar" class="fc" placeholder="Buscar en descripción..." value="<?= u($_GET['buscar'] ?? '') ?>">
            </div>
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
                <label><i class="fas fa-flag"></i> Prioridad</label>
                <select name="prioridad" class="fc">
                    <option value="">Todas</option>
                    <?php foreach($prioridades as $p): ?>
                        <option value="<?= $p ?>" <?= ($_GET['prioridad'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:6px;align-items:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="<?= BASE ?>/modules/minsur/reportes/" class="btn btn-outline btn-sm">
                    <i class="fas fa-undo"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- TABLA                                        -->
<!-- ============================================ -->
<div class="card">
    <div class="tbl-wrap">
        <table class="table-modern">
            <thead>
                <tr>
                    <th><i class="fas fa-calendar-day"></i> Fecha</th>
                    <th><i class="fas fa-clock"></i> Turno</th>
                    <th><i class="fas fa-user"></i> Supervisor</th>
                    <th><i class="fas fa-users"></i> Personal</th>
                    <th><i class="fas fa-exclamation-triangle"></i> Incidencias</th>
                    <th><i class="fas fa-flag"></i> Prioridad</th>
                    <th><i class="fas fa-circle"></i> Estado</th>
                    <th><i class="fas fa-cogs"></i> Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($lista as $r):
                $prioridad_color = ['Baja'=>'bdg-green','Media'=>'bdg-yellow','Alta'=>'bdg-orange','Crítica'=>'bdg-red'][$r['prioridad']??'Media']??'bdg-gray';
                $estado_color = ($r['estado']??'Pendiente') === 'Completado' ? 'bdg-green' : 'bdg-yellow';
            ?>
                <tr>
                    <td><?= formatDateOnly($r['fecha']) ?></td>
                    <td><?= u($r['turno']) ?></td>
                    <td><strong><?= u($r['supervisor']) ?></strong></td>
                    <td><?= count($r['personal'] ?? []) ?></td>
                    <td><?= count($r['incidencias_generales'] ?? []) ?></td>
                    <td><span class="bdg <?= $prioridad_color ?>"><?= u($r['prioridad'] ?? 'Media') ?></span></td>
                    <td><span class="bdg <?= $estado_color ?>"><?= u($r['estado'] ?? 'Pendiente') ?></span></td>
                    <td style="display:flex;gap:5px;">
                        <a href="<?= BASE ?>/modules/minsur/reportes/ver.php?id=<?= getId($r) ?>" class="btn btn-outline btn-sm btn-icon" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?= BASE ?>/modules/minsur/reportes/editar.php?id=<?= getId($r) ?>" class="btn btn-outline btn-sm btn-icon" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="<?= BASE ?>/modules/minsur/reportes/eliminar.php?id=<?= getId($r) ?>" class="btn btn-danger btn-sm btn-icon" onclick="return confirm('¿Eliminar?')" title="Eliminar">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if($total === 0): ?>
                <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-3);">
                    <i class="fas fa-inbox"></i> No hay reportes registrados
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/layout_end.php'; ?>