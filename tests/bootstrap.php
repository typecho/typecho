<?php
/**
 * PHPUnit bootstrap: load Typecho's autoloader without config.inc.php or an install.
 *
 * TYPECHO_ROOT may point at another checkout (e.g. an older tree when bisecting a
 * regression); it defaults to the repository this file lives in.
 */
$root = getenv('TYPECHO_ROOT') ?: dirname(__DIR__);

define('__TYPECHO_ROOT_DIR__', $root);
define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');
define('__TYPECHO_THEME_DIR__', '/usr/themes');
define('__TYPECHO_ADMIN_DIR__', '/admin/');

// Registers the spl autoloader and _t()/_e()/_n(). Do NOT call Common::init() here:
// it starts output buffering and installs an exception handler that calls exit(),
// which would silently kill the PHPUnit process on the first uncaught exception.
require_once $root . '/var/Typecho/Common.php';

date_default_timezone_set('UTC');

require_once __DIR__ . '/Db/SqliteTestCase.php';
