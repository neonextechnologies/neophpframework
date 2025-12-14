<?php

declare(strict_types=1);

namespace NeoCore\Auth;

use NeoCore\Container\Container;

/**
 * Authentication Manager
 * 
 * Manages authentication guards and user providers
 */
class AuthManager
{
    protected Container $container;
    protected array $config;
    protected array $guards = [];

    public function __construct(Container $container, array $config = [])
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * Get a guard instance
     */
    public function guard(?string $name = null): Guard
    {
        $name = $name ?? $this->getDefaultGuard();

        if (isset($this->guards[$name])) {
            return $this->guards[$name];
        }

        return $this->guards[$name] = $this->resolveGuard($name);
    }

    /**
     * Resolve a guard instance
     */
    protected function resolveGuard(string $name): Guard
    {
        $config = $this->config['guards'][$name] ?? [];

        if (empty($config)) {
            throw new \InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        $driver = $config['driver'] ?? 'session';
        $provider = $this->getUserProvider($config['provider'] ?? 'users');

        return match ($driver) {
            'session' => new SessionGuard($provider, $this->container->get('session')),
            'token' => new TokenGuard($provider, $this->container->get('request')),
            default => throw new \InvalidArgumentException("Unsupported auth driver: {$driver}")
        };
    }

    /**
     * Get user provider
     */
    protected function getUserProvider(string $name): UserProviderInterface
    {
        $config = $this->config['providers'][$name] ?? [];

        if (empty($config)) {
            throw new \InvalidArgumentException("Auth provider [{$name}] is not defined.");
        }

        $driver = $config['driver'] ?? 'database';

        return match ($driver) {
            'database' => new DatabaseUserProvider(
                $this->container->get('db'),
                $config['table'] ?? 'users'
            ),
            'orm' => new ORMUserProvider(
                $this->container->get('orm'),
                $config['entity'] ?? 'User'
            ),
            default => throw new \InvalidArgumentException("Unsupported provider driver: {$driver}")
        };
    }

    /**
     * Get default guard name
     */
    protected function getDefaultGuard(): string
    {
        return $this->config['defaults']['guard'] ?? 'web';
    }

    /**
     * Dynamically call the default guard
     */
    public function __call(string $method, array $parameters)
    {
        return $this->guard()->$method(...$parameters);
    }
}
