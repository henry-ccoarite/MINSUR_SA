<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdmin(); // ← Solo Admin puede acceder
$pageTitle = 'Reportes';
$pg = 'reportes';
$db = getDB();

// Estadísticas para el dashboard
$stats = [];
$stats['ventas_mes'] = $db->query("SELECT COUNT(*) as total, IFNULL(SUM(total),0) as monto FROM contratos WHERE tipo='Venta' AND MONTH(fecha)=MONTH(CURDATE())")->fetch_assoc();
$stats['alquileres'] = $db->query("SELECT COUNT(*) as total FROM contratos WHERE tipo='Alquiler' AND estado='Activo'")->fetch_assoc()['total'];
$stats['morosos'] = $db->query("SELECT COUNT(*) as total FROM cuotas WHERE estado IN('Pendiente','Vencida') AND fecha_vencimiento < CURDATE()")->fetch_assoc()['total'];
$stats['clientes'] = $db->query("SELECT COUNT(*) as total FROM clientes WHERE estado='Activo'")->fetch_assoc()['total'];
$stats['ingresos_mes'] = $db->query("SELECT IFNULL(SUM(monto),0) as total FROM pagos WHERE MONTH(fecha_pago)=MONTH(CURDATE())")->fetch_assoc()['total'];

require_once __DIR__ . '/../../includes/layout.php';
?>

<style>
    .report-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        padding: 24px;
        transition: all 0.2s;
        cursor: pointer;
        text-align: center;
    }
    .report-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }
    .report-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
    }
    .report-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }
    @media print {
        .sidebar, .topbar, .btn, .ph a, .report-card { display: none !important; }
    }
</style>

<div class="ph">
    <div>
        <h1>📊 Centro de Reportes</h1>
        <p>Análisis, estadísticas y documentos oficiales</p>
    </div>
</div>

<!-- Stats Rápidas -->
<div class="stats-row">
    <div class="stat blue"><div class="stat-val">S/ <?= number_format($stats['ingresos_mes'], 0) ?></div><div class="stat-label">Ingresos del mes</div></div>
    <div class="stat green"><div class="stat-val"><?= $stats['ventas_mes']['total'] ?></div><div class="stat-label">Ventas este mes</div></div>
    <div class="stat yellow"><div class="stat-val">S/ <?= number_format($stats['ventas_mes']['monto'], 0) ?></div><div class="stat-label">Monto vendido</div></div>
    <div class="stat orange"><div class="stat-val"><?= $stats['clientes'] ?></div><div class="stat-label">Clientes activos</div></div>
    <?php if($stats['morosos'] > 0): ?>
    <div class="stat red"><div class="stat-val"><?= $stats['morosos'] ?></div><div class="stat-label">⚠️ Cuotas vencidas</div></div>
    <?php endif; ?>
</div>

<!-- Tarjetas de Reportes -->
<div class="report-grid">
    <div class="report-card" onclick="location.href='<?= BASE ?>/modules/reportes/ventas.php'">
        <div class="report-icon" style="background: #e8f0fe; color: #1e40af;">📈</div>
        <h3>Reporte de Ventas</h3>
        <p style="color: var(--text-2); font-size: 13px;">Por mes, empleado y cliente</p>
    </div>

    <div class="report-card" onclick="location.href='<?= BASE ?>/modules/reportes/cuotas.php'">
        <div class="report-icon" style="background: #fef3c7; color: #d97706;">💰</div>
        <h3>Cuotas y Morosos</h3>
        <p style="color: var(--text-2); font-size: 13px;">Control de pagos y vencimientos</p>
    </div>

    <div class="report-card" onclick="location.href='<?= BASE ?>/modules/reportes/clientes.php'">
        <div class="report-icon" style="background: #dcfce7; color: #16a34a;">👥</div>
        <h3>Reporte de Clientes</h3>
        <p style="color: var(--text-2); font-size: 13px;">Top compradores y análisis</p>
    </div>

    <div class="report-card" onclick="location.href='<?= BASE ?>/modules/reportes/propiedades.php'">
        <div class="report-icon" style="background: #fce7f3; color: #db2777;">🏠</div>
        <h3>Inventario</h3>
        <p style="color: var(--text-2); font-size: 13px;">Propiedades por estado y tipo</p>
    </div>

    <div class="report-card" onclick="location.href='<?= BASE ?>/modules/reportes/pagos.php'">
        <div class="report-icon" style="background: #e0e7ff; color: #4f46e5;">💵</div>
        <h3>Historial de Pagos</h3>
        <p style="color: var(--text-2); font-size: 13px;">Por método y periodo</p>
    </div>

    <div class="report-card" onclick="location.href='<?= BASE ?>/modules/reportes/boleta_grupo.php'">
        <div class="report-icon" style="background: #fed7aa; color: #ea580c;">🧾</div>
        <h3>Boletas de Pago</h3>
        <p style="color: var(--text-2); font-size: 13px;">Imprimir boletas individuales o grupales</p>
    </div>
</div>

<!-- Gráfica rápida -->
<div class="card">
    <div class="card-hd"><span class="card-title">📊 Ventas Últimos 6 Meses</span></div>
    <div class="card-body">
        <div class="chart-wrap" style="height: 280px;">
            <canvas id="ventasChart"></canvas>
        </div>
    </div>
</div>

<?php
// Datos para gráfica
$ventas_data = [];
for($i = 5; $i >= 0; $i--) {
    $res = $db->query("SELECT IFNULL(SUM(total),0) as total FROM contratos WHERE tipo='Venta' AND MONTH(fecha)=MONTH(DATE_SUB(CURDATE(), INTERVAL $i MONTH)) AND YEAR(fecha)=YEAR(DATE_SUB(CURDATE(), INTERVAL $i MONTH))")->fetch_assoc();
    $ventas_data[] = ['mes' => date('M', strtotime("-$i months")), 'total' => (float)$res['total']];
}

$extraJs = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById("ventasChart"), {
    type: "bar",
    data: {
        labels: ' . json_encode(array_column($ventas_data, 'mes')) . ',
        datasets: [{
            label: "S/ Ventas",
            data: ' . json_encode(array_column($ventas_data, 'total')) . ',
            backgroundColor: "rgba(30,64,175,0.7)",
            borderRadius: 8
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { callback: v => "S/ " + (v/1000).toFixed(0) + "K" } } } }
});
</script>';

require_once __DIR__ . '/../../includes/layout_end.php';
?>