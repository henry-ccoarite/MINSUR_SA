<?php
// modules/clientes/index.php - Listar clientes con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$pageTitle = 'Clientes';
$pg = 'cli';
$db = getMongoDB();
$collection = $db->selectCollection('clientes');

// Buscar
$q = trim($_GET['q'] ?? '');
$filtros = [];
if ($q) {
    $filtros['$or'] = [
        ['nombre' => ['$regex' => $q, '$options' => 'i']],
        ['dni' => ['$regex' => $q, '$options' => 'i']],
        ['contacto.telefono' => ['$regex' => $q, '$options' => 'i']],
        ['contacto.email' => ['$regex' => $q, '$options' => 'i']]
    ];
}

$clientes = $collection->find($filtros, ['sort' => ['nombre' => 1]]);

// POST crear cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'crear') {
    $data = [
        'dni' => trim($_POST['dni'] ?? ''),
        'nombre' => trim($_POST['nombre'] ?? ''),
        'contacto' => [
            'telefono' => trim($_POST['telefono'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? '')
        ],
        'financiero' => [
            'ingresos' => !empty($_POST['ingresos']) ? (float)$_POST['ingresos'] : null,
            'calificacion' => 'Pendiente'
        ],
        'estado' => 'Activo',
        'fechas' => [
            'registro' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ]
    ];

    if (empty($data['nombre']) || empty($data['dni'])) {
        flash('Nombre y DNI son obligatorios.', 'error');
    } else {
        try {
            $collection->insertOne($data);
            flash('✅ Cliente registrado correctamente.', 'success');
        } catch (Exception $e) {
            flash('❌ Error: ' . $e->getMessage(), 'error');
        }
    }
    header('Location: ' . BASE . '/modules/clientes/index.php');
    exit;
}

require_once __DIR__ . '/../../includes/layout.php';
$flash = getFlash();
?>

<div class="ph">
    <div>
        <h1>👤 Clientes</h1>
        <p>Base de datos de clientes - MongoDB</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('mCli')">
        ➕ Nuevo Cliente
    </button>
</div>

<?php if($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 's' : 'e' ?>">
        <?= u($flash['msg']) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-hd">
        <form method="GET" style="display:flex;gap:8px;flex:1;max-width:420px;">
            <input type="text" name="q" class="fc" placeholder="Buscar por nombre, DNI, teléfono..." value="<?= u($q) ?>">
            <button type="submit" class="btn btn-outline btn-sm">🔍</button>
            <?php if($q): ?>
                <a href="<?= BASE ?>/modules/clientes/index.php" class="btn btn-outline btn-sm">✕</a>
            <?php endif; ?>
        </form>
        <span style="font-size:13px;color:var(--text-2);"><?= iterator_count($clientes) ?> clientes</span>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>DNI</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Ingresos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($clientes as $c): ?>
                <tr>
                    <td style="color:var(--text-3);"><?= getId($c) ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:9px;">
                            <div style="width:30px;height:30px;border-radius:50%;background:var(--primary);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <?= strtoupper(substr($c['nombre'], 0, 1)) ?>
                            </div>
                            <strong><?= u($c['nombre']) ?></strong>
                        </div>
                    </td>
                    <td><?= u($c['dni']) ?></td>
                    <td><?= u($c['contacto']['telefono'] ?? '—') ?></td>
                    <td><?= u($c['contacto']['email'] ?? '—') ?></td>
                    <td><?= !empty($c['financiero']['ingresos']) ? 'S/ '.number_format($c['financiero']['ingresos'], 2) : '—' ?></td>
                    <td>
                        <span class="bdg <?= ($c['estado'] ?? 'Activo') === 'Activo' ? 'bdg-green' : 'bdg-gray' ?>">
                            <?= u($c['estado'] ?? 'Activo') ?>
                        </span>
                    </td>
                    <td style="display:flex;gap:5px;">
                        <a href="<?= BASE ?>/modules/clientes/view.php?id=<?= getId($c) ?>" class="btn btn-outline btn-sm btn-icon" title="Ver">👁️</a>
                        <?php if(isAdmin()): ?>
                            <a href="<?= BASE ?>/modules/clientes/form.php?id=<?= getId($c) ?>" class="btn btn-outline btn-sm btn-icon" title="Editar">✏️</a>
                            <a href="<?= BASE ?>/modules/clientes/delete.php?id=<?= getId($c) ?>" class="btn btn-danger btn-sm btn-icon" title="Eliminar" onclick="return confirm('¿Eliminar este cliente?')">🗑️</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVO CLIENTE -->
<div class="modal-ov" id="mCli">
    <div class="modal">
        <div class="modal-hd">
            <h3>Nuevo Cliente</h3>
            <button class="modal-cl" onclick="closeModal('mCli')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="crear">
            <div class="modal-bd">
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Nombre completo *</label>
                        <input type="text" name="nombre" class="fc" required>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">DNI *</label>
                            <input type="text" name="dni" class="fc" maxlength="15" required>
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Teléfono</label>
                            <input type="text" name="telefono" class="fc">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Email</label>
                            <input type="email" name="email" class="fc">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Ingresos (S/)</label>
                            <input type="number" step="0.01" name="ingresos" class="fc">
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Dirección</label>
                        <input type="text" name="direccion" class="fc">
                    </div>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-outline" onclick="closeModal('mCli')">Cancelar</button>
                <button type="submit" class="btn btn-primary">💾 Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>