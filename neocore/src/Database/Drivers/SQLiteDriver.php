<?php

declare(strict_types=1);

namespace NeoCore\Database\Drivers;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\FileConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;

/**
 * SQLite Database Driver
 * 
 * Provides SQLite database support
 */
class SQLiteDriver
{
    /**
     * Create SQLite connection configuration
     */
    public static function createConfig(array $config): SQLiteDriverConfig
    {
        $database = $config['database'] ?? ':memory:';
        
        return new SQLiteDriverConfig(
            connection: new FileConnectionConfig(
                database: $database
            ),
            queryCache: $config['query_cache'] ?? true
        );
    }

    /**
     * Create in-memory database config
     */
    public static function memory(): SQLiteDriverConfig
    {
        return new SQLiteDriverConfig(
            connection: new FileConnectionConfig(database: ':memory:'),
            queryCache: true
        );
    }

    /**
     * Create file-based database config
     */
    public static function file(string $path): SQLiteDriverConfig
    {
        // Ensure directory exists
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return new SQLiteDriverConfig(
            connection: new FileConnectionConfig(database: $path),
            queryCache: true
        );
    }

    /**
     * Get default testing config
     */
    public static function testing(): SQLiteDriverConfig
    {
        return self::memory();
    }
}
