# n0c0.de-dsh
Minimal, self-hosted server dashboard for services and widgets.

### What is n0c0.de?
[n0c0.de](https://n0c0.de) - a collection of small and lightweight apps based mostly on PHP, with a minimal amount of CSS and JS.

### Features
- 100% self-hosted, zero dependencies
- No JavaScript needed (works without JS)
- Minimal service tiles with title as a direct link and optional subtitle (e.g. IP/port or note)
- Embed live iframes (Netdata, Grafana, status monitors, web UIs)
- Configurable layout: 1, 2, or 3 columns for tiles and iframes
- Selectable iframe position: top of the dashboard or bottom
- Custom categories for organizing services
- JSON only - no database needed (no SQLite, no MySQL)
- Backup and restore with one-click JSON export/import
- Dedicated admin panel with optional password protection (set during setup or in settings)
- CSRF protection, `password_hash`
- Classless CSS - no frameworks, no build step

## Requirements
- **PHP >= 7.4** (recommended >= 8.1)
- PHP extensions: `json`, `session`
- Writable script directory (`data/` directory is created there)
- Any webserver: Apache / Nginx / Caddy or built-in `php -S`

## Installation
1. Download / clone the repo
2. Unpack on your hosting
3. Make sure the script directory is writable and the `data/` directory is protected from direct access (deny rules)
4. Go through `setup.php`
5. Enjoy!

To start fresh, just delete the `data/` directory (or `data/config.json`).
