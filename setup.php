<?php
/**
 * n0c0.de-dsh - Initial Setup Wizard
 * Part of n0c0.de: 100% self-hosted, zero dependencies, classless.
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

// If already configured and not an authenticated admin, redirect to dashboard
if (is_installed() && !is_admin()) {
    header('Location: index.php');
    exit;
}

$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    
    $site_title = trim($_POST['site_title'] ?? '');
    if ($site_title === '') {
        $site_title = 'n0c0.de // DSH';
    }
    
    $columns = max(1, min(3, (int)($_POST['columns'] ?? 2)));
    $require_password = !empty($_POST['require_password']);
    $password = $_POST['admin_password'] ?? '';
    $password_confirm = $_POST['admin_password_confirm'] ?? '';
    $load_samples = !empty($_POST['load_samples']);

    $password_hash = null;
    if ($require_password) {
        if (strlen($password) < 4) {
            $error = 'Admin password must be at least 4 characters long.';
        } elseif ($password !== $password_confirm) {
            $error = 'Admin passwords do not match. Please verify.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    if ($error === null) {
        $config = get_default_config();
        $config['setup_completed'] = true;
        $config['site_title'] = $site_title;
        $config['columns'] = $columns;
        $config['require_password'] = $require_password;
        $config['admin_password_hash'] = $password_hash;
        
        if ($load_samples) {
            $sampleData = get_sample_data();
            $config['items'] = $sampleData['items'];
            $config['iframes'] = $sampleData['iframes'];
        }

        if (save_config($config)) {
            login_admin();
            flash_set('Setup completed! Welcome to your server dashboard.');
            header('Location: index.php');
            exit;
        } else {
            $error = 'Failed to write configuration file. Please ensure data directory is writable.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup // n0c0.de-dsh</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <h1>n0c0.de // DSH</h1>
  <hr>

  <h2>SYSTEM // SETUP</h2>
  <p>Configure your lightweight service dashboard. Settings can be updated anytime in the admin panel.</p>

  <?php if ($error): ?>
    <blockquote>
      <strong>ERROR:</strong> <?= h($error) ?>
    </blockquote>
  <?php endif; ?>

  <form method="POST" action="setup.php">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

    <h3>DASHBOARD IDENTITY</h3>
    <p>
      <label for="site_title"><strong>Dashboard Title:</strong></label><br>
      <small>Display name shown in browser tab and dashboard</small><br>
      <input type="text" id="site_title" name="site_title" value="<?= h($_POST['site_title'] ?? 'n0c0.de // DSH') ?>" required>
    </p>

    <p>
      <label for="columns"><strong>Columns:</strong></label><br>
      <select id="columns" name="columns">
        <option value="1" <?= ($_POST['columns'] ?? '2') === '1' ? 'selected' : '' ?>>1 column</option>
        <option value="2" <?= ($_POST['columns'] ?? '2') === '2' ? 'selected' : '' ?>>2 columns</option>
        <option value="3" <?= ($_POST['columns'] ?? '2') === '3' ? 'selected' : '' ?>>3 columns</option>
      </select>
    </p>

    <hr>

    <h3>ADMIN SECURITY</h3>
    <p>Choose whether to protect the admin panel with a password. Default is enabled for server security.</p>

    <p>
      <label for="require_password">
        <input type="checkbox" id="require_password" name="require_password" value="1" <?= (!isset($_POST['site_title']) || !empty($_POST['require_password'])) ? 'checked' : '' ?> onchange="togglePassword(this.checked)">
        <strong>Require password for Admin panel</strong>
      </label>
    </p>

    <div id="password_fields">
      <p>
        <label for="admin_password"><strong>Admin Password:</strong></label><br>
        <small>At least 4 characters (hashed with bcrypt)</small><br>
        <input type="password" id="admin_password" name="admin_password" autocomplete="new-password">
      </p>

      <p>
        <label for="admin_password_confirm"><strong>Confirm Password:</strong></label><br>
        <small>Re-type the admin password</small><br>
        <input type="password" id="admin_password_confirm" name="admin_password_confirm" autocomplete="new-password">
      </p>
    </div>

    <hr>

    <h3>INITIAL CONTENT</h3>
    <p>
      <label for="load_samples">
        <input type="checkbox" id="load_samples" name="load_samples" value="1" <?= (!isset($_POST['site_title']) || !empty($_POST['load_samples'])) ? 'checked' : '' ?>>
        <strong>Populate with sample homelab services &amp; widgets</strong>
      </label><br>
      <small>Adds starter tiles (Pi-hole, Nextcloud, Portainer, Jellyfin, etc.) and a sample monitoring iframe.</small>
    </p>

    <hr>

    <p>
      <button type="submit">INITIALIZE DASHBOARD &rarr;</button>
    </p>
  </form>

  <hr>
  <p><small>[n0c0.de-dsh] &bull; 100% self-hosted &bull; zero dependencies &bull; PHP <?= PHP_VERSION ?></small></p>

  <script>
    function togglePassword(show) {
      var box = document.getElementById('password_fields');
      if (box) { box.style.display = show ? 'block' : 'none'; }
    }
    var chk = document.getElementById('require_password');
    if (chk) { togglePassword(chk.checked); }
  </script>
</body>
</html>
