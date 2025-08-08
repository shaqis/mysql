<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? '';
$user = $_ENV['DB_USER'] ?? '';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

// Allowlist of tables (comma-separated) and selected table
$allowedTables = array_filter(array_map('trim', explode(',', $_ENV['EXPORT_TABLES'] ?? '')));
$table = $_ENV['EXPORT_TABLE'] ?? 'your_table';
if (!empty($allowedTables) && !in_array($table, $allowedTables, true)) {
    error_log('Attempt to export table not in allowlist: ' . $table);
    http_response_code(403);
    echo 'Not authorized for this table.';
    exit;
}

$filename = 'export_' . $table . '_' . date('Ymd_His') . '.csv';

// Secure session start (reuse settings from login ideally)
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
} else {
    session_start();
}

// Require authenticated session
if (!($_SESSION['logged_in'] ?? false)) {
    header('Location: index.php');
    exit;
}
// Fingerprint validation (optional)
if (isset($_SESSION['fingerprint'])) {
    $expected = $_SESSION['fingerprint'];
    $current = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    if (!hash_equals($expected, $current)) {
        session_unset();
        session_destroy();
        http_response_code(403);
        echo 'Session invalid.';
        exit;
    }
}

// Idle timeout 30m
if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
$_SESSION['last_activity'] = time();

// Regenerate periodically
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// CSRF: require POST with token to trigger export; if GET, show minimal confirmation form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Confirm Export</title></head><body>';
    echo '<form method="post" action="export.php">';
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
    echo '<button type="submit">Download CSV</button>';
    echo '</form></body></html>';
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    http_response_code(400);
    echo 'Invalid CSRF token.';
    exit;
}
// One-time use token (optional)
unset($_SESSION['csrf_token']);

try {
    if (empty($host) || empty($db) || empty($user) || empty($pass) || empty($table)) {
        error_log('Missing required environment variables for database connection or table name.');
        echo 'Configuration error.';
        exit;
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        error_log('Invalid table name.');
        echo 'Invalid table name.';
        exit;
    }

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false, // stream large result
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);

    $stmt = $pdo->prepare("SELECT * FROM `$table`");
    $stmt->execute();

    // Security headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header("Content-Security-Policy: default-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none';");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: interest-cohort=()');

    $output = fopen('php://output', 'w');

    // Enhanced CSV injection mitigation
    function sanitize_csv_field($v) {
        if (!is_scalar($v)) return $v; // skip objects/arrays
        $v = (string)$v;
        $trimmed = ltrim($v);
        if ($trimmed !== '' && preg_match('/^[=+\-@]/', $trimmed)) {
            return "'" . $v;
        }
        if ($v !== '' && (ord($v[0]) === 0x09 || ord($v[0]) === 0x0D || ord($v[0]) === 0x0A)) {
            return "'" . $v;
        }
        return $v;
    }
    function sanitize_csv_row($row) {
        return array_map('sanitize_csv_field', $row);
    }

    // Output column headings and data streaming
    $firstRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($firstRow) {
        fputcsv($output, sanitize_csv_row(array_keys($firstRow)));
        fputcsv($output, sanitize_csv_row($firstRow));
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, sanitize_csv_row($row));
    }
    fclose($output);
    exit;
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo 'An error occurred.';
    exit;
}
?>
