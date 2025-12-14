<?php

declare(strict_types=1);

namespace NeoCore\Auth;

use NeoCore\Container\ServiceProvider;
use NeoCore\Container\Container;

/**
 * Authentication Service Provider
 * 
 * Registers authentication services into the container
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register authentication services
     */
    public function register(): void
    {
        // Register Auth Manager
        $this->container->singleton(AuthManager::class, function (Container $container) {
            $config = $container->get('config')->get('auth', []);
            return new AuthManager($container, $config);
        });

        // Register Auth Guard
        $this->container->singleton(Guard::class, function (Container $container) {
            $manager = $container->get(AuthManager::class);
            return $manager->guard();
        });

        // Register User Provider
        $this->container->singleton(UserProviderInterface::class, function (Container $container) {
            $config = $container->get('config')->get('auth.providers.default', []);
            $driver = $config['driver'] ?? 'database';
            
            if ($driver === 'database') {
                return new DatabaseUserProvider(
                    $container->get('db'),
                    $config['table'] ?? 'users'
                );
            }
            
            throw new \InvalidArgumentException("Unsupported user provider driver: {$driver}");
        });

        // Alias for easier access
        $this->container->alias('auth', AuthManager::class);
        $this->container->alias('auth.guard', Guard::class);
    }

    /**
     * Boot authentication services
     */
    public function boot(): void
    {
        // Set up default authentication configuration
        $this->loadConfig();
    }

    /**
     * Load authentication configuration
     */
    protected function loadConfig(): void
    {
        $configPath = $this->container->get('path.config');
        $authConfig = $configPath . '/auth.php';

        if (file_exists($authConfig)) {
            $config = require $authConfig;
            $this->container->get('config')->set('auth', $config);
        }
    }
}
