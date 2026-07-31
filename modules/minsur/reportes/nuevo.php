<?php
// modules/minsur/reportes/nuevo.php - Crear reporte con evidencias
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$pageTitle = 'Nuevo Reporte de Guardia';
$pg = 'minsur_reportes';
$db = getMongoDB();

$personal = $db->selectCollection('personal_mina');
$lista_personal = $personal->find(['activo' => true], ['sort' => ['nombre' => 1]])->toArray();

$actividades = [
    'Perforación' => '🔨 Perforación',
    'Voladura' => '💥 Voladura',
    'Sostenimiento' => '🛡️ Sostenimiento',
    'Acarreo' => '🚛 Acarreo de Mineral',
    'Explotación' => '⛏️ Explotación',
    'Mantenimiento' => '🔧 Mantenimiento',
    'Seguridad' => '🦺 Seguridad',
    'Ventilación' => '💨 Ventilación',
    'Desatado' => '🏔️ Desatado de Roca',
    'Otro' => '📌 Otro'
];

$tipos_incidencia = ['Seguridad', 'Operacional', 'Ambiental', 'Mecánico', 'Personal', 'Otro'];
$gravedades = ['Baja', 'Media', 'Alta', 'Crítica'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $turno = $_POST['turno'] ?? '';
    $supervisor = trim($_POST['supervisor'] ?? '');
    $area = trim($_POST['area'] ?? 'Mina San Rafael');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $prioridad = $_POST['prioridad'] ?? 'Media';

    // ============================================
    // PERSONAL ASIGNADO
    // ============================================
    $personal_data = [];
    if (isset($_POST['personal_id']) && is_array($_POST['personal_id'])) {
        foreach ($_POST['personal_id'] as $idx => $p_id) {
            if (!empty($p_id) && !empty($_POST['actividad'][$idx])) {
                $p = $personal->findOne(['_id' => toObjectId($p_id)]);
                if ($p) {
                    $personal_data[] = [
                        'personal_id' => toObjectId($p_id),
                        'nombre' => $p['nombre'],
                        'cargo' => $p['cargo'] ?? '—',
                        'area' => $p['area'] ?? '—',
                        'actividad' => $_POST['actividad'][$idx] ?? '',
                        'descripcion' => trim($_POST['descripcion_personal'][$idx] ?? ''),
                        'incidencias' => trim($_POST['incidencias_personal'][$idx] ?? 'Ninguna')
                    ];
                }
            }
        }
    }

    // ============================================
    // INCIDENCIAS GENERALES
    // ============================================
    $incidencias = [];
    if (isset($_POST['incidencia_tipo']) && is_array($_POST['incidencia_tipo'])) {
        foreach ($_POST['incidencia_tipo'] as $idx => $tipo) {
            if (!empty($tipo) && !empty($_POST['incidencia_desc'][$idx])) {
                $incidencias[] = [
                    'tipo' => $tipo,
                    'descripcion' => trim($_POST['incidencia_desc'][$idx]),
                    'gravedad' => $_POST['incidencia_gravedad'][$idx] ?? 'Media',
                    'accion' => trim($_POST['incidencia_accion'][$idx] ?? ''),
                    'fecha_registro' => new MongoDB\BSON\UTCDateTime(time() * 1000)
                ];
            }
        }
    }

    // ============================================
    // PROCESAR IMÁGENES / VIDEOS (EVIDENCIAS)
    // ============================================
    $imagenes = [];
    if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
        $upload_dir = __DIR__ . '/../../../uploads/minsur/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['imagenes']['tmp_name'] as $idx => $tmp_name) {
            if (!empty($tmp_name)) {
                $nombre = time() . '_' . $_FILES['imagenes']['name'][$idx];
                move_uploaded_file($tmp_name, $upload_dir . $nombre);
                $imagenes[] = $nombre;
            }
        }
    }

    // ============================================
    // VALIDAR Y GUARDAR
    // ============================================
    if (empty($turno) || empty($supervisor) || empty($descripcion) || empty($personal_data)) {
        $error = 'Completa todos los campos obligatorios (Turno, Supervisor, Descripción y al menos un personal).';
    } else {
        $reportes = $db->selectCollection('reportes_guardia');
        $data = [
            'fecha' => new MongoDB\BSON\UTCDateTime(strtotime($fecha) * 1000),
            'turno' => $turno,
            'supervisor' => $supervisor,
            'area' => $area,
            'descripcion' => $descripcion,
            'observaciones' => $observaciones,
            'prioridad' => $prioridad,
            'personal' => $personal_data,
            'incidencias_generales' => $incidencias,
            'imagenes' => $imagenes,
            'estado' => 'Completado',
            'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000),
            'created_by' => $_SESSION['nombre'] ?? 'Sistema'
        ];

        try {
            $reportes->insertOne($data);
            flash('✅ Reporte registrado correctamente.', 'success');
            header('Location: ' . BASE . '/modules/minsur/reportes/');
            exit;
        } catch (Exception $e) {
            $error = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../../includes/layout.php';
$isMinsur = true;
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/minsur/reportes/" style="font-size:13px;color:var(--text-2);">
            <i class="fas fa-arrow-left"></i> Reportes
        </a>
        <h1 style="margin-top:4px;"><i class="fas fa-plus-circle"></i> Nuevo Reporte de Guardia</h1>
        <p><i class="fas fa-clipboard"></i> Registro detallado de actividades, incidencias y evidencias</p>
    </div>
</div>

<?php if($error): ?>
    <div class="alert alert-e"><i class="fas fa-exclamation-circle"></i> <?= u($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="formReporte">
    <!-- ============================================ -->
    <!-- DATOS DE LA GUARDIA                          -->
    <!-- ============================================ -->
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-edit"></i> Datos de la Guardia</span></div>
        <div class="card-body">
            <div class="fgrid">
                <div class="fg">
                    <label><i class="fas fa-calendar"></i> Fecha *</label>
                    <input type="date" name="fecha" class="fc" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="fg">
                    <label><i class="fas fa-clock"></i> Turno *</label>
                    <select name="turno" class="fc" required>
                        <option value="">Seleccionar...</option>
                        <option value="06:00-14:00">🌅 Primer Turno (06:00 - 14:00)</option>
                        <option value="14:00-22:00">🌤️ Segundo Turno (14:00 - 22:00)</option>
                        <option value="22:00-06:00">🌙 Tercer Turno (22:00 - 06:00)</option>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-user"></i> Supervisor *</label>
                    <input type="text" name="supervisor" class="fc" placeholder="Nombre del supervisor" required>
                </div>
                <div class="fg">
                    <label><i class="fas fa-map-marker-alt"></i> Área</label>
                    <input type="text" name="area" class="fc" value="Mina San Rafael">
                </div>
                <div class="fg">
                    <label><i class="fas fa-flag"></i> Prioridad</label>
                    <select name="prioridad" class="fc">
                        <option value="Baja">🟢 Baja</option>
                        <option value="Media" selected>🟡 Media</option>
                        <option value="Alta">🟠 Alta</option>
                        <option value="Crítica">🔴 Crítica</option>
                    </select>
                </div>
                <div class="fg span2">
                    <label><i class="fas fa-align-left"></i> Descripción *</label>
                    <textarea name="descripcion" class="fc" rows="4" placeholder="Describe detalladamente lo que ocurrió durante la guardia..." required></textarea>
                </div>
                <div class="fg span2">
                    <label><i class="fas fa-comment"></i> Observaciones</label>
                    <textarea name="observaciones" class="fc" rows="2" placeholder="Observaciones adicionales..."></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- PERSONAL ASIGNADO                            -->
    <!-- ============================================ -->
    <div class="card" style="margin-top:16px;">
        <div class="card-hd">
            <span class="card-title"><i class="fas fa-users"></i> Personal Asignado</span>
            <button type="button" class="btn btn-outline btn-sm" onclick="agregarPersonal()">
                <i class="fas fa-plus"></i> Agregar Personal
            </button>
        </div>
        <div class="card-body" id="personalContainer">
            <div class="personal-row" id="personalRow1" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;padding:12px;background:var(--bg);border-radius:8px;margin-bottom:10px;align-items:end;">
                <div class="fg">
                    <label><i class="fas fa-user"></i> Personal</label>
                    <select name="personal_id[]" class="fc" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach($lista_personal as $p): ?>
                            <option value="<?= getId($p) ?>"><?= u($p['nombre']) ?> - <?= u($p['cargo'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-tasks"></i> Actividad</label>
                    <select name="actividad[]" class="fc" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach($actividades as $k => $v): ?>
                            <option value="<?= $k ?>"><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-pen"></i> Descripción</label>
                    <input type="text" name="descripcion_personal[]" class="fc" placeholder="Qué hizo...">
                </div>
                <div class="fg" style="display:flex;gap:8px;align-items:center;">
                    <div style="flex:1;">
                        <label><i class="fas fa-exclamation-circle"></i> Incidencias</label>
                        <input type="text" name="incidencias_personal[]" class="fc" placeholder="Ninguna">
                    </div>
                    <button type="button" class="btn btn-danger btn-sm" style="margin-top:18px;" onclick="eliminarPersonal(this)" title="Eliminar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- INCIDENCIAS GENERALES                        -->
    <!-- ============================================ -->
    <div class="card" style="margin-top:16px;">
        <div class="card-hd">
            <span class="card-title"><i class="fas fa-exclamation-triangle"></i> Incidencias Generales</span>
            <button type="button" class="btn btn-outline btn-sm" onclick="agregarIncidencia()">
                <i class="fas fa-plus"></i> Agregar Incidencia
            </button>
        </div>
        <div class="card-body" id="incidenciasContainer">
            <div class="incidencia-row" id="incidenciaRow1" style="display:grid;grid-template-columns:1fr 2fr 1fr 1.5fr;gap:10px;padding:12px;background:#fff8f8;border-radius:8px;margin-bottom:10px;border:1px solid #fee;align-items:end;">
                <div class="fg">
                    <label><i class="fas fa-tag"></i> Tipo</label>
                    <select name="incidencia_tipo[]" class="fc">
                        <option value="">Seleccionar...</option>
                        <?php foreach($tipos_incidencia as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-align-left"></i> Descripción</label>
                    <input type="text" name="incidencia_desc[]" class="fc" placeholder="Describe la incidencia...">
                </div>
                <div class="fg">
                    <label><i class="fas fa-flag"></i> Gravedad</label>
                    <select name="incidencia_gravedad[]" class="fc">
                        <?php foreach($gravedades as $g): ?>
                            <option value="<?= $g ?>"><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg" style="display:flex;gap:8px;align-items:center;">
                    <div style="flex:1;">
                        <label><i class="fas fa-check-circle"></i> Acción Tomada</label>
                        <input type="text" name="incidencia_accion[]" class="fc" placeholder="Qué se hizo...">
                    </div>
                    <button type="button" class="btn btn-danger btn-sm" style="margin-top:18px;" onclick="eliminarIncidencia(this)" title="Eliminar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- EVIDENCIAS (FOTOS / VIDEOS)                  -->
    <!-- ============================================ -->
    <div class="card" style="margin-top:16px;">
        <div class="card-hd">
            <span class="card-title"><i class="fas fa-camera"></i> Evidencias (Fotos / Videos)</span>
            <span style="font-size:11px;color:var(--text-3);">Formatos: JPG, PNG, GIF, MP4 (Max 10MB)</span>
        </div>
        <div class="card-body">
            <div class="upload-zone" id="uploadZone" style="cursor:pointer;border:2px dashed var(--border);border-radius:10px;padding:30px;text-align:center;transition:all 0.3s;">
                <i class="fas fa-cloud-upload-alt" style="font-size:40px;color:var(--text-3);"></i>
                <p style="font-size:14px;color:var(--text-2);margin-top:8px;">
                    <i class="fas fa-hand-pointer"></i> Haz clic para subir imágenes o videos
                </p>
                <p style="font-size:12px;color:var(--text-3);">
                    <i class="fas fa-info-circle"></i> Puedes seleccionar múltiples archivos
                </p>
            </div>
            <input type="file" name="imagenes[]" id="imagenesInput" accept="image/*,video/*" multiple style="display:none;">
            <div id="previewContainer" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;"></div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- BOTONES                                      -->
    <!-- ============================================ -->
    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
        <a href="<?= BASE ?>/modules/minsur/reportes/" class="btn btn-outline">
            <i class="fas fa-times"></i> Cancelar
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar Reporte
        </button>
    </div>
</form>

<?php
$extraJs = '
<script>
// ============================================
// PERSONAL
// ============================================
let personalCount = 1;

function agregarPersonal() {
    personalCount++;
    const container = document.getElementById("personalContainer");
    const row = document.createElement("div");
    row.className = "personal-row";
    row.id = "personalRow" + personalCount;
    row.style.cssText = "display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;padding:12px;background:var(--bg);border-radius:8px;margin-bottom:10px;align-items:end;";
    row.innerHTML = `
        <div class="fg">
            <label><i class="fas fa-user"></i> Personal</label>
            <select name="personal_id[]" class="fc" required>
                <option value="">Seleccionar...</option>
                ' . implode('', array_map(function($p) {
                    return '<option value="' . getId($p) . '">' . u($p['nombre']) . ' - ' . u($p['cargo'] ?? '') . '</option>';
                }, $lista_personal)) . '
            </select>
        </div>
        <div class="fg">
            <label><i class="fas fa-tasks"></i> Actividad</label>
            <select name="actividad[]" class="fc" required>
                <option value="">Seleccionar...</option>
                ' . implode('', array_map(function($k, $v) {
                    return '<option value="' . $k . '">' . $v . '</option>';
                }, array_keys($actividades), $actividades)) . '
            </select>
        </div>
        <div class="fg">
            <label><i class="fas fa-pen"></i> Descripción</label>
            <input type="text" name="descripcion_personal[]" class="fc" placeholder="Qué hizo...">
        </div>
        <div class="fg" style="display:flex;gap:8px;align-items:center;">
            <div style="flex:1;">
                <label><i class="fas fa-exclamation-circle"></i> Incidencias</label>
                <input type="text" name="incidencias_personal[]" class="fc" placeholder="Ninguna">
            </div>
            <button type="button" class="btn btn-danger btn-sm" style="margin-top:18px;" onclick="eliminarPersonal(this)" title="Eliminar">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
}

function eliminarPersonal(btn) {
    const row = btn.closest(".personal-row");
    if (document.querySelectorAll(".personal-row").length > 1) {
        row.remove();
    } else {
        alert("Debe haber al menos un personal asignado.");
    }
}

// ============================================
// INCIDENCIAS
// ============================================
let incidenciaCount = 1;

function agregarIncidencia() {
    incidenciaCount++;
    const container = document.getElementById("incidenciasContainer");
    const row = document.createElement("div");
    row.className = "incidencia-row";
    row.id = "incidenciaRow" + incidenciaCount;
    row.style.cssText = "display:grid;grid-template-columns:1fr 2fr 1fr 1.5fr;gap:10px;padding:12px;background:#fff8f8;border-radius:8px;margin-bottom:10px;border:1px solid #fee;align-items:end;";
    row.innerHTML = `
        <div class="fg">
            <label><i class="fas fa-tag"></i> Tipo</label>
            <select name="incidencia_tipo[]" class="fc">
                <option value="">Seleccionar...</option>
                ' . implode('', array_map(function($t) {
                    return '<option value="' . $t . '">' . $t . '</option>';
                }, $tipos_incidencia)) . '
            </select>
        </div>
        <div class="fg">
            <label><i class="fas fa-align-left"></i> Descripción</label>
            <input type="text" name="incidencia_desc[]" class="fc" placeholder="Describe la incidencia...">
        </div>
        <div class="fg">
            <label><i class="fas fa-flag"></i> Gravedad</label>
            <select name="incidencia_gravedad[]" class="fc">
                ' . implode('', array_map(function($g) {
                    return '<option value="' . $g . '">' . $g . '</option>';
                }, $gravedades)) . '
            </select>
        </div>
        <div class="fg" style="display:flex;gap:8px;align-items:center;">
            <div style="flex:1;">
                <label><i class="fas fa-check-circle"></i> Acción Tomada</label>
                <input type="text" name="incidencia_accion[]" class="fc" placeholder="Qué se hizo...">
            </div>
            <button type="button" class="btn btn-danger btn-sm" style="margin-top:18px;" onclick="eliminarIncidencia(this)" title="Eliminar">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
}

function eliminarIncidencia(btn) {
    const row = btn.closest(".incidencia-row");
    if (document.querySelectorAll(".incidencia-row").length > 1) {
        row.remove();
    } else {
        alert("Debe haber al menos una incidencia.");
    }
}

// ============================================
// EVIDENCIAS (FOTOS / VIDEOS)
// ============================================
document.getElementById("uploadZone").addEventListener("click", function() {
    document.getElementById("imagenesInput").click();
});

document.getElementById("imagenesInput").addEventListener("change", function(e) {
    const container = document.getElementById("previewContainer");
    container.innerHTML = "";
    container.style.display = "flex";

    for (let file of this.files) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const div = document.createElement("div");
            div.style.cssText = "position:relative;width:120px;height:120px;border-radius:8px;overflow:hidden;border:1px solid var(--border);background:var(--bg);";
            
            if (file.type.startsWith("image/")) {
                div.innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
            } else if (file.type.startsWith("video/")) {
                div.innerHTML = `<video src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;"></video>`;
            } else {
                div.innerHTML = `<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:32px;color:var(--text-3);"><i class="fas fa-file"></i></div>`;
            }
            
            // Botón eliminar
            const btn = document.createElement("button");
            btn.innerHTML = `<i class="fas fa-times"></i>`;
            btn.style.cssText = "position:absolute;top:4px;right:4px;background:#dc2626;color:white;border:none;border-radius:50%;width:26px;height:26px;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;";
            btn.onclick = function(e) {
                e.stopPropagation();
                div.remove();
                // Remover el archivo del input
                const input = document.getElementById("imagenesInput");
                const dt = new DataTransfer();
                for (let f of input.files) {
                    if (f !== file) dt.items.add(f);
                }
                input.files = dt.files;
            };
            div.appendChild(btn);
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    }
});

// Drag & Drop
document.getElementById("uploadZone").addEventListener("dragover", function(e) {
    e.preventDefault();
    this.style.borderColor = "#1e40af";
    this.style.background = "rgba(30,64,175,0.05)";
});

document.getElementById("uploadZone").addEventListener("dragleave", function(e) {
    e.preventDefault();
    this.style.borderColor = "var(--border)";
    this.style.background = "transparent";
});

document.getElementById("uploadZone").addEventListener("drop", function(e) {
    e.preventDefault();
    this.style.borderColor = "var(--border)";
    this.style.background = "transparent";
    
    const input = document.getElementById("imagenesInput");
    const dt = new DataTransfer();
    for (let f of e.dataTransfer.files) {
        dt.items.add(f);
    }
    input.files = dt.files;
    input.dispatchEvent(new Event("change"));
});
</script>
';

require_once __DIR__ . '/../../../includes/layout_end.php';
?>