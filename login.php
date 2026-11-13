<?php
/**
 * n0c0.de-dsh - Admin Login
 * Part of n0c0.de: 100% self-hosted, zero dependencies, classless.
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (!is_installed()) {
    header('Location: setup.php');
    exit;
}

// If password is not required or already logged in, redirect directly to admin
if (!is_password_required() || is_admin()) {
    header('Location: admin.php');
    exit;
}

$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $config = load_config();

    if (!empty($config['admin_password_hash']) && password_verify($password, $config['admin_password_hash'])) {
        login_admin();
        flash_set('Authenticated successfully.');
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid admin password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login // <?= h(load_config()['site_title']) ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <h1><?= h(load_config()['site_title']) ?></h1>
  <p>Admin authentication &bull; <a href="index.php">&larr; Back to Dashboard</a></p>
  <hr>

  <h2>ADMIN // LOGIN</h2>

  <?php if ($error): ?>
    <blockquote>
      <strong>ERROR:</strong> <?= h($error) ?>
    </blockquote>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

    <p>
      <label for="password"><strong>Admin Password:</strong></label><br>
      <input type="password" id="password" name="password" required autofocus autocomplete="current-password">
    </p>

    <p>
      <button type="submit">LOGIN &rarr;</button>
    </p>
  </form>

  <hr>
  <p><small>[n0c0.de-dsh] &bull; 100% self-hosted &bull; zero dependencies</small></p>

</body>
</html>
