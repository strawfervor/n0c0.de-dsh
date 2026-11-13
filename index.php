<?php
/**
 * n0c0.de-dsh - Self-Hosted Server Dashboard
 * Part of n0c0.de: 100% self-hosted, zero dependencies, classless.
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

// If setup is not completed, redirect to setup wizard
if (!is_installed()) {
    header('Location: setup.php');
    exit;
}

$config = load_config();
$flash = flash_get();

$items = $config['items'] ?? [];
$iframes = $config['iframes'] ?? [];

// Sort items by order
usort($items, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
usort($iframes, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

// Group items by category
$categories = $config['categories'] ?? [];
$groupedItems = [];

foreach ($categories as $cat) {
    $groupedItems[$cat] = [];
}

foreach ($items as $item) {
    $cat = !empty($item['category']) ? $item['category'] : 'Uncategorized';
    if (!isset($groupedItems[$cat])) {
        $groupedItems[$cat] = [];
    }
    $groupedItems[$cat][] = $item;
}

$activeCategories = array_keys(array_filter($groupedItems, fn($arr) => !empty($arr)));
$totalServices = count($items);
$totalIframes = count($iframes);

$columns = (int)($config['columns'] ?? 2);
if ($columns < 1 || $columns > 3) {
    $columns = 2;
}

$iframe_position = ($config['iframe_position'] ?? 'bottom') === 'top' ? 'top' : 'bottom';
$iframe_columns = (int)($config['iframe_columns'] ?? 1);
if ($iframe_columns < 1 || $iframe_columns > 3) {
    $iframe_columns = 1;
}

// Helper to render iframe section
$renderIframes = function() use ($iframes, $iframe_columns) {
    if (empty($iframes)) {
        return;
    }
?>
    <h2>WIDGETS</h2>
    <?php if ($iframe_columns > 1): ?>
      <div style="display: grid; grid-template-columns: repeat(<?= $iframe_columns ?>, minmax(0, 1fr)); gap: 1rem; margin: 1rem 0;">
        <?php foreach ($iframes as $ifm): 
          $ifmUrl = sanitize_url($ifm['url'] ?? '');
          $height = !empty($ifm['height']) ? (int)$ifm['height'] : 380;
        ?>
          <div>
            <p>
              <strong><?= h($ifm['title'] ?? 'Widget') ?></strong>
              <small>(<a href="<?= h($ifmUrl) ?>" target="_blank" rel="noopener noreferrer">open ↗</a>)</small>
            </p>
            <iframe 
              src="<?= h($ifmUrl) ?>" 
              height="<?= $height ?>" 
              width="100%"
              loading="lazy" 
              referrerpolicy="no-referrer"
            ></iframe>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <?php foreach ($iframes as $ifm): 
        $ifmUrl = sanitize_url($ifm['url'] ?? '');
        $height = !empty($ifm['height']) ? (int)$ifm['height'] : 380;
      ?>
        <p>
          <strong><?= h($ifm['title'] ?? 'Widget') ?></strong>
          <small>(<a href="<?= h($ifmUrl) ?>" target="_blank" rel="noopener noreferrer">open ↗</a>)</small>
        </p>
        <iframe 
          src="<?= h($ifmUrl) ?>" 
          height="<?= $height ?>" 
          width="100%"
          loading="lazy" 
          referrerpolicy="no-referrer"
        ></iframe>
      <?php endforeach; ?>
    <?php endif; ?>
    <hr>
<?php
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($config['site_title']) ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <h1><?= h($config['site_title']) ?></h1>
  <hr>

  <?php if ($flash): ?>
    <blockquote>
      <strong><?= h($flash['message']) ?></strong>
    </blockquote>
  <?php endif; ?>

  <?php if ($totalServices === 0 && $totalIframes === 0): ?>
    <p>No services or iframe widgets configured yet.</p>
  <?php else: ?>

    <?php if ($iframe_position === 'top') { $renderIframes(); } ?>

    <?php foreach ($activeCategories as $cat): 
        $catItems = $groupedItems[$cat] ?? [];
        if (empty($catItems)) {
            continue;
        }
    ?>
      <h2><?= h($cat) ?></h2>

      <?php if ($columns > 1): ?>
        <div style="display: grid; grid-template-columns: repeat(<?= $columns ?>, minmax(0, 1fr)); gap: 0.8rem; margin: 1rem 0;">
          <?php foreach ($catItems as $item): 
            $url = sanitize_url($item['url'] ?? '');
            $target = ($item['target'] ?? '_blank') === '_blank' ? '_blank' : '_self';
          ?>
            <blockquote style="margin: 0;">
              <a href="<?= h($url) ?>" target="<?= h($target) ?>" rel="noopener noreferrer"><strong><?= h($item['title'] ?? '') ?></strong></a>
              <?php if (!empty($item['subtitle'])): ?>
                <br><small><?= h($item['subtitle']) ?></small>
              <?php endif; ?>
            </blockquote>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <?php foreach ($catItems as $item): 
          $url = sanitize_url($item['url'] ?? '');
          $target = ($item['target'] ?? '_blank') === '_blank' ? '_blank' : '_self';
        ?>
          <blockquote>
            <a href="<?= h($url) ?>" target="<?= h($target) ?>" rel="noopener noreferrer"><strong><?= h($item['title'] ?? '') ?></strong></a>
            <?php if (!empty($item['subtitle'])): ?>
              <br><small><?= h($item['subtitle']) ?></small>
            <?php endif; ?>
          </blockquote>
        <?php endforeach; ?>
      <?php endif; ?>

      <hr>
    <?php endforeach; ?>

    <?php if ($iframe_position === 'bottom') { $renderIframes(); } ?>

  <?php endif; ?>

  <p>
    <small>
      <a href="https://n0c0.de" target="_blank" rel="noopener">n0c0.de</a> &bull;
      <a href="admin.php">[admin]</a>
      <?php if (is_admin() && is_password_required()): ?>
        &bull; <a href="logout.php">[logout]</a>
      <?php endif; ?>
    </small>
  </p>

</body>
</html>
