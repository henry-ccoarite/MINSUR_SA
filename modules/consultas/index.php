<?php
// modules/consultas/index.php - Menú de Consultas
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$pageTitle = 'Menú de Consultas';
$pg = 'consultas';
$db = getMongoDB();

// Obtener datos de la consulta
$buscar = trim($_GET['buscar'] ?? '');
$tipo = $_GET['tipo'] ?? 'propiedades';

// Colección según el tipo
$colecciones = [
    'propiedades' => 'propiedades',
    'clientes' => 'clientes',
    'contratos' => 'contratos',
    'reservas' => 'reservas',
    'empleados' => 'empleados',
    'reportes' => 'reportes_guardia'
];

$collection_name = $colecciones[$tipo] ?? 'propiedades';
$collection = $db->selectCollection($collection_name);

// Construir filtro de búsqueda
$filtro = [];
if (!empty($buscar)) {
    // Buscar en varios campos según el tipo
    switch ($tipo) {
        case 'propiedades':
            $filtro['$or'] = [
                ['codigo' => ['$regex' => $buscar, '$options' => 'i']],
                ['direccion' => ['$regex' => $buscar, '$options' => 'i']],
                ['ciudad' => ['$regex' => $buscar, '$options' => 'i']],
                ['tipo' => ['$regex' => $buscar, '$options' => 'i']]
            ];
            break;
        case 'clientes':
            $filtro['$or'] = [
                ['nombre' => ['$regex' => $buscar, '$options' => 'i']],
                ['dni' => ['$regex' => $buscar, '$options' => 'i']],
                ['contacto.telefono' => ['$regex' => $buscar, '$options' => 'i']],
                ['contacto.email' => ['$regex' => $buscar, '$options' => 'i']]
            ];
            break;
        case 'contratos':
            $filtro['$or'] = [
                ['numero' => ['$regex' => $buscar, '$options' => 'i']],
                ['cliente.nombre' => ['$regex' => $buscar, '$options' => 'i']],
                ['propiedad.codigo' => ['$regex' => $buscar, '$options' => 'i']]
            ];
            break;
        default:
            $filtro['$or'] = [
                ['nombre' => ['$regex' => $buscar, '$options' => 'i']]
            ];
    }
}

// Ejecutar consulta
$resultados = $collection->find($filtro, ['sort' => ['_id' => -1]]);

require_once __DIR__ . '/../../includes/layout.php';
?>

<style>
    .consulta-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: var(--shadow);
    }
    .consulta-card:hover {
        box-shadow: var(--shadow-md);
    }
    .consulta-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 16px;
    }
    .consulta-title .badge {
        font-size: 12px;
        font-weight: 500;
        background: var(--primary-l);
        color: var(--primary);
        padding: 2px 10px;
        border-radius: 20px;
        margin-left: 8px;
    }
    .consulta-resultados {
        font-size: 13px;
        color: var(--text-2);
        margin-bottom: 12px;
    }
    .btn-consulta {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        background: var(--bg);
        color: var(--text-2);
        border: 1px solid var(--border);
        cursor: pointer;
        transition: all 0.15s;
        text-decoration: none;
    }
    .btn-consulta:hover {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    .btn-consulta.active {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    .search-box {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    .search-box input {
        flex: 1;
        min-width: 200px;
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 14px;
    }
    .search-box input:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.08);
    }
    .search-box select {
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 14px;
        background: var(--white);
        min-width: 160px;
    }
    .table-consulta {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .table-consulta thead th {
        background: var(--bg);
        text-align: left;
        padding: 10px 12px;
        font-weight: 600;
        color: var(--text-2);
        border-bottom: 2px solid var(--border);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .table-consulta tbody td {
        padding: 10px 12px;
        border-bottom: 1px solid var(--border-light);
    }
    .table-consulta tbody tr:hover {
        background: var(--bg);
    }
    .table-consulta .empty {
        text-align: center;
        padding: 40px;
        color: var(--text-3);
    }
</style>

<div class="ph">
    <div>
        <h1>📊 Menú de Consultas</h1>
        <p>Visualización y búsqueda de datos del sistema</p>
    </div>
</div>

<!-- Menú de opciones -->
<div class="consulta-card">
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
        <a href="?tipo=propiedades" class="btn-consulta <?= $tipo === 'propiedades' ? 'active' : '' ?>">🏠 Propiedades</a>
        <a href="?tipo=clientes" class="btn-consulta <?= $tipo === 'clientes' ? 'active' : '' ?>">👤 Clientes</a>
        <a href="?tipo=contratos" class="btn-consulta <?= $tipo === 'contratos' ? 'active' : '' ?>">📄 Contratos</a>
        <a href="?tipo=reservas" class="btn-consulta <?= $tipo === 'reservas' ? 'active' : '' ?>">📌 Reservas</a>
        <a href="?tipo=empleados" class="btn-consulta <?= $tipo === 'empleados' ? 'active' : '' ?>">👔 Empleados</a>
        <a href="?tipo=reportes" class="btn-consulta <?= $tipo === 'reportes' ? 'active' : '' ?>">⛏️ Reportes</a>
    </div>
</div>

<!-- Buscador -->
<div class="consulta-card">
    <div class="consulta-title">
        🔍 Buscar
        <span class="badge"><?= ucfirst($tipo) ?></span>
    </div>
    <form method="GET" class="search-box">
        <input type="hidden" name="tipo" value="<?= u($tipo) ?>">
        <input type="text" name="buscar" placeholder="Buscar por nombre, código, DNI..." value="<?= u($buscar) ?>">
        <button type="submit" class="btn btn-primary">🔍 Buscar</button>
        <?php if($buscar): ?>
            <a href="?tipo=<?= u($tipo) ?>" class="btn btn-outline">✕ Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<!-- Resultados -->
<div class="consulta-card">
    <div class="consulta-title">
        📋 Resultados
        <span class="badge"><?= iterator_count($resultados) ?> registros</span>
    </div>

    <div class="table-responsive">
        <table class="table-consulta">
            <thead>
                <tr>
                    <?php if($tipo === 'propiedades'): ?>
                        <th>Código</th>
                        <th>Tipo</th>
                        <th>Dirección</th>
                        <th>Ciudad</th>
                        <th>Precio Venta</th>
                        <th>Estado</th>
                    <?php elseif($tipo === 'clientes'): ?>
                        <th>Nombre</th>
                        <th>DNI</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Ingresos</th>
                        <th>Estado</th>
                    <?php elseif($tipo === 'contratos'): ?>
                        <th>N° Contrato</th>
                        <th>Tipo</th>
                        <th>Cliente</th>
                        <th>Propiedad</th>
                        <th>Total</th>
                        <th>Estado</th>
                    <?php elseif($tipo === 'reservas'): ?>
                        <th>Cliente</th>
                        <th>Propiedad</th>
                        <th>Monto</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    <?php elseif($tipo === 'empleados'): ?>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Email</th>
                        <th>Estado</th>
                    <?php elseif($tipo === 'reportes'): ?>
                        <th>Fecha</th>
                        <th>Turno</th>
                        <th>Supervisor</th>
                        <th>Personal</th>
                        <th>Incidencias</th>
                        <th>Estado</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $count = 0;
                foreach($resultados as $row):
                    $count++;
                ?>
                    <tr>
                        <?php if($tipo === 'propiedades'): ?>
                            <td><strong><?= u($row['codigo'] ?? '—') ?></strong></td>
                            <td><?= u($row['tipo'] ?? '—') ?></td>
                            <td><?= u($row['direccion'] ?? '—') ?></td>
                            <td><?= u($row['ciudad'] ?? '—') ?></td>
                            <td>S/ <?= number_format($row['precios']['venta'] ?? 0, 2) ?></td>
                            <td><span class="bdg <?= ($row['estado'] ?? '') === 'Disponible' ? 'bdg-green' : 'bdg-gray' ?>"><?= u($row['estado'] ?? '—') ?></span></td>
                        <?php elseif($tipo === 'clientes'): ?>
                            <td><strong><?= u($row['nombre'] ?? '—') ?></strong></td>
                            <td><?= u($row['dni'] ?? '—') ?></td>
                            <td><?= u($row['contacto']['telefono'] ?? '—') ?></td>
                            <td><?= u($row['contacto']['email'] ?? '—') ?></td>
                            <td><?= !empty($row['financiero']['ingresos']) ? 'S/ '.number_format($row['financiero']['ingresos'], 2) : '—' ?></td>
                            <td><span class="bdg <?= ($row['estado'] ?? 'Activo') === 'Activo' ? 'bdg-green' : 'bdg-gray' ?>"><?= u($row['estado'] ?? 'Activo') ?></span></td>
                        <?php elseif($tipo === 'contratos'): ?>
                            <td><strong><?= u($row['numero'] ?? '—') ?></strong></td>
                            <td><span class="bdg <?= ($row['tipo'] ?? '') === 'Venta' ? 'bdg-blue' : 'bdg-orange' ?>"><?= u($row['tipo'] ?? '—') ?></span></td>
                            <td><?= u($row['cliente']['nombre'] ?? '—') ?></td>
                            <td><?= u($row['propiedad']['codigo'] ?? '—') ?></td>
                            <td>S/ <?= number_format($row['total'] ?? 0, 2) ?></td>
                            <td><span class="bdg <?= ($row['estado'] ?? '') === 'Activo' ? 'bdg-green' : 'bdg-gray' ?>"><?= u($row['estado'] ?? '—') ?></span></td>
                        <?php elseif($tipo === 'reservas'): ?>
                            <td><strong><?= u($row['cliente']['nombre'] ?? '—') ?></strong></td>
                            <td><?= u($row['propiedad']['codigo'] ?? '—') ?></td>
                            <td>S/ <?= number_format($row['monto'] ?? 0, 2) ?></td>
                            <td><?= formatDateOnly($row['fecha'] ?? null) ?></td>
                            <td><span class="bdg <?= ($row['estado'] ?? '') === 'Activa' ? 'bdg-green' : 'bdg-gray' ?>"><?= u($row['estado'] ?? '—') ?></span></td>
                        <?php elseif($tipo === 'empleados'): ?>
                            <td><strong><?= u($row['nombre'] ?? '—') ?></strong></td>
                            <td><code><?= u($row['usuario'] ?? '—') ?></code></td>
                            <td><span class="bdg bdg-blue"><?= u($row['rol'] ?? '—') ?></span></td>
                            <td><?= u($row['contacto']['email'] ?? '—') ?></td>
                            <td><span class="bdg <?= ($row['activo'] ?? true) ? 'bdg-green' : 'bdg-gray' ?>"><?= ($row['activo'] ?? true) ? 'Activo' : 'Inactivo' ?></span></td>
                        <?php elseif($tipo === 'reportes'): ?>
                            <td><?= formatDateOnly($row['fecha'] ?? null) ?></td>
                            <td><?= u($row['turno'] ?? '—') ?></td>
                            <td><?= u($row['supervisor'] ?? '—') ?></td>
                            <td><?= count($row['personal'] ?? []) ?></td>
                            <td><?= count($row['incidencias_generales'] ?? []) ?></td>
                            <td><span class="bdg <?= ($row['estado'] ?? 'Pendiente') === 'Completado' ? 'bdg-green' : 'bdg-yellow' ?>"><?= u($row['estado'] ?? 'Pendiente') ?></span></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if($count === 0): ?>
                    <tr><td colspan="10" class="empty">No se encontraron registros</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_end.php'; ?>