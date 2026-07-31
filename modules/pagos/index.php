<?php
// modules/pagos/index.php - Pagos con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$pageTitle = 'Pagos';
$pg = 'pag';
$db = getMongoDB();
$contratos = $db->selectCollection('contratos');

// Buscar contratos con financiación
$fins = $contratos->find(
    ['financiacion' => ['$exists' => true]],
    ['sort' => ['_id' => -1]]
);

require_once __DIR__ . '/../../includes/layout.php';
$flash = getFlash();
?>

<div class="ph">
    <div>
        <h1>💵 Pagos</h1>
        <p>Registro y control de pagos de cuotas - MongoDB</p>
    </div>
</div>

<?php if($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 's' : 'e' ?>"><?= u($flash['msg']) ?></div>
<?php endif; ?>

<!-- RESULTADOS DE PRUEBA -->
<div class="card">
    <div class="card-hd">
        <span class="card-title">Contratos con Financiación</span>
        <span style="font-size:13px;color:var(--text-2);"><?= iterator_count($fins) ?> contratos</span>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>Contrato</th>
                    <th>Cliente</th>
                    <th>Propiedad</th>
                    <th>Total</th>
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
                    <td><strong style="font-size:12px;"><?= u($f['numero']) ?></strong></td>
                    <td><?= u($f['cliente']['nombre'] ?? '—') ?></td>
                    <td><?= u($f['propiedad']['codigo'] ?? '—') ?></td>
                    <td>S/ <?= number_format($f['total'], 2) ?></td>
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