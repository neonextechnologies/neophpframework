<?php

declare(strict_types=1);

namespace NeoCore\Auth\Access;

use NeoCore\Container\ServiceProvider;
use NeoCore\Container\Container;

/**
 * Authorization Service Provider
 * 
 * Registers authorization services
 */
class AuthorizationServiceProvider extends ServiceProvider
{
    /**
     * Register authorization services
     */
    public function register(): void
    {
        $this->container->singleton(Gate::class, function (Container $container) {
            $auth = $container->get('auth');
            return new Gate($container, $auth->user());
        });

        $this->container->alias('gate', Gate::class);
    }

    /**
     * Boot authorization services
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerGates();
    }

    /**
     * Register policies
     */
    protected function registerPolicies(): void
    {
        $gate = $this->container->get(Gate::class);

        // Register policies from config or auto-discovery
        $policies = config('auth.policies', []);

        foreach ($policies as $model => $policy) {
            $gate->policy($model, $policy);
        }
    }

    /**
     * Register gate definitions
     */
    protected function registerGates(): void
    {
        $gate = $this->container->get(Gate::class);

        // Example gate definitions
        $gate->define('update-settings', function ($user) {
            return $user->hasRole('admin');
        });

        $gate->define('view-admin-dashboard', function ($user) {
            return $user->hasAnyRole(['admin', 'editor']);
        });

        // Super admin bypass
        $gate->before(function ($user, $ability) {
            if ($user && $user->hasRole('super-admin')) {
                return true;
            }
        });
    }
}
