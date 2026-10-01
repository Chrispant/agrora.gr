<?php
// api/db.php

require_once __DIR__ . '/config.php';

$host = agrora_config_string('db_host');
$db = agrora_config_string('db_name');
$user = agrora_config_string('db_user');
$pass = agrora_config_string('db_pass');
$charset = agrora_config_string('db_charset');
$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  error_log("Agrora DB connection failed: " . $e->getMessage());

  http_response_code(500);
  echo json_encode([
    "ok" => false,
    "error" => "Database connection failed"
  ], JSON_UNESCAPED_UNICODE);
  exit;
}