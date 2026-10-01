<?php
header("Content-Type: application/json; charset=utf-8");

require __DIR__ . "/db.php";

function client_ip(): string {
  return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function bad($msg, $extra = []) {
  http_response_code(400);
  echo json_encode(array_merge(["ok" => false, "error" => $msg], $extra), JSON_UNESCAPED_UNICODE);
  exit;
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

function make_public_id($crop, $region) {
  // simple slug (latin-safe). We'll keep it basic for now.
  $base = strtolower(trim($crop . "-" . $region));
  $base = preg_replace('/[^a-z0-9]+/i', '-', $base);
  $base = trim($base, '-');
  if ($base === "") $base = "listing";
  $rand = substr(bin2hex(random_bytes(3)), 0, 6);
  return $base . "-" . $rand;
}

// ---- Read fields ----
$cropType = trim($_POST["cropType"] ?? "");
$variety = trim($_POST["variety"] ?? "");
$region   = trim($_POST["region"] ?? "");
$quantityTons = $_POST["quantityTons"] ?? "";
$pricePerKg   = $_POST["pricePerKg"] ?? "";
$priceNote    = trim($_POST["priceNote"] ?? "");
$harvestStart = trim($_POST["harvestStart"] ?? "");
$harvestEnd   = trim($_POST["harvestEnd"] ?? "");
$sellerName   = trim($_POST["sellerName"] ?? "");
$sellerPhone  = trim($_POST["sellerPhone"] ?? "");
$sellerEmail  = trim($_POST["sellerEmail"] ?? "");
$description  = trim($_POST["description"] ?? "");

// ---- Validate minimal ----
if ($cropType === "") bad("Απαιτείται Καλλιέργεια");
if ($variety === "") $variety = null;
if ($region === "") bad("Απαιτείται Περιοχή");
if ($sellerName === "") bad("Απαιτείται Όνομα");

if ($sellerPhone === "") bad("Απαιτείται Τηλέφωνο");
if ($harvestStart === "") bad("Απαιτείται Αρχή Συγκομιδής");
if ($harvestEnd === "") bad("Απαιτείται Τέλος Συγκομιδής");

if (!preg_match('/^\d{4}-\d{2}$/', $harvestStart)) bad("harvestStart must be YYYY-MM");
if (!preg_match('/^\d{4}-\d{2}$/', $harvestEnd)) bad("harvestEnd must be YYYY-MM");

if ($harvestEnd < $harvestStart) bad("Το Τέλος Συγκομιδής πρέπει να είναι ίδιο ή μετά του Αρχή Συγκομιδής");

if ($quantityTons === "" || !is_numeric($quantityTons) || floatval($quantityTons) <= 0) {
  bad("Η ποσότητα τόνων πρέπει να είναι Θετικός αριθμός");
}
$quantityTons = floatval($quantityTons);

$pricePerKg = trim((string)$pricePerKg);
if ($pricePerKg === "") {
  $pricePerKgVal = null;
} else {
  if (!is_numeric($pricePerKg) || floatval($pricePerKg) < 0) bad("Η τιμή πρέπει να είναι >= 0");
  $pricePerKgVal = floatval($pricePerKg);
}

$website = trim($_POST["website"] ?? "");
$formStartedAt = (int)($_POST["formStartedAt"] ?? 0);

if ($website !== "") bad("Spam detected");

if ($formStartedAt <= 0 || ((int)round(microtime(true) * 1000) - $formStartedAt) < 2000) {
  bad("Η φόρμα υποβλήθηκε πολύ γρήγορα");
}

rate_limit_or_die("create_listing", 5, 600); // 5 submissions / 10 λεπτά / IP

// ---- Handle uploads ----
$uploadDirFs = realpath(__DIR__ . "/../uploads/listings");
if ($uploadDirFs === false) bad("Upload folder missing on server");

$maxFiles = 8;
$maxBytes = 3 * 1024 * 1024; // 3MB each
$allowed = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];

$storedPaths = [];

if (isset($_FILES["images"]) && is_array($_FILES["images"]["name"])) {
  $count = count($_FILES["images"]["name"]);
  if ($count > $maxFiles) bad("Too many images (max $maxFiles)");

  for ($i = 0; $i < $count; $i++) {
    $err = $_FILES["images"]["error"][$i];
    if ($err === UPLOAD_ERR_NO_FILE) continue;
    if ($err !== UPLOAD_ERR_OK) bad("Upload error", ["code" => $err]);

    $tmp = $_FILES["images"]["tmp_name"][$i];
    $size = (int)$_FILES["images"]["size"][$i];
    if ($size <= 0 || $size > $maxBytes) bad("Each image must be <= 3MB");

    $mime = mime_content_type($tmp);
    if (!isset($allowed[$mime])) bad("Ο τύπος εικόνας δεν υποστηρίζεται. Χρησημοποιείστε JPG, PNG, ή WEBP.");

    $ext = $allowed[$mime];
    $fileName = "l_" . date("Ymd_His") . "_" . substr(bin2hex(random_bytes(4)), 0, 8) . "." . $ext;

    $destFs = $uploadDirFs . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($tmp, $destFs)) bad("Αποτυχία αποθήκευσης εικόνας");

    // public path from web root
    $storedPaths[] = "uploads/listings/" . $fileName;
  }
}



function cleanup_uploaded_files(array $storedPaths, string $projectRootFs): void {
  foreach ($storedPaths as $relPath) {
    $relPath = ltrim(str_replace(['\\', '..'], ['/', ''], $relPath), '/');
    $fullPath = $projectRootFs . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

    if (is_file($fullPath)) {
      @unlink($fullPath);
    }
  }
}

$projectRootFs = realpath(__DIR__ . "/..");
if ($projectRootFs === false) bad("Project root missing on server");

// ---- Insert listing + images ----
$publicId = make_public_id($cropType, $region);



try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare("
 INSERT INTO listings
  (public_id, crop_type, variety, region, quantity_tons, price_per_kg, price_note,
   harvest_start, harvest_end, seller_name, seller_phone, seller_email,
   description, created_at)
VALUES
  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
  ");

  $stmt->execute([
    $publicId,
    $cropType,
    $variety,
    $region,
    $quantityTons,
    $pricePerKgVal,
    $priceNote === "" ? null : $priceNote,
    $harvestStart,
    $harvestEnd,
    $sellerName,
    $sellerPhone,
    $sellerEmail === "" ? null : $sellerEmail,
    $description === "" ? null : $description
  ]);

  $listingId = (int)$pdo->lastInsertId();

  if (!empty($storedPaths)) {
    $imgStmt = $pdo->prepare("INSERT INTO listing_images (listing_id, path, sort_order) VALUES (?, ?, ?)");
    $order = 0;
    foreach ($storedPaths as $p) {
      $imgStmt->execute([$listingId, $p, $order]);
      $order++;
    }
  }

  $pdo->commit();
} catch (Throwable $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }

  cleanup_uploaded_files($storedPaths, $projectRootFs);

  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error"], JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode(["ok" => true, "id" => $publicId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);