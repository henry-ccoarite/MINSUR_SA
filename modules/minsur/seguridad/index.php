<?php
// modules/minsur/seguridad/index.php - Seguridad con gráfico de abanico
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$pageTitle = 'Seguridad - MINSUR';
$pg = 'minsur_seguridad';
$db = getMongoDB();

// ============================================================
// OBTENER DATOS DE MONGODB PARA EL ABANICO
// ============================================================
$reportes = $db->selectCollection('reportes_guardia');

// --- 1. Contar incidentes por año (total) ---
$incidentes_por_anio = $reportes->aggregate([
    ['$unwind' => '$incidencias_generales'],
    ['$group' => [
        '_id' => ['$year' => '$fecha'],
        'count' => ['$sum' => 1]
    ]],
    ['$sort' => ['_id' => 1]]
])->toArray();

// --- 2. Desglose por GRAVEDAD, año por año ---
$gravedad_raw = $reportes->aggregate([
    ['$unwind' => '$incidencias_generales'],
    ['$match' => ['incidencias_generales.gravedad' => ['$ne' => null]]],
    ['$group' => [
        '_id' => [
            'anio' => ['$year' => '$fecha'],
            'gravedad' => '$incidencias_generales.gravedad'
        ],
        'count' => ['$sum' => 1]
    ]]
])->toArray();

// --- 3. Desglose por TIPO, año por año ---
$tipo_raw = $reportes->aggregate([
    ['$unwind' => '$incidencias_generales'],
    ['$match' => ['incidencias_generales.tipo' => ['$ne' => null]]],
    ['$group' => [
        '_id' => [
            'anio' => ['$year' => '$fecha'],
            'tipo' => '$incidencias_generales.tipo'
        ],
        'count' => ['$sum' => 1]
    ]]
])->toArray();

// --- 4. Contar incidentes por tipo (para tarjetas) ---
$incidentes_tipo = $reportes->aggregate([
    ['$unwind' => '$incidencias_generales'],
    ['$group' => [
        '_id' => '$incidencias_generales.tipo',
        'count' => ['$sum' => 1]
    ]],
    ['$sort' => ['count' => -1]]
])->toArray();

// --- 5. Obtener años disponibles ---
$anios_disponibles = [];
foreach ($incidentes_por_anio as $item) {
    $anios_disponibles[] = $item['_id'];
}
sort($anios_disponibles);

if (empty($anios_disponibles)) {
    $anios_disponibles = [2023, 2024, 2025, 2026];
}

$years = $anios_disponibles;

// --- 6. Helper ---
function agruparPorCategoriaAnio(array $raw, string $campo): array {
    $out = [];
    foreach ($raw as $item) {
        $cat = $item['_id'][$campo] ?? 'Sin clasificar';
        $anio = $item['_id']['anio'];
        $out[$cat][$anio] = $item['count'];
    }
    return $out;
}

$gravedad_map = agruparPorCategoriaAnio($gravedad_raw, 'gravedad');
$tipo_map = agruparPorCategoriaAnio($tipo_raw, 'tipo');

$orden_gravedad = ['Crítica', 'Alta', 'Media', 'Baja'];
$gravedad_categorias = array_values(array_intersect($orden_gravedad, array_keys($gravedad_map)));
$tipo_categorias = array_keys($tipo_map);

function alinearConAnios(array $map, array $years): array {
    $out = [];
    foreach ($map as $cat => $porAnio) {
        $out[$cat] = [];
        foreach ($years as $y) {
            $out[$cat][] = $porAnio[$y] ?? 0;
        }
    }
    return $out;
}

$gravedad_data = alinearConAnios($gravedad_map, $years);
$tipo_data = alinearConAnios($tipo_map, $years);

$total_incidentes = array_sum(array_column($incidentes_por_anio, 'count'));
$total_reportes = $reportes->countDocuments();

require_once __DIR__ . '/../../../includes/layout.php';
$isMinsur = true;
?>

<style>
    .content, .main-w, .grafico-abanico, .grafico-abanico .chart-card {
        min-width: 0 !important;
        max-width: 100% !important;
    }

    .grafico-abanico {
        background: var(--white);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        padding: 20px;
        box-shadow: var(--shadow);
        margin-bottom: 20px;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
    }
    .grafico-abanico .banner {
        display: inline-block;
        background: #BBDDF2;
        color: #0F2A4A;
        font-weight: 800;
        font-size: 22px;
        letter-spacing: 0.5px;
        padding: 12px 40px 12px 20px;
        clip-path: polygon(0 0, 92% 0, 100% 50%, 92% 100%, 0 100%);
        margin-bottom: 8px;
    }
    
    .grafico-abanico .chart-card {
        width: 100%;
        display: block;
        max-width: 1100px;
        margin: 0 auto;
        min-height: 750px;
    }

    #chartFrame {
        width: 100%;
        max-width: 1100px;
        height: 700px;
        border: 0;
        display: block;
        margin: 0 auto;
    }

    .meta-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        padding: 20px 24px;
        text-align: center;
        box-shadow: var(--shadow);
    }
    .meta-card .number {
        font-size: 32px;
        font-weight: 700;
        color: var(--primary);
    }
    .meta-card .label {
        font-size: 13px;
        color: var(--text-2);
    }
    .meta-card .label i {
        margin-right: 6px;
    }

    .meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .data-panel{
        margin-top:20px;
        border-top:2px solid #eee;
        padding-top:14px;
    }
    .data-panel h3{margin:0 0 4px 0;font-size:15px;color:var(--navy);}
    .data-panel p{margin:0 0 14px 0;font-size:12.5px;color:#666;}
    .data-panel table{border-collapse:collapse;width:100%;font-size:12px;margin-bottom:22px;}
    .data-panel th,.data-panel td{border:1px solid #e2e2e2;padding:3px 4px;text-align:center;}
    .data-panel th{background:var(--navy);color:#fff;font-weight:600;font-size:11px;}
    .data-panel td.rowhead{background:#f2f2f2;font-weight:700;}

    .legend{display:flex;gap:18px;flex-wrap:wrap;font-size:12px;margin:10px 0 18px 0;}
    .legend span{display:inline-flex;align-items:center;gap:6px;}
    .sw{width:12px;height:12px;border-radius:2px;display:inline-block;}

    @media (max-width: 768px) {
        .grafico-abanico .banner {
            font-size: 16px;
            padding: 8px 20px 8px 12px;
        }
        .grafico-abanico .chart-card {
            max-width: 100%;
        }
        #chartFrame {
            max-width: 100%;
            height: 450px;
        }
        .data-panel table {
            font-size: 10px;
        }
        .meta-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 480px) {
        #chartFrame {
            height: 350px;
        }
        .meta-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/minsur/index.php" style="font-size:13px;color:var(--text-2);">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
        <h1 style="margin-top:4px;"><i class="fas fa-shield-alt"></i> Seguridad MINSUR</h1>
        <p><i class="fas fa-hard-hat"></i> Desempeño histórico y políticas de seguridad minera</p>
    </div>
</div>

<!-- ESTADÍSTICAS -->
<div class="meta-grid">
    <div class="meta-card">
        <div class="number"><?= $total_reportes ?></div>
        <div class="label"><i class="fas fa-clipboard-list"></i> Total Reportes</div>
    </div>
    <div class="meta-card">
        <div class="number" style="color:#dc2626;"><?= $total_incidentes ?></div>
        <div class="label"><i class="fas fa-exclamation-triangle"></i> Total Incidentes</div>
    </div>
    <div class="meta-card">
        <div class="number" style="color:#16a34a;">
            <?php
            $criticos = 0;
            foreach ($incidentes_tipo as $tipo) {
                if ($tipo['_id'] === 'Seguridad') {
                    $criticos = $tipo['count'];
                }
            }
            echo $criticos;
            ?>
        </div>
        <div class="label"><i class="fas fa-shield-alt"></i> Incidentes de Seguridad</div>
    </div>
    <div class="meta-card">
        <div class="number" style="color:#7c3aed;">
            <?php
            $operacional = 0;
            foreach ($incidentes_tipo as $tipo) {
                if ($tipo['_id'] === 'Operacional') {
                    $operacional = $tipo['count'];
                }
            }
            echo $operacional;
            ?>
        </div>
        <div class="label"><i class="fas fa-cogs"></i> Incidentes Operacionales</div>
    </div>
</div>

<!-- ============================================================ -->
<!-- GRÁFICO DE ABANICO                                            -->
<!-- ============================================================ -->
<div class="grafico-abanico">
    <div class="banner">DESEMPEÑO HISTÓRICO Y METAS DE SEGURIDAD Y SALUD</div>
    <div class="chart-card">
        <iframe id="chartFrame" style="width:100%;max-width:1100px;height:700px;border:0;display:block;margin:0 auto;"></iframe>
    </div>

    <div class="data-panel">
        <h3>📊 Datos del gráfico (desde MongoDB)</h3>
        <p>Los datos se actualizan automáticamente desde los reportes registrados en el sistema.</p>
        <div class="legend">
            <span><i class="sw" style="background:var(--cyan)"></i>Año</span>
            <span><i class="sw" style="background:var(--navy)"></i>Total</span>
            <span><i class="sw" style="background:var(--magenta)"></i>Categoría principal (izq)</span>
            <span><i class="sw" style="background:var(--green)"></i>Otras categorías (izq)</span>
            <span><i class="sw" style="background:var(--orange)"></i>Categorías (der)</span>
        </div>
        <div id="tables"></div>
    </div>
</div>

<!-- ============================================================ -->
<!-- SCRIPTS DEL GRÁFICO DE ABANICO                                -->
<!-- ============================================================ -->
<script>
/* =======================  DATOS DESDE MONGODB  ======================= */
const years = <?= json_encode($years) ?>;

const gravedadCategorias = <?= json_encode($gravedad_categorias) ?>;
const gravedadData = <?= json_encode($gravedad_data) ?>;

const tipoCategorias = <?= json_encode($tipo_categorias) ?>;
const tipoData = <?= json_encode($tipo_data) ?>;

const lesiones = {
    title: "INCIDENTES POR GRAVEDAD",
    columns: gravedadCategorias,
    data: gravedadData
};

const eventos = {
    title: "INCIDENTES POR TIPO",
    columns: tipoCategorias,
    data: tipoData
};

/* =======================  GEOMETRÍA  ======================= */
const CX = 700, CY = 700;
const R0 = 120;
const RING_H = 60;
const HEADER_H = 70;
const BANNER_H = 65;

function deg2rad(d){ return d*Math.PI/180; }
function pol(r,a){
    const rad = deg2rad(a);
    return { x: CX + r*Math.cos(rad), y: CY - r*Math.sin(rad) };
}
function sectorPath(r0,r1,a0,a1){
    const p1 = pol(r1,a0), p2 = pol(r1,a1), p3 = pol(r0,a1), p4 = pol(r0,a0);
    const large = Math.abs(a0-a1) > 180 ? 1 : 0;
    return `M ${p1.x} ${p1.y} A ${r1} ${r1} 0 ${large} 1 ${p2.x} ${p2.y} L ${p3.x} ${p3.y} A ${r0} ${r0} 0 ${large} 0 ${p4.x} ${p4.y} Z`;
}
function arcPath(r,a0,a1){
    const p1 = pol(r,a0), p2 = pol(r,a1);
    const large = Math.abs(a0-a1) > 180 ? 1 : 0;
    return `M ${p1.x} ${p1.y} A ${r} ${r} 0 ${large} 1 ${p2.x} ${p2.y}`;
}
function svgEl(tag, attrs){
    const el = document.createElementNS("http://www.w3.org/2000/svg", tag);
    for(const k in attrs) el.setAttribute(k, attrs[k]);
    return el;
}

/* =======================  COLORES  ======================= */
function colColor(side, name){
    if(name === "Año") return "var(--cyan)";
    if(name === "Total") return "var(--navy)";
    if(side === "left"){
        return name === lesiones.columns[0] ? "var(--magenta)" : "var(--green)";
    }
    return "var(--orange)";
}

function headerColor(name){
    if(name === "Total" || name === "Año") return "var(--navy)";
    return "var(--gray)";
}

/* =======================  RENDER DENTRO DE IFRAME  ======================= */
function render(){
    const frame = document.getElementById('chartFrame');
    const frameDoc = frame.contentDocument || frame.contentWindow.document;
    
    frameDoc.open();
    frameDoc.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { 
                    margin: 0; 
                    padding: 0; 
                    background: transparent; 
                    font-family: 'Segoe UI', Arial, sans-serif;
                }
                :root{
                    --navy:#173A64;
                    --navy-dark:#0F2A4A;
                    --cyan:#29ABE2;
                    --magenta:#C0158A;
                    --green:#2FA24C;
                    --orange:#F5941F;
                    --gray:#AEB4BA;
                    --bg:#FFFFFF;
                    --ink:#1A1A1A;
                    --red:#E2261C;
                }
                svg {
                    width: 100% !important;
                    height: auto !important;
                    max-width: 1050px !important;
                    display: block !important;
                    margin: 0 auto !important;
                    min-width: 0 !important;
                }
                text {
                    font-family: 'Segoe UI', Arial, sans-serif;
                }
                #chart {
                    background: transparent;
                }
            </style>
        </head>
        <body>
            <svg id="chart" viewBox="0 0 1400 800" xmlns="http://www.w3.org/2000/svg"></svg>
        </body>
        </html>
    `);
    frameDoc.close();

    const svg = frameDoc.getElementById('chart');
    svg.innerHTML = "";

    const defs = svgEl('defs',{});
    svg.appendChild(defs);

    const nYears = years.length;
    const dataTop  = R0 + nYears*RING_H;
    const headerR1 = dataTop + HEADER_H;
    const bannerR1 = headerR1 + BANNER_H;

    const leftCols = ["Año", "Total", ...lesiones.columns];
    const rightCols = ["Total", ...eventos.columns];

    const leftSpan = 90, rightSpan = 90;
    const leftStep = leftSpan / leftCols.length;
    const rightStep = rightSpan / rightCols.length;

    function totalFor(side, yIdx){
        const src = side === "left" ? lesiones.data : eventos.data;
        return Object.values(src).reduce((s, arr) => s + (Number(arr[yIdx]) || 0), 0);
    }

    const bandGroup = svgEl('g',{});
    svg.appendChild(bandGroup);

    [{a0:180, a1:90, label: lesiones.title}, {a0:90, a1:0, label: eventos.title}].forEach(band => {
        const p = sectorPath(headerR1, bannerR1, band.a0, band.a1);
        bandGroup.appendChild(svgEl('path', {d: p, fill: 'var(--navy)'}));
        const id = 'bandpath_' + band.a0;
        const arcD = arcPath(bannerR1 - BANNER_H/2, band.a0 - 2, band.a1 + 2);
        const pathForText = svgEl('path', {d: arcD, id: id, fill: 'none'});
        defs.appendChild(pathForText);
        const t = svgEl('text', {fill: '#fff', 'font-size': '24', 'font-weight': '800', 'letter-spacing': '1.5'});
        const tp = svgEl('textPath', {'href': '#' + id, startOffset: '50%', 'text-anchor': 'middle'});
        tp.textContent = band.label;
        t.appendChild(tp);
        bandGroup.appendChild(t);
    });

    function drawSide(side, cols, step, startAngle){
        cols.forEach((colName, i) => {
            const a0 = startAngle - i * step;
            const a1 = startAngle - (i + 1) * step;
            const fill = colColor(side, colName);

            const hp = sectorPath(dataTop, headerR1, a0, a1);
            const hEl = svgEl('path', {d: hp, fill: headerColor(colName), stroke: '#fff', 'stroke-width': 1.5});
            svg.appendChild(hEl);

            const midA = (a0 + a1) / 2;
            const labelR = dataTop + HEADER_H/2;
            const lp = pol(labelR, midA);
            const rot = 90 - midA;
            const txt = svgEl('text', {
                x: lp.x, y: lp.y,
                fill: (colName === "Total" || colName === "Año") ? '#fff' : '#28313a',
                'font-size': colName === "Año" ? '14' : '11',
                'font-weight': '700',
                'text-anchor': 'middle',
                transform: `rotate(${rot} ${lp.x} ${lp.y})`
            });
            const words = colName.split(' ');
            if(words.length > 1 && colName.length > 10){
                const line1 = svgEl('tspan', {x: lp.x, dy: '-3'});
                line1.textContent = words.slice(0, Math.ceil(words.length/2)).join(' ');
                const line2 = svgEl('tspan', {x: lp.x, dy: '12'});
                line2.textContent = words.slice(Math.ceil(words.length/2)).join(' ');
                txt.appendChild(line1); txt.appendChild(line2);
            } else {
                txt.textContent = colName;
            }
            svg.appendChild(txt);

            years.forEach((yr, yi) => {
                const r0 = R0 + yi * RING_H;
                const r1 = r0 + RING_H;
                const cp = sectorPath(r0, r1, a0, a1);
                const cEl = svgEl('path', {d: cp, fill: fill, stroke: '#fff', 'stroke-width': 1.4});
                svg.appendChild(cEl);

                let val;
                if(colName === "Año"){
                    val = yr;
                } else if(colName === "Total"){
                    val = totalFor(side, yi);
                } else {
                    val = (side === "left" ? lesiones.data : eventos.data)[colName][yi];
                }

                const midR = (r0 + r1) / 2;
                const p2 = pol(midR, midA);
                const t2 = svgEl('text', {
                    x: p2.x, y: p2.y,
                    fill: '#fff',
                    'font-size': colName === "Año" ? '14' : '16',
                    'font-weight': '700',
                    'text-anchor': 'middle',
                    'dominant-baseline': 'middle',
                    transform: `rotate(${rot} ${p2.x} ${p2.y})`
                });
                t2.textContent = val;
                svg.appendChild(t2);
            });
        });
    }

    drawSide("left", leftCols, leftStep, 180);
    drawSide("right", rightCols, rightStep, 90);

    const c1 = svgEl('text', {x: CX, y: CY - 100, 'text-anchor': 'middle', 'font-size': '48', 'font-weight': '900', fill: '#111'});
    c1.textContent = "VISIÓN";
    svg.appendChild(c1);
    const c2 = svgEl('text', {x: CX, y: CY - 40, 'text-anchor': 'middle', 'font-size': '48', 'font-weight': '900', fill: '#111'});
    c2.textContent = "ZERO";
    svg.appendChild(c2);

    svg.appendChild(svgEl('line', {x1: 40, y1: CY + 6, x2: CX * 2 - 40, y2: CY + 6, stroke: 'var(--red)', 'stroke-width': 4}));
    svg.appendChild(svgEl('circle', {cx: CX * 2 - 40, cy: CY + 6, r: 7, fill: 'var(--red)'}));
}

/* =======================  TABLA AUTOMÁTICA  ======================= */
function buildTable(section, side){
    const cols = section.columns;
    let html = `<table><thead><tr><th>Año</th>`;
    cols.forEach(c => html += `<th>${c}</th>`);
    html += `<th>Total</th></tr></thead><tbody>`;
    years.forEach((yr, yi) => {
        html += `<tr><td class="rowhead">${yr}</td>`;
        cols.forEach(c => {
            const val = (side === "left" ? lesiones.data : eventos.data)[c][yi];
            html += `<td>${val}</td>`;
        });
        const tot = cols.reduce((s,c) => s + (Number((side === "left" ? lesiones.data : eventos.data)[c][yi]) || 0), 0);
        html += `<td class="rowhead">${tot}</td></tr>`;
    });
    html += `</tbody></table>`;
    return html;
}

function renderTables(){
    const container = document.getElementById('tables');
    container.innerHTML = `<strong>Incidentes por Gravedad</strong>` + buildTable(lesiones, "left") +
                         `<strong>Incidentes por Tipo</strong>` + buildTable(eventos, "right");
}

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        render();
        renderTables();
    }, 100);
});
</script>

<?php require_once __DIR__ . '/../../../includes/layout_end.php'; ?>