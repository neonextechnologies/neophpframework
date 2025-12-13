<?php

/**
 * NeoCore Framework - Entry Point
 * 
 * No magic. Explicit bootstrapping.
 */

// Define paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('SYSTEM_PATH', BASE_PATH . '/system');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('MODULES_PATH', BASE_PATH . '/modules');

// Autoloader
require_once SYSTEM_PATH . '/Core/Autoloader.php';

use NeoCore\System\Core\Autoloader;
use NeoCore\System\Core\Router;
use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;
use NeoCore\System\Core\Config;
use NeoCore\System\Core\EventBus;
use NeoCore\System\Core\ModuleLoader;
use NeoCore\System\Core\TenantManager;
use NeoCore\System\Core\ORMService;
use NeoCore\System\Core\ViewService;

// Initialize autoloader
Autoloader::register();

// Load helper functions
require_once SYSTEM_PATH . '/Helpers/helpers.php';

// Load configuration
Config::init(CONFIG_PATH);

// Initialize ORM
$ormConfig = Config::getAll('orm');
ORMService::init($ormConfig);

// Initialize View Service
$viewConfig = Config::getAll('view');
ViewService::init($viewConfig);

// Add global view variables
$viewGlobals = $viewConfig['globals'] ?? [];
foreach ($viewGlobals as $name => $value) {
    ViewService::addGlobal($name, $value);
}

// Set error reporting based on config
$appConfig = Config::getAll('app');
error_reporting($appConfig['error_reporting'] ?? E_ALL);
ini_set('display_errors', $appConfig['display_errors'] ?? '1');
date_default_timezone_set($appConfig['timezone'] ?? 'UTC');

try {
    // Create core instances
    $request = new Request();
    $response = new Response();
    $router = new Router();
    $eventBus = new EventBus();

    // Initialize tenant manager (optional)
    $tenantConfig = Config::getAll('tenant');
    if (!empty($tenantConfig)) {
        $tenantManager = new TenantManager($tenantConfig);
        $tenant = $tenantManager->detectTenant($request);
        
        if ($tenant && $tenantManager->validateTenantAccess($tenant)) {
            // Tenant detected and validated
            // You can inject tenant-specific database connection here
        }
    }

    // Load application routes
    $routesCallback = require CONFIG_PATH . '/routes.php';
    if (is_callable($routesCallback)) {
        $routesCallback($router);
    }

    // Load modules
    $moduleConfig = Config::get('modules.enabled', []);
    if (!empty($moduleConfig)) {
        $moduleLoader = new ModuleLoader(MODULES_PATH, $router, $eventBus);
        $moduleLoader->loadModules($moduleConfig);
    }

    // Dispatch request
    $response = $router->dispatch($request, $response);

    // Send response
    $response->send();

} catch (\Exception $e) {
    // Handle exceptions
    http_response_code(500);
    
    if (Config::get('app.debug', false)) {
        echo json_encode([
            'error' => 'Internal Server Error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'error' => 'Internal Server Error',
            'message' => 'An error occurred while processing your request'
        ]);
    }
}
