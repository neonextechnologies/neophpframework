<?php

namespace NeoCore\System\Core;

/**
 * ModuleLoader - Module registration and loading
 * 
 * No auto-discovery. Modules must be listed explicitly.
 */
class ModuleLoader
{
    private string $modulesPath;
    private array $loadedModules = [];
    private Router $router;
    private EventBus $eventBus;

    public function __construct(string $modulesPath, Router $router, EventBus $eventBus)
    {
        $this->modulesPath = rtrim($modulesPath, '/');
        $this->router = $router;
        $this->eventBus = $eventBus;
    }

    /**
     * Load modules from config
     */
    public function loadModules(array $moduleNames): void
    {
        foreach ($moduleNames as $moduleName) {
            $this->loadModule($moduleName);
        }
    }

    /**
     * Load single module
     */
    public function loadModule(string $moduleName): bool
    {
        $modulePath = $this->modulesPath . '/' . strtolower($moduleName);
        $configPath = $modulePath . '/Config/module.php';

        if (!file_exists($configPath)) {
            error_log("Module config not found: $configPath");
            return false;
        }

        $moduleConfig = require $configPath;

        // Validate module config
        if (!isset($moduleConfig['name'])) {
            error_log("Module name not defined in config: $moduleName");
            return false;
        }

        // Register routes
        if (isset($moduleConfig['routes'])) {
            $this->registerRoutes($moduleName, $modulePath, $moduleConfig['routes']);
        }

        // Register events
        if (isset($moduleConfig['events'])) {
            $this->registerEvents($moduleConfig['events']);
        }

        $this->loadedModules[$moduleName] = $moduleConfig;

        return true;
    }

    /**
     * Register module routes
     */
    private function registerRoutes(string $moduleName, string $modulePath, array $routeConfig): void
    {
        foreach ($routeConfig as $type => $routeFile) {
            $routeFilePath = $modulePath . '/' . $routeFile;

            if (!file_exists($routeFilePath)) {
                error_log("Route file not found: $routeFilePath");
                continue;
            }

            // Load routes with module namespace context
            $router = $this->router;
            $moduleNamespace = $this->getModuleNamespace($moduleName);

            // Set up prefix for module routes (optional)
            require $routeFilePath;
        }
    }

    /**
     * Register event listeners
     */
    private function registerEvents(array $events): void
    {
        foreach ($events as $eventName => $listeners) {
            if (!is_array($listeners)) {
                $listeners = [$listeners];
            }

            foreach ($listeners as $listenerClass) {
                $this->eventBus->listen($eventName, $listenerClass);
            }
        }
    }

    /**
     * Get module namespace
     */
    private function getModuleNamespace(string $moduleName): string
    {
        return 'Modules\\' . ucfirst($moduleName);
    }

    /**
     * Get loaded modules
     */
    public function getLoadedModules(): array
    {
        return $this->loadedModules;
    }

    /**
     * Check if module is loaded
     */
    public function isLoaded(string $moduleName): bool
    {
        return isset($this->loadedModules[$moduleName]);
    }

    /**
     * Get module config
     */
    public function getModuleConfig(string $moduleName): ?array
    {
        return $this->loadedModules[$moduleName] ?? null;
    }

    /**
     * Get all migration files from modules
     */
    public function getModuleMigrations(): array
    {
        $migrations = [];

        foreach ($this->loadedModules as $moduleName => $config) {
            $migrationsPath = $this->modulesPath . '/' . strtolower($moduleName) . '/Migrations';

            if (!is_dir($migrationsPath)) {
                continue;
            }

            $files = glob($migrationsPath . '/*.php');
            
            foreach ($files as $file) {
                $migrations[] = [
                    'module' => $moduleName,
                    'file' => $file,
                    'name' => basename($file, '.php')
                ];
            }
        }

        // Sort by filename (timestamp)
        usort($migrations, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $migrations;
    }
}
