<?php
// Core application configuration

// You can manually set BASE_URL if auto-detection fails
// e.g., define('BASE_URL', 'http://freshmart.epizy.com');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$dir = dirname($_SERVER['PHP_SELF']);
$dir = str_replace(array('\\', '/config', '/includes', '/pages', '/print'), array('/', '', '', '', ''), $dir);
$dir = rtrim($dir, '/');

if (!defined('BASE_URL')) {
    define('BASE_URL', $protocol . $domainName . $dir);
}

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', $protocol === 'https://' ? 1 : 0);

// Default timezone - we will override this from DB settings later if needed
date_default_timezone_set('UTC');
