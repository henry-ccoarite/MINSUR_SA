<?php
// modules/minsur/personal/ver_qr.php - Ver QR de un trabajador
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$id = $_GET['id'] ?? null;
$db = getMongoDB();
$collection = $db->selectCollection('personal_mina');

$persona = $collection->findOne(['_id' => toObjectId($id)]);

if (!$persona) {
    header('Location: ' . BASE . '/modules/minsur/personal/');
    exit;
}

// Generar token único para el trabajador (si no tiene, crear uno)
if (!isset($persona['token_qr'])) {
    $token = bin2hex(random_bytes(16)); // Token único
    $collection->updateOne(
        ['_id' => toObjectId($id)],
        ['$set' => ['token_qr' => $token]]
    );
    $persona['token_qr'] = $token;
}

$url_qr = BASE . '/trabajador.php?token=' . $persona['token_qr'];

$pageTitle = 'QR - ' . $persona['nombre'];
$pg = 'minsur_personal';

require_once __DIR__ . '/../../../includes/layout.php';
$isMinsur = true;
?>

<style>
    .qr-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 30px;
        text-align: center;
    }
    .qr-container .avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #1e40af;
        color: white;
        font-size: 32px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }
    .qr-container .name {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .qr-container .cargo {
        font-size: 14px;
        color: var(--text-2);
        margin-bottom: 20px;
    }
    .qr-container .qr-box {
        background: white;
        padding: 20px;
        border-radius: 16px;
        border: 2px solid var(--border);
        display: inline-block;
        margin-bottom: 16px;
    }
    .qr-container .qr-box img {
        width: 200px;
        height: 200px;
    }
    .qr-container .url {
        font-size: 12px;
        color: var(--text-3);
        word-break: break-all;
        background: var(--bg);
        padding: 8px 16px;
        border-radius: 8px;
        max-width: 100%;
    }
    .qr-container .btn-descargar {
        margin-top: 16px;
    }
    .qr-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 16px;
    }
</style>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/minsur/personal/" style="font-size:13px;color:var(--text-2);">
            <i class="fas fa-arrow-left"></i> Personal
        </a>
        <h1 style="margin-top:4px;"><i class="fas fa-qrcode"></i> Código QR</h1>
        <p>Escanea para ver los reportes de <?= u($persona['nombre']) ?></p>
    </div>
</div>

<div class="card">
    <div class="card-body qr-container">
        <div class="avatar"><?= strtoupper(substr($persona['nombre'], 0, 1)) ?></div>
        <div class="name"><?= u($persona['nombre']) ?></div>
        <div class="cargo"><?= u($persona['cargo'] ?? '') ?> · <?= u($persona['area'] ?? '') ?></div>

        <div class="qr-box">
            <!-- Usamos una API gratuita para generar QR -->
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $url_qr) ?>" alt="QR Code">
        </div>

        <div class="url">
            <i class="fas fa-link"></i> <?= 'http://' . $_SERVER['HTTP_HOST'] . $url_qr ?>
        </div>

        <div class="qr-actions">
            <a href="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $url_qr) ?>" 
               target="_blank" class="btn btn-primary">
                <i class="fas fa-download"></i> Descargar QR
            </a>
            <a href="<?= $url_qr ?>" target="_blank" class="btn btn-outline">
                <i class="fas fa-eye"></i> Ver mis reportes
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/layout_end.php'; ?>