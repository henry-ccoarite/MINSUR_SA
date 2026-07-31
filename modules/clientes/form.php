<?php
// modules/clientes/form.php - Editar cliente con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ' . BASE . '/modules/clientes/index.php');
    exit;
}

$db = getMongoDB();
$collection = $db->selectCollection('clientes');

$id = $_GET['id'] ?? null;
$cliente = null;
if ($id) {
    $cliente = $collection->findOne(['_id' => toObjectId($id)]);
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            'calificacion' => $_POST['calificacion'] ?? 'Pendiente'
        ],
        'estado' => $_POST['estado'] ?? 'Activo'
    ];

    if (empty($data['nombre']) || empty($data['dni'])) {
        $err = 'Nombre y DNI son obligatorios.';
    } else {
        $collection->updateOne(
            ['_id' => toObjectId($id)],
            ['$set' => $data]
        );
        flash('✅ Cliente actualizado correctamente.', 'success');
        header('Location: ' . BASE . '/modules/clientes/index.php');
        exit;
    }
}

$pageTitle = 'Editar Cliente';
$pg = 'cli';
$contacto = $cliente['contacto'] ?? [];
$financiero = $cliente['financiero'] ?? [];

require_once __DIR__ . '/../../includes/layout.php';
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/clientes/index.php" style="font-size:13px;color:var(--text-2);">← Clientes</a>
        <h1 style="margin-top:4px;">✏️ Editar Cliente</h1>
    </div>
</div>

<?php if($err): ?>
    <div class="alert alert-e">❌ <?= u($err) ?></div>
<?php endif; ?>

<form method="POST">
    <div class="card">
        <div class="card-body">
            <div class="fgrid">
                <div class="fg">
                    <label>DNI *</label>
                    <input type="text" name="dni" class="fc" value="<?= u($cliente['dni'] ?? '') ?>" required>
                </div>
                <div class="fg">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="fc" value="<?= u($cliente['nombre'] ?? '') ?>" required>
                </div>
                <div class="fg">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="fc" value="<?= u($contacto['telefono'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label>Email</label>
                    <input type="email" name="email" class="fc" value="<?= u($contacto['email'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label>Ingresos (S/)</label>
                    <input type="number" step="0.01" name="ingresos" class="fc" value="<?= u($financiero['ingresos'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label>Calificación</label>
                    <select name="calificacion" class="fc">
                        <?php foreach(['Pendiente', 'Bueno', 'Excelente', 'Malo'] as $c): ?>
                            <option value="<?= $c ?>" <?= ($financiero['calificacion'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg span2">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="fc" value="<?= u($contacto['direccion'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label>Estado</label>
                    <select name="estado" class="fc">
                        <option value="Activo" <?= ($cliente['estado'] ?? '') === 'Activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="Inactivo" <?= ($cliente['estado'] ?? '') === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
        <a href="<?= BASE ?>/modules/clientes/index.php" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary">💾 Guardar</button>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>