<?php
// Usage: php make_hash.php your_password
if ($argc !== 2) {
    echo htmlspecialchars("Usage: php make_hash.php your_password\n", ENT_QUOTES, 'UTF-8');
    exit(1);
}
$password = $argv[1];
if (!is_string($password) || strlen($password) < 6 || strlen($password) > 128) {
    echo htmlspecialchars("Password must be 6-128 characters.\n", ENT_QUOTES, 'UTF-8');
    exit(1);
}
echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
