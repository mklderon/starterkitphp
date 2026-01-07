<?php
defined('BASEPATH') or define('BASEPATH', true);

require_once __DIR__ . '/../app/config/constants.php';

define('DB_DSN', 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHAR);
