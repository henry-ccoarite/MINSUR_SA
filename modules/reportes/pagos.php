<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdmin();
$pageTitle = 'Reporte de Pagos';
$pg = 'reportes';
$db = getDB();

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');
$metodo = $_GET['metodo'] ?? '';

$where = "p.fecha_pago BETWEEN '$fecha_inicio' AND '$fecha_fin'";
if($metodo) $where .= " AND p.metodo = '$metodo'";

$pagos = $db->query("
    SELECT 
        p.*,
        c.nombre as cliente,
        p2.codigo as propiedad,
        cu.numero as cuota,
        co.numero as contrato
    FROM pagos p
    JOIN cuotas cu ON p.cuota_id = cu.id
    JOIN financiaciones f ON cu.financiacion_id = f.id
    JOIN contratos co ON f.contrato_id = co.id
    JOIN clientes c ON co.cliente_id = c.id
    JOIN propiedades p2 ON co.propiedad_id = p2.id
    WHERE $where
    ORDER BY p.fecha_pago DESC
");

$totales = $db->query("
    SELECT 
        COUNT(*) as total_pagos,
        IFNULL(SUM(monto),0) as total_monto,
        metodo,
        COUNT(*) as cantidad
    FROM pagos
    WHERE $where
    GROUP BY metodo WITH ROLLUP
");

$resumen = [];
while($t = $totales->fetch_assoc()) {
    if($t['metodo'] === null) {
        $resumen['total_general'] = $t['total_monto'];
        $resumen['total_pagos'] = $t['total_pagos'];
    } else {
        $resumen['por_metodo'][$t['metodo']] = ['monto' => $t['total_monto'], 'cantidad' => $t['cantidad']];
    }
}

require_once __DIR__ . '/../../includes/layout.php';
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/reportes/index.php" style="font-size:13px;">← Reportes</a>
        <h1>💵 Reporte de Pagos</h1>
    </div>
    <div style="display: flex; gap: 8px;">
        <button onclick="window.print()" class="btn btn-outline">🖨️ Imprimir</button>
        <a href="<?= BASE ?>/modules/reportes/export.php?tipo=pagos&inicio=<?= $fecha_inicio ?>&fin=<?= $fecha_fin ?>" class="btn btn-success">📥 Exportar</a>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body">
        <form method="GET" class="fbar">
            <div class="fg"><label>Fecha Inicio</label><input type="date" name="fecha_inicio" class="fc" value="<?= $fecha_inicio ?>"></div>
            <div class="fg"><label>Fecha Fin</label><input type="date" name="fecha_fin" class="fc" value="<?= $fecha_fin ?>"></div>
            <div class="fg">
                <label>Método</label>
                <select name="metodo" class="fc">
                    <option value="">Todos</option>
                    <option value="Efectivo" <?= $metodo=='Efectivo'?'selected':'' ?>>Efectivo</option>
                    <option value="Transferencia" <?= $metodo=='Transferencia'?'selected':'' ?>>Transferencia</option>
                    <option value="Yape" <?= $metodo=='Yape'?'selected':'' ?>>Yape</option>
                    <option value="Plin" <?= $metodo=='Plin'?'selected':'' ?>>Plin</option>
                    <option value="Tarjeta" <?= $metodo=='Tarjeta'?'selected':'' ?>>Tarjeta</option>
                </select>
            </div>
            <div style="display: flex; gap: 6px; align-items: flex-end;">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="<?= BASE ?>/modules/reportes/pagos.php" class="btn btn-outline">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Resumen por método -->
<div class="stats" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
    <?php if(isset($resumen['por_metodo'])): ?>
        <?php foreach($resumen['por_metodo'] as $met => $data): ?>
        <div class="stat">
            <div class="stat-val"><?= $data['cantidad'] ?></div>
            <div class="stat-label"><?= $met ?> - S/ <?= number_format($data['monto'], 0) ?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <div class="stat green">
        <div class="stat-val">S/ <?= number_format($resumen['total_general'] ?? 0, 0) ?></div>
        <div class="stat-label">Total General (<?= $resumen['total_pagos'] ?? 0 ?> pagos)</div>
    </div>
</div>

<!-- Tabla de pagos -->
<div class="card">
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr><th>Fecha</th><th>Cliente</th><th>Contrato</th><th>Propiedad</th><th>Cuota</th><th>Monto</th><th>Método</th><th>N° Operación</th><th>Boleta</th></tr>
            </thead>
            <tbody>
            <?php while($p = $pagos->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
                    <td><strong><?= $p['cliente'] ?></strong></td>
                    <td><?= $p['contrato'] ?></td>
                    <td><?= $p['propiedad'] ?></td>
                    <td>#<?= $p['cuota'] ?></td>
                    <td><strong class="text-green">S/ <?= number_format($p['monto'], 2) ?></strong></td>
                    <td><span class="bdg bdg-blue"><?= $p['metodo'] ?></span></td>
                    <td><?= $p['numero_operacion'] ?? '—' ?></td>
                    <td><a href="<?= BASE ?>/modules/reportes/boleta.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-outline">🧾 Boleta</a></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>