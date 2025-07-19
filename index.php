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
<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<div class="container">
    <h2>Login to Export CSV</h2>
    <?php if (isset($_GET['error'])): ?>
        <div class="error">
            <?php
            if ($_GET['error'] === 'csrf') {
                echo 'Invalid CSRF token.';
            } else {
                echo 'Invalid username or password.';
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
