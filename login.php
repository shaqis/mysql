<?php
// Enforce PHP version 8.1 or above
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    http_response_code(500);
    echo "Error: PHP 8.1 or higher is required.";
    exit;
}

// Secure session initialization with strict mode and cookie flags
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$valid_user = $_ENV['APP_USER'] ?? '';
$valid_hash = $_ENV['APP_PASS_HASH'] ?? '';

// Ensure APP_PASS_HASH is a valid bcrypt hash (starts with $2y$ or $2a$)
if (!preg_match('/^\$2[ayb]\$/', $valid_hash)) {
    error_log('APP_PASS_HASH is not a valid bcrypt hash. Please set APP_PASS_HASH to the output of password_hash().');
    http_response_code(500);
    echo "Server misconfiguration: password hash invalid.";
    exit;
}

// Basic rate limiting (session-based)
$maxAttempts = 10;       // allow 10 attempts
$windowSeconds = 900;    // per 15 minutes
$now = time();
if (!isset($_SESSION['first_attempt_time']) || ($now - ($_SESSION['first_attempt_time'])) > $windowSeconds) {
    $_SESSION['first_attempt_time'] = $now;
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Enforce rate limit
    if ($_SESSION['login_attempts'] >= $maxAttempts) {
        // Optional: add small delay to slow automated attacks
        usleep(500000); // 0.5s
        header('Location: index.php?error=1');
        exit;
    }

    // Input validation and sanitization
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate username and password types/lengths
    if (!is_string($username) || !is_string($password) || strlen($username) > 64 || strlen($password) > 128) {
        header('Location: index.php?error=1');
        exit;
    }

    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        header('Location: index.php?error=csrf');
        exit;
    }

    if (hash_equals($valid_user, $username) && $valid_hash !== '' && password_verify($password, $valid_hash)) {
        // Successful login
        session_regenerate_id(true); // mitigate fixation
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        // Bind session to user agent fingerprint
        $_SESSION['fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
        // Reset rate limiting counters
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt_time'] = $now;
        // (Optional) Check if hash needs rehash (cannot persist automatically since stored in env)
        if (password_needs_rehash($valid_hash, PASSWORD_DEFAULT)) {
            error_log('Password hash algorithm outdated. Regenerate APP_PASS_HASH.');
        }
        header('Location: export.php');
        exit;
    } else {
        // Log failed password verification for diagnostics
        if (hash_equals($valid_user, $username) && $valid_hash !== '' && !password_verify($password, $valid_hash)) {
            error_log('Password verification failed for user: ' . $username);
        }
        // Failed attempt
        $_SESSION['login_attempts']++;
        // Generic error to avoid user enumeration
        header('Location: index.php?error=1');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>
