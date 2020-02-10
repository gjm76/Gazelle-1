<?php

$site_root = dirname(__DIR__);
set_include_path(get_include_path() . PATH_SEPARATOR . $site_root);

$document = basename(parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH), '.php');
$target_file = "{$site_root}/sections/{$document}";

spl_autoload_register('autoloader');

function autoloader($classname) {
    include_once "classes/class_" . strtolower($classname) . '.php';
}

if (preg_match('/^[a-z0-9_]+$/i', $document) && file_exists($target_file)) {
    switch($document) {
        case 'feeds':
            require($site_root . '/sections/feeds/entry.php');
            break;
        case 'git_update':
            require($site_root . '/sections/git_update/index.php');
            break;
        default :
            require($site_root . '/classes/script_start.php');
    }
} else {
    require($site_root . '/index.php');
}

