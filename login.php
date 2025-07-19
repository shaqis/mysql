<?php
session_start();
// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$valid_user = $_ENV['APP_USER'] ?? '';
$valid_hash = $_ENV['APP_PASS_HASH'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        header('Location: index.php?error=csrf');
        exit;
    }
    if (hash_equals($valid_user, $username) && password_verify($password, $valid_hash)) {
        $_SESSION['logged_in'] = true;
        header('Location: export.php');
        exit;
    } else {
        header('Location: index.php?error=1');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>
