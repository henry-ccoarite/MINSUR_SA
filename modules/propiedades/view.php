<?php
// modules/propiedades/view.php - Ver detalle con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$id = $_GET['id'] ?? null;
$db = getMongoDB();
$collection = $db->selectCollection('propiedades');

$prop = $collection->findOne(['_id' => toObjectId($id)]);

if (!$prop) {
    header('Location: ' . BASE . '/modules/propiedades/index.php');
    exit;
}

$pageTitle = 'Propiedad ' . $prop['codigo'];
$pg = 'prop';
$bdgEstado = ['Disponible' => 'bdg-green', 'Vendido' => 'bdg-gray', 'Alquilado' => 'bdg-blue', 'Reservado' => 'bdg-yellow'];
$bc = $bdgEstado[$prop['estado']] ?? 'bdg-gray';
$tipoIcons = ['Casa' => '🏠', 'Departamento' => '🏢', 'Terreno' => '🗺️', 'Local' => '🏪'];
$carac = $prop['caracteristicas'] ?? [];
$precios = $prop['precios'] ?? [];

require_once __DIR__ . '/../../includes/layout.php';
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/propiedades/index.php" style="font-size:13px;color:var(--text-2);">← Propiedades</a>
        <h1 style="margin-top:4px;"><?= u($prop['codigo']) ?> <span class="bdg <?= $bc ?>"><?= u($prop['estado']) ?></span></h1>
    </div>
    <div style="display:flex;gap:8px;">
        <?php if(isAdmin()): ?>
            <a href="<?= BASE ?>/modules/propiedades/form.php?id=<?= getId($prop) ?>" class="btn btn-outline">✏️ Editar</a>
        <?php endif; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">
    <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="card">
            <div style="height:280px;background:linear-gradient(135deg,#eff6ff,#dbeafe);display:flex;align-items:center;justify-content:center;font-size:72px;border-radius:13px 13px 0 0;">
                <?php if(!empty($prop['imagen'])): ?>
                    <img src="<?= BASE ?>/uploads/<?= u($prop['imagen']) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <?= $tipoIcons[$prop['tipo']] ?? '🏗️' ?>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <h2 style="font-size:16px;font-weight:600;"><?= u($prop['direccion']) ?></h2>
                <p style="color:var(--text-2);font-size:13px;margin-top:4px;"><?= u($prop['ciudad']) ?></p>
            </div>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="card">
            <div class="card-body">
                <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;">Precio de Venta</div>
                <div style="font-size:28px;font-weight:700;margin:6px 0;color:var(--primary);">S/ <?= number_format($precios['venta'] ?? 0, 2) ?></div>
                <?php if(!empty($precios['alquiler'])): ?>
                    <div style="font-size:13px;color:var(--text-2);">Alquiler: S/ <?= number_format($precios['alquiler'], 2) ?>/mes</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-hd"><span class="card-title">Características</span></div>
            <div class="card-body">
                <?php
                $detalles = [
                    ['Tipo', $prop['tipo']],
                    ['Ciudad', $prop['ciudad']],
                    ['Área total', !empty($carac['area']) ? $carac['area'] . ' m²' : '—'],
                    ['Habitaciones', $carac['habitaciones'] ?? 0],
                    ['Baños', $carac['banos'] ?? 0],
                    ['Estacionamiento', !empty($carac['estacionamiento']) ? 'Sí' : 'No'],
                    ['Estado', $prop['estado']]
                ];
                foreach($detalles as [$lbl, $val]):
                ?>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);">
                        <span style="color:var(--text-3);font-size:13px;"><?= $lbl ?></span>
                        <span style="font-weight:500;font-size:13px;"><?= u((string)$val) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>