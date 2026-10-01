<?php
session_start();

require_once __DIR__ . '/../config.php';

function admin_password_hash(): string {
  $hash = agrora_config_string('admin_password_hash');
  if (password_get_info($hash)['algoName'] === 'unknown') {
    agrora_configuration_error();
  }
  return $hash;
}

function is_admin_logged_in(): bool {
  return !empty($_SESSION['agrora_admin_ok']);
}

function require_admin(): void {
  if (!is_admin_logged_in()) {
    header("Location: /api/admin/admin-login.php");
    exit;
  }
}

// API callers need a JSON error rather than an HTML login redirect.
function require_admin_api(): void {
  if (!is_admin_logged_in()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Authentication required']);
    exit;
  }
}
