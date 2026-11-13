<?php
/**
 * n0c0.de-dsh - Admin Panel
 * Part of n0c0.de: 100% self-hosted, zero dependencies, classless.
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

// Redirect to setup if not installed
if (!is_installed()) {
    header('Location: setup.php');
    exit;
}

// Authentication enforcement
require_admin();

$config = load_config();
$error = null;
$editItemId = $_GET['edit_item'] ?? null;
$editIframeId = $_GET['edit_iframe'] ?? null;

// Handle Export Action
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $exportData = $config;
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="n0c0-dsh-backup-' . date('Y-m-d') . '.json"');
    echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Handle Form POST Actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // ==========================================
    // 1. SERVICE TILES ACTIONS
    // ==========================================
    if ($action === 'add_item' || $action === 'edit_item') {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $target = ($_POST['target'] ?? '_blank') === '_blank' ? '_blank' : '_self';

        if ($title === '' || $url === '') {
            $error = 'Service Title and URL are required.';
        } else {
            if (!in_array($category, $config['categories'] ?? [], true)) {
                $config['categories'][] = $category;
            }

            if ($action === 'add_item') {
                $maxOrder = 0;
                foreach ($config['items'] as $it) {
                    if (($it['order'] ?? 0) > $maxOrder) {
                        $maxOrder = $it['order'];
                    }
                }
                $config['items'][] = [
                    'id'       => 'srv_' . bin2hex(random_bytes(5)),
                    'title'    => $title,
                    'subtitle' => $subtitle,
                    'url'      => $url,
                    'category' => $category,
                    'target'   => $target,
                    'order'    => $maxOrder + 1
                ];
                flash_set("Service '{$title}' added.");
            } else {
                $id = $_POST['id'] ?? '';
                $found = false;
                foreach ($config['items'] as &$it) {
                    if ($it['id'] === $id) {
                        $it['title'] = $title;
                        $it['subtitle'] = $subtitle;
                        $it['url'] = $url;
                        $it['category'] = $category;
                        $it['target'] = $target;
                        $found = true;
                        break;
                    }
                }
                unset($it);
                flash_set($found ? "Service updated." : "Service not found.");
            }
            save_config($config);
            header('Location: admin.php#services');
            exit;
        }
    }

    elseif ($action === 'delete_item') {
        $id = $_POST['id'] ?? '';
        $config['items'] = array_values(array_filter($config['items'], fn($i) => $i['id'] !== $id));
        save_config($config);
        flash_set('Service tile deleted.');
        header('Location: admin.php#services');
        exit;
    }

    elseif ($action === 'move_item') {
        $id = $_POST['id'] ?? '';
        $direction = $_POST['direction'] ?? '';
        
        usort($config['items'], fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        $idx = -1;
        foreach ($config['items'] as $i => $it) {
            if ($it['id'] === $id) {
                $idx = $i;
                break;
            }
        }
        if ($idx !== -1) {
            if ($direction === 'up' && $idx > 0) {
                $temp = $config['items'][$idx]['order'];
                $config['items'][$idx]['order'] = $config['items'][$idx - 1]['order'];
                $config['items'][$idx - 1]['order'] = $temp;
            } elseif ($direction === 'down' && $idx < count($config['items']) - 1) {
                $temp = $config['items'][$idx]['order'];
                $config['items'][$idx]['order'] = $config['items'][$idx + 1]['order'];
                $config['items'][$idx + 1]['order'] = $temp;
            }
            save_config($config);
        }
        header('Location: admin.php#services');
        exit;
    }

    // ==========================================
    // 2. IFRAME WIDGETS ACTIONS
    // ==========================================
    elseif ($action === 'add_iframe' || $action === 'edit_iframe') {
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $category = trim($_POST['category'] ?? 'Monitoring');
        $height = max(100, min(2000, (int)($_POST['height'] ?? 400)));

        if ($title === '' || $url === '') {
            $error = 'Iframe Title and URL are required.';
        } else {
            if (!in_array($category, $config['categories'] ?? [], true)) {
                $config['categories'][] = $category;
            }

            if ($action === 'add_iframe') {
                $maxOrder = 0;
                foreach ($config['iframes'] as $ifm) {
                    if (($ifm['order'] ?? 0) > $maxOrder) {
                        $maxOrder = $ifm['order'];
                    }
                }
                $config['iframes'][] = [
                    'id'       => 'ifm_' . bin2hex(random_bytes(5)),
                    'title'    => $title,
                    'url'      => $url,
                    'category' => $category,
                    'height'   => $height,
                    'order'    => $maxOrder + 1
                ];
                flash_set("Iframe '{$title}' added.");
            } else {
                $id = $_POST['id'] ?? '';
                $found = false;
                foreach ($config['iframes'] as &$ifm) {
                    if ($ifm['id'] === $id) {
                        $ifm['title'] = $title;
                        $ifm['url'] = $url;
                        $ifm['category'] = $category;
                        $ifm['height'] = $height;
                        $found = true;
                        break;
                    }
                }
                unset($ifm);
                flash_set($found ? "Iframe widget updated." : "Iframe not found.");
            }
            save_config($config);
            header('Location: admin.php#iframes');
            exit;
        }
    }

    elseif ($action === 'delete_iframe') {
        $id = $_POST['id'] ?? '';
        $config['iframes'] = array_values(array_filter($config['iframes'], fn($i) => $i['id'] !== $id));
        save_config($config);
        flash_set('Iframe widget deleted.');
        header('Location: admin.php#iframes');
        exit;
    }

    elseif ($action === 'move_iframe') {
        $id = $_POST['id'] ?? '';
        $direction = $_POST['direction'] ?? '';
        
        usort($config['iframes'], fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        $idx = -1;
        foreach ($config['iframes'] as $i => $it) {
            if ($it['id'] === $id) {
                $idx = $i;
                break;
            }
        }
        if ($idx !== -1) {
            if ($direction === 'up' && $idx > 0) {
                $temp = $config['iframes'][$idx]['order'];
                $config['iframes'][$idx]['order'] = $config['iframes'][$idx - 1]['order'];
                $config['iframes'][$idx - 1]['order'] = $temp;
            } elseif ($direction === 'down' && $idx < count($config['iframes']) - 1) {
                $temp = $config['iframes'][$idx]['order'];
                $config['iframes'][$idx]['order'] = $config['iframes'][$idx + 1]['order'];
                $config['iframes'][$idx + 1]['order'] = $temp;
            }
            save_config($config);
        }
        header('Location: admin.php#iframes');
        exit;
    }

    // ==========================================
    // 3. CATEGORIES MANAGEMENT
    // ==========================================
    elseif ($action === 'add_category') {
        $cat = trim($_POST['category_name'] ?? '');
        if ($cat !== '' && !in_array($cat, $config['categories'] ?? [], true)) {
            $config['categories'][] = $cat;
            save_config($config);
            flash_set("Category '{$cat}' added.");
        }
        header('Location: admin.php#categories');
        exit;
    }

    elseif ($action === 'delete_category') {
        $cat = trim($_POST['category_name'] ?? '');
        $config['categories'] = array_values(array_filter($config['categories'] ?? [], fn($c) => $c !== $cat));
        save_config($config);
        flash_set("Category '{$cat}' removed.");
        header('Location: admin.php#categories');
        exit;
    }

    // ==========================================
    // 4. GENERAL SETTINGS & SECURITY
    // ==========================================
    elseif ($action === 'save_settings') {
        $site_title = trim($_POST['site_title'] ?? '');
        $columns = max(1, min(3, (int)($_POST['columns'] ?? 2)));
        $iframe_position = ($_POST['iframe_position'] ?? 'bottom') === 'top' ? 'top' : 'bottom';
        $iframe_columns = max(1, min(3, (int)($_POST['iframe_columns'] ?? 1)));
        $require_password = !empty($_POST['require_password']);
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $new_password_confirm = $_POST['new_password_confirm'] ?? '';

        if ($site_title === '') {
            $site_title = 'n0c0.de // DSH';
        }

        $needsPasswordChange = ($new_password !== '');
        $wasPasswordRequired = !empty($config['require_password']);

        if ($wasPasswordRequired && ($needsPasswordChange || !$require_password)) {
            if (empty($config['admin_password_hash']) || !password_verify($current_password, $config['admin_password_hash'])) {
                $error = 'Current admin password incorrect. Security settings not changed.';
            }
        }

        if ($error === null && $needsPasswordChange) {
            if (strlen($new_password) < 4) {
                $error = 'New password must be at least 4 characters long.';
            } elseif ($new_password !== $new_password_confirm) {
                $error = 'New passwords do not match.';
            } else {
                $config['admin_password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
                $require_password = true;
            }
        }

        if ($error === null && $require_password && empty($config['admin_password_hash']) && !$needsPasswordChange) {
            $error = 'Please set an admin password before enabling password protection.';
        }

        if ($error === null) {
            $config['site_title'] = $site_title;
            $config['columns'] = $columns;
            $config['iframe_position'] = $iframe_position;
            $config['iframe_columns'] = $iframe_columns;
            $config['require_password'] = $require_password;
            save_config($config);
            flash_set('General settings updated successfully.');
            header('Location: admin.php#settings');
            exit;
        }
    }

    // ==========================================
    // 5. BACKUP & RESTORE ACTIONS
    // ==========================================
    elseif ($action === 'import_json') {
        if (!empty($_FILES['backup_file']['tmp_name'])) {
            $content = file_get_contents($_FILES['backup_file']['tmp_name']);
            $parsed = json_decode($content, true);
            if (is_array($parsed) && isset($parsed['items']) && isset($parsed['categories'])) {
                if (empty($parsed['admin_password_hash']) && !empty($config['admin_password_hash'])) {
                    $parsed['admin_password_hash'] = $config['admin_password_hash'];
                    $parsed['require_password'] = $config['require_password'];
                }
                $parsed['setup_completed'] = true;
                save_config($parsed);
                flash_set('Configuration successfully restored from backup.');
                header('Location: admin.php');
                exit;
            } else {
                $error = 'Invalid backup JSON file format.';
            }
        } else {
            $error = 'Please select a valid JSON backup file to import.';
        }
    }

    elseif ($action === 'reset_demo') {
        $samples = get_sample_data();
        $config['items'] = $samples['items'];
        $config['iframes'] = $samples['iframes'];
        $config['categories'] = ['Core', 'Network', 'Media', 'Monitoring', 'Tools'];
        save_config($config);
        flash_set('Dashboard reset to sample homelab configuration.');
        header('Location: admin.php');
        exit;
    }
}

// Find item being edited if requested
$editingItem = null;
if ($editItemId) {
    foreach ($config['items'] ?? [] as $it) {
        if ($it['id'] === $editItemId) {
            $editingItem = $it;
            break;
        }
    }
}

// Find iframe being edited if requested
$editingIframe = null;
if ($editIframeId) {
    foreach ($config['iframes'] ?? [] as $ifm) {
        if ($ifm['id'] === $editIframeId) {
            $editingIframe = $ifm;
            break;
        }
    }
}

$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin // <?= h($config['site_title']) ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <h1><?= h($config['site_title']) ?></h1>
  <p>System Administration &bull; <?= is_password_required() ? 'Password Protected' : 'Open Access Mode' ?></p>

  <p>
    <a href="index.php">&larr; Dashboard</a> &bull;
    <a href="#services">[Services]</a> &bull;
    <a href="#iframes">[Iframes]</a> &bull;
    <a href="#categories">[Categories]</a> &bull;
    <a href="#settings">[Settings]</a> &bull;
    <a href="#backup">[Backup]</a>
    <?php if (is_password_required()): ?>
      &bull; <a href="logout.php">[LOGOUT]</a>
    <?php endif; ?>
  </p>
  <hr>

  <?php if ($flash): ?>
    <blockquote>
      <strong><?= h($flash['message']) ?></strong>
    </blockquote>
  <?php endif; ?>

  <?php if ($error): ?>
    <blockquote>
      <strong>ERROR:</strong> <?= h($error) ?>
    </blockquote>
  <?php endif; ?>

  <!-- 1. SERVICE TILES -->
  <h2 id="services">SERVICE TILES</h2>
  <p>Manage direct link tiles to your server services.</p>

  <h3><?= $editingItem ? 'EDIT SERVICE: ' . h($editingItem['title']) : '+ ADD NEW SERVICE TILE' ?></h3>
  <form method="POST" action="admin.php">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $editingItem ? 'edit_item' : 'add_item' ?>">
    <?php if ($editingItem): ?>
      <input type="hidden" name="id" value="<?= h($editingItem['id']) ?>">
    <?php endif; ?>

    <p>
      <label for="item_title"><strong>Service Title *:</strong></label><br>
      <input type="text" id="item_title" name="title" value="<?= h($editingItem['title'] ?? '') ?>" required placeholder="e.g. Pi-hole">
    </p>

    <p>
      <label for="item_subtitle"><strong>Subtitle / Note:</strong></label><br>
      <small>Optional (e.g. 192.168.1.5:8080 or port/LAN IP)</small><br>
      <input type="text" id="item_subtitle" name="subtitle" value="<?= h($editingItem['subtitle'] ?? '') ?>" placeholder="e.g. 192.168.1.5:8080">
    </p>

    <p>
      <label for="item_url"><strong>Service URL *:</strong></label><br>
      <input type="text" id="item_url" name="url" value="<?= h($editingItem['url'] ?? '') ?>" required placeholder="http://192.168.1.5:8080">
    </p>

    <p>
      <label for="item_category"><strong>Category:</strong></label><br>
      <input list="category_options" id="item_category" name="category" value="<?= h($editingItem['category'] ?? 'Core') ?>"><br>
      <datalist id="category_options">
        <?php foreach ($config['categories'] ?? [] as $cat): ?>
          <option value="<?= h($cat) ?>">
        <?php endforeach; ?>
      </datalist>
    </p>

    <p>
      <label for="item_target">
        <input type="checkbox" id="item_target" name="target" value="_blank" <?= ($editingItem['target'] ?? '_blank') === '_blank' ? 'checked' : '' ?>>
        <strong>Open link in a new browser tab (target="_blank")</strong>
      </label>
    </p>

    <p>
      <button type="submit"><?= $editingItem ? 'SAVE CHANGES' : 'CREATE TILE' ?></button>
      <?php if ($editingItem): ?>
        <a href="admin.php#services">[CANCEL]</a>
      <?php endif; ?>
    </p>
  </form>

  <h3>EXISTING SERVICES</h3>
  <?php if (empty($config['items'])): ?>
    <p><em>No service tiles configured yet.</em></p>
  <?php else: ?>
    <ul>
      <?php 
      $items = $config['items'];
      usort($items, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
      foreach ($items as $idx => $it): 
      ?>
        <li>
          <a href="<?= h($it['url']) ?>" target="_blank"><strong><?= h($it['title']) ?></strong></a>
          <?php if (!empty($it['subtitle'])): ?>
            <small>(<?= h($it['subtitle']) ?>)</small>
          <?php endif; ?>
          (<code><?= h($it['category'] ?? 'General') ?></code> &bull; <code><?= h($it['url']) ?></code>)
          &bull; <a href="admin.php?edit_item=<?= urlencode($it['id']) ?>#services">[EDIT]</a>
          
          <form method="POST" action="admin.php" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="move_item">
            <input type="hidden" name="id" value="<?= h($it['id']) ?>">
            <input type="hidden" name="direction" value="up">
            <button type="submit" <?= $idx === 0 ? 'disabled' : '' ?>>[&uarr; UP]</button>
          </form>

          <form method="POST" action="admin.php" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="move_item">
            <input type="hidden" name="id" value="<?= h($it['id']) ?>">
            <input type="hidden" name="direction" value="down">
            <button type="submit" <?= $idx === count($items) - 1 ? 'disabled' : '' ?>>[&darr; DOWN]</button>
          </form>

          <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('Delete this tile?');">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete_item">
            <input type="hidden" name="id" value="<?= h($it['id']) ?>">
            <button type="submit">[DEL]</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <hr>

  <!-- 2. IFRAME WIDGETS -->
  <h2 id="iframes">IFRAME WIDGETS</h2>
  <p>Embed live web interfaces directly into the dashboard (Netdata, Grafana, Weather, Ping).</p>

  <h3><?= $editingIframe ? 'EDIT IFRAME: ' . h($editingIframe['title']) : '+ ADD NEW IFRAME WIDGET' ?></h3>
  <form method="POST" action="admin.php">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $editingIframe ? 'edit_iframe' : 'add_iframe' ?>">
    <?php if ($editingIframe): ?>
      <input type="hidden" name="id" value="<?= h($editingIframe['id']) ?>">
    <?php endif; ?>

    <p>
      <label for="ifm_title"><strong>Iframe Title *:</strong></label><br>
      <input type="text" id="ifm_title" name="title" value="<?= h($editingIframe['title'] ?? '') ?>" required placeholder="e.g. Netdata Metrics">
    </p>

    <p>
      <label for="ifm_url"><strong>Source URL *:</strong></label><br>
      <input type="text" id="ifm_url" name="url" value="<?= h($editingIframe['url'] ?? '') ?>" required placeholder="http://192.168.1.5:19999">
    </p>

    <p>
      <label for="ifm_category"><strong>Category:</strong></label><br>
      <input list="category_options" id="ifm_category" name="category" value="<?= h($editingIframe['category'] ?? 'Monitoring') ?>">
    </p>

    <p>
      <label for="ifm_height"><strong>Height (pixels):</strong></label><br>
      <input type="number" id="ifm_height" name="height" value="<?= (int)($editingIframe['height'] ?? 400) ?>" min="100" max="2000" step="10">
    </p>

    <p>
      <button type="submit"><?= $editingIframe ? 'SAVE IFRAME' : 'CREATE IFRAME' ?></button>
      <?php if ($editingIframe): ?>
        <a href="admin.php#iframes">[CANCEL]</a>
      <?php endif; ?>
    </p>
  </form>

  <h3>EXISTING IFRAMES</h3>
  <?php if (empty($config['iframes'])): ?>
    <p><em>No iframe widgets configured yet.</em></p>
  <?php else: ?>
    <ul>
      <?php 
      $iframes = $config['iframes'];
      usort($iframes, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
      foreach ($iframes as $idx => $ifm): 
      ?>
        <li>
          <strong><?= h($ifm['title']) ?></strong>
          (<code><?= h($ifm['category'] ?? 'Monitoring') ?></code> &bull; <code><?= h($ifm['url']) ?></code> &bull; <?= (int)($ifm['height'] ?? 400) ?>px)
          <br>
          <a href="admin.php?edit_iframe=<?= urlencode($ifm['id']) ?>#iframes">[EDIT]</a>
          
          <form method="POST" action="admin.php" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="move_iframe">
            <input type="hidden" name="id" value="<?= h($ifm['id']) ?>">
            <input type="hidden" name="direction" value="up">
            <button type="submit" <?= $idx === 0 ? 'disabled' : '' ?>>[&uarr; UP]</button>
          </form>

          <form method="POST" action="admin.php" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="move_iframe">
            <input type="hidden" name="id" value="<?= h($ifm['id']) ?>">
            <input type="hidden" name="direction" value="down">
            <button type="submit" <?= $idx === count($iframes) - 1 ? 'disabled' : '' ?>>[&darr; DOWN]</button>
          </form>

          <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('Delete this iframe?');">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete_iframe">
            <input type="hidden" name="id" value="<?= h($ifm['id']) ?>">
            <button type="submit">[DEL]</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <hr>

  <!-- 3. CATEGORIES -->
  <h2 id="categories">CATEGORIES</h2>
  <p>Organize services into custom sections.</p>

  <form method="POST" action="admin.php">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="add_category">
    <p>
      <input type="text" name="category_name" placeholder="New category name" required>
      <button type="submit">ADD CATEGORY</button>
    </p>
  </form>

  <p>
    <strong>Current categories:</strong>
  </p>
  <ul>
    <?php foreach ($config['categories'] ?? [] as $c): ?>
      <li>
        <code><?= h($c) ?></code>
        <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('Remove category <?= h($c) ?>?');">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="action" value="delete_category">
          <input type="hidden" name="category_name" value="<?= h($c) ?>">
          <button type="submit">[DELETE]</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>

  <hr>

  <!-- 4. SETTINGS & SECURITY -->
  <h2 id="settings">SETTINGS &amp; SECURITY</h2>

  <form method="POST" action="admin.php">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="save_settings">

    <h3>BRANDING</h3>
    <p>
      <label for="settings_title"><strong>Dashboard Title:</strong></label><br>
      <input type="text" id="settings_title" name="site_title" value="<?= h($config['site_title'] ?? '') ?>" required>
    </p>

    <h3>LAYOUT</h3>
    <p>
      <label for="settings_columns"><strong>Tiles Columns:</strong></label><br>
      <select id="settings_columns" name="columns">
        <option value="1" <?= ($config['columns'] ?? 2) === 1 ? 'selected' : '' ?>>1 column</option>
        <option value="2" <?= ($config['columns'] ?? 2) === 2 ? 'selected' : '' ?>>2 columns</option>
        <option value="3" <?= ($config['columns'] ?? 2) === 3 ? 'selected' : '' ?>>3 columns</option>
      </select>
    </p>

    <p>
      <label for="settings_iframe_position"><strong>Iframe Position:</strong></label><br>
      <select id="settings_iframe_position" name="iframe_position">
        <option value="top" <?= ($config['iframe_position'] ?? 'bottom') === 'top' ? 'selected' : '' ?>>Top of dashboard (above services)</option>
        <option value="bottom" <?= ($config['iframe_position'] ?? 'bottom') === 'bottom' ? 'selected' : '' ?>>Bottom of dashboard (below services)</option>
      </select>
    </p>

    <p>
      <label for="settings_iframe_columns"><strong>Iframe Columns:</strong></label><br>
      <select id="settings_iframe_columns" name="iframe_columns">
        <option value="1" <?= ($config['iframe_columns'] ?? 1) === 1 ? 'selected' : '' ?>>1 column (full width)</option>
        <option value="2" <?= ($config['iframe_columns'] ?? 1) === 2 ? 'selected' : '' ?>>2 columns</option>
        <option value="3" <?= ($config['iframe_columns'] ?? 1) === 3 ? 'selected' : '' ?>>3 columns</option>
      </select>
    </p>

    <h3>ADMIN SECURITY</h3>
    <p>
      <label for="settings_require_password">
        <input type="checkbox" id="settings_require_password" name="require_password" value="1" <?= is_password_required() ? 'checked' : '' ?>>
        <strong>Require Password to Access Admin Panel</strong>
      </label>
    </p>

    <?php if (is_password_required()): ?>
      <p>
        <label for="current_password"><strong>Current Admin Password:</strong></label><br>
        <small>Required to change password or disable password protection</small><br>
        <input type="password" id="current_password" name="current_password" autocomplete="current-password">
      </p>
    <?php endif; ?>

    <p>
      <label for="new_password"><strong>New Admin Password:</strong></label><br>
      <small>Leave blank to keep existing password</small><br>
      <input type="password" id="new_password" name="new_password" autocomplete="new-password">
    </p>

    <p>
      <label for="new_password_confirm"><strong>Confirm New Password:</strong></label><br>
      <input type="password" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password">
    </p>

    <p>
      <button type="submit">SAVE SETTINGS &rarr;</button>
    </p>
  </form>

  <hr>

  <!-- 5. BACKUP & RESTORE -->
  <h2 id="backup">BACKUP &amp; RESTORE</h2>

  <h3>EXPORT CONFIGURATION</h3>
  <p>
    <a href="admin.php?action=export"><strong>[ DOWNLOAD JSON BACKUP &darr; ]</strong></a>
  </p>

  <h3>IMPORT / RESTORE</h3>
  <form method="POST" action="admin.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="import_json">

    <p>
      <label for="backup_file"><strong>Select JSON Backup File:</strong></label><br>
      <input type="file" id="backup_file" name="backup_file" accept=".json,application/json" required>
    </p>

    <p>
      <button type="submit">RESTORE FROM JSON &rarr;</button>
    </p>
  </form>

  <h3>RESET TO DEMO</h3>
  <p>Reload default homelab demo services and widgets.</p>
  <form method="POST" action="admin.php" onsubmit="return confirm('This will replace your current services with sample demo items. Continue?');">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="reset_demo">
    <p>
      <button type="submit">RESET TO DEMO SERVICES</button>
    </p>
  </form>

  <hr>
  <p><small>[n0c0.de-dsh] &bull; 100% self-hosted &bull; zero dependencies &bull; <a href="index.php">View Dashboard &rarr;</a></small></p>

</body>
</html>
