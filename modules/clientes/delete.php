<?php
// modules/clientes/delete.php - Eliminar cliente con MongoDB
require_once __DIR__ . '/../../config/db_mongo.php';
require_once __DIR__ . '/../../config/auth.php';

if (!isAdmin()) {
    header('Location: ' . BASE . '/modules/clientes/index.php');
    exit;
}

$id = $_GET['id'] ?? null;
$db = getMongoDB();
$collection = $db->selectCollection('clientes');

if ($id) {
    $result = $collection->deleteOne(['_id' => toObjectId($id)]);
    if ($result->getDeletedCount() > 0) {
        flash('✅ Cliente eliminado correctamente.', 'success');
    } else {
        flash('❌ Cliente no encontrado.', 'error');
    }
}

header('Location: ' . BASE . '/modules/clientes/index.php');
exit;
?>