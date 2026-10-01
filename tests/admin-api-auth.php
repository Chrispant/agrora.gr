<?php
// Run: php tests/admin-api-auth.php
// Uses isolated sessions and a database stub; never connects to a real database.
$source = dirname(__DIR__) . '/api';
$temp = sys_get_temp_dir() . '/agrora-auth-' . bin2hex(random_bytes(8));
$endpoints = ['get-listing.php', 'update-listing.php', 'delete-listing.php',
              'delete-image.php', 'upload-images.php'];
mkdir($temp . '/api/admin', 0700, true);
mkdir($temp . '/sessions', 0700);
copy($source . '/config.php', $temp . '/api/config.php');
copy($source . '/admin/admin-auth.php', $temp . '/api/admin/admin-auth.php');
foreach ($endpoints as $endpoint) {
  copy($source . '/admin/' . $endpoint, $temp . '/api/admin/' . $endpoint);
}
file_put_contents($temp . '/api/db.php', <<<'PHP'
<?php
$GLOBALS['databaseReached'] = true;
http_response_code(200);
echo json_encode(['databaseReached' => true]);
exit;
PHP
);
file_put_contents($temp . '/request.php', <<<'PHP'
<?php
ini_set('session.save_path', __DIR__ . '/sessions');
session_start();
$_SESSION = [];
if ($argv[2] === 'authenticated') {
  $_SESSION['agrora_admin_ok'] = 1;
} elseif ($argv[2] === 'expired') {
  $_SESSION['agrora_admin_ok'] = 0;
}
session_write_close();
$_SERVER['REQUEST_METHOD'] = $argv[3];
if ($argv[2] === 'spoofed') {
  $_GET['agrora_admin_ok'] = 1;
  $_POST['agrora_admin_ok'] = 1;
  $_COOKIE['agrora_admin_ok'] = 1;
}
ob_start();
register_shutdown_function(function () {
  $body = ob_get_clean();
  echo json_encode([
    'status' => http_response_code(),
    'body' => json_decode($body, true),
    'databaseReached' => !empty($GLOBALS['databaseReached']),
  ]);
});
require __DIR__ . '/api/admin/' . $argv[1];
PHP
);

$checks = 0;
$failed = false;
try {
  foreach ($endpoints as $endpoint) {
    foreach (['guest', 'expired', 'spoofed', 'authenticated'] as $state) {
      foreach (['GET', 'POST'] as $method) {
        $process = proc_open(
          [PHP_BINARY, $temp . '/request.php', $endpoint, $state, $method],
          [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes
        );
        if (!is_resource($process)) {
          throw new RuntimeException('Unable to start PHP test process.');
        }
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        $result = json_decode($output, true);
        $authenticated = $state === 'authenticated';
        if ($exitCode !== 0 || $errors !== '' || !is_array($result)
            || $result['status'] !== ($authenticated ? 200 : 401)
            || $result['databaseReached'] !== $authenticated
            || (!$authenticated && ($result['body']['error'] ?? '') !== 'Authentication required')) {
          throw new RuntimeException("Failed: $endpoint $state $method\n$output\n$errors");
        }
        $checks++;
      }
    }
  }
  echo "PASS: $checks authentication checks across five admin endpoints.\n";
} catch (Throwable $e) {
  fwrite(STDERR, $e->getMessage() . "\n");
  $failed = true;
} finally {
  // Remove only this test run's randomly named temporary directory.
  $files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($temp, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
  );
  foreach ($files as $file) {
    if ($file->isDir()) {
      rmdir($file->getPathname());
    } else {
      unlink($file->getPathname());
    }
  }
  rmdir($temp);
}
exit($failed ? 1 : 0);
