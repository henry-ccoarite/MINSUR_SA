<?php
// modules/financiaciones/index.php - Financiaciones con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$pageTitle = 'Financiaciones';
$pg = 'fin';
$db = getMongoDB();
$contratos = $db->selectCollection('contratos');

$fins = $contratos->find(
    ['financiacion' => ['$exists' => true]],
    ['sort' => ['_id' => -1]]
);

require_once __DIR__ . '/../../includes/layout.php';
?>

<div class="ph">
    <div>
        <h1>💰 Financiaciones</h1>
        <p>Control de financiaciones y cuotas - MongoDB</p>
    </div>
</div>

<div class="card">
    <div class="card-hd"><span class="card-title">Todas las Financiaciones</span></div>
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Contrato</th>
                    <th>Cliente</th>
                    <th>Propiedad</th>
                    <th>Enganche</th>
                    <th>Saldo</th>
                    <th>Interés</th>
                    <th>Cuotas</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($fins as $f):
                $fin = $f['financiacion'] ?? [];
                $bc = ['Activa' => 'bdg-green', 'Pagada' => 'bdg-blue', 'Mora' => 'bdg-red'][$fin['estado'] ?? ''] ?? 'bdg-gray';
            ?>
                <tr>
                    <td><?= getId($f) ?></td>
                    <td><a href="<?= BASE ?>/modules/contratos/view.php?id=<?= getId($f) ?>" style="color:var(--primary);font-size:12px;"><?= u($f['numero']) ?></a></td>
                    <td><?= u($f['cliente']['nombre'] ?? '—') ?></td>
                    <td><?= u($f['propiedad']['codigo'] ?? '—') ?></td>
                    <td>S/ <?= number_format($fin['enganche'] ?? 0, 0, '.', ',') ?></td>
                    <td>S/ <?= number_format($fin['saldo'] ?? 0, 0, '.', ',') ?></td>
                    <td><?= $fin['interes'] ?? 0 ?>%</td>
                    <td><?= $fin['cuotas'] ?? 0 ?></td>
                    <td><span class="bdg <?= $bc ?>"><?= u($fin['estado'] ?? '—') ?></span></td>
                    <td>
                        <a href="<?= BASE ?>/modules/contratos/view.php?id=<?= getId($f) ?>" class="btn btn-outline btn-sm btn-icon">👁️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?><?php
// modules/financiaciones/index.php - Financiaciones con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$pageTitle = 'Financiaciones';
$pg = 'fin';
$db = getMongoDB();
$contratos = $db->selectCollection('contratos');

$fins = $contratos->find(
    ['financiacion' => ['$exists' => true]],
    ['sort' => ['_id' => -1]]
);

require_once __DIR__ . '/../../includes/layout.php';
?>

<div class="ph">
    <div>
        <h1>💰 Financiaciones</h1>
        <p>Control de financiaciones y cuotas - MongoDB</p>
    </div>
</div>

<div class="card">
    <div class="card-hd"><span class="card-title">Todas las Financiaciones</span></div>
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Contrato</th>
                    <th>Cliente</th>
                    <th>Propiedad</th>
                    <th>Enganche</th>
                    <th>Saldo</th>
                    <th>Interés</th>
                    <th>Cuotas</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($fins as $f):
                $fin = $f['financiacion'] ?? [];
                $bc = ['Activa' => 'bdg-green', 'Pagada' => 'bdg-blue', 'Mora' => 'bdg-red'][$fin['estado'] ?? ''] ?? 'bdg-gray';
            ?>
                <tr>
                    <td><?= getId($f) ?></td>
                    <td><a href="<?= BASE ?>/modules/contratos/view.php?id=<?= getId($f) ?>" style="color:var(--primary);font-size:12px;"><?= u($f['numero']) ?></a></td>
                    <td><?= u($f['cliente']['nombre'] ?? '—') ?></td>
                    <td><?= u($f['propiedad']['codigo'] ?? '—') ?></td>
                    <td>S/ <?= number_format($fin['enganche'] ?? 0, 0, '.', ',') ?></td>
                    <td>S/ <?= number_format($fin['saldo'] ?? 0, 0, '.', ',') ?></td>
                    <td><?= $fin['interes'] ?? 0 ?>%</td>
                    <td><?= $fin['cuotas'] ?? 0 ?></td>
                    <td><span class="bdg <?= $bc ?>"><?= u($fin['estado'] ?? '—') ?></span></td>
                    <td>
                        <a href="<?= BASE ?>/modules/contratos/view.php?id=<?= getId($f) ?>" class="btn btn-outline btn-sm btn-icon">👁️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>