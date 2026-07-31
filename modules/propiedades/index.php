<?php
// modules/propiedades/index.php - Listar propiedades con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$pageTitle = 'Propiedades';
$pg = 'prop';
$db = getMongoDB();
$collection = $db->selectCollection('propiedades');

// Filtros
$filtros = [];
if (!empty($_GET['ciudad'])) $filtros['ciudad'] = $_GET['ciudad'];
if (!empty($_GET['tipo'])) $filtros['tipo'] = $_GET['tipo'];
if (!empty($_GET['estado'])) $filtros['estado'] = $_GET['estado'];
if (!empty($_GET['min'])) $filtros['precios.venta']['$gte'] = (float)$_GET['min'];
if (!empty($_GET['max'])) $filtros['precios.venta']['$lte'] = (float)$_GET['max'];

$propiedades = $collection->find($filtros, ['sort' => ['_id' => -1]]);
$ciudades = $collection->distinct('ciudad');

require_once __DIR__ . '/../../includes/layout.php';
$flash = getFlash();
?>

<div class="ph">
    <div>
        <h1>🏠 Propiedades</h1>
        <p>Catálogo de inmuebles - MongoDB</p>
    </div>
    <?php if(isAdmin()): ?>
        <a href="<?= BASE ?>/modules/propiedades/form.php" class="btn btn-primary">➕ Nueva Propiedad</a>
    <?php endif; ?>
</div>

<?php if($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 's' : 'e' ?>">
        <?= u($flash['msg']) ?>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card" style="margin-bottom:18px;">
    <div class="card-body" style="padding:14px 18px;">
        <form method="GET" class="fbar">
            <div class="fg">
                <label>Ciudad</label>
                <select name="ciudad" class="fc">
                    <option value="">Todas</option>
                    <?php foreach($ciudades as $c): ?>
                        <option value="<?= u($c) ?>" <?= ($_GET['ciudad'] ?? '') === $c ? 'selected' : '' ?>>
                            <?= u($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Tipo</label>
                <select name="tipo" class="fc">
                    <option value="">Todos</option>
                    <?php foreach(['Casa', 'Departamento', 'Terreno', 'Local'] as $t): ?>
                        <option value="<?= $t ?>" <?= ($_GET['tipo'] ?? '') === $t ? 'selected' : '' ?>>
                            <?= $t ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Estado</label>
                <select name="estado" class="fc">
                    <option value="">Todos</option>
                    <?php foreach(['Disponible', 'Vendido', 'Alquilado', 'Reservado'] as $e): ?>
                        <option value="<?= $e ?>" <?= ($_GET['estado'] ?? '') === $e ? 'selected' : '' ?>>
                            <?= $e ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:6px;align-items:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">🔍 Filtrar</button>
                <a href="<?= BASE ?>/modules/propiedades/index.php" class="btn btn-outline btn-sm">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Catálogo -->
<div class="prop-grid">
    <?php 
    $tipoIcons = ['Casa' => '🏠', 'Departamento' => '🏢', 'Terreno' => '🗺️', 'Local' => '🏪'];
    $bdgEstado = ['Disponible' => 'bdg-green', 'Vendido' => 'bdg-gray', 'Alquilado' => 'bdg-blue', 'Reservado' => 'bdg-yellow'];
    
    foreach($propiedades as $prop): 
        $bc = $bdgEstado[$prop['estado']] ?? 'bdg-gray';
        $precio = $prop['precios']['venta'] ?? 0;
        $alquiler = $prop['precios']['alquiler'] ?? null;
        $carac = $prop['caracteristicas'] ?? [];
    ?>
        <div class="prop-card">
            <div class="prop-thumb-ph">
                <?php if(!empty($prop['imagen'])): ?>
                    <img class="prop-thumb-ph-img" src="<?= BASE ?>/uploads/<?= u($prop['imagen']) ?>">
                <?php else: ?>
                    <?= $tipoIcons[$prop['tipo']] ?? '🏗️' ?>
                <?php endif; ?>
            </div>
            <div class="prop-body">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div class="prop-tipo"><?= u($prop['tipo']) ?></div>
                    <span class="bdg <?= $bc ?>"><?= u($prop['estado']) ?></span>
                </div>
                <div class="prop-cod"><?= u($prop['codigo']) ?></div>
                <div class="prop-dir"><?= u($prop['direccion']) ?>, <?= u($prop['ciudad']) ?></div>
                <div class="prop-meta">
                    <?php if(!empty($carac['area'])): ?>
                        <div class="prop-mi">📐 <?= $carac['area'] ?> m²</div>
                    <?php endif; ?>
                    <?php if(!empty($carac['habitaciones'])): ?>
                        <div class="prop-mi">🛏️ <?= $carac['habitaciones'] ?></div>
                    <?php endif; ?>
                    <?php if(!empty($carac['banos'])): ?>
                        <div class="prop-mi">🚿 <?= $carac['banos'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="prop-price">S/ <?= number_format($precio, 0, '.', ',') ?> <small>venta</small></div>
                <?php if(!empty($alquiler)): ?>
                    <div style="font-size:12px;color:var(--text-2);">S/ <?= number_format($alquiler, 0, '.', ',') ?>/mes</div>
                <?php endif; ?>
            </div>
            <div class="prop-acts">
                <a href="<?= BASE ?>/modules/propiedades/view.php?id=<?= getId($prop) ?>" class="btn btn-outline btn-sm">👁️ Ver</a>
                <?php if(isAdmin()): ?>
                    <a href="<?= BASE ?>/modules/propiedades/form.php?id=<?= getId($prop) ?>" class="btn btn-outline btn-sm">✏️ Editar</a>
                    <a href="<?= BASE ?>/modules/propiedades/delete.php?id=<?= getId($prop) ?>" class="btn btn-danger btn-sm" onclick="return confirm('⚠️ ¿Eliminar esta propiedad?')">🗑️ Eliminar</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>