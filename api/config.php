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

  $path = dirname(__DIR__, 2) . '/private/agrora-config.php';
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
