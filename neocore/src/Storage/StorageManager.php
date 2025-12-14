<?php

declare(strict_types=1);

namespace NeoCore\Storage;

use NeoCore\Container\Container;

/**
 * Storage Manager
 * 
 * Manages multiple storage disks
 */
class StorageManager
{
    protected Container $container;
    protected array $config;
    protected array $disks = [];

    public function __construct(Container $container, array $config = [])
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * Get a filesystem instance
     */
    public function disk(?string $name = null): FilesystemInterface
    {
        $name = $name ?? $this->getDefaultDisk();

        if (!isset($this->disks[$name])) {
            $this->disks[$name] = $this->resolve($name);
        }

        return $this->disks[$name];
    }

    /**
     * Resolve a disk instance
     */
    protected function resolve(string $name): FilesystemInterface
    {
        $config = $this->getConfig($name);
        $driver = $config['driver'] ?? 'local';

        $method = 'create' . ucfirst($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method($config);
        }

        throw new \InvalidArgumentException("Storage driver [{$driver}] is not supported.");
    }

    /**
     * Create local driver
     */
    protected function createLocalDriver(array $config): FilesystemInterface
    {
        return new LocalFilesystem($config['root']);
    }

    /**
     * Create S3 driver
     */
    protected function createS3Driver(array $config): FilesystemInterface
    {
        $client = new \Aws\S3\S3Client([
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
            'region' => $config['region'],
            'version' => 'latest',
            'endpoint' => $config['endpoint'] ?? null,
        ]);

        return new S3Filesystem($client, $config['bucket'], $config);
    }

    /**
     * Get the configuration for a disk
     */
    protected function getConfig(string $name): array
    {
        if (!isset($this->config['disks'][$name])) {
            throw new \InvalidArgumentException("Disk [{$name}] is not configured.");
        }

        return $this->config['disks'][$name];
    }

    /**
     * Get the default disk name
     */
    protected function getDefaultDisk(): string
    {
        return $this->config['default'] ?? 'local';
    }

    /**
     * Dynamically call the default driver instance
     */
    public function __call(string $method, array $parameters)
    {
        return $this->disk()->$method(...$parameters);
    }
}
