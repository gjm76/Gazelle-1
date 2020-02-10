<?php
define('MEMORY_EXCEPTION', true);
define('TIME_EXCEPTION', true);
define('ERROR_EXCEPTION', true);
$_SERVER['SCRIPT_FILENAME'] = 'fixup.php'; // CLI fix
require 'classes/script_start.php';
