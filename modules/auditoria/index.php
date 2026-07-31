<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin(); if(!isAdmin()) { header('Location: '.BASE.'/index.php'); exit; }
$pageTitle = 'Auditoría'; $pg = 'aud';
$db = getDB();

$accion = $_GET['accion'] ?? '';
$tabla  = $_GET['tabla']  ?? '';
$w='1=1'; $p=[]; $t='';
if ($accion) { $w.=' AND accion=?'; $p[]=$accion; $t.='s'; }
if ($tabla)  { $w.=' AND tabla_afectada=?'; $p[]=$tabla; $t.='s'; }

$stmt=$db->prepare("SELECT * FROM auditoria WHERE $w ORDER BY fecha DESC LIMIT 200");
if($p) $stmt->bind_param($t,...$p); $stmt->execute();
$logs=$stmt->get_result();

$tablas=$db->query("SELECT DISTINCT tabla_afectada FROM auditoria ORDER BY tabla_afectada");

require_once __DIR__ . '/../../includes/layout.php';
?>

<div class="ph">
  <div><h1>Auditoría</h1><p>Registro de operaciones del sistema</p></div>
</div>

<div class="card" style="margin-bottom:16px;">
  <div class="card-body" style="padding:12px 18px;">
    <form method="GET" class="fbar">
      <div class="fg">
        <label>Acción</label>
        <select name="accion" class="fc">
          <option value="">Todas</option>
          <?php foreach(['INSERT','UPDATE','DELETE','SELECT'] as $a2): ?>
            <option value="<?= $a2 ?>" <?= $accion===$a2?'selected':'' ?>><?= $a2 ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg">
        <label>Tabla</label>
        <select name="tabla" class="fc">
          <option value="">Todas</option>
          <?php while($tb=$tablas->fetch_assoc()): ?>
            <option value="<?= u($tb['tabla_afectada']) ?>" <?= $tabla===$tb['tabla_afectada']?'selected':'' ?>><?= u($tb['tabla_afectada']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div style="display:flex;gap:6px;align-items:flex-end;">
        <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
        <a href="<?= BASE ?>/modules/auditoria/index.php" class="btn btn-outline btn-sm">Limpiar</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>#</th><th>Tabla</th><th>Acción</th><th>Descripción</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php while($a=$logs->fetch_assoc()):
        $bc=['INSERT'=>'bdg-green','UPDATE'=>'bdg-yellow','DELETE'=>'bdg-red','SELECT'=>'bdg-blue'][$a['accion']]??'bdg-gray';
      ?>
        <tr>
          <td style="color:var(--text-3);"><?= $a['id'] ?></td>
          <td><code style="font-size:12px;background:var(--bg);padding:2px 6px;border-radius:4px;"><?= u($a['tabla_afectada']) ?></code></td>
          <td><span class="bdg <?= $bc ?>"><?= u($a['accion']) ?></span></td>
          <td style="max-width:380px;font-size:12px;color:var(--text-2);"><?= u($a['descripcion']??'') ?></td>
          <td style="font-size:12px;color:var(--text-3);white-space:nowrap;"><?= date('d/m/Y H:i',strtotime($a['fecha'])) ?></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>
