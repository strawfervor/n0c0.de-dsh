<?php
/**
 * n0c0.de-dsh - Admin Logout
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

logout_admin();
header('Location: index.php');
exit;
