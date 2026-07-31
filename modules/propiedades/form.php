<?php
// modules/propiedades/form.php - Crear/Editar propiedad con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ' . BASE . '/modules/propiedades/index.php');
    exit;
}

$db = getMongoDB();
$collection = $db->selectCollection('propiedades');

$id = $_GET['id'] ?? null;
$prop = null;
if ($id) {
    $prop = $collection->findOne(['_id' => toObjectId($id)]);
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'codigo' => trim($_POST['codigo'] ?? ''),
        'direccion' => trim($_POST['direccion'] ?? ''),
        'ciudad' => trim($_POST['ciudad'] ?? ''),
        'tipo' => $_POST['tipo'] ?? '',
        'caracteristicas' => [
            'habitaciones' => (int)($_POST['habitaciones'] ?? 0),
            'banos' => (int)($_POST['banos'] ?? 0),
            'area' => !empty($_POST['area']) ? (float)$_POST['area'] : null,
            'estacionamiento' => isset($_POST['estacionamiento'])
        ],
        'precios' => [
            'venta' => (float)($_POST['precio_venta'] ?? 0),
            'alquiler' => !empty($_POST['precio_alquiler']) ? (float)$_POST['precio_alquiler'] : null
        ],
        'estado' => $_POST['estado'] ?? 'Disponible',
        'fechas' => [
            'actualizacion' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ]
    ];

    if (empty($data['codigo']) || empty($data['direccion']) || empty($data['ciudad']) || empty($data['tipo']) || $data['precios']['venta'] <= 0) {
        $err = 'Completa los campos obligatorios.';
    } else {
        // Manejar imagen
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $nombreArchivo = time() . '_' . $_FILES['imagen']['name'];
            $rutaDestino = __DIR__ . '/../../uploads/' . $nombreArchivo;
            move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino);
            $data['imagen'] = $nombreArchivo;
        } elseif ($prop && isset($prop['imagen'])) {
            $data['imagen'] = $prop['imagen'];
        }

        if ($id) {
            $collection->updateOne(
                ['_id' => toObjectId($id)],
                ['$set' => $data]
            );
            flash('✅ Propiedad actualizada correctamente.', 'success');
        } else {
            $data['fechas']['creacion'] = new MongoDB\BSON\UTCDateTime(time() * 1000);
            $collection->insertOne($data);
            flash('✅ Propiedad registrada correctamente.', 'success');
        }
        
        header('Location: ' . BASE . '/modules/propiedades/index.php');
        exit;
    }
}

$pageTitle = $id ? 'Editar Propiedad' : 'Nueva Propiedad';
$pg = 'prop';
$carac = $prop['caracteristicas'] ?? [];
$precios = $prop['precios'] ?? [];

require_once __DIR__ . '/../../includes/layout.php';
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/propiedades/index.php" style="font-size:13px;color:var(--text-2);">← Propiedades</a>
        <h1 style="margin-top:4px;"><?= $pageTitle ?></h1>
    </div>
</div>

<?php if($err): ?>
    <div class="alert alert-e">❌ <?= u($err) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="card">
        <div class="card-hd"><span class="card-title">Información de la Propiedad</span></div>
        <div class="card-body">
            <div class="fgrid">
                <div class="fg">
                    <label>Código *</label>
                    <input type="text" name="codigo" class="fc" value="<?= u($prop['codigo'] ?? '') ?>" required>
                </div>
                <div class="fg">
                    <label>Tipo *</label>
                    <select name="tipo" class="fc" required>
                        <?php foreach(['Casa', 'Departamento', 'Terreno', 'Local'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($prop['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg span2">
                    <label>Dirección *</label>
                    <input type="text" name="direccion" class="fc" value="<?= u($prop['direccion'] ?? '') ?>" required>
                </div>
                <div class="fg">
                    <label>Ciudad *</label>
                    <input type="text" name="ciudad" class="fc" value="<?= u($prop['ciudad'] ?? 'Puno') ?>" required>
                </div>
                <div class="fg">
                    <label>Estado</label>
                    <select name="estado" class="fc">
                        <?php foreach(['Disponible', 'Vendido', 'Alquilado', 'Reservado'] as $e): ?>
                            <option value="<?= $e ?>" <?= ($prop['estado'] ?? 'Disponible') === $e ? 'selected' : '' ?>><?= $e ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label>Área (m²)</label>
                    <input type="number" step="0.01" name="area" class="fc" value="<?= u($carac['area'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label>Precio Venta (S/) *</label>
                    <input type="number" step="0.01" name="precio_venta" class="fc" value="<?= u($precios['venta'] ?? '') ?>" required>
                </div>
                <div class="fg">
                    <label>Precio Alquiler (S/mes)</label>
                    <input type="number" step="0.01" name="precio_alquiler" class="fc" value="<?= u($precios['alquiler'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label>Habitaciones</label>
                    <input type="number" name="habitaciones" class="fc" value="<?= u($carac['habitaciones'] ?? 0) ?>">
                </div>
                <div class="fg">
                    <label>Baños</label>
                    <input type="number" name="banos" class="fc" value="<?= u($carac['banos'] ?? 0) ?>">
                </div>
                <div class="fg">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="estacionamiento" <?= (!empty($carac['estacionamiento'])) ? 'checked' : '' ?>>
                        Estacionamiento
                    </label>
                </div>
                <div class="fg span2">
                    <label>Imagen</label>
                    <?php if(!empty($prop['imagen'])): ?>
                        <div style="margin-bottom:10px;">
                            <img src="<?= BASE ?>/uploads/<?= u($prop['imagen']) ?>" style="max-width:200px;max-height:150px;border-radius:8px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="imagen" class="fc" accept="image/*">
                </div>
            </div>
        </div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
        <a href="<?= BASE ?>/modules/propiedades/index.php" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary">💾 Guardar</button>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>