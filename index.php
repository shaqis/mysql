<?php
// Move headers before any output
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
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none';");
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: interest-cohort=()');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login to Export MySQL to CSV</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { max-width: 400px; margin: 60px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 8px 0 16px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: #d00; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Login to Export CSV</h2>
    <?php if (isset($_GET['error'])): ?>
        <div class="error">
            <?php
            // Validate error parameter and encode output
            $error = $_GET['error'];
            if ($error === 'csrf') {
                echo htmlspecialchars('Invalid CSRF token.', ENT_QUOTES, 'UTF-8');
            } else {
                echo htmlspecialchars('Invalid username or password.', ENT_QUOTES, 'UTF-8');
            }
            ?>
        </div>
    <?php endif; ?>
    <form method="post" action="login.php">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
