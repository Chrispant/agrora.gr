<?php
// Copy to ../private/agrora-config.php, outside the public document root.
// Replace every placeholder locally. Never commit the completed file.
return [
    'db_host' => 'localhost',
    'db_name' => 'agrora_dev',
    'db_user' => 'YOUR_LOCAL_DATABASE_USER',
    'db_pass' => 'YOUR_LOCAL_DATABASE_PASSWORD',
    'db_charset' => 'utf8mb4',
    // Generate with PHP password_hash($password, PASSWORD_DEFAULT).
    'admin_password_hash' => 'YOUR_GENERATED_PASSWORD_HASH',
];
