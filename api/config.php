<?php
// The private directory is a sibling of the public agrora.gr directory.
function agrora_configuration_error(): void {
  error_log('Agrora private configuration is missing, unreadable, or invalid.');
  http_response_code(503);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'Service temporarily unavailable']);
  exit;
}

function agrora_config(): array {
  static $config = null;
  if ($config !== null) {
    return $config;
  }

  // Use the on-disk deployment folder, never the request's Host header.
  // Unknown deployments must not silently connect to the live database.
  $configFiles = [
    'agrora.gr' => 'agrora-config.php',
    'elegant-visvesvaraya.213-158-90-234.plesk.page' => 'agrora-test-config.php',
  ];
  $siteFolder = basename(dirname(__DIR__));
  if (!isset($configFiles[$siteFolder])) {
    agrora_configuration_error();
  }
  $path = dirname(__DIR__, 2) . '/private/' . $configFiles[$siteFolder];
  if (!is_readable($path)) {
    agrora_configuration_error();
  }

  try {
    $loaded = require $path;
  } catch (Throwable $e) {
    agrora_configuration_error();
  }

  if (!is_array($loaded)) {
    agrora_configuration_error();
  }
  $config = $loaded;
  return $config;
}

function agrora_config_string(string $key): string {
  $config = agrora_config();
  $value = $config[$key] ?? null;
  if (!is_string($value) || $value === '') {
    agrora_configuration_error();
  }
  return $value;
}
