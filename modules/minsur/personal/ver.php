<?php
// modules/minsur/personal/ver.php - Ver detalle de personal con foto
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

$pageTitle = 'Detalle Personal - ' . $persona['nombre'];
$pg = 'minsur_personal';
$contacto = $persona['contacto'] ?? [];
$datos = $persona['datos_personales'] ?? [];
$fechas = $persona['fechas_contrato'] ?? [];

require_once __DIR__ . '/../../../includes/layout.php';
$isMinsur = true;
?>

<div class="ph">
    <div style="display:flex;align-items:center;gap:20px;">
        <?php if(!empty($persona['foto'])): ?>
            <img src="<?= BASE ?>/uploads/personal/<?= u($persona['foto']) ?>" 
                 style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);">
        <?php else: ?>
            <div style="width:80px;height:80px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;">
                <?= strtoupper(substr($persona['nombre'], 0, 1)) ?>
            </div>
        <?php endif; ?>
        <div>
            <a href="<?= BASE ?>/modules/minsur/personal/" style="font-size:13px;color:var(--text-2);">
                <i class="fas fa-arrow-left"></i> Personal
            </a>
            <h1 style="margin-top:4px;"><?= u($persona['nombre']) ?></h1>
            <p><i class="fas fa-id-card"></i> DNI: <?= u($persona['dni']) ?></p>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= BASE ?>/modules/minsur/personal/editar.php?id=<?= getId($persona) ?>" class="btn btn-outline">
            <i class="fas fa-edit"></i> Editar
        </a>
        <a href="<?= BASE ?>/modules/minsur/personal/ver_qr.php?id=<?= getId($persona) ?>" class="btn btn-outline">
            <i class="fas fa-qrcode"></i> QR
        </a>
    </div>
</div>

<!-- ============================================ -->
<!-- INFORMACIÓN                                  -->
<!-- ============================================ -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-id-card"></i> Datos Personales</span></div>
        <div class="card-body">
            <?php foreach([
                ['<i class="fas fa-user"></i> Nombre', $persona['nombre']],
                ['<i class="fas fa-id-card"></i> DNI', $persona['dni']],
                ['<i class="fas fa-calendar"></i> Fecha Nacimiento', $datos['fecha_nacimiento'] ?? '—'],
                ['<i class="fas fa-venus-mars"></i> Género', $datos['genero'] ?? '—'],
                ['<i class="fas fa-ring"></i> Estado Civil', $datos['estado_civil'] ?? '—'],
                ['<i class="fas fa-globe"></i> Nacionalidad', $datos['nacionalidad'] ?? 'Peruana']
            ] as [$l, $v]): ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light);">
                    <span style="color:var(--text-3);font-size:13px;"><?= $l ?></span>
                    <span style="font-weight:500;font-size:13px;"><?= u($v) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-briefcase"></i> Datos Laborales</span></div>
        <div class="card-body">
            <?php foreach([
                ['<i class="fas fa-briefcase"></i> Cargo', $persona['cargo']],
                ['<i class="fas fa-building"></i> Área', $persona['area'] ?? '—'],
                ['<i class="fas fa-star"></i> Especialidad', $persona['especialidad'] ?? '—'],
                ['<i class="fas fa-clock"></i> Turno Asignado', $persona['turno_asignado'] ?? '—'],
                ['<i class="fas fa-user-tie"></i> Supervisor', $persona['supervisor'] ?? '—'],
                ['<i class="fas fa-calendar"></i> Fecha Ingreso', $fechas['ingreso'] ?? '—'],
                ['<i class="fas fa-circle"></i> Estado', ($persona['activo'] ?? true) ? 'Activo' : 'Inactivo']
            ] as [$l, $v]): ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light);">
                    <span style="color:var(--text-3);font-size:13px;"><?= $l ?></span>
                    <span style="font-weight:500;font-size:13px;"><?= u($v) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Contacto -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-hd"><span class="card-title"><i class="fas fa-phone"></i> Contacto</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <?php foreach([
                ['<i class="fas fa-phone"></i> Teléfono', $contacto['telefono'] ?? '—'],
                ['<i class="fas fa-envelope"></i> Email', $contacto['email'] ?? '—'],
                ['<i class="fas fa-map-marker-alt"></i> Dirección', $contacto['direccion'] ?? '—']
            ] as [$l, $v]): ?>
                <div style="padding:6px 0;border-bottom:1px solid var(--border-light);">
                    <span style="color:var(--text-3);font-size:13px;"><?= $l ?></span><br>
                    <span style="font-weight:500;font-size:14px;"><?= u($v) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Botón volver -->
<div style="display:flex;justify-content:flex-end;">
    <a href="<?= BASE ?>/modules/minsur/personal/" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<?php require_once __DIR__ . '/../../../includes/layout_end.php'; ?>