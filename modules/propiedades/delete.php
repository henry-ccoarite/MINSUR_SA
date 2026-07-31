<?php
// modules/propiedades/delete.php - Eliminar propiedad con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';

if (!isAdmin()) {
    header('Location: ' . BASE . '/modules/propiedades/index.php');
    exit;
}

$id = $_GET['id'] ?? null;
$db = getMongoDB();
$collection = $db->selectCollection('propiedades');

if ($id) {
    $prop = $collection->findOne(['_id' => toObjectId($id)]);
    
    if ($prop) {
        // Eliminar imagen si existe
        if (!empty($prop['imagen'])) {
            $ruta = __DIR__ . '/../../uploads/' . $prop['imagen'];
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }
        
        $collection->deleteOne(['_id' => toObjectId($id)]);
        flash('✅ Propiedad eliminada correctamente.', 'success');
    } else {
        flash('❌ Propiedad no encontrada.', 'error');
    }
}

header('Location: ' . BASE . '/modules/propiedades/index.php');
exit;
?>