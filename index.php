<?php
// index.php - Dashboard con MongoDB
session_start();
require_once __DIR__ . '/config/db_mongo.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

$pageTitle = 'Dashboard';
$pg = 'dash';
$db = getMongoDB();

$propiedades = $db->selectCollection('propiedades');
$clientes = $db->selectCollection('clientes');
$contratos = $db->selectCollection('contratos');

$s_disp = $propiedades->countDocuments(['estado' => 'Disponible']);
$s_vend = $propiedades->countDocuments(['estado' => 'Vendido']);
$s_cli  = $clientes->countDocuments();
$s_cont = $contratos->countDocuments(['estado' => 'Activo']);

$ingresos = $contratos->aggregate([
    ['$match' => ['estado' => 'Activo']],
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$total']]]
])->toArray();
$s_ingr = isset($ingresos[0]) ? $ingresos[0]['total'] : 0;

$meses_lbl = [];
$meses_val = [];
for ($i = 5; $i >= 0; $i--) {
    $fecha = date('Y-m-01', strtotime("-$i months"));
    $fecha_fin = date('Y-m-t', strtotime("-$i months"));
    $meses_lbl[] = date('M Y', strtotime("-$i months"));
    
    $count = $contratos->countDocuments([
        'fecha' => [
            '$gte' => new MongoDB\BSON\UTCDateTime(strtotime($fecha) * 1000),
            '$lte' => new MongoDB\BSON\UTCDateTime(strtotime($fecha_fin . ' 23:59:59') * 1000)
        ]
    ]);
    $meses_val[] = $count;
}

$tipos = $propiedades->aggregate([
    ['$group' => ['_id' => '$tipo', 'count' => ['$sum' => 1]]]
])->toArray();
$tipos_lbl = array_column($tipos, '_id');
$tipos_val = array_column($tipos, 'count');

$propDisp = $propiedades->find(
    ['estado' => 'Disponible'],
    ['sort' => ['_id' => -1], 'limit' => 4]
)->toArray();

require_once __DIR__ . '/includes/layout.php';
$flash = getFlash();
?>

<?php if($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'s':'e' ?>">
  <?= $flash['type']==='success'?'✓':'✕' ?> <?= u($flash['msg']) ?>
</div>
<?php endif; ?>

<div class="stats">
  <div class="stat blue">
    <div class="stat-top">
      <div class="stat-icon">🏠</div>
      <span class="stat-trend up">+<?= $s_disp ?></span>
    </div>
    <div class="stat-val"><?= $s_disp ?></div>
    <div class="stat-label">Propiedades Disponibles</div>
  </div>

  <div class="stat green">
    <div class="stat-top">
      <div class="stat-icon">👤</div>
    </div>
    <div class="stat-val"><?= $s_cli ?></div>
    <div class="stat-label">Clientes Registrados</div>
  </div>

  <div class="stat yellow">
    <div class="stat-top">
      <div class="stat-icon">📄</div>
    </div>
    <div class="stat-val"><?= $s_cont ?></div>
    <div class="stat-label">Contratos Activos</div>
  </div>

  <div class="stat orange">
    <div class="stat-top">
      <div class="stat-icon">💰</div>
    </div>
    <div class="stat-val">S/ <?= number_format($s_ingr/1000,0) ?>K</div>
    <div class="stat-label">Ingresos en Contratos</div>
  </div>

  <div class="stat blue">
    <div class="stat-top">
      <div class="stat-icon">📊</div>
    </div>
    <div class="stat-val"><?= $s_vend ?></div>
    <div class="stat-label">Propiedades Vendidas</div>
  </div>
</div>

<div class="chart-grid" style="margin-bottom:24px;">
  <div class="card">
    <div class="card-hd">
      <span class="card-title">Contratos por Mes</span>
    </div>
    <div class="card-body">
      <div class="chart-wrap"><canvas id="chartContratos"></canvas></div>
    </div>
  </div>

  <div class="card">
    <div class="card-hd">
      <span class="card-title">Propiedades por Tipo</span>
    </div>
    <div class="card-body">
      <div class="chart-wrap"><canvas id="chartTipos"></canvas></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-hd">
    <span class="card-title">Propiedades Disponibles</span>
    <a href="<?= BASE ?>/modules/propiedades/index.php" class="btn btn-outline btn-sm">Ver catálogo</a>
  </div>
  <div style="padding:16px;">
    <div class="prop-grid">
    <?php
    $tipoIcons = ['Casa'=>'🏠','Departamento'=>'🏢','Terreno'=>'🗺️','Local'=>'🏪'];
    foreach($propDisp as $p):
      $carac = $p['caracteristicas'] ?? [];
      $precios = $p['precios'] ?? [];
    ?>
      <div class="prop-card">
        <div class="prop-thumb-ph"><?= $tipoIcons[$p['tipo']]??'🏗️' ?></div>
        <div class="prop-body">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div class="prop-tipo"><?= u($p['tipo']) ?></div>
            <span class="bdg bdg-green">Disponible</span>
          </div>
          <div class="prop-cod"><?= u($p['codigo']) ?></div>
          <div class="prop-dir"><?= u($p['direccion']) ?>, <?= u($p['ciudad']) ?></div>
          <div class="prop-meta">
            <?php if(!empty($carac['area'])): ?><div class="prop-mi">📐 <?= $carac['area'] ?> m²</div><?php endif; ?>
            <?php if(!empty($carac['habitaciones'])): ?><div class="prop-mi">🛏️ <?= $carac['habitaciones'] ?></div><?php endif; ?>
          </div>
          <div class="prop-price">S/ <?= number_format($precios['venta'] ?? 0,0,'.',',') ?><small> venta</small></div>
          <?php if(!empty($precios['alquiler'])): ?>
          <div style="font-size:12px;color:var(--text-2);margin-top:2px;">S/ <?= number_format($precios['alquiler'],0,'.',',') ?>/mes alquiler</div>
          <?php endif; ?>
        </div>
        <div class="prop-acts">
          <a href="<?= BASE ?>/modules/propiedades/view.php?id=<?= getId($p) ?>" class="btn btn-outline btn-sm" style="flex:1;justify-content:center;">Ver detalle</a>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>

<?php
$extraJs = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "Inter, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = "#64748b";

const gridColor = "rgba(0,0,0,0.04)";

new Chart(document.getElementById("chartContratos"), {
  type:"bar",
  data:{
    labels:' . json_encode($meses_lbl) . ',
    datasets:[{
      label:"Contratos",
      data:' . json_encode($meses_val) . ',
      backgroundColor:"rgba(30,64,175,0.08)",
      borderColor:"#1e40af",
      borderWidth:2,
      borderRadius:6,
      borderSkipped:false
    }]
  },
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false}},
    scales:{y:{grid:{color:gridColor},ticks:{stepSize:1}},x:{grid:{display:false}}}
  }
});

new Chart(document.getElementById("chartTipos"), {
  type:"doughnut",
  data:{
    labels:' . json_encode($tipos_lbl) . ',
    datasets:[{
      data:' . json_encode($tipos_val) . ',
      backgroundColor:["#1e40af","#16a34a","#ca8a04","#dc2626"],
      borderWidth:2, borderColor:"#fff",
      hoverOffset:6
    }]
  },
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{position:"bottom",labels:{padding:16,usePointStyle:true}}}
  }
});
</script>';

require_once __DIR__ . '/includes/layout_end.php';
?>