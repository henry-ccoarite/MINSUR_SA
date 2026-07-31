<?php
// modules/contratos/view.php - Ver detalle contrato con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$id = $_GET['id'] ?? null;
$db = getMongoDB();
$collection = $db->selectCollection('contratos');

$contrato = $collection->findOne(['_id' => toObjectId($id)]);

if (!$contrato) {
    header('Location: ' . BASE . '/modules/contratos/index.php');
    exit;
}

$pageTitle = 'Contrato ' . $contrato['numero'];
$pg = 'cont';
$bc = ['Activo' => 'bdg-green', 'Finalizado' => 'bdg-gray', 'Anulado' => 'bdg-red'][$contrato['estado']] ?? 'bdg-gray';

require_once __DIR__ . '/../../includes/layout.php';
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/contratos/index.php" style="font-size:13px;color:var(--text-2);">← Contratos</a>
        <h1 style="margin-top:4px;"><?= u($contrato['numero']) ?> <span class="bdg <?= $bc ?>"><?= u($contrato['estado']) ?></span></h1>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <div class="card">
        <div class="card-hd"><span class="card-title">Cliente</span></div>
        <div class="card-body">
            <?php foreach([['Nombre', $contrato['cliente']['nombre']], ['DNI', $contrato['cliente']['dni']]] as [$l, $v]): ?>
                <div><div style="font-size:11px;color:var(--text-3);"><?= $l ?></div><div style="font-size:13px;font-weight:500;"><?= u($v) ?></div></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-hd"><span class="card-title">Propiedad</span></div>
        <div class="card-body">
            <?php foreach([['Código', $contrato['propiedad']['codigo']], ['Dirección', $contrato['propiedad']['direccion']]] as [$l, $v]): ?>
                <div><div style="font-size:11px;color:var(--text-3);"><?= $l ?></div><div style="font-size:13px;font-weight:500;"><?= u($v) ?></div></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-hd"><span class="card-title">Detalles del Contrato</span></div>
    <div class="card-body">
        <div class="fgrid">
            <?php foreach([['Tipo', $contrato['tipo']], ['Fecha', formatDateOnly($contrato['fecha'])], ['Total', 'S/ '.number_format($contrato['total'], 2)], ['Observaciones', $contrato['observaciones'] ?? '—']] as [$l, $v]): ?>
                <div class="fg"><div style="font-size:11px;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em;"><?= $l ?></div><div style="font-size:14px;font-weight:600;margin-top:3px;"><?= u($v) ?></div></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>