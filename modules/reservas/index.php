<?php
// modules/reservas/index.php - Gestionar reservas con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$pageTitle = 'Reservas';
$pg = 'res';
$db = getMongoDB();
$reservas = $db->selectCollection('reservas');
$clientes = $db->selectCollection('clientes');
$propiedades = $db->selectCollection('propiedades');

// Procesar nueva reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $_POST['cliente_id'] ?? null;
    $propiedad_id = $_POST['propiedad_id'] ?? null;
    $monto = (float)($_POST['monto'] ?? 0);

    if ($cliente_id && $propiedad_id && $monto > 0) {
        $cliente = $clientes->findOne(['_id' => toObjectId($cliente_id)]);
        $propiedad = $propiedades->findOne(['_id' => toObjectId($propiedad_id)]);

        if ($cliente && $propiedad) {
            $reservas->insertOne([
                'cliente' => [
                    'id' => toObjectId($cliente_id),
                    'nombre' => $cliente['nombre']
                ],
                'propiedad' => [
                    'id' => toObjectId($propiedad_id),
                    'codigo' => $propiedad['codigo'],
                    'ciudad' => $propiedad['ciudad']
                ],
                'monto' => $monto,
                'fecha' => new MongoDB\BSON\UTCDateTime(time() * 1000),
                'estado' => 'Activa',
                'observaciones' => trim($_POST['observaciones'] ?? '')
            ]);

            // Actualizar estado de la propiedad
            $propiedades->updateOne(
                ['_id' => toObjectId($propiedad_id)],
                ['$set' => ['estado' => 'Reservado']]
            );

            flash('✅ Reserva registrada correctamente.', 'success');
        }
    } else {
        flash('❌ Completa todos los campos.', 'error');
    }
    header('Location: ' . BASE . '/modules/reservas/index.php');
    exit;
}

$lista_reservas = $reservas->find([], ['sort' => ['fecha' => -1]]);
$clientes_list = $clientes->find(['estado' => 'Activo'], ['sort' => ['nombre' => 1]]);
$propiedades_list = $propiedades->find(['estado' => 'Disponible'], ['sort' => ['codigo' => 1]]);

$prop_id_pre = (int)($_GET['propiedad_id'] ?? 0);

require_once __DIR__ . '/../../includes/layout.php';
$flash = getFlash();
?>

<div class="ph">
    <div>
        <h1>📌 Reservas</h1>
        <p>Gestión de reservas de propiedades - MongoDB</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('mRes')">➕ Nueva Reserva</button>
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
                    <th>Cliente</th>
                    <th>Propiedad</th>
                    <th>Ciudad</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($lista_reservas as $r):
                $bc = ['Activa' => 'bdg-green', 'Vencida' => 'bdg-red', 'Convertida' => 'bdg-blue'][$r['estado']] ?? 'bdg-gray';
            ?>
                <tr>
                    <td style="color:var(--text-3);"><?= getId($r) ?></td>
                    <td><strong><?= u($r['cliente']['nombre'] ?? '—') ?></strong></td>
                    <td><span class="bdg bdg-gray"><?= u($r['propiedad']['codigo'] ?? '—') ?></span></td>
                    <td><?= u($r['propiedad']['ciudad'] ?? '—') ?></td>
                    <td>S/ <?= number_format($r['monto'], 2) ?></td>
                    <td><?= formatDateOnly($r['fecha']) ?></td>
                    <td><span class="bdg <?= $bc ?>"><?= u($r['estado']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVA RESERVA -->
<div class="modal-ov" id="mRes">
    <div class="modal">
        <div class="modal-hd">
            <h3>Nueva Reserva</h3>
            <button class="modal-cl" onclick="closeModal('mRes')">✕</button>
        </div>
        <form method="POST">
            <div class="modal-bd">
                <div class="fgrid">
                    <div class="fg span2">
                        <label>Cliente *</label>
                        <select name="cliente_id" class="fc" required>
                            <option value="">Seleccionar cliente...</option>
                            <?php foreach($clientes_list as $c): ?>
                                <option value="<?= getId($c) ?>"><?= u($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg span2">
                        <label>Propiedad *</label>
                        <select name="propiedad_id" class="fc" required>
                            <option value="">Seleccionar propiedad...</option>
                            <?php foreach($propiedades_list as $p): ?>
                                <option value="<?= getId($p) ?>" <?= $prop_id_pre == $p['id'] ? 'selected' : '' ?>>
                                    <?= u($p['codigo']) ?> — <?= u($p['ciudad']) ?> — S/ <?= number_format($p['precios']['venta'] ?? 0, 0, '.', ',') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Monto (S/) *</label>
                        <input type="number" step="0.01" name="monto" class="fc" placeholder="0.00" required>
                    </div>
                    <div class="fg span2">
                        <label>Observaciones</label>
                        <textarea name="observaciones" class="fc" rows="2" placeholder="Opcional..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-outline" onclick="closeModal('mRes')">Cancelar</button>
                <button type="submit" class="btn btn-primary">💾 Registrar Reserva</button>
            </div>
        </form>
    </div>
</div>

<?php if($prop_id_pre): ?>
    <script>document.addEventListener('DOMContentLoaded', function() { openModal('mRes'); });</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>