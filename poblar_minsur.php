<?php
// poblar_minsur.php - Poblar base de datos con datos de ejemplo
require_once __DIR__ . '/config/db_mongo.php';

$db = getMongoDB();

echo "<h2>🚀 Poblando Base de Datos - MINSUR</h2>";

// ============================================
// 1. PERSONAL MINA
// ============================================
$personal = $db->selectCollection('personal_mina');

// Limpiar datos existentes
$personal->deleteMany([]);

$personal_data = [
    [
        'nombre' => 'Carlos Mendoza',
        'dni' => '12345678',
        'cargo' => 'Jefe de Guardia',
        'area' => 'Operaciones',
        'especialidad' => 'Supervisión',
        'turno_asignado' => '06:00-14:00',
        'supervisor' => 'Jefe de Mina',
        'contacto' => [
            'telefono' => '987654321',
            'email' => 'carlos@minsur.com'
        ],
        'datos_personales' => [
            'fecha_nacimiento' => '1985-06-15',
            'genero' => 'Masculino',
            'estado_civil' => 'Casado',
            'nacionalidad' => 'Peruana'
        ],
        'activo' => true,
        'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000)
    ],
    [
        'nombre' => 'Luis Torres',
        'dni' => '87654321',
        'cargo' => 'Operador',
        'area' => 'Explotación',
        'especialidad' => 'Perforación',
        'turno_asignado' => '06:00-14:00',
        'supervisor' => 'Carlos Mendoza',
        'contacto' => [
            'telefono' => '987654322',
            'email' => 'luis@minsur.com'
        ],
        'datos_personales' => [
            'fecha_nacimiento' => '1990-03-20',
            'genero' => 'Masculino',
            'estado_civil' => 'Soltero',
            'nacionalidad' => 'Peruana'
        ],
        'activo' => true,
        'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000)
    ],
    [
        'nombre' => 'Ana Quispe',
        'dni' => '45678912',
        'cargo' => 'Operador',
        'area' => 'Acarreo',
        'especialidad' => 'Scooptram',
        'turno_asignado' => '14:00-22:00',
        'supervisor' => 'Carlos Mendoza',
        'contacto' => [
            'telefono' => '987654323',
            'email' => 'ana@minsur.com'
        ],
        'datos_personales' => [
            'fecha_nacimiento' => '1988-11-05',
            'genero' => 'Femenino',
            'estado_civil' => 'Casada',
            'nacionalidad' => 'Peruana'
        ],
        'activo' => true,
        'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000)
    ],
    [
        'nombre' => 'Roberto Puma',
        'dni' => '78912345',
        'cargo' => 'Técnico',
        'area' => 'Mantenimiento',
        'especialidad' => 'Mecánico',
        'turno_asignado' => '22:00-06:00',
        'supervisor' => 'Jefe de Mantenimiento',
        'contacto' => [
            'telefono' => '987654324',
            'email' => 'roberto@minsur.com'
        ],
        'datos_personales' => [
            'fecha_nacimiento' => '1982-09-30',
            'genero' => 'Masculino',
            'estado_civil' => 'Divorciado',
            'nacionalidad' => 'Peruana'
        ],
        'activo' => true,
        'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000)
    ],
    [
        'nombre' => 'Maria Flores',
        'dni' => '32165498',
        'cargo' => 'Supervisor',
        'area' => 'Seguridad',
        'especialidad' => 'Seguridad Minera',
        'turno_asignado' => '06:00-14:00',
        'supervisor' => 'Jefe de Seguridad',
        'contacto' => [
            'telefono' => '987654325',
            'email' => 'maria@minsur.com'
        ],
        'datos_personales' => [
            'fecha_nacimiento' => '1992-07-12',
            'genero' => 'Femenino',
            'estado_civil' => 'Soltera',
            'nacionalidad' => 'Peruana'
        ],
        'activo' => true,
        'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000)
    ]
];

$personal->insertMany($personal_data);
echo "✅ " . count($personal_data) . " registros de personal insertados<br>";

// ============================================
// 2. REPORTES DE GUARDIA
// ============================================
$reportes = $db->selectCollection('reportes_guardia');

// Limpiar datos existentes
$reportes->deleteMany([]);

// Fechas de ejemplo
$fechas = ['2026-07-25', '2026-07-26', '2026-07-27', '2026-07-28', '2026-07-29'];

$reportes_data = [];

foreach ($fechas as $fecha) {
    // Turnos del día
    $turnos = ['06:00-14:00', '14:00-22:00', '22:00-06:00'];
    foreach ($turnos as $turno) {
        $reportes_data[] = [
            'fecha' => new MongoDB\BSON\UTCDateTime(strtotime($fecha) * 1000),
            'turno' => $turno,
            'supervisor' => 'Carlos Mendoza',
            'area' => 'Mina San Rafael',
            'descripcion' => 'Guardia normal, se realizaron las actividades programadas sin incidentes mayores.',
            'observaciones' => 'Ninguna observación relevante.',
            'prioridad' => 'Media',
            'personal' => [
                [
                    'nombre' => 'Luis Torres',
                    'cargo' => 'Operador',
                    'area' => 'Explotación',
                    'actividad' => 'Perforación',
                    'descripcion' => 'Perforación de 12 taladros en frente 305',
                    'incidencias' => 'Ninguna'
                ],
                [
                    'nombre' => 'Ana Quispe',
                    'cargo' => 'Operador',
                    'area' => 'Acarreo',
                    'actividad' => 'Acarreo',
                    'descripcion' => 'Transporte de 45 toneladas de mineral',
                    'incidencias' => 'Ninguna'
                ]
            ],
            'incidencias_generales' => [],
            'imagenes' => [],
            'estado' => 'Completado',
            'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000),
            'created_by' => 'Sistema'
        ];
    }
}

// Agregar un reporte con incidencias
$reportes_data[] = [
    'fecha' => new MongoDB\BSON\UTCDateTime(strtotime('2026-07-28') * 1000),
    'turno' => '06:00-14:00',
    'supervisor' => 'Carlos Mendoza',
    'area' => 'Mina San Rafael',
    'descripcion' => 'Se presentó una situación de riesgo durante la voladura programada.',
    'observaciones' => 'Se reforzaron las medidas de seguridad.',
    'prioridad' => 'Alta',
    'personal' => [
        [
            'nombre' => 'Luis Torres',
            'cargo' => 'Operador',
            'area' => 'Explotación',
            'actividad' => 'Voladura',
            'descripcion' => 'Preparación de frentes para voladura',
            'incidencias' => 'Ninguna'
        ],
        [
            'nombre' => 'Maria Flores',
            'cargo' => 'Supervisor',
            'area' => 'Seguridad',
            'actividad' => 'Seguridad',
            'descripcion' => 'Supervisión de zona de voladura',
            'incidencias' => 'Personal sin EPP completo'
        ]
    ],
    'incidencias_generales' => [
        [
            'tipo' => 'Seguridad',
            'descripcion' => 'Se encontró a un trabajador sin lentes de seguridad en zona de voladura',
            'gravedad' => 'Alta',
            'accion' => 'Se corrigió en el momento y se registró en bitácora',
            'fecha_registro' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ],
        [
            'tipo' => 'Operacional',
            'descripcion' => 'Retraso de 20 minutos por verificación de gases',
            'gravedad' => 'Media',
            'accion' => 'Se normalizaron las operaciones',
            'fecha_registro' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ]
    ],
    'imagenes' => [],
    'estado' => 'Completado',
    'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000),
    'created_by' => 'Sistema'
];

$reportes->insertMany($reportes_data);
echo "✅ " . count($reportes_data) . " reportes de guardia insertados<br>";

// ============================================
// 3. VERIFICAR DATOS
// ============================================
echo "<h3>📊 Resumen de datos:</h3>";
echo "👷 Personal: " . $personal->countDocuments() . " registros<br>";
echo "📋 Reportes: " . $reportes->countDocuments() . " registros<br>";

echo "<h3>🎉 ¡Base de datos poblada exitosamente!</h3>";
echo "<p><a href='modules/minsur/reportes/'>👉 Ver Reportes</a></p>";
echo "<p><a href='modules/minsur/personal/'>👉 Ver Personal</a></p>";
?>