<?php
// modules/minsur/reportes/ver.php - Ver detalle con evidencias
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$id = $_GET['id'] ?? null;
$db = getMongoDB();
$reporte = $db->selectCollection('reportes_guardia')->findOne(['_id' => toObjectId($id)]);

if (!$reporte) {
    header('Location: ' . BASE . '/modules/minsur/reportes/');
    exit;
}

$pageTitle = 'Reporte - ' . formatDateOnly($reporte['fecha']);
$pg = 'minsur_reportes';
$prioridad_color = ['Baja'=>'bdg-green','Media'=>'bdg-yellow','Alta'=>'bdg-orange','Crítica'=>'bdg-red'][$reporte['prioridad']??'Media']??'bdg-gray';
$estado_color = ($reporte['estado']??'Pendiente') === 'Completado' ? 'bdg-green' : 'bdg-yellow';

require_once __DIR__ . '/../../../includes/layout.php';
$isMinsur = true;
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/minsur/reportes/" style="font-size:13px;color:var(--text-2);">
            <i class="fas fa-arrow-left"></i> Reportes
        </a>
        <h1 style="margin-top:4px;">
            <i class="fas fa-file-alt"></i> <?= formatDateOnly($reporte['fecha']) ?>
            <span class="bdg <?= $estado_color ?>"><?= u($reporte['estado'] ?? 'Pendiente') ?></span>
            <span class="bdg <?= $prioridad_color ?>"><?= u($reporte['prioridad'] ?? 'Media') ?></span>
        </h1>
        <p><i class="fas fa-clock"></i> Turno: <?= u($reporte['turno']) ?> | <i class="fas fa-user"></i> Supervisor: <?= u($reporte['supervisor']) ?></p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= BASE ?>/modules/minsur/reportes/editar.php?id=<?= getId($reporte) ?>" class="btn btn-outline">
            <i class="fas fa-edit"></i> Editar
        </a>
        <a href="<?= BASE ?>/modules/minsur/reportes/exportar.php?id=<?= getId($reporte) ?>&formato=pdf" class="btn btn-outline">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
    </div>
</div>

<!-- ============================================ -->
<!-- INFORMACIÓN GENERAL                          -->
<!-- ============================================ -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-info-circle"></i> Información General</span></div>
        <div class="card-body">
            <?php foreach([
                ['<i class="fas fa-calendar"></i> Fecha', formatDateOnly($reporte['fecha'])],
                ['<i class="fas fa-clock"></i> Turno', $reporte['turno']],
                ['<i class="fas fa-user"></i> Supervisor', $reporte['supervisor']],
                ['<i class="fas fa-map-marker-alt"></i> Área', $reporte['area'] ?? 'Mina San Rafael'],
                ['<i class="fas fa-flag"></i> Prioridad', $reporte['prioridad'] ?? 'Media'],
                ['<i class="fas fa-circle"></i> Estado', $reporte['estado'] ?? 'Pendiente']
            ] as [$l, $v]): ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light);">
                    <span style="color:var(--text-3);font-size:13px;"><?= $l ?></span>
                    <span style="font-weight:500;font-size:13px;"><?= u($v) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-align-left"></i> Descripción</span></div>
        <div class="card-body">
            <p style="font-size:14px;line-height:1.6;"><?= nl2br(u($reporte['descripcion'] ?? '')) ?></p>
            <?php if(!empty($reporte['observaciones'])): ?>
                <hr>
                <p style="font-size:13px;color:var(--text-2);"><i class="fas fa-comment"></i> <strong>Observaciones:</strong><br><?= nl2br(u($reporte['observaciones'])) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- PERSONAL ASIGNADO                            -->
<!-- ============================================ -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-hd"><span class="card-title"><i class="fas fa-users"></i> Personal Asignado</span></div>
    <div class="tbl-wrap">
        <table class="table-modern">
            <thead>
                <tr><th><i class="fas fa-user"></i> Nombre</th><th><i class="fas fa-briefcase"></i> Cargo</th><th><i class="fas fa-tasks"></i> Actividad</th><th><i class="fas fa-exclamation-circle"></i> Incidencias</th></tr>
            </thead>
            <tbody>
            <?php foreach($reporte['personal'] ?? [] as $p): ?>
                <tr>
                    <td><strong><?= u($p['nombre'] ?? '—') ?></strong></td>
                    <td><?= u($p['cargo'] ?? '—') ?></td>
                    <td><?= u($p['actividad'] ?? '—') ?></td>
                    <td><?= u($p['incidencias'] ?? 'Ninguna') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================ -->
<!-- INCIDENCIAS                                  -->
<!-- ============================================ -->
<?php if(!empty($reporte['incidencias_generales'])): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-hd"><span class="card-title"><i class="fas fa-exclamation-triangle"></i> Incidencias</span></div>
    <div class="tbl-wrap">
        <table class="table-modern">
            <thead>
                <tr><th><i class="fas fa-tag"></i> Tipo</th><th><i class="fas fa-align-left"></i> Descripción</th><th><i class="fas fa-flag"></i> Gravedad</th><th><i class="fas fa-check-circle"></i> Acción</th></tr>
            </thead>
            <tbody>
            <?php foreach($reporte['incidencias_generales'] as $i): ?>
                <tr>
                    <td><span class="bdg <?= $i['tipo'] === 'Seguridad' ? 'bdg-red' : 'bdg-yellow' ?>"><?= u($i['tipo']??'—') ?></span></td>
                    <td><?= u($i['descripcion']??'—') ?></td>
                    <td><span class="bdg <?= $i['gravedad'] === 'Crítica' ? 'bdg-red' : 'bdg-yellow' ?>"><?= u($i['gravedad']??'Media') ?></span></td>
                    <td><?= u($i['accion']??'—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- EVIDENCIAS (FOTOS / VIDEOS)                  -->
<!-- ============================================ -->
<?php if(!empty($reporte['imagenes'])): ?>
<div class="card" style="margin-top:20px;">
    <div class="card-hd">
        <span class="card-title"><i class="fas fa-camera"></i> Evidencias</span>
        <span style="font-size:11px;color:var(--text-3);"><?= count($reporte['imagenes']) ?> archivos</span>
    </div>
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:12px;">
        <?php foreach($reporte['imagenes'] as $img): ?>
            <div style="width:150px;height:150px;border-radius:8px;overflow:hidden;border:1px solid var(--border);position:relative;">
                <?php 
                $ext = pathinfo($img, PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), ['mp4', 'webm', 'ogg', 'mov'])): 
                ?>
                    <video src="<?= BASE ?>/uploads/minsur/<?= u($img) ?>" style="width:100%;height:100%;object-fit:cover;"></video>
                    <div style="position:absolute;bottom:4px;right:4px;background:rgba(0,0,0,0.6);color:white;padding:2px 8px;border-radius:4px;font-size:10px;">
                        <i class="fas fa-video"></i> Video
                    </div>
                <?php else: ?>
                    <img src="<?= BASE ?>/uploads/minsur/<?= u($img) ?>" style="width:100%;height:100%;object-fit:cover;">
                    <div style="position:absolute;bottom:4px;right:4px;background:rgba(0,0,0,0.6);color:white;padding:2px 8px;border-radius:4px;font-size:10px;">
                        <i class="fas fa-image"></i> Foto
                    </div>
                <?php endif; ?>
                <!-- Botón para ver en grande -->
                <a href="<?= BASE ?>/uploads/minsur/<?= u($img) ?>" target="_blank" style="position:absolute;top:4px;right:4px;background:rgba(0,0,0,0.6);color:white;border:none;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;text-decoration:none;">
                    <i class="fas fa-expand"></i>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- BOTÓN VOLVER                                 -->
<!-- ============================================ -->
<div style="display:flex;justify-content:flex-end;">
    <a href="<?= BASE ?>/modules/minsur/reportes/" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<?php require_once __DIR__ . '/../../../includes/layout_end.php'; ?>