<?php
// crear_usuarios_jerarquia.php - Crear usuarios con diferentes roles
require_once __DIR__ . '/config/db_mongo.php';

$db = getMongoDB();
$empleados = $db->selectCollection('empleados');

$usuarios = [
    [
        'usuario' => 'admin',
        'password_hash' => md5('123456'),
        'nombre' => 'Administrador',
        'rol' => 'Admin',
        'activo' => true
    ],
    [
        'usuario' => 'gerente',
        'password_hash' => md5('123456'),
        'nombre' => 'Carlos Gerente',
        'rol' => 'Gerente',
        'activo' => true
    ],
    [
        'usuario' => 'jefe_guardia',
        'password_hash' => md5('123456'),
        'nombre' => 'Mario Jefe',
        'rol' => 'Jefe Guardia',
        'activo' => true
    ],
    [
        'usuario' => 'supervisor',
        'password_hash' => md5('123456'),
        'nombre' => 'Luis Supervisor',
        'rol' => 'Supervisor',
        'activo' => true
    ],
    [
        'usuario' => 'agente',
        'password_hash' => md5('123456'),
        'nombre' => 'Ana Agente',
        'rol' => 'Agente',
        'activo' => true
    ],
    [
        'usuario' => 'tecnico',
        'password_hash' => md5('123456'),
        'nombre' => 'Roberto Técnico',
        'rol' => 'Tecnico',
        'activo' => true
    ],
    [
        'usuario' => 'operador',
        'password_hash' => md5('123456'),
        'nombre' => 'Juvenal Operador',
        'rol' => 'Operador',
        'activo' => true
    ]
];

echo "<h2>🚀 Creando Usuarios con Jerarquía</h2>";

foreach ($usuarios as $user) {
    $existe = $empleados->findOne(['usuario' => $user['usuario']]);
    if ($existe) {
        echo "ℹ️ Usuario '{$user['usuario']}' ya existe<br>";
    } else {
        $empleados->insertOne($user);
        echo "✅ Usuario '{$user['usuario']}' creado (rol: {$user['rol']})<br>";
    }
}

echo "<br><a href='login.php'>👉 Ir al Login</a>";
echo "<br><br><strong>Credenciales:</strong><br>";
echo "Todos los usuarios tienen contraseña: <strong>123456</strong><br>";
echo "<table border='1' cellpadding='8' style='margin-top:10px;border-collapse:collapse;'>";
echo "<tr><th>Usuario</th><th>Rol</th><th>Nivel</th></tr>";
$niveles = ['Admin'=>7, 'Gerente'=>6, 'Jefe Guardia'=>5, 'Supervisor'=>4, 'Agente'=>3, 'Tecnico'=>2, 'Operador'=>1];
foreach ($usuarios as $u) {
    echo "<tr><td>{$u['usuario']}</td><td>{$u['rol']}</td><td>{$niveles[$u['rol']]}</td></tr>";
}
echo "</table>";
?>