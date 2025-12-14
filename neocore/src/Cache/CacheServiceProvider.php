<?php

declare(strict_types=1);

namespace NeoCore\Cache;

use NeoCore\Container\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('cache', function ($container) {
            $config = require base_path('config/cache.php');
            return new CacheManager($container, $config);
        });
    }

    public function boot(): void
    {
        // Boot logic if needed
    }
}
