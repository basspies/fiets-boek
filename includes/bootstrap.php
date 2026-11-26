<?php
declare(strict_types=1);

// Wordt door elke pagina (website en admin) als eerste geladen.

define('ROOT', dirname(__DIR__));

$configFile = ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('config.php ontbreekt. Kopieer config.example.php naar config.php en vul de databasegegevens in.');
}

$GLOBALS['config'] = require $configFile;

if (!empty($GLOBALS['config']['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

require ROOT . '/includes/functions.php';
require ROOT . '/includes/routes.php';

define('BASE', $GLOBALS['config']['base_url'] ?? detect_base());
