<?php
// modules/contratos/form.php - Crear contrato con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ' . BASE . '/modules/contratos/index.php');
    exit;
}

$db = getMongoDB();
$contratos = $db->selectCollection('contratos');
$clientes = $db->selectCollection('clientes');
$propiedades = $db->selectCollection('propiedades');

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $_POST['cliente_id'] ?? null;
    $propiedad_id = $_POST['propiedad_id'] ?? null;
    $tipo = $_POST['tipo'] ?? '';
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $total = (float)($_POST['total'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');

    // Obtener datos del cliente y propiedad
    $cliente = $clientes->findOne(['_id' => toObjectId($cliente_id)]);
    $propiedad = $propiedades->findOne(['_id' => toObjectId($propiedad_id)]);

    if (!$cliente || !$propiedad) {
        $err = 'Cliente o propiedad no válidos.';
    } elseif (empty($tipo) || $total <= 0) {
        $err = 'Completa todos los campos obligatorios.';
    } else {
        $data = [
            'numero' => 'CONT-' . strtoupper(substr($tipo, 0, 3)) . '-' . date('Ymd') . '-' . uniqid(),
            'cliente' => [
                'id' => toObjectId($cliente_id),
                'nombre' => $cliente['nombre'],
                'dni' => $cliente['dni']
            ],
            'propiedad' => [
                'id' => toObjectId($propiedad_id),
                'codigo' => $propiedad['codigo'],
                'direccion' => $propiedad['direccion']
            ],
            'tipo' => $tipo,
            'fecha' => new MongoDB\BSON\UTCDateTime(strtotime($fecha) * 1000),
            'total' => $total,
            'estado' => 'Activo',
            'observaciones' => $observaciones,
            'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ];

        $contratos->insertOne($data);

        // Actualizar estado de la propiedad
        $nuevo_estado = $tipo === 'Venta' ? 'Vendido' : 'Alquilado';
        $propiedades->updateOne(
            ['_id' => toObjectId($propiedad_id)],
            ['$set' => ['estado' => $nuevo_estado]]
        );

        flash('✅ Contrato creado correctamente.', 'success');
        header('Location: ' . BASE . '/modules/contratos/index.php');
        exit;
    }
}

$clientes_list = $clientes->find(['estado' => 'Activo'], ['sort' => ['nombre' => 1]]);
$propiedades_list = $propiedades->find(
    ['estado' => ['$in' => ['Disponible', 'Reservado']]],
    ['sort' => ['codigo' => 1]]
);

$pageTitle = 'Nuevo Contrato';
$pg = 'cont';

require_once __DIR__ . '/../../includes/layout.php';
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/contratos/index.php" style="font-size:13px;color:var(--text-2);">← Contratos</a>
        <h1 style="margin-top:4px;">📝 Nuevo Contrato</h1>
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
                    <label>Tipo *</label>
                    <select name="tipo" class="fc" required>
                        <option value="Venta">Venta</option>
                        <option value="Alquiler">Alquiler</option>
                    </select>
                </div>
                <div class="fg">
                    <label>Fecha *</label>
                    <input type="date" name="fecha" class="fc" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="fg">
                    <label>Cliente *</label>
                    <select name="cliente_id" class="fc" required>
                        <option value="">Seleccionar cliente...</option>
                        <?php foreach($clientes_list as $c): ?>
                            <option value="<?= getId($c) ?>"><?= u($c['nombre']) ?> — <?= u($c['dni']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label>Propiedad *</label>
                    <select name="propiedad_id" class="fc" required>
                        <option value="">Seleccionar propiedad...</option>
                        <?php foreach($propiedades_list as $p): ?>
                            <option value="<?= getId($p) ?>" data-venta="<?= $p['precios']['venta'] ?? 0 ?>" data-alq="<?= $p['precios']['alquiler'] ?? 0 ?>">
                                <?= u($p['codigo']) ?> — <?= u($p['ciudad']) ?> — S/ <?= number_format($p['precios']['venta'] ?? 0, 0, '.', ',') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label>Total (S/) *</label>
                    <input type="number" step="0.01" name="total" id="total" class="fc" required>
                </div>
                <div class="fg span2">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="fc" rows="3" placeholder="Opcional..."></textarea>
                </div>
            </div>
        </div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
        <a href="<?= BASE ?>/modules/contratos/index.php" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary">💾 Guardar Contrato</button>
    </div>
</form>

<?php
$extraJs = '
<script>
document.getElementById("propiedad_id").addEventListener("change", function() {
    var tipo = document.querySelector("select[name=tipo]").value;
    var opt = this.options[this.selectedIndex];
    if (!opt || !opt.value) return;
    var precio = tipo === "Alquiler" ? parseFloat(opt.dataset.alq) || 0 : parseFloat(opt.dataset.venta);
    document.getElementById("total").value = precio.toFixed(2);
});
document.querySelector("select[name=tipo]").addEventListener("change", function() {
    document.getElementById("propiedad_id").dispatchEvent(new Event("change"));
});
</script>';
require_once __DIR__ . '/../../includes/layout_end.php';
?>