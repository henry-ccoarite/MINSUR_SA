<?php
// modules/minsur/personal/editar.php - Editar personal
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$id = $_GET['id'] ?? null;
$db = getMongoDB();
$collection = $db->selectCollection('personal_mina');

$persona = $collection->findOne(['_id' => toObjectId($id)]);

if (!$persona) {
    header('Location: ' . BASE . '/modules/minsur/personal/');
    exit;
}

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
        'activo' => isset($_POST['activo'])
    ];

    if (empty($data['nombre']) || empty($data['dni']) || empty($data['cargo'])) {
        $error = 'Completa los campos obligatorios (Nombre, DNI, Cargo).';
    } else {
        try {
            $collection->updateOne(
                ['_id' => toObjectId($id)],
                ['$set' => $data]
            );
            flash('✅ Personal actualizado correctamente.', 'success');
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

$pageTitle = 'Editar Personal';
$pg = 'minsur_personal';
$contacto = $persona['contacto'] ?? [];
$datos = $persona['datos_personales'] ?? [];
$fechas = $persona['fechas_contrato'] ?? [];

require_once __DIR__ . '/../../../includes/layout.php';
$isMinsur = true;
?>

<div class="ph">
    <div>
        <a href="<?= BASE ?>/modules/minsur/personal/" style="font-size:13px;color:var(--text-2);">
            <i class="fas fa-arrow-left"></i> Personal
        </a>
        <h1 style="margin-top:4px;"><i class="fas fa-edit"></i> Editar Personal</h1>
        <p><i class="fas fa-user"></i> <?= u($persona['nombre']) ?></p>
    </div>
</div>

<?php if($error): ?>
    <div class="alert alert-e"><i class="fas fa-exclamation-circle"></i> <?= u($error) ?></div>
<?php endif; ?>

<form method="POST">
    <div class="card">
        <div class="card-hd"><span class="card-title"><i class="fas fa-id-card"></i> Datos Personales</span></div>
        <div class="card-body">
            <div class="fgrid">
                <div class="fg">
                    <label><i class="fas fa-id-card"></i> DNI *</label>
                    <input type="text" name="dni" class="fc" value="<?= u($persona['dni']) ?>" required>
                </div>
                <div class="fg">
                    <label><i class="fas fa-user"></i> Nombre Completo *</label>
                    <input type="text" name="nombre" class="fc" value="<?= u($persona['nombre']) ?>" required>
                </div>
                <div class="fg">
                    <label><i class="fas fa-calendar"></i> Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="fc" value="<?= u($datos['fecha_nacimiento'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label><i class="fas fa-venus-mars"></i> Género</label>
                    <select name="genero" class="fc">
                        <option value="">Seleccionar...</option>
                        <option value="Masculino" <?= ($datos['genero'] ?? '') === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                        <option value="Femenino" <?= ($datos['genero'] ?? '') === 'Femenino' ? 'selected' : '' ?>>Femenino</option>
                        <option value="Otro" <?= ($datos['genero'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-ring"></i> Estado Civil</label>
                    <select name="estado_civil" class="fc">
                        <option value="">Seleccionar...</option>
                        <option value="Soltero/a" <?= ($datos['estado_civil'] ?? '') === 'Soltero/a' ? 'selected' : '' ?>>Soltero/a</option>
                        <option value="Casado/a" <?= ($datos['estado_civil'] ?? '') === 'Casado/a' ? 'selected' : '' ?>>Casado/a</option>
                        <option value="Divorciado/a" <?= ($datos['estado_civil'] ?? '') === 'Divorciado/a' ? 'selected' : '' ?>>Divorciado/a</option>
                        <option value="Viudo/a" <?= ($datos['estado_civil'] ?? '') === 'Viudo/a' ? 'selected' : '' ?>>Viudo/a</option>
                        <option value="Conviviente" <?= ($datos['estado_civil'] ?? '') === 'Conviviente' ? 'selected' : '' ?>>Conviviente</option>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-globe"></i> Nacionalidad</label>
                    <input type="text" name="nacionalidad" class="fc" value="<?= u($datos['nacionalidad'] ?? 'Peruana') ?>">
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
                            <option value="<?= $c ?>" <?= ($persona['cargo'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-building"></i> Área</label>
                    <select name="area" class="fc">
                        <option value="">Seleccionar...</option>
                        <?php foreach($areas as $a): ?>
                            <option value="<?= $a ?>" <?= ($persona['area'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-star"></i> Especialidad</label>
                    <input type="text" name="especialidad" class="fc" value="<?= u($persona['especialidad'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label><i class="fas fa-clock"></i> Turno Asignado</label>
                    <select name="turno_asignado" class="fc">
                        <option value="">Seleccionar...</option>
                        <?php foreach($turnos as $t): ?>
                            <option value="<?= $t ?>" <?= ($persona['turno_asignado'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-user-tie"></i> Supervisor</label>
                    <input type="text" name="supervisor" class="fc" value="<?= u($persona['supervisor'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label><i class="fas fa-calendar"></i> Fecha de Ingreso</label>
                    <input type="date" name="fecha_ingreso" class="fc" value="<?= u($fechas['ingreso'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="fg">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="activo" <?= ($persona['activo'] ?? true) ? 'checked' : '' ?>>
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
                    <input type="text" name="telefono" class="fc" value="<?= u($contacto['telefono'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" class="fc" value="<?= u($contacto['email'] ?? '') ?>">
                </div>
                <div class="fg span2">
                    <label><i class="fas fa-map-marker-alt"></i> Dirección</label>
                    <input type="text" name="direccion" class="fc" value="<?= u($contacto['direccion'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
        <a href="<?= BASE ?>/modules/minsur/personal/" class="btn btn-outline">
            <i class="fas fa-times"></i> Cancelar
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar Cambios
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../../../includes/layout_end.php'; ?>