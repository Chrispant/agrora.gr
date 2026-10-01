<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: /api/admin/admin-login.php');
exit;