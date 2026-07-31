<?php
// modules/empleados/index.php - Gestionar empleados con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ' . BASE . '/index.php');
    exit;
}

$pageTitle = 'Empleados';
$pg = 'emp';
$db = getMongoDB();
$collection = $db->selectCollection('empleados');

// Crear empleado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'crear') {
    $data = [
        'usuario' => trim($_POST['usuario'] ?? ''),
        'password_hash' => md5(trim($_POST['password'] ?? '')),
        'nombre' => trim($_POST['nombre'] ?? ''),
        'contacto' => [
            'email' => trim($_POST['email'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? '')
        ],
        'rol' => $_POST['rol'] ?? 'Agente',
        'activo' => true,
        'fechas' => [
            'creacion' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ]
    ];

    if (empty($data['usuario']) || empty($data['nombre']) || empty($_POST['password'])) {
        flash('Completa todos los campos obligatorios.', 'error');
    } else {
        try {
            $collection->insertOne($data);
            flash('✅ Empleado registrado correctamente.', 'success');
        } catch (Exception $e) {
            flash('❌ Error: ' . $e->getMessage(), 'error');
        }
    }
    header('Location: ' . BASE . '/modules/empleados/index.php');
    exit;
}

// Toggle estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'toggle') {
    $id = $_POST['emp_id'] ?? null;
    if ($id) {
        $emp = $collection->findOne(['_id' => toObjectId($id)]);
        if ($emp) {
            $nuevo_estado = !($emp['activo'] ?? true);
            $collection->updateOne(
                ['_id' => toObjectId($id)],
                ['$set' => ['activo' => $nuevo_estado]]
            );
            flash('✅ Estado actualizado.', 'success');
        }
    }
    header('Location: ' . BASE . '/modules/empleados/index.php');
    exit;
}

$empleados = $collection->find([], ['sort' => ['nombre' => 1]]);
$roles = ['Admin', 'Gerente', 'SubGerente', 'Agente', 'Supervisor'];

require_once __DIR__ . '/../../includes/layout.php';
$flash = getFlash();
?>

<div class="ph">
    <div>
        <h1>👔 Empleados</h1>
        <p>Usuarios del sistema - MongoDB</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('mEmp')">➕ Nuevo Empleado</button>
</div>

<?php if($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 's' : 'e' ?>"><?= u($flash['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Empleado</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($empleados as $e): ?>
                <tr>
                    <td style="color:var(--text-3);"><?= getId($e) ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:9px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <?= strtoupper(substr($e['nombre'], 0, 1)) ?>
                            </div>
                            <strong><?= u($e['nombre']) ?></strong>
                        </div>
                    </td>
                    <td><code style="font-size:12px;background:var(--bg);padding:2px 6px;border-radius:4px;"><?= u($e['usuario']) ?></code></td>
                    <td><?= u($e['contacto']['email'] ?? '—') ?></td>
                    <td><?= u($e['contacto']['telefono'] ?? '—') ?></td>
                    <td><span class="bdg bdg-blue"><?= u($e['rol']) ?></span></td>
                    <td>
                        <span class="bdg <?= ($e['activo'] ?? true) ? 'bdg-green' : 'bdg-gray' ?>">
                            <?= ($e['activo'] ?? true) ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="_action" value="toggle">
                            <input type="hidden" name="emp_id" value="<?= getId($e) ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" title="<?= ($e['activo'] ?? true) ? 'Desactivar' : 'Activar' ?>">
                                <?= ($e['activo'] ?? true) ? '🔒' : '🔓' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVO EMPLEADO -->
<div class="modal-ov" id="mEmp">
    <div class="modal">
        <div class="modal-hd">
            <h3>Nuevo Empleado</h3>
            <button class="modal-cl" onclick="closeModal('mEmp')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="crear">
            <div class="modal-bd">
                <div class="fgrid">
                    <div class="fg span2">
                        <label>Nombre completo *</label>
                        <input type="text" name="nombre" class="fc" required>
                    </div>
                    <div class="fg">
                        <label>Usuario *</label>
                        <input type="text" name="usuario" class="fc" required>
                    </div>
                    <div class="fg">
                        <label>Contraseña *</label>
                        <input type="password" name="password" class="fc" required>
                    </div>
                    <div class="fg">
                        <label>Email</label>
                        <input type="email" name="email" class="fc">
                    </div>
                    <div class="fg">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" class="fc">
                    </div>
                    <div class="fg span2">
                        <label>Rol *</label>
                        <select name="rol" class="fc" required>
                            <option value="">Seleccionar rol...</option>
                            <?php foreach($roles as $r): ?>
                                <option value="<?= $r ?>"><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <p style="font-size:12px;color:var(--text-3);margin-top:12px;">La contraseña se guarda encriptada con MD5.</p>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-outline" onclick="closeModal('mEmp')">Cancelar</button>
                <button type="submit" class="btn btn-primary">💾 Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>