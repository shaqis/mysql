<?php
// Usage: php make_hash.php your_password
if ($argc !== 2) {
    echo "Usage: php make_hash.php your_password\n";
    exit(1);
}
$password = $argv[1];
echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
