<?php
// modules/clientes/view.php - Ver detalle cliente con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$id = $_GET['id'] ?? null;
$db = getMongoDB();
$collection = $db->selectCollection('clientes');

$cliente = $collection->findOne(['_id' => toObjectId($id)]);

if (!$cliente) {
    header('Location: ' . BASE . '/modules/clientes/index.php');
    exit;
}

$pageTitle = u($cliente['nombre']);
$pg = 'cli';
$contacto = $cliente['contacto'] ?? [];
$financiero = $cliente['financiero'] ?? [];

require_once __DIR__ . '/../../includes/layout.php';
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/clientes/index.php" style="font-size:13px;color:var(--text-2);">← Clientes</a>
        <h1 style="margin-top:4px;"><?= u($cliente['nombre']) ?></h1>
    </div>
    <?php if(isAdmin()): ?>
        <a href="<?= BASE ?>/modules/clientes/form.php?id=<?= getId($cliente) ?>" class="btn btn-outline">✏️ Editar</a>
    <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start;">
    <div class="card">
        <div class="card-body" style="text-align:center;padding-top:28px;">
            <div style="width:64px;height:64px;background:var(--primary);color:#fff;font-size:24px;font-weight:700;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <?= strtoupper(substr($cliente['nombre'], 0, 1)) ?>
            </div>
            <h2 style="font-size:16px;font-weight:600;"><?= u($cliente['nombre']) ?></h2>
            <span class="bdg <?= ($cliente['estado'] ?? 'Activo') === 'Activo' ? 'bdg-green' : 'bdg-gray' ?>" style="margin-top:6px;">
                <?= u($cliente['estado'] ?? 'Activo') ?>
            </span>
        </div>
        <div style="padding:0 20px 20px;display:flex;flex-direction:column;gap:10px;border-top:1px solid var(--border-light);padding-top:16px;margin-top:4px;">
            <?php
            $info = [
                ['DNI', $cliente['dni']],
                ['Teléfono', $contacto['telefono'] ?? '—'],
                ['Email', $contacto['email'] ?? '—'],
                ['Dirección', $contacto['direccion'] ?? '—'],
                ['Ingresos', !empty($financiero['ingresos']) ? 'S/ '.number_format($financiero['ingresos'], 2) : '—'],
                ['Calificación', $financiero['calificacion'] ?? 'Pendiente']
            ];
            foreach($info as [$l, $v]): ?>
                <div>
                    <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em;"><?= $l ?></div>
                    <div style="font-size:13px;font-weight:500;margin-top:2px;"><?= u($v) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>