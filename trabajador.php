<?php
// trabajador.php - Página pública con foto
require_once __DIR__ . '/config/db_mongo.php';

function u($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

session_start();

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('<h2>❌ Acceso denegado</h2><p>No se proporcionó un token válido.</p>');
}

$db = getMongoDB();
$personal = $db->selectCollection('personal_mina');

$trabajador = $personal->findOne(['token_qr' => $token]);

if (!$trabajador) {
    die('<h2>❌ Acceso denegado</h2><p>Token no válido.</p>');
}

// Obtener reportes
$reportes = $db->selectCollection('reportes_guardia');
$reportes_trabajador = $reportes->find(
    ['personal.nombre' => $trabajador['nombre']],
    ['sort' => ['fecha' => -1]]
)->toArray();

$total_reportes = count($reportes_trabajador);

$incidencias = 0;
foreach ($reportes_trabajador as $r) {
    foreach ($r['personal'] ?? [] as $p) {
        if ($p['nombre'] === $trabajador['nombre'] && isset($p['incidencias']) && $p['incidencias'] !== 'Ninguna') {
            $incidencias++;
        }
    }
}

$ultimo_reporte = !empty($reportes_trabajador) ? $reportes_trabajador[0] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Reportes - <?= u($trabajador['nombre']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .header {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            color: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .header .foto {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.3);
            flex-shrink: 0;
        }
        .header .avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #e94560;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .header .info h1 { font-size: 22px; font-weight: 700; }
        .header .info p { opacity: 0.7; font-size: 14px; }
        .header .info .badge {
            display: inline-block;
            background: rgba(255,255,255,0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 4px;
        }
        .btn-volver {
            display: inline-block;
            padding: 8px 16px;
            background: rgba(255,255,255,0.1);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: background 0.3s;
        }
        .btn-volver:hover { background: rgba(255,255,255,0.2); }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-card .number { font-size: 28px; font-weight: 700; color: #1e40af; }
        .stat-card .label { font-size: 13px; color: #666; margin-top: 4px; }
        .stat-card .icon { font-size: 24px; margin-bottom: 6px; color: #999; }
        .stat-card.red .number { color: #dc2626; }
        .stat-card.orange .number { color: #ea580c; }
        .stat-card.green .number { color: #16a34a; }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 16px;
        }
        .card-header {
            padding: 14px 20px;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-header i { color: #999; }
        .card-body { padding: 16px 20px; }

        .report-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
            gap: 12px;
        }
        .report-item:last-child { border-bottom: none; }
        .report-item .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .report-item .dot.verde { background: #16a34a; }
        .report-item .dot.amarillo { background: #ca8a04; }
        .report-item .dot.naranja { background: #ea580c; }
        .report-item .dot.rojo { background: #dc2626; }
        .report-item .content { flex: 1; }
        .report-item .content .fecha { font-weight: 600; font-size: 14px; }
        .report-item .content .meta { font-size: 13px; color: #666; }
        .report-item .content .meta i { margin-right: 4px; }
        .report-item .badge-estado {
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .badge-estado.completado { background: #dcfce7; color: #16a34a; }
        .badge-estado.pendiente { background: #fef3c7; color: #ca8a04; }

        .empty-state { text-align: center; padding: 30px; color: #999; }
        .empty-state i { font-size: 40px; display: block; margin-bottom: 12px; opacity: 0.5; }

        .footer {
            text-align: center;
            padding: 20px;
            color: #999;
            font-size: 12px;
        }

        @media (max-width: 600px) {
            .header { flex-direction: column; text-align: center; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <?php if(!empty($trabajador['foto'])): ?>
            <img src="<?= BASE ?>/uploads/personal/<?= u($trabajador['foto']) ?>" class="foto">
        <?php else: ?>
            <div class="avatar"><?= strtoupper(substr($trabajador['nombre'], 0, 1)) ?></div>
        <?php endif; ?>
        <div class="info">
            <h1><?= u($trabajador['nombre']) ?></h1>
            <p><i class="fas fa-briefcase"></i> <?= u($trabajador['cargo'] ?? '') ?> · <i class="fas fa-building"></i> <?= u($trabajador['area'] ?? '') ?></p>
            <span class="badge"><i class="fas fa-qrcode"></i> Acceso por QR</span>
        </div>
        <div style="margin-left:auto;">
            <a href="<?= BASE ?>/login.php" class="btn-volver">
                <i class="fas fa-sign-in-alt"></i> Iniciar sesión
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="number"><?= $total_reportes ?></div>
            <div class="label">Total Reportes</div>
        </div>
        <div class="stat-card orange">
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="number"><?= $incidencias ?></div>
            <div class="label">Incidencias Registradas</div>
        </div>
        <div class="stat-card green">
            <div class="icon"><i class="fas fa-clock"></i></div>
            <div class="number">
                <?php if($ultimo_reporte): ?>
                    <?= formatDateOnly($ultimo_reporte['fecha']) ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </div>
            <div class="label">Último Reporte</div>
        </div>
    </div>

    <!-- Último Reporte -->
    <?php if($ultimo_reporte): ?>
        <div class="card" style="border-left:4px solid #1e40af;">
            <div class="card-header">
                <i class="fas fa-star" style="color:#f5a623;"></i> Último Reporte
                <span style="margin-left:auto;font-size:12px;color:#999;">
                    <?= formatDateOnly($ultimo_reporte['fecha']) ?> · <?= u($ultimo_reporte['turno']) ?>
                </span>
            </div>
            <div class="card-body">
                <p style="font-size:14px;line-height:1.6;"><?= nl2br(u(substr($ultimo_reporte['descripcion'] ?? '', 0, 200))) ?>...</p>
                <div style="margin-top:10px;display:flex;gap:12px;flex-wrap:wrap;">
                    <span class="badge-estado <?= ($ultimo_reporte['estado'] ?? 'Pendiente') === 'Completado' ? 'completado' : 'pendiente' ?>">
                        <i class="fas fa-circle"></i> <?= u($ultimo_reporte['estado'] ?? 'Pendiente') ?>
                    </span>
                    <span style="font-size:12px;color:#999;">
                        <i class="fas fa-users"></i> <?= count($ultimo_reporte['personal'] ?? []) ?> personas
                    </span>
                    <span style="font-size:12px;color:#999;">
                        <i class="fas fa-exclamation-triangle"></i> <?= count($ultimo_reporte['incidencias_generales'] ?? []) ?> incidencias
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Lista de Reportes -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Todos mis Reportes
            <span style="margin-left:auto;font-size:12px;color:#999;"><?= $total_reportes ?> reportes</span>
        </div>
        <div class="card-body">
            <?php if(!empty($reportes_trabajador)): ?>
                <?php foreach($reportes_trabajador as $r): 
                    $dot_color = ($r['prioridad'] ?? 'Media') === 'Crítica' ? 'rojo' : (($r['prioridad'] ?? 'Media') === 'Alta' ? 'naranja' : (($r['prioridad'] ?? 'Media') === 'Media' ? 'amarillo' : 'verde'));
                    $estado_class = ($r['estado'] ?? 'Pendiente') === 'Completado' ? 'completado' : 'pendiente';
                ?>
                    <div class="report-item">
                        <div class="dot <?= $dot_color ?>"></div>
                        <div class="content">
                            <div class="fecha"><?= formatDateOnly($r['fecha']) ?> · <?= u($r['turno']) ?></div>
                            <div class="meta">
                                <i class="fas fa-user-tie"></i> <?= u($r['supervisor']) ?>
                                <i class="fas fa-users" style="margin-left:10px;"></i> <?= count($r['personal'] ?? []) ?>
                                <i class="fas fa-exclamation-triangle" style="margin-left:10px;"></i> <?= count($r['incidencias_generales'] ?? []) ?>
                            </div>
                        </div>
                        <span class="badge-estado <?= $estado_class ?>">
                            <?= u($r['estado'] ?? 'Pendiente') ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No tienes reportes registrados aún.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        <i class="fas fa-hard-hat"></i> MINSUR S.A. · Sistema de Reportes de Guardia
    </div>
</div>
</body>
</html>