<?php
// ============================================
// INCLUIR AUTENTICACIÓN
// ============================================
require_once __DIR__ . '/../config/auth.php';

// ============================================
// FUNCIÓN PARA ÍCONOS SVG
// ============================================
function icon($name) {
    $icons = [
        'home' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'menu' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
    ];
    return $icons[$name] ?? '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>';
}

// ============================================
// VERIFICAR SESIÓN
// ============================================
if (!isset($_SESSION)) session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title><?= u($pageTitle ?? 'MINSUR S.A. - Reportes de Guardia') ?></title>
    
    <!-- ============================================ -->
    <!-- FUENTES E ÍCONOS                            -->
    <!-- ============================================ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/minsur.css">
</head>
<body>
<?php if(isLogged()):
    $db2 = getMongoDB();
?>
<div class="layout">
<aside class="sidebar" id="sidebar">
    <div class="sb-brand">
        <div class="sb-logo" style="padding:0;overflow:hidden;border-radius:60px;display:flex;align-items:center;justify-content:center;width:120px;height:120px;">
    <img src="<?= BASE ?>/assets/img/logo3.png" style="width:120+0px;height:120px;object-fit:cover;border-radius:12px;">
</div>
        <div class="sb-brand-text">
            <div class="sb-name" style="font-size:16px;">MINSUR S.A.</div>
            <div class="sb-sub" style="font-size:11px;">Reportes de Guardia</div>
        </div>
    </div>

    <nav class="sb-nav">
        <!-- ============================================ -->
        <!-- PRINCIPAL                                    -->
        <!-- ============================================ -->
        <span class="sb-sect"><i class="fas fa-home"></i> Principal</span>
        <a href="<?= BASE ?>/modules/minsur/index.php" class="sb-link <?= ($pg??'')==='minsur'?'active':'' ?>">
            <i class="fas fa-chart-pie"></i> <span>Dashboard</span>
        </a>

        <!-- ============================================ -->
        <!-- MINSUR S.A. - MENÚ DINÁMICO POR ROL           -->
        <!-- ============================================ -->
        <?php if(canAccessMinsur()): ?>
        <span class="sb-sect"><i class="fas fa-hard-hat"></i> MINSUR S.A.</span>

        <!-- Reportes (visible para todos) -->
        <a href="<?= BASE ?>/modules/minsur/reportes/" class="sb-link <?= ($pg??'')==='minsur_reportes'?'active':'' ?>">
            <i class="fas fa-clipboard-list"></i> <span>Reportes</span>
        </a>
        <a href="<?= BASE ?>/modules/minsur/seguridad/" class="sb-link <?= ($pg??'')==='minsur_seguridad'?'active':'' ?>">
    <i class="fas fa-shield-alt"></i> <span>Seguridad</span>
</a>

        <!-- Personal (solo Admin, Gerente, Jefe Guardia, Supervisor) -->
        <?php if(puedeVerPersonal()): ?>
        <a href="<?= BASE ?>/modules/minsur/personal/" class="sb-link <?= ($pg??'')==='minsur_personal'?'active':'' ?>">
            <i class="fas fa-users"></i> <span>Personal</span>
        </a>
        <?php endif; ?>

        <!-- Incidencias (solo Admin, Gerente, Jefe Guardia, Supervisor, Agente) -->
        <?php if(puedeVerTodasIncidencias() || $_SESSION['rol'] === 'Agente'): ?>
        <a href="<?= BASE ?>/modules/minsur/incidencias/" class="sb-link <?= ($pg??'')==='minsur_incidencias'?'active':'' ?>">
            <i class="fas fa-exclamation-triangle"></i> <span>Incidencias</span>
        </a>
        <?php endif; ?>

        <!-- Consultas (solo Admin, Gerente, Jefe Guardia, Supervisor) -->
        <?php if(puedeVerConsultasAvanzadas() || $_SESSION['rol'] === 'Jefe Guardia' || $_SESSION['rol'] === 'Supervisor'): ?>
        <a href="<?= BASE ?>/modules/minsur/consultas/" class="sb-link <?= ($pg??'')==='minsur_consultas'?'active':'' ?>">
            <i class="fas fa-search"></i> <span>Consultas</span>
        </a>
        <?php endif; ?>

        <!-- Estadísticas (solo Admin, Gerente, Jefe Guardia) -->
        <?php if(puedeVerEstadisticas()): ?>
        <a href="<?= BASE ?>/modules/minsur/estadisticas/" class="sb-link <?= ($pg??'')==='minsur_estadisticas'?'active':'' ?>">
            <i class="fas fa-chart-bar"></i> <span>Estadísticas</span>
        </a>
        <?php endif; ?>

        <!-- Empleados (solo Admin) -->
        <?php if(puedeGestionarEmpleados()): ?>
        <a href="<?= BASE ?>/modules/empleados/index.php" class="sb-link <?= ($pg??'')==='emp'?'active':'' ?>">
            <i class="fas fa-user-cog"></i> <span>Empleados</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </nav>

    <div class="sb-footer">
        <div class="sb-avatar"><?= strtoupper(substr($_SESSION['nombre']??'U',0,1)) ?></div>
        <div class="sb-meta">
            <span class="sb-uname"><?= u($_SESSION['nombre']??'') ?></span>
            <span class="sb-urole"><?= u($_SESSION['rol']??'') ?></span>
        </div>
        <a href="<?= BASE ?>/logout.php" class="sb-logout" title="Salir">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</aside>

<div class="main-w">
    <header class="topbar">
        <button class="topbar-menu-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title"><?= u($pageTitle ?? 'MINSUR S.A.') ?></div>
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="topbar-chip" style="background:#e94560;color:white;">
                <i class="fas fa-hard-hat"></i> MINSUR
            </span>
            <span class="topbar-chip">
                <i class="fas fa-user"></i> <?= u($_SESSION['rol']??'') ?>
            </span>
        </div>
    </header>
    <main class="content">
<?php endif; ?>