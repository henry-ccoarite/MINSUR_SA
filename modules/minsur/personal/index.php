<?php
// modules/minsur/personal/index.php - Lista de personal con foto
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$pageTitle = 'Personal Mina';
$pg = 'minsur_personal';
$db = getMongoDB();
$collection = $db->selectCollection('personal_mina');

// Filtros
$filtros = [];
if (!empty($_GET['area'])) $filtros['area'] = $_GET['area'];
if (!empty($_GET['cargo'])) $filtros['cargo'] = $_GET['cargo'];
if (!empty($_GET['turno_asignado'])) $filtros['turno_asignado'] = $_GET['turno_asignado'];
if (!empty($_GET['estado']) && $_GET['estado'] === 'activo') $filtros['activo'] = true;
if (!empty($_GET['estado']) && $_GET['estado'] === 'inactivo') $filtros['activo'] = false;
if (!empty($_GET['buscar'])) {
    $filtros['$or'] = [
        ['nombre' => ['$regex' => $_GET['buscar'], '$options' => 'i']],
        ['dni' => ['$regex' => $_GET['buscar'], '$options' => 'i']],
        ['cargo' => ['$regex' => $_GET['buscar'], '$options' => 'i']]
    ];
}

$cursor = $collection->find($filtros, ['sort' => ['nombre' => 1]]);
$personal = $cursor->toArray();
$total_personal = count($personal);

$areas = ['Operaciones', 'Explotación', 'Acarreo', 'Mantenimiento', 'Seguridad', 'Ventilación', 'Geomecánica', 'Comunicación Digital'];
$cargos = ['Jefe de Guardia', 'Supervisor', 'Operador', 'Técnico', 'Ayudante', 'Seguridad', 'Mecánico', 'Electricista', 'Influencer Minero'];
$turnos = ['06:00-14:00', '14:00-22:00', '22:00-06:00'];

require_once __DIR__ . '/../../../includes/layout.php';
$isMinsur = true;
$flash = getFlash();
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/minsur/index.php" style="font-size:13px;color:var(--text-2);">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
        <h1 style="margin-top:4px;"><i class="fas fa-users"></i> Personal Mina</h1>
        <p><i class="fas fa-database"></i> <?= $total_personal ?> registros</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= BASE ?>/modules/minsur/personal/nuevo.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Personal
        </a>
    </div>
</div>

<?php if($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 's' : 'e' ?>">
        <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
        <?= u($flash['msg']) ?>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 18px;">
        <form method="GET" class="fbar">
            <div class="fg">
                <label><i class="fas fa-search"></i> Buscar</label>
                <input type="text" name="buscar" class="fc" placeholder="Nombre, DNI, cargo..." value="<?= u($_GET['buscar'] ?? '') ?>">
            </div>
            <div class="fg">
                <label><i class="fas fa-building"></i> Área</label>
                <select name="area" class="fc">
                    <option value="">Todas</option>
                    <?php foreach($areas as $a): ?>
                        <option value="<?= $a ?>" <?= ($_GET['area'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label><i class="fas fa-briefcase"></i> Cargo</label>
                <select name="cargo" class="fc">
                    <option value="">Todos</option>
                    <?php foreach($cargos as $c): ?>
                        <option value="<?= $c ?>" <?= ($_GET['cargo'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label><i class="fas fa-clock"></i> Turno</label>
                <select name="turno_asignado" class="fc">
                    <option value="">Todos</option>
                    <?php foreach($turnos as $t): ?>
                        <option value="<?= $t ?>" <?= ($_GET['turno_asignado'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:6px;align-items:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="<?= BASE ?>/modules/minsur/personal/" class="btn btn-outline btn-sm">
                    <i class="fas fa-undo"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="tbl-wrap">
        <table class="table-modern">
            <thead>
                <tr>
                    <th><i class="fas fa-user"></i> Nombre</th>
                    <th><i class="fas fa-id-card"></i> DNI</th>
                    <th><i class="fas fa-briefcase"></i> Cargo</th>
                    <th><i class="fas fa-building"></i> Área</th>
                    <th><i class="fas fa-clock"></i> Turno</th>
                    <th><i class="fas fa-circle"></i> Estado</th>
                    <th><i class="fas fa-qrcode"></i> QR</th>
                    <th><i class="fas fa-cogs"></i> Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($personal as $p): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if(!empty($p['foto'])): ?>
                                <img src="<?= BASE ?>/uploads/personal/<?= u($p['foto']) ?>" 
                                     style="width:35px;height:35px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
                            <?php else: ?>
                                <div style="width:35px;height:35px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">
                                    <?= strtoupper(substr($p['nombre'] ?? 'U', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <strong><?= u($p['nombre'] ?? '—') ?></strong>
                        </div>
                    </td>
                    <td><?= u($p['dni'] ?? '—') ?></td>
                    <td><span class="bdg bdg-blue"><?= u($p['cargo'] ?? '—') ?></span></td>
                    <td><?= u($p['area'] ?? '—') ?></td>
                    <td><?= u($p['turno_asignado'] ?? '—') ?></td>
                    <td>
                        <span class="status <?= ($p['activo'] ?? true) ? 'active' : 'inactive' ?>">
                            <i class="fas <?= ($p['activo'] ?? true) ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                            <?= ($p['activo'] ?? true) ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= BASE ?>/modules/minsur/personal/ver_qr.php?id=<?= getId($p) ?>" 
                           class="btn btn-outline btn-sm btn-icon" title="Ver QR">
                            <i class="fas fa-qrcode"></i>
                        </a>
                    </td>
                    <td style="display:flex;gap:5px;">
                        <a href="<?= BASE ?>/modules/minsur/personal/ver.php?id=<?= getId($p) ?>" class="btn btn-outline btn-sm btn-icon" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?= BASE ?>/modules/minsur/personal/editar.php?id=<?= getId($p) ?>" class="btn btn-outline btn-sm btn-icon" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="<?= BASE ?>/modules/minsur/personal/eliminar.php?id=<?= getId($p) ?>" class="btn btn-danger btn-sm btn-icon" onclick="return confirm('¿Eliminar este personal?')" title="Eliminar">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if($total_personal === 0): ?>
                <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-3);">
                    <i class="fas fa-inbox"></i> No hay personal registrado
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/layout_end.php'; ?>