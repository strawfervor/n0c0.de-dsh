<?php
/**
 * n0c0.de-dsh - Core Configuration & Storage Engine
 * Part of n0c0.de: lightweight, 100% self-hosted, zero dependencies.
 */

declare(strict_types=1);

// Error reporting for production-ready robustness
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Session configuration
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $cookieParams = [
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Directory constants
define('N0C0_ROOT', __DIR__);
define('N0C0_DATA_DIR', N0C0_ROOT . '/data');
define('N0C0_CONFIG_FILE', N0C0_DATA_DIR . '/config.json');

/**
 * Ensure the data directory and protective files exist.
 */
function init_data_storage(): void {
    if (!is_dir(N0C0_DATA_DIR)) {
        mkdir(N0C0_DATA_DIR, 0750, true);
    }
    
    // Protect data directory in Apache
    $htaccess = N0C0_DATA_DIR . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n");
    }
    
    // Protect data directory from direct PHP script listing
    $indexPhp = N0C0_DATA_DIR . '/index.php';
    if (!file_exists($indexPhp)) {
        file_put_contents($indexPhp, "<?php http_response_code(403); exit('Access denied');\n");
    }
}

/**
 * Check if the application setup has been completed.
 */
function is_installed(): bool {
    if (!file_exists(N0C0_CONFIG_FILE)) {
        return false;
    }
    $cfg = load_config();
    return !empty($cfg['setup_completed']);
}

/**
 * Load the configuration array from JSON.
 */
function load_config(): array {
    if (!file_exists(N0C0_CONFIG_FILE)) {
        return get_default_config();
    }
    $raw = @file_get_contents(N0C0_CONFIG_FILE);
    if ($raw === false) {
        return get_default_config();
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return get_default_config();
    }
    return array_replace_recursive(get_default_config(), $data);
}

/**
 * Atomically save the configuration array to JSON.
 */
function save_config(array $data): bool {
    init_data_storage();
    $data['updated_at'] = time();
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }

    $tmp = N0C0_CONFIG_FILE . '.tmp.' . bin2hex(random_bytes(6));
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    return rename($tmp, N0C0_CONFIG_FILE);
}

/**
 * Default empty configuration structure.
 */
function get_default_config(): array {
    return [
        'setup_completed'     => false,
        'created_at'          => time(),
        'updated_at'          => time(),
        'site_title'          => 'n0c0.de // DSH',
        'columns'             => 2,
        'iframe_position'     => 'bottom',
        'iframe_columns'      => 1,
        'require_password'    => true,
        'admin_password_hash' => null,
        'categories'          => ['Core', 'Network', 'Media', 'Monitoring', 'Tools'],
        'items'               => [],
        'iframes'             => []
    ];
}

/**
 * Sample items and widgets for quick setup.
 */
function get_sample_data(): array {
    return [
        'items' => [
            [
                'id'       => 'srv_' . bin2hex(random_bytes(4)),
                'title'    => 'Pi-hole',
                'subtitle' => '192.168.1.2:80',
                'url'      => 'http://pi.hole/admin',
                'category' => 'Network',
                'target'   => '_blank',
                'order'    => 1
            ],
            [
                'id'       => 'srv_' . bin2hex(random_bytes(4)),
                'title'    => 'Nextcloud',
                'subtitle' => 'cloud.local',
                'url'      => 'https://cloud.local',
                'category' => 'Core',
                'target'   => '_blank',
                'order'    => 2
            ],
            [
                'id'       => 'srv_' . bin2hex(random_bytes(4)),
                'title'    => 'Portainer',
                'subtitle' => 'docker.local:9443',
                'url'      => 'https://docker.local:9443',
                'category' => 'Tools',
                'target'   => '_blank',
                'order'    => 3
            ],
            [
                'id'       => 'srv_' . bin2hex(random_bytes(4)),
                'title'    => 'Jellyfin',
                'subtitle' => 'media.local:8096',
                'url'      => 'http://media.local:8096',
                'category' => 'Media',
                'target'   => '_blank',
                'order'    => 4
            ],
            [
                'id'       => 'srv_' . bin2hex(random_bytes(4)),
                'title'    => 'Home Assistant',
                'subtitle' => 'home.local:8123',
                'url'      => 'http://homeassistant.local:8123',
                'category' => 'Core',
                'target'   => '_blank',
                'order'    => 5
            ],
            [
                'id'       => 'srv_' . bin2hex(random_bytes(4)),
                'title'    => 'Uptime Kuma',
                'subtitle' => 'status.local:3001',
                'url'      => 'http://status.local:3001',
                'category' => 'Monitoring',
                'target'   => '_blank',
                'order'    => 6
            ]
        ],
        'iframes' => [
            [
                'id'       => 'ifm_' . bin2hex(random_bytes(4)),
                'title'    => 'Netdata Metrics',
                'url'      => 'http://localhost:19999',
                'category' => 'Monitoring',
                'height'   => 400,
                'order'    => 1
            ]
        ]
    ];
}

/**
 * Authentication and authorization helpers.
 */
function is_password_required(): bool {
    $cfg = load_config();
    return !empty($cfg['require_password']);
}

function is_admin(): bool {
    if (!is_password_required()) {
        return true;
    }
    return !empty($_SESSION['n0c0_admin']);
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: login.php');
        exit;
    }
}

function login_admin(): void {
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['n0c0_admin'] = true;
}

function logout_admin(): void {
    unset($_SESSION['n0c0_admin']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (ini_get("session.use_cookies") && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}

/**
 * CSRF Protection.
 */
function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('<!DOCTYPE html><html><head><meta charset="utf-8"><title>CSRF Error</title><link rel="stylesheet" href="style.css"></head><body><h1>SECURITY ERROR</h1><blockquote><strong>CSRF ERROR:</strong> CSRF token validation failed. Please return to the previous page and try again.</blockquote><p><a href="javascript:history.back()">[ GO BACK ]</a></p></body></html>');
    }
}

/**
 * Flash message handling.
 */
function flash_set(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = [
        'message' => $message,
        'type'    => $type
    ];
}

function flash_get(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/**
 * Safe HTML escaping.
 */
function h(?string $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Validate and sanitize external URL.
 */
function sanitize_url(?string $url): string {
    $url = trim((string)$url);
    if ($url === '') {
        return '#';
    }
    // Block javascript:, data:, etc.
    if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
        return '#';
    }
    return $url;
}

/**
 * Helper to get clean host display from URL.
 */
function get_url_host(string $url): string {
    $parts = parse_url($url);
    if (!empty($parts['host'])) {
        $host = $parts['host'];
        if (!empty($parts['port'])) {
            $host .= ':' . $parts['port'];
        }
        return $host;
    }
    return $url;
}
