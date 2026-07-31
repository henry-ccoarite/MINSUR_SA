<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();
$pageTitle = 'Clientes'; $pg = 'cli';
$db = getDB();

// POST crear cliente
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['_action']??'')==='crear') {
    $nombre   = trim($_POST['nombre']   ?? '');
    $dni      = trim($_POST['dni']      ?? '');
    $tel      = trim($_POST['telefono'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $dir      = trim($_POST['direccion']?? '');
    $sueldo   = $_POST['sueldo_mensual'] ?: null;
    if ($nombre && $dni) {
        $s = $db->prepare("INSERT INTO clientes(dni,nombre,telefono,email,direccion,ingresos) VALUES(?,?,?,?,?,?)");
        $s->bind_param('sssssd',$dni,$nombre,$tel,$email,$dir,$sueldo);
        $s->execute();
        flash('Cliente registrado correctamente.','success');
    } else {
        flash('Nombre y DNI son obligatorios.','error');
    }
    header('Location: '.BASE.'/modules/clientes/index.php'); exit;
}

$q = trim($_GET['q'] ?? '');
$w = '1=1'; $p=[]; $t='';
if ($q) {
    $like="%$q%";
    $w .= ' AND (nombre LIKE ? OR dni LIKE ? OR telefono LIKE ? OR email LIKE ?)';
    $p=[$like,$like,$like,$like]; $t='ssss';
}
$stmt = $db->prepare("SELECT * FROM clientes WHERE $w ORDER BY nombre ASC");
if ($p) $stmt->bind_param($t,...$p);
$stmt->execute();
$clientes = $stmt->get_result();

require_once __DIR__ . '/../../includes/layout.php';
$flash = getFlash();
?>

<div class="ph">
  <div><h1>Clientes</h1><p>Base de datos de clientes</p></div>
  <button class="btn btn-primary" onclick="openModal('mCli')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Nuevo Cliente
  </button>
</div>

<?php if($flash): ?><div class="alert alert-<?= $flash['type']==='success'?'s':'e' ?>"><?= u($flash['msg']) ?></div><?php endif; ?>

<div class="card">
  <div class="card-hd">
    <form method="GET" style="display:flex;gap:8px;flex:1;max-width:400px;">
      <input type="text" name="q" class="fc" placeholder="Buscar por nombre, DNI, teléfono..." value="<?= u($q) ?>">
      <button type="submit" class="btn btn-outline btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </button>
      <?php if($q): ?><a href="<?= BASE ?>/modules/clientes/index.php" class="btn btn-outline btn-sm">✕</a><?php endif; ?>
    </form>
    <span style="font-size:13px;color:var(--text-2);"><?= $clientes->num_rows ?> clientes</span>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>#</th><th>Nombre</th><th>DNI</th><th>Teléfono</th><th>Email</th><th>Sueldo</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php while($c=$clientes->fetch_assoc()): ?>
        <tr>
          <td style="color:var(--text-3);"><?= $c['id'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:9px;">
              <div style="width:30px;height:30px;border-radius:50%;background:var(--primary);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= strtoupper(substr($c['nombre'],0,1)) ?></div>
              <strong><?= u($c['nombre']) ?></strong>
            </div>
          </td>
          <td><?= u($c['dni']) ?></td>
          <td><?= u($c['telefono']??'—') ?></td>
          <td><?= u($c['email']??'—') ?></td>
          <td><?= ($c['ingresos'] ?? 0) ? 'S/ '.number_format($c['ingresos'],2) : '—' ?></td>
          <td><span class="bdg <?= $c['estado']==='Activo'?'bdg-green':'bdg-gray' ?>"><?= u($c['estado']??'Activo') ?></span></td>
          <td style="display:flex;gap:5px;">
            <a href="<?= BASE ?>/modules/clientes/view.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm btn-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </a>
            <?php if(isAdmin()): ?>
            <a href="<?= BASE ?>/modules/clientes/form.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm btn-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
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
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div class="fg">
            <label>Nombre completo *</label>
            <input type="text" name="nombre" class="fc" required>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="fg">
              <label>DNI *</label>
              <input type="text" name="dni" class="fc" maxlength="15" required>
            </div>
            <div class="fg">
              <label>Teléfono</label>
              <input type="text" name="telefono" class="fc">
            </div>
            <div class="fg">
              <label>Email</label>
              <input type="email" name="email" class="fc">
            </div>
            <div class="fg">
              <label>Sueldo mensual (S/)</label>
              <input type="number" step="0.01" name="sueldo_mensual" class="fc">
            </div>
          </div>
          <div class="fg">
            <label>Dirección</label>
            <input type="text" name="direccion" class="fc">
          </div>
        </div>
      </div>
      <div class="modal-ft">
        <button type="button" class="btn btn-outline" onclick="closeModal('mCli')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>
