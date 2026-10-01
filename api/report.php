<?php
header("Content-Type: application/json; charset=utf-8");
require __DIR__ . "/db.php";

// Expect JSON body
$body = json_decode(file_get_contents("php://input"), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Invalid JSON"]);
    exit;
}

function client_ip(): string {
  return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function rate_limit_or_die(string $action, int $limit, int $windowSeconds): void {
  $ip = client_ip();
  $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "agrora_rate_limits";

  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }

  $key = sha1($action . '|' . $ip);
  $file = $dir . DIRECTORY_SEPARATOR . $key . ".json";

  $now = time();
  $hits = [];

  if (is_file($file)) {
    $raw = file_get_contents($file);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
      $hits = $decoded;
    }
  }

  $hits = array_values(array_filter($hits, fn($ts) => is_int($ts) && ($now - $ts) < $windowSeconds));

  if (count($hits) >= $limit) {
    http_response_code(429);
    echo json_encode(["ok" => false, "error" => "Πάρα πολλές προσπάθειες. Δοκίμασε ξανά αργότερα."], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $hits[] = $now;
  file_put_contents($file, json_encode($hits), LOCK_EX);
}

$listingId = trim($body["listingId"] ?? "");
$reporterName = trim($body["name"] ?? "");
$reporterContact = trim($body["contact"] ?? "");
$description = trim($body["description"] ?? "");

if ($listingId === "" || $description === "") {
    http_response_code(422);
    echo json_encode(["ok" => false, "error" => "Missing required fields"]);
    exit;
}

if (mb_strlen($description) > 2000) {
    http_response_code(422);
    echo json_encode(["ok" => false, "error" => "Το report είναι πολύ μεγάλο"], JSON_UNESCAPED_UNICODE);
    exit;
}

$website = trim($body["website"] ?? "");
$formStartedAt = (int)($body["formStartedAt"] ?? 0);

if ($website !== "") {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Spam detected"], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($formStartedAt <= 0 || ((int)round(microtime(true) * 1000) - $formStartedAt) < 1500) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Η φόρμα υποβλήθηκε πολύ γρήγορα"], JSON_UNESCAPED_UNICODE);
    exit;
}

rate_limit_or_die("report_listing", 8, 3600); // 8 reports / ώρα / IP

// insert
$stmt = $pdo->prepare("
  INSERT INTO reports (listing_public_id, reporter_name, reporter_contact, description)
  VALUES (?, ?, ?, ?)
");
$stmt->execute([$listingId, $reporterName, $reporterContact, $description]);

echo json_encode(["ok" => true]);
