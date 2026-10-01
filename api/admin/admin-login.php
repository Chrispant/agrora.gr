<?php
require_once __DIR__ . '/admin-auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $password = (string)($_POST['password'] ?? '');

  if (password_verify($password, admin_password_hash())) {
    session_regenerate_id(true);
    $_SESSION['agrora_admin_ok'] = 1;
    header('Location: /admin.php');
    exit;
  } else {
    $error = 'Λάθος κωδικός.';
  }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="page-layout">
  <main class="page-content">
    <div class="container" style="max-width:420px;">
      <section class="panel" style="padding:24px; margin-top:40px;">
        <h1 style="margin-top:0;">Admin Login</h1>
        <p style="color:#555;">Δώσε τον κωδικό διαχείρισης.</p>

        <?php if ($error): ?>
          <div style="color:crimson; margin-bottom:12px;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <form method="post" action="/api/admin/admin-login.php">
          <div class="form-group">
            <label for="password">Κωδικός</label>
            <input id="password" name="password" type="password" required autofocus>
          </div>

          <button class="search-button" type="submit" style="width:100%;">
            Είσοδος
          </button>
        </form>
      </section>
    </div>
  </main>
</body>
</html>