<?php
// modules/contratos/index.php - Listar contratos con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$pageTitle = 'Contratos';
$pg = 'cont';
$db = getMongoDB();
$collection = $db->selectCollection('contratos');

// Filtros
$filtros = [];
if (!empty($_GET['estado'])) $filtros['estado'] = $_GET['estado'];
if (!empty($_GET['tipo'])) $filtros['tipo'] = $_GET['tipo'];

$contratos = $collection->find($filtros, ['sort' => ['fecha' => -1]]);

// Stats
$tot_venta = $collection->countDocuments(['tipo' => 'Venta', 'estado' => 'Activo']);
$tot_alq = $collection->countDocuments(['tipo' => 'Alquiler', 'estado' => 'Activo']);

$ingresos = $collection->aggregate([
    ['$match' => ['estado' => 'Activo']],
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$total']]]
])->toArray();
$tot_ingr = isset($ingresos[0]) ? $ingresos[0]['total'] : 0;

require_once __DIR__ . '/../../includes/layout.php';
$flash = getFlash();
?>

<div class="ph">
    <div>
        <h1>📄 Contratos</h1>
        <p>Gestión de contratos de venta y alquiler - MongoDB</p>
    </div>
    <?php if(isAdmin()): ?>
        <a href="<?= BASE ?>/modules/contratos/form.php" class="btn btn-primary">➕ Nuevo Contrato</a>
    <?php endif; ?>
</div>

<?php if($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 's' : 'e' ?>"><?= u($flash['msg']) ?></div>
<?php endif; ?>

<!-- Mini stats -->
<div class="stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
    <div class="stat blue">
        <div class="stat-top"><div class="stat-icon">📄</div></div>
        <div class="stat-val"><?= $tot_venta ?></div>
        <div class="stat-label">Ventas Activas</div>
    </div>
    <div class="stat green">
        <div class="stat-top"><div class="stat-icon">🏠</div></div>
        <div class="stat-val"><?= $tot_alq ?></div>
        <div class="stat-label">Alquileres Activos</div>
    </div>
    <div class="stat orange">
        <div class="stat-top"><div class="stat-icon">💰</div></div>
        <div class="stat-val">S/ <?= number_format($tot_ingr/1000, 0) ?>K</div>
        <div class="stat-label">Ingresos Activos</div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 18px;">
        <form method="GET" class="fbar">
            <div class="fg">
                <label>Estado</label>
                <select name="estado" class="fc">
                    <option value="">Todos</option>
                    <?php foreach(['Activo', 'Finalizado', 'Anulado'] as $e): ?>
                        <option value="<?= $e ?>" <?= ($_GET['estado'] ?? '') === $e ? 'selected' : '' ?>><?= $e ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Tipo</label>
                <select name="tipo" class="fc">
                    <option value="">Todos</option>
                    <option value="Venta" <?= ($_GET['tipo'] ?? '') === 'Venta' ? 'selected' : '' ?>>Venta</option>
                    <option value="Alquiler" <?= ($_GET['tipo'] ?? '') === 'Alquiler' ? 'selected' : '' ?>>Alquiler</option>
                </select>
            </div>
            <div style="display:flex;gap:6px;align-items:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">🔍 Filtrar</button>
                <a href="<?= BASE ?>/modules/contratos/index.php" class="btn btn-outline btn-sm">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>N° Contrato</th>
                    <th>Tipo</th>
                    <th>Cliente</th>
                    <th>Propiedad</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($contratos as $co):
                $bc = ['Activo' => 'bdg-green', 'Finalizado' => 'bdg-gray', 'Anulado' => 'bdg-red'][$co['estado']] ?? 'bdg-gray';
                $tc = $co['tipo'] === 'Venta' ? 'bdg-blue' : 'bdg-orange';
            ?>
                <tr>
                    <td><strong style="font-size:12px;"><?= u($co['numero']) ?></strong></td>
                    <td><span class="bdg <?= $tc ?>"><?= u($co['tipo']) ?></span></td>
                    <td><?= u($co['cliente']['nombre'] ?? '—') ?></td>
                    <td><span class="bdg bdg-gray"><?= u($co['propiedad']['codigo'] ?? '—') ?></span></td>
                    <td><?= formatDateOnly($co['fecha']) ?></td>
                    <td><strong>S/ <?= number_format($co['total'], 2) ?></strong></td>
                    <td><span class="bdg <?= $bc ?>"><?= u($co['estado']) ?></span></td>
                    <td style="display:flex;gap:5px;">
                        <a href="<?= BASE ?>/modules/contratos/view.php?id=<?= getId($co) ?>" class="btn btn-outline btn-sm btn-icon">👁️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>