<?php
/** constants */
defined('BASEPATH') or exit('No direct script access allowed');

date_default_timezone_set("America/Bogota");

define('DS', DIRECTORY_SEPARATOR);

define('PROJECTROOT', dirname(__DIR__, 2) . DS);
define('APP_PATH', PROJECTROOT . 'app' . DS);
define('SYSTEM_PATH', PROJECTROOT . 'system' . DS);
define('PUBLIC_PATH', PROJECTROOT . 'public' . DS);

define('CONTROLLERS_PATH', APP_PATH . 'controllers' . DS);
define('MODELS_PATH', APP_PATH . 'models' . DS);
define('VIEWS_PATH', APP_PATH . 'views' . DS);

define('APP_VERSION', '2.1.1');
define('SITE_NAME', 'My Pulse App');
define('DEFAULT_CONTROLLER', 'landing');
define('DEFAULT_METHOD', 'index');

$httpHost = $_SERVER['HTTP_HOST'] ?? 'cli';
define('PROJECT_REMOTE', $httpHost);

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = str_replace('/public', '', $scriptDir);
$scriptDir = ltrim($scriptDir, '/');
$projectLocal = $scriptDir ? $httpHost . '/' . $scriptDir : $httpHost;
define('PROJECT_LOCAL', $projectLocal);
define('ENABLE_SESSION', TRUE);

if (in_array($httpHost, ['localhost', '127.0.0.1', 'cli']) || strpos($httpHost, 'localhost:') === 0) {
    define('ENVIRONMENT', 'development');
    define('URL_PATH', 'http://' . PROJECT_LOCAL . '/');
} else {
    define('ENVIRONMENT', 'production');
    define('URL_PATH', 'https://' . PROJECT_REMOTE . '/');
}

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// define('DB_HOST', 'localhost');
// define('DB_PORT', '3306');
// define('DB_USER', 'root');
// define('DB_PASS', 'Mario@7723702*/');
// define('DB_NAME', 'pulse_db');
// define('DB_CHAR', 'utf8mb4');

define('DB_HOST', '185.239.210.154');
define('DB_PORT', '3306');
define('DB_USER', 'u467113866_4dm1n');
define('DB_PASS', 'Q2w3e4r5t6y@*');
define('DB_NAME', 'u467113866_salamandra2');
define('DB_CHAR', 'utf8mb4');
