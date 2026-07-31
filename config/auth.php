<?php
// config/auth.php - Autenticación y jerarquía de roles
if (session_status() === PHP_SESSION_NONE) session_start();

define('BASE', '/grupo01');

// ============================================================
// FUNCIONES DE AUTENTICACIÓN
// ============================================================

function isLogged() { 
    return isset($_SESSION['emp_id']); 
}

function isAdmin() { 
    return in_array($_SESSION['rol'] ?? '', ['Admin', 'Gerente', 'SubGerente']); 
}

function isAgente() { 
    return ($_SESSION['rol'] ?? '') === 'Agente'; 
}

function requireAdmin() {
    if (!isLogged() || !isAdmin()) {
        header('Location: ' . BASE . '/index.php');
        exit;
    }
}

function requireLogin() {
    if (!isLogged()) {
        header('Location: ' . BASE . '/login.php');
        exit;
    }
}

// ============================================================
// FUNCIONES PARA MÓDULO MINSUR
// ============================================================

function canAccessMinsur() {
    return isLogged() && in_array($_SESSION['rol'] ?? '', ['Admin', 'Gerente', 'SubGerente', 'Supervisor', 'Jefe Guardia', 'Agente', 'Tecnico', 'Operador']);
}

function requireMinsurAccess() {
    if (!canAccessMinsur()) {
        header('Location: ' . BASE . '/index.php');
        exit;
    }
}

// ============================================================
// JERARQUÍA DE ROLES Y PERMISOS
// ============================================================

// Niveles de jerarquía (número mayor = más permisos)
$roles_niveles = [
    'Admin' => 7,
    'Gerente' => 6,
    'Jefe Guardia' => 5,
    'Supervisor' => 4,
    'Agente' => 3,
    'Tecnico' => 2,
    'Operador' => 1
];

// Obtener el nivel del usuario actual
function getRolNivel() {
    global $roles_niveles;
    $rol = $_SESSION['rol'] ?? 'Operador';
    return $roles_niveles[$rol] ?? 1;
}

// Verificar si tiene acceso a un módulo (por nivel mínimo)
function tieneAcceso($nivel_requerido) {
    return getRolNivel() >= $nivel_requerido;
}

// ============================================================
// PERMISOS ESPECÍFICOS
// ============================================================

function puedeVerTodosLosReportes() {
    return in_array($_SESSION['rol'] ?? '', ['Admin', 'Gerente', 'Jefe Guardia', 'Supervisor']);
}

function puedeEditarTodosLosReportes() {
    return in_array($_SESSION['rol'] ?? '', ['Admin', 'Jefe Guardia']);
}

function puedeEliminarReportes() {
    return in_array($_SESSION['rol'] ?? '', ['Admin']);
}

function puedeVerPersonal() {
    return in_array($_SESSION['rol'] ?? '', ['Admin', 'Gerente', 'Jefe Guardia', 'Supervisor']);
}

function puedeGestionarPersonal() {
    return in_array($_SESSION['rol'] ?? '', ['Admin']);
}

function puedeVerTodasIncidencias() {
    return in_array($_SESSION['rol'] ?? '', ['Admin', 'Gerente', 'Jefe Guardia', 'Supervisor']);
}

function puedeVerConsultasAvanzadas() {
    return in_array($_SESSION['rol'] ?? '', ['Admin', 'Gerente']);
}

function puedeGestionarEmpleados() {
    return in_array($_SESSION['rol'] ?? '', ['Admin']);
}

function puedeVerEstadisticas() {
    return in_array($_SESSION['rol'] ?? '', ['Admin', 'Gerente', 'Jefe Guardia']);
}

function puedeVerDashboardCompleto() {
    return in_array($_SESSION['rol'] ?? '', ['Admin', 'Gerente']);
}

function puedeVerQR() {
    return in_array($_SESSION['rol'] ?? '', ['Admin', 'Gerente', 'Jefe Guardia']);
}

// ============================================================
// FUNCIONES DE UTILIDAD
// ============================================================

function u($s) { 
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); 
}

function flash($msg, $type = 'success') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
?>