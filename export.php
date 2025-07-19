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

// Table to export - should be configured via environment variable
$table = $_ENV['EXPORT_TABLE'] ?? 'your_table';

// Output CSV file name
$filename = 'export_' . $table . '_' . date('Ymd_His') . '.csv';

// Session-based authentication check
session_start();

// Session timeout and regeneration
if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

try {
    // Validate environment variables
    if (empty($host) || empty($db) || empty($user) || empty($pass) || empty($table)) {
        error_log('Missing required environment variables for database connection or table name.');
        echo 'Configuration error. Please check the logs.';
        exit;
    }

    // Sanitize table name to prevent SQL injection if it becomes dynamic
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        error_log('Invalid table name.');
        echo 'Invalid table name.';
        exit;
    }

    // Set up PDO connection
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Query all data from the table using prepared statement
    $stmt = $pdo->prepare("SELECT * FROM `$table`");
    $stmt->execute();

    // Set headers to force download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header("Content-Security-Policy: default-src 'none'; script-src 'self'; connect-src 'self'; img-src 'self'; style-src 'self';");

    $output = fopen('php://output', 'w');

    function sanitize_csv_data($data) {
        return array_map(function ($value) {
            if (strpos($value, '=') === 0 || strpos($value, '+') === 0 || strpos($value, '-') === 0 || strpos($value, '@') === 0) {
                return "'" . $value;
            }
            return $value;
        }, $data);
    }

    // Output column headings
    $firstRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($firstRow) {
        fputcsv($output, sanitize_csv_data(array_keys($firstRow)));
        fputcsv($output, sanitize_csv_data($firstRow));
    }

    // Output the rest of the rows
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, sanitize_csv_data($row));
    }

    fclose($output);
    exit;
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo 'An error occurred. Please check the logs.';
    exit;
}
?>
