<?php
// modules/minsur/personal/eliminar.php - Eliminar personal
require_once __DIR__ . '/../../../config/db_mongo.php';
require_once __DIR__ . '/../../../config/auth.php';
requireLogin();
requireMinsurAccess();

$id = $_GET['id'] ?? null;
$db = getMongoDB();
$collection = $db->selectCollection('personal_mina');

if ($id) {
    $result = $collection->deleteOne(['_id' => toObjectId($id)]);
    if ($result->getDeletedCount() > 0) {
        flash('✅ Personal eliminado correctamente.', 'success');
    } else {
        flash('❌ Personal no encontrado.', 'error');
    }
}

header('Location: ' . BASE . '/modules/minsur/personal/');
exit;
?>