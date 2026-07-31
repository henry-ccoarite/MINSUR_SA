<?php
// config/db_mongo.php - Conexión a MongoDB con Composer

// Cargar autoload de Composer
require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

define('MONGO_URI', 'mongodb://localhost:27017');
define('MONGO_DB', 'ortiz_inmobiliaria');

function getMongoDB() {
    static $db = null;
    if ($db === null) {
        try {
            $client = new Client(MONGO_URI);
            $db = $client->selectDatabase(MONGO_DB);
        } catch (Exception $e) {
            die('Error MongoDB: ' . $e->getMessage());
        }
    }
    return $db;
}

function toObjectId($id) {
    if (is_string($id) && !empty($id)) {
        try {
            return new ObjectId($id);
        } catch (Exception $e) {
            return null;
        }
    }
    return $id;
}

function getId($doc) {
    return isset($doc['_id']) ? (string)$doc['_id'] : null;
}

function formatDateOnly($timestamp) {
    if ($timestamp instanceof UTCDateTime) {
        return date('d/m/Y', $timestamp->toDateTime()->getTimestamp());
    }
    return $timestamp;
}

function getUserByCredentials($usuario, $password) {
    $db = getMongoDB();
    $hash = md5($password);
    return $db->selectCollection('empleados')->findOne([
        'usuario' => $usuario,
        'password_hash' => $hash,
        'activo' => true
    ]);
}

function countDocuments($collection, $filter = []) {
    $db = getMongoDB();
    if (!$db) return 0;
    return $db->selectCollection($collection)->countDocuments($filter);
}
?>