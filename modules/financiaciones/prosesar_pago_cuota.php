<?php
// 1. Incluimos tu conexión global preestablecida
require_once '../../config/db.php'; 

// 2. Verificamos que la petición venga de hacer clic en el botón "Pagar"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Capturamos el ID de la cuota y el método de pago seleccionado
    $cuota_id = isset($_POST['cuota_id']) ? intval($_POST['cuota_id']) : 0;
    $metodo   = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : '';

    if ($cuota_id > 0 && !empty($metodo)) {
        try {
            // Instanciar la conexión PDO usando tus constantes configuradas en config/db.php
            $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Preparamos la llamada a tu SP en MySQL
            $stmt = $pdo->prepare("CALL sp_pagar_cuota(?, ?)");
            
            // Ejecutamos pasándole (p_cuota_id, p_metodo)
            $stmt->execute([$cuota_id, $metodo]);

            // Redireccionamos de vuelta a la pantalla de financiaciones con un mensaje de éxito
            echo "<script>
                    alert('¡Pago registrado correctamente! La cuota pasó a estado PAGADA.');
                    window.location.href = '" . $_SERVER['HTTP_REFERER'] . "';
                  </script>";

        } catch (PDOException $e) {
            // Si la base de datos restringe algo, salta el error aquí de inmediato
            echo "<script>
                    alert('Error en el proceso de pago: " . addslashes($e->getMessage()) . "');
                    window.history.back();
                  </script>";
        }
    } else {
        echo "<script>
                alert('Datos inválidos para procesar el pago.');
                window.history.back();
              </script>";
    }
} else {
    // Si intentan entrar directo al archivo sin usar el formulario, los mandamos fuera
    header("Location: index.php");
    exit;
}
?>