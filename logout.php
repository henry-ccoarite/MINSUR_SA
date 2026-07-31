<?php
require_once __DIR__ . '/config/auth.php';
session_destroy();
header('Location: ' . BASE . '/login.php');
exit;
