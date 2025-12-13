<?php

/**
 * PHPUnit Bootstrap File
 */

define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . '/storage');

// Load autoloader
require_once BASE_PATH . '/system/Core/Autoloader.php';

use NeoCore\System\Core\Autoloader;

$autoloader = new Autoloader(BASE_PATH);
$autoloader->register();

// Load helpers
require_once BASE_PATH . '/system/Helpers/helpers.php';

// Load environment variables (if using dotenv)
if (file_exists(BASE_PATH . '/.env')) {
    // You can load .env here if needed
}
