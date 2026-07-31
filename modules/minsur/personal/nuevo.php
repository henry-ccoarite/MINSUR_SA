<?php
// modules/minsur/personal/nuevo.php - Agregar personal con foto
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$pageTitle = 'Nuevo Personal';
$pg = 'minsur_personal';
$db = getMongoDB();
$collection = $db->selectCollection('personal_mina');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'dni' => trim($_POST['dni'] ?? ''),
        'cargo' => trim($_POST['cargo'] ?? ''),
        'area' => trim($_POST['area'] ?? ''),
        'especialidad' => trim($_POST['especialidad'] ?? ''),
        'turno_asignado' => $_POST['turno_asignado'] ?? '',
        'supervisor' => trim($_POST['supervisor'] ?? ''),
        'contacto' => [
            'telefono' => trim($_POST['telefono'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? '')
        ],
        'datos_personales' => [
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'genero' => $_POST['genero'] ?? '',
            'estado_civil' => $_POST['estado_civil'] ?? '',
            'nacionalidad' => $_POST['nacionalidad'] ?? 'Peruana'
        ],
        'fechas_contrato' => [
            'ingreso' => $_POST['fecha_ingreso'] ?? date('Y-m-d'),
            'cese' => null
        ],
        'activo' => isset($_POST['activo']),
        'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000)
    ];

    // Procesar foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $upload_dir = __DIR__ . '/../../../uploads/personal/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $foto_nombre = time() . '_' . $_FILES['foto']['name'];
        move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_nombre);
        $data['foto'] = $foto_nombre;
    }

    if (empty($data['nombre']) || empty($data['dni']) || empty($data['cargo'])) {
        $error = 'Completa los campos obligatorios (Nombre, DNI, Cargo).';
    } else {
        try {
            $collection->insertOne($data);
            flash('✅ Personal registrado correctamente.', 'success');
            header('Location: ' . BASE . '/modules/minsur/personal/');
            exit;
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$cargos = ['Jefe de Guardia', 'Supervisor', 'Operador', 'Técnico', 'Ayudante', 'Seguridad', 'Mecánico', 'Electricista'];
$areas = ['Operaciones', 'Explotación', 'Acarreo', 'Mantenimiento', 'Seguridad', 'Ventilación', 'Geomecánica'];
$turnos = ['06:00-14:00', '14:00-22:00', '22:00-06:00'];

require_once __DIR__ . '/../../../includes/layout.php';
$isMinsur = true;
?>

<style>
    .upload-zone:hover {
        border-color: var(--primary);
        background: var(--primary-l);
    }
</style>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/minsur/personal/" style="font-size:13px;color:var(--text-2);">
            <i class="fas fa-arrow-left"></i> Personal
        </a>
        <h1 style="margin-top:4px;"><i class="fas fa-user-plus"></i> Nuevo Personal</h1>
        <p><i class="fas fa-clipboard"></i> Registro de personal minero con foto</p>
    </div>
</div>

<?php if($error): ?>
    <div class="alert alert-e"><i class="fas fa-exclamation-circle"></i> <?= u($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-id-card"></i> Datos Personales</span></div>
        <div class="card-body">
            <div class="fgrid">
                <div class="fg">
                    <label><i class="fas fa-id-card"></i> DNI *</label>
                    <input type="text" name="dni" class="fc" maxlength="15" required>
                </div>
                <div class="fg">
                    <label><i class="fas fa-user"></i> Nombre Completo *</label>
                    <input type="text" name="nombre" class="fc" required>
                </div>
                <div class="fg">
                    <label><i class="fas fa-calendar"></i> Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="fc">
                </div>
                <div class="fg">
                    <label><i class="fas fa-venus-mars"></i> Género</label>
                    <select name="genero" class="fc">
                        <option value="">Seleccionar...</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-ring"></i> Estado Civil</label>
                    <select name="estado_civil" class="fc">
                        <option value="">Seleccionar...</option>
                        <option value="Soltero/a">Soltero/a</option>
                        <option value="Casado/a">Casado/a</option>
                        <option value="Divorciado/a">Divorciado/a</option>
                        <option value="Viudo/a">Viudo/a</option>
                        <option value="Conviviente">Conviviente</option>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-globe"></i> Nacionalidad</label>
                    <input type="text" name="nacionalidad" class="fc" value="Peruana">
                </div>
                <div class="fg span2">
                    <label><i class="fas fa-camera"></i> Foto de Perfil</label>
                    <div class="upload-zone" id="uploadFoto" style="cursor:pointer;border:2px dashed var(--border);border-radius:10px;padding:20px;text-align:center;">
                        <i class="fas fa-user-circle" style="font-size:40px;color:var(--text-3);"></i>
                        <p style="font-size:13px;color:var(--text-2);margin-top:4px;">
                            <i class="fas fa-hand-pointer"></i> Haz clic para subir una foto
                        </p>
                        <p style="font-size:11px;color:var(--text-3);">Formatos: JPG, PNG (Max 2MB)</p>
                    </div>
                    <input type="file" name="foto" id="fotoInput" accept="image/*" style="display:none;">
                    <div id="previewFoto" style="display:none;margin-top:10px;text-align:center;">
                        <img id="fotoPreviewImg" src="" style="max-width:150px;max-height:150px;border-radius:50%;border:3px solid var(--primary);object-fit:cover;">
                        <br>
                        <button type="button" class="btn btn-outline btn-sm" onclick="eliminarFoto()" style="margin-top:6px;">
                            <i class="fas fa-times"></i> Eliminar foto
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <div class="card-hd"><span class="card-title"><i class="fas fa-briefcase"></i> Datos Laborales</span></div>
        <div class="card-body">
            <div class="fgrid">
                <div class="fg">
                    <label><i class="fas fa-briefcase"></i> Cargo *</label>
                    <select name="cargo" class="fc" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach($cargos as $c): ?>
                            <option value="<?= $c ?>"><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-building"></i> Área</label>
                    <select name="area" class="fc">
                        <option value="">Seleccionar...</option>
                        <?php foreach($areas as $a): ?>
                            <option value="<?= $a ?>"><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-star"></i> Especialidad</label>
                    <input type="text" name="especialidad" class="fc" placeholder="Ej: Perforación, Mecánica...">
                </div>
                <div class="fg">
                    <label><i class="fas fa-clock"></i> Turno Asignado</label>
                    <select name="turno_asignado" class="fc">
                        <option value="">Seleccionar...</option>
                        <?php foreach($turnos as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-user-tie"></i> Supervisor</label>
                    <input type="text" name="supervisor" class="fc" placeholder="Nombre del supervisor">
                </div>
                <div class="fg">
                    <label><i class="fas fa-calendar"></i> Fecha de Ingreso</label>
                    <input type="date" name="fecha_ingreso" class="fc" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="fg">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="activo" checked>
                        <i class="fas fa-check-circle" style="color:var(--green);"></i> Activo
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <div class="card-hd"><span class="card-title"><i class="fas fa-phone"></i> Contacto</span></div>
        <div class="card-body">
            <div class="fgrid">
                <div class="fg">
                    <label><i class="fas fa-phone"></i> Teléfono</label>
                    <input type="text" name="telefono" class="fc">
                </div>
                <div class="fg">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" class="fc">
                </div>
                <div class="fg span2">
                    <label><i class="fas fa-map-marker-alt"></i> Dirección</label>
                    <input type="text" name="direccion" class="fc">
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
        <a href="<?= BASE ?>/modules/minsur/personal/" class="btn btn-outline">
            <i class="fas fa-times"></i> Cancelar
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar Personal
        </button>
    </div>
</form>

<?php
$extraJs = '
<script>
// ============================================
// FOTO DE PERFIL
// ============================================
document.getElementById("uploadFoto").addEventListener("click", function() {
    document.getElementById("fotoInput").click();
});

document.getElementById("fotoInput").addEventListener("change", function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            document.getElementById("fotoPreviewImg").src = ev.target.result;
            document.getElementById("previewFoto").style.display = "block";
            document.getElementById("uploadFoto").style.display = "none";
        };
        reader.readAsDataURL(this.files[0]);
    }
});

function eliminarFoto() {
    document.getElementById("fotoInput").value = "";
    document.getElementById("previewFoto").style.display = "none";
    document.getElementById("uploadFoto").style.display = "block";
}
</script>
';

require_once __DIR__ . '/../../../includes/layout_end.php';
?>