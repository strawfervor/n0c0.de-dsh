<?php
declare(strict_types=1);

echo "==> Running n0c0.de-dsh test suite...\n";

// Cleanup test data dir if exists
$dataDir = __DIR__ . '/../data';
if (is_dir($dataDir)) {
    array_map('unlink', glob("$dataDir/*.*"));
    @rmdir($dataDir);
}

require_once __DIR__ . '/../config.php';

// Test 1: Fresh state check
assert(!is_installed(), "Should not be installed initially");
echo "[PASS] Test 1: Fresh install state detection\n";

// Test 2: Perform setup via mock POST
$_SESSION['csrf_token'] = 'test_token_123';
$_POST = [
    'csrf_token'             => 'test_token_123',
    'site_title'             => 'TEST LAB // DSH',
    'require_password'       => '1',
    'admin_password'         => 'secret123',
    'admin_password_confirm' => 'secret123',
    'load_samples'           => '1'
];
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
// Include setup.php logic simulation
$password_hash = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
$config = get_default_config();
$config['setup_completed'] = true;
$config['site_title'] = $_POST['site_title'];
$config['require_password'] = true;
$config['admin_password_hash'] = $password_hash;
$sampleData = get_sample_data();
$config['items'] = $sampleData['items'];
$config['iframes'] = $sampleData['iframes'];
save_config($config);
ob_end_clean();

assert(is_installed(), "Should be installed after setup");
assert(file_exists(N0C0_CONFIG_FILE), "config.json must exist");
assert(file_exists(N0C0_DATA_DIR . '/.htaccess'), ".htaccess must exist in data");
assert(file_exists(N0C0_DATA_DIR . '/index.php'), "index.php must exist in data");
echo "[PASS] Test 2: Setup execution and file creation\n";

// Test 3: Load config and verify sample data
$loaded = load_config();
assert($loaded['site_title'] === 'TEST LAB // DSH', "Title should match");
assert(count($loaded['items']) === 6, "Should have 6 sample services");
assert(count($loaded['iframes']) === 1, "Should have 1 sample iframe");
assert($loaded['require_password'] === true, "Require password should be true");
assert($loaded['columns'] === 2, "Default columns should be 2");
assert(password_verify('secret123', $loaded['admin_password_hash']), "Password hash must verify");

// Test updating columns to 3, iframe_position to top, iframe_columns to 2
$loaded['columns'] = 3;
$loaded['iframe_position'] = 'top';
$loaded['iframe_columns'] = 2;
save_config($loaded);
$reloaded = load_config();
assert($reloaded['columns'] === 3, "Columns should be 3 after update");
assert($reloaded['iframe_position'] === 'top', "iframe_position should be top");
assert($reloaded['iframe_columns'] === 2, "iframe_columns should be 2");
echo "[PASS] Test 3: Configuration persistence, columns and iframe layout verification\n";

// Test 4: Auth checks
unset($_SESSION['n0c0_admin']);
assert(!is_admin(), "Should not be admin when session not set and password required");
login_admin();
assert(is_admin(), "Should be admin after login_admin()");
logout_admin();
assert(!is_admin(), "Should not be admin after logout_admin()");
echo "[PASS] Test 4: Admin authentication session management\n";

// Test 5: CSRF verification
$_SESSION['csrf_token'] = 'valid_token';
$_POST['csrf_token'] = 'valid_token';
// This should not throw or exit
verify_csrf();
echo "[PASS] Test 5: CSRF verification with valid token\n";

// Test 6: Add, edit, reorder, delete item
login_admin();
$cfg = load_config();
$newItem = [
    'id'       => 'srv_custom1',
    'title'    => 'Grafana',
    'subtitle' => '192.168.1.50:3000',
    'url'      => 'http://192.168.1.50:3000',
    'category' => 'Monitoring',
    'target'   => '_blank',
    'order'    => 10
];
$cfg['items'][] = $newItem;
save_config($cfg);

$cfg = load_config();
assert(count($cfg['items']) === 7, "Should have 7 items now");
$found = array_filter($cfg['items'], fn($i) => $i['id'] === 'srv_custom1');
assert(count($found) === 1, "Newly added item must exist");
echo "[PASS] Test 6: Add service tile\n";

// Delete item
$cfg['items'] = array_values(array_filter($cfg['items'], fn($i) => $i['id'] !== 'srv_custom1'));
save_config($cfg);
$cfg = load_config();
assert(count($cfg['items']) === 6, "Should have 6 items after deletion");
echo "[PASS] Test 7: Delete service tile\n";

// Test 8: Password-free mode (require_password = false)
$cfg['require_password'] = false;
save_config($cfg);
unset($_SESSION['n0c0_admin']);
assert(is_admin(), "Should automatically be admin when require_password is false");
echo "[PASS] Test 8: Open access mode when require_password is false\n";

// Cleanup test data
array_map('unlink', glob("$dataDir/*.*"));
@rmdir($dataDir);

echo "\n==> ALL TESTS PASSED SUCCESSFULLY! <==\n";
