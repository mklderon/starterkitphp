<?php

/** init */
defined('BASEPATH') or exit('No direct script access allowed');

$current_session_status = session_status();

if (file_exists(APP_PATH . 'functions/functions.php')) {
    require_once APP_PATH . 'functions/functions.php';
}

if (defined('ENABLE_SESSION') && ENABLE_SESSION && $current_session_status !== PHP_SESSION_ACTIVE) {
    session_start();
}

spl_autoload_register(function($class_name) {
    $file = SYSTEM_PATH . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
