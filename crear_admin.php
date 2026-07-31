<?php
// crear_admin.php - Crear usuario admin en MongoDB
require_once __DIR__ . '/config/db_mongo.php';

$db = getMongoDB();
$empleados = $db->selectCollection('empleados');

echo "<h2>🔧 Creando Usuario Admin</h2>";

// Verificar si ya existe
$admin = $empleados->findOne(['usuario' => 'admin']);

if ($admin) {
    echo "✅ Usuario admin YA EXISTE<br>";
    echo "Usuario: " . $admin['usuario'] . "<br>";
    echo "Nombre: " . $admin['nombre'] . "<br>";
    echo "Rol: " . $admin['rol'] . "<br>";
    echo "<br><a href='login.php'>👉 Ir al Login</a>";
    exit;
}

// Crear usuario
$result = $empleados->insertOne([
    'usuario' => 'admin',
    'password_hash' => md5('123456'),
    'nombre' => 'Administrador',
    'contacto' => [
        'email' => 'admin@ortiz.com',
        'telefono' => '999999999'
    ],
    'rol' => 'Admin',
    'activo' => true,
    'fechas' => [
        'creacion' => new MongoDB\BSON\UTCDateTime(time() * 1000)
    ]
]);

if ($result->getInsertedCount() > 0) {
    echo "✅ Usuario admin CREADO EXITOSAMENTE<br>";
    echo "<strong>Usuario:</strong> admin<br>";
    echo "<strong>Contraseña:</strong> 123456<br>";
    echo "<br><a href='login.php'>👉 Ir al Login</a>";
} else {
    echo "❌ Error al crear el usuario";
}
?>