<?php
session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

require_once '../src/Ruxla/Drive/EnvReader.php';
\Ruxla\Drive\EnvReader::load(__DIR__ . '/.env');

$db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));

if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header("Location: login.php"); exit;
}

$userSession = [
    'id' => $_SESSION['user_id'] ?? null,
    'role' => $_SESSION['role'] ?? null
];