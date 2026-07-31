<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdmin();
$pageTitle = 'Boletas de Pago';
$pg = 'reportes';
$db = getDB();

$filtro_cliente = $_GET['cliente'] ?? '';
$filtro_mes = $_GET['mes'] ?? date('Y-m');

$where = "1=1";
if($filtro_cliente) $where .= " AND c.id = " . (int)$filtro_cliente;
if($filtro_mes) $where .= " AND DATE_FORMAT(p.fecha_pago, '%Y-%m') = '$filtro_mes'";

$pagos = $db->query("
    SELECT 
        p.*,
        c.nombre as cliente,
        c.dni,
        p2.codigo as propiedad,
        cu.numero as cuota
    FROM pagos p
    JOIN cuotas cu ON p.cuota_id = cu.id
    JOIN financiaciones f ON cu.financiacion_id = f.id
    JOIN contratos co ON f.contrato_id = co.id
    JOIN clientes c ON co.cliente_id = c.id
    JOIN propiedades p2 ON co.propiedad_id = p2.id
    WHERE $where
    ORDER BY p.fecha_pago DESC
");

$clientes = $db->query("SELECT id, nombre FROM clientes WHERE estado='Activo' ORDER BY nombre");

require_once __DIR__ . '/../../includes/layout.php';
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/reportes/index.php" style="font-size:13px;">← Reportes</a>
        <h1>🧾 Boletas de Pago</h1>
        <p>Selecciona un pago para generar su boleta</p>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body">
        <form method="GET" class="fbar">
            <div class="fg">
                <label>Cliente</label>
                <select name="cliente" class="fc">
                    <option value="">Todos</option>
                    <?php while($c = $clientes->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= $filtro_cliente == $c['id'] ? 'selected' : '' ?>><?= $c['nombre'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="fg">
                <label>Mes</label>
                <input type="month" name="mes" class="fc" value="<?= $filtro_mes ?>">
            </div>
            <div style="display: flex; gap: 6px; align-items: flex-end;">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="<?= BASE ?>/modules/reportes/boleta_grupo.php" class="btn btn-outline">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de pagos para generar boletas -->
<div class="card">
    <div class="card-hd">
        <span class="card-title">Pagos Registrados</span>
        <span style="font-size: 13px;"><?= $pagos->num_rows ?> pagos encontrados</span>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr><th>Fecha</th><th>Cliente</th><th>Propiedad</th><th>Cuota</th><th>Monto</th><th>Método</th><th>Boleta</th></tr>
            </thead>
            <tbody>
            <?php while($p = $pagos->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
                    <td><strong><?= $p['cliente'] ?></strong><br><small>DNI: <?= $p['dni'] ?></small></td>
                    <td><?= $p['propiedad'] ?></td>
                    <td>#<?= $p['cuota'] ?></td>
                    <td><strong class="text-green">S/ <?= number_format($p['monto'], 2) ?></strong></td>
                    <td><span class="bdg bdg-blue"><?= $p['metodo'] ?></span></td>
                    <td>
                        <a href="<?= BASE ?>/modules/reportes/boleta.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-primary btn-sm">
                            🧾 Ver Boleta
                        </a>
                        <a href="<?= BASE ?>/modules/reportes/boleta.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-outline btn-sm" onclick="window.print()">
                            🖨️ Imprimir
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.text-green { color: #16a34a; }
</style>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>