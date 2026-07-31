<?php
$host = 'localhost';
$user = 'bpuma_grup02';
$pass = '5&Kii23sds';
$db   = 'bpuma_grup02';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("❌ Error BD: " . $conn->connect_error);
} else {
    echo "✅ Conexión OK<br>";
    
    $r = $conn->query("SELECT COUNT(*) as total FROM empleados");
    $f = $r->fetch_assoc();
    echo "Total empleados: " . $f['total'];
}
?>