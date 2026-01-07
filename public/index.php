<?php
/** index.php */
define('BASEPATH', true);

require_once dirname(__DIR__) . '/app/config/constants.php';

require_once APP_PATH . 'init.php';
require_once SYSTEM_PATH . 'helpers/functions.php';

try {
    new PulseDispatcher();
} catch (Exception $e) {
    if (class_exists('PulseErrorHandler')) {
        (new PulseErrorHandler())->handleException($e);
    } else {
        echo "<h1>Error Crítico del Sistema</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
}
