<?php

declare(strict_types=1);

namespace NeoCore\Storage;

use NeoCore\Container\ServiceProvider;
use NeoCore\Container\Container;

/**
 * Storage Service Provider
 * 
 * Registers storage services into the container
 */
class StorageServiceProvider extends ServiceProvider
{
    /**
     * Register storage services
     */
    public function register(): void
    {
        $this->container->singleton(StorageManager::class, function (Container $container) {
            $config = $container->get('config')->get('filesystems', []);
            return new StorageManager($container, $config);
        });

        $this->container->alias('storage', StorageManager::class);
    }

    /**
     * Boot storage services
     */
    public function boot(): void
    {
        $this->loadConfig();
    }

    /**
     * Load storage configuration
     */
    protected function loadConfig(): void
    {
        $configPath = $this->container->get('path.config');
        $storageConfig = $configPath . '/filesystems.php';

        if (file_exists($storageConfig)) {
            $config = require $storageConfig;
            $this->container->get('config')->set('filesystems', $config);
        }
    }
}
