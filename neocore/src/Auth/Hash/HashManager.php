<?php

declare(strict_types=1);

namespace NeoCore\Auth\Hash;

use NeoCore\Container\Container;

/**
 * Hash Manager
 * 
 * Manages multiple password hashers
 */
class HashManager
{
    protected Container $container;
    protected array $config;
    protected array $drivers = [];

    public function __construct(Container $container, array $config = [])
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * Get a hasher instance
     */
    public function driver(?string $name = null): HasherInterface
    {
        $name = $name ?? $this->getDefaultDriver();

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Create a hasher driver
     */
    protected function createDriver(string $name): HasherInterface
    {
        $method = 'create' . ucfirst($name) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new \InvalidArgumentException("Hasher driver [{$name}] not supported");
    }

    /**
     * Create bcrypt driver
     */
    protected function createBcryptDriver(): HasherInterface
    {
        return new BcryptHasher([
            'rounds' => $this->config['bcrypt']['rounds'] ?? 10,
        ]);
    }

    /**
     * Create argon2 driver
     */
    protected function createArgon2Driver(): HasherInterface
    {
        return new Argon2Hasher([
            'memory' => $this->config['argon2']['memory'] ?? 65536,
            'time' => $this->config['argon2']['time'] ?? 4,
            'threads' => $this->config['argon2']['threads'] ?? 1,
        ]);
    }

    /**
     * Get the default driver name
     */
    protected function getDefaultDriver(): string
    {
        return $this->config['driver'] ?? 'bcrypt';
    }

    /**
     * Proxy methods to the default driver
     */
    public function make(string $value, array $options = []): string
    {
        return $this->driver()->make($value, $options);
    }

    public function check(string $value, string $hashedValue): bool
    {
        return $this->driver()->check($value, $hashedValue);
    }

    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return $this->driver()->needsRehash($hashedValue, $options);
    }

    public function info(string $hashedValue): array
    {
        return $this->driver()->info($hashedValue);
    }
}
