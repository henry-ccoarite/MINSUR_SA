<?php
// verificar_usuarios.php - Verificar usuarios en MongoDB
require_once __DIR__ . '/config/db_mongo.php';

$db = getMongoDB();
$empleados = $db->selectCollection('empleados');

echo "<h2>🔍 Usuarios en MongoDB</h2>";

$usuarios = $empleados->find()->toArray();

if (count($usuarios) === 0) {
    echo "❌ No hay usuarios en la base de datos<br>";
    echo "<a href='crear_admin.php'>Crear usuario admin</a>";
} else {
    echo "✅ " . count($usuarios) . " usuarios encontrados:<br><br>";
    foreach ($usuarios as $u) {
        echo "👤 Usuario: " . $u['usuario'] . "<br>";
        echo "   Nombre: " . $u['nombre'] . "<br>";
        echo "   Rol: " . $u['rol'] . "<br>";
        echo "   Hash: " . $u['password_hash'] . "<br>";
        echo "   Activo: " . ($u['activo'] ? 'Sí' : 'No') . "<br>";
        
        // Verificar contraseña
        $hash = md5('123456');
        echo "   MD5('123456') = " . $hash . "<br>";
        echo "   ¿Coincide? " . ($u['password_hash'] === $hash ? '✅ SI' : '❌ NO') . "<br>";
        echo "<hr>";
    }
}
?>