<?php
// modules/pagos/historial.php - Historial de pagos con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$pageTitle = 'Historial de Pagos';
$pg = 'pag';
$db = getMongoDB();
$contratos = $db->selectCollection('contratos');

// Buscar contratos con pagos
$fins = $contratos->find(
    ['financiacion.cuotas.pagos' => ['$exists' => true]],
    ['sort' => ['_id' => -1]]
);

require_once __DIR__ . '/../../includes/layout.php';
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/pagos/index.php" style="font-size:13px;color:var(--text-2);">← Pagos</a>
        <h1 style="margin-top:4px;">📊 Historial de Pagos</h1>
    </div>
</div>

<div class="card">
    <div class="card-hd"><span class="card-title">Pagos Registrados</span></div>
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Contrato</th>
                    <th>Propiedad</th>
                    <th>Cuota</th>
                    <th>Monto</th>
                    <th>Método</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $encontrado = false;
            foreach($fins as $f):
                $fin = $f['financiacion'] ?? [];
                foreach($fin['cuotas'] ?? [] as $cuota):
                    foreach($cuota['pagos'] ?? [] as $pago):
                        $encontrado = true;
            ?>
                <tr>
                    <td><?= formatDateOnly($pago['fecha'] ?? null) ?></td>
                    <td><strong><?= u($f['cliente']['nombre'] ?? '—') ?></strong></td>
                    <td><?= u($f['numero']) ?></td>
                    <td><?= u($f['propiedad']['codigo'] ?? '—') ?></td>
                    <td>#<?= $cuota['numero'] ?? '—' ?></td>
                    <td><strong style="color:var(--green);">S/ <?= number_format($pago['monto'] ?? 0, 2) ?></strong></td>
                    <td><span class="bdg bdg-blue"><?= u($pago['metodo'] ?? '—') ?></span></td>
                </tr>
            <?php
                    endforeach;
                endforeach;
            endforeach;
            if (!$encontrado):
            ?>
                <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-3);">No hay pagos registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>