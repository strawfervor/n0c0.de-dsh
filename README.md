# n0c0.de-dsh

A minimal, lightweight, and customizable self-hosted service dashboard and widget board for home servers and homelabs.

## What is n0c0.de?

[n0c0.de](https://n0c0.de) - a collection of small and lightweight apps based mostly on PHP, with a minimal amount of CSS and JS.

## Features

- **100% self-hosted, zero external dependencies**: Runs everywhere PHP 8+ runs.
- **Minimalist Dashboard**: Clean service tiles with just the title as a direct link, custom target (`_blank` / `_self`), and embedded live iframes (Netdata, Grafana, status pages, web UIs).
- **Setup Wizard**: Fast first-time setup with configurable admin password protection (enabled by default, can be toggled off) and optional demo homelab services.
- **Dedicated Admin Panel**: Manage service tiles, iframes, and categories with simple reordering, editing, and deletion.
- **JSON Storage**: Flat-file JSON configuration with atomic writes (`flock`) — zero database setup required (no MySQL, no SQLite).
- **Export & Import**: One-click JSON backup export and restore directly from the admin panel.
- **Zero JavaScript**: Pure PHP and HTML, runs completely without any JavaScript.
- **Security built-in**: Strict CSRF token protection on all state-changing actions and `password_hash()` (bcrypt) for admin authentication.
- **Classless CSS**: Pure semantic HTML styling via `style.css` — retro cyberpunk/terminal aesthetics with zero frameworks and zero build steps.

## Quick Start

### Built-in PHP Server

```bash
git clone https://github.com/strawfervor/n0c0.de-dsh.git
cd n0c0.de-dsh
php -S 0.0.0.0:8080
```

Open `http://localhost:8080` in your browser. The setup wizard (`setup.php`) will guide you through the initial configuration.

### Apache / Nginx / Caddy

Point your web server root to the project directory. The `data/` folder is automatically protected from unauthorized web access via `.htaccess` and `data/index.php`.

For Nginx, ensure internal data files are not served directly:
```nginx
location ^~ /data/ {
    deny all;
    return 403;
}
```

## Structure

```
├── admin.php       # Management panel for services, iframes, and settings
├── config.php      # Core helpers, session, security, and atomic storage engine
├── index.php       # Main user-facing dashboard with filter search
├── login.php       # Admin authentication form
├── logout.php      # Session terminator
├── setup.php       # First-time installation wizard
├── style.css       # Classless retro stylesheet (n0c0.de)
├── tests/          # Automated test suite
│   └── test.php
└── data/           # Created automatically on setup (protected)
    ├── .htaccess
    ├── index.php
    └── config.json
```

## Testing

Run the included standalone test suite:

```bash
php tests/test.php
```

## License

[MIT](LICENSE)
