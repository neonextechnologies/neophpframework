<?php

declare(strict_types=1);

namespace NeoCore\Database\Drivers;

use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Config\Postgres\TcpConnectionConfig;

/**
 * PostgreSQL Database Driver
 * 
 * Provides PostgreSQL database support
 */
class PostgreSQLDriver
{
    /**
     * Create PostgreSQL connection configuration
     */
    public static function createConfig(array $config): PostgresDriverConfig
    {
        return new PostgresDriverConfig(
            connection: new TcpConnectionConfig(
                database: $config['database'],
                host: $config['host'] ?? '127.0.0.1',
                port: $config['port'] ?? 5432,
                user: $config['username'] ?? 'postgres',
                password: $config['password'] ?? ''
            ),
            schema: $config['schema'] ?? 'public',
            queryCache: $config['query_cache'] ?? true
        );
    }

    /**
     * Create config from DSN
     */
    public static function fromDsn(string $dsn): PostgresDriverConfig
    {
        $parts = parse_url($dsn);
        
        return new PostgresDriverConfig(
            connection: new TcpConnectionConfig(
                database: ltrim($parts['path'] ?? '', '/'),
                host: $parts['host'] ?? '127.0.0.1',
                port: $parts['port'] ?? 5432,
                user: $parts['user'] ?? 'postgres',
                password: $parts['pass'] ?? ''
            ),
            schema: 'public',
            queryCache: true
        );
    }

    /**
     * Create local development config
     */
    public static function local(string $database, string $username = 'postgres', string $password = ''): PostgresDriverConfig
    {
        return new PostgresDriverConfig(
            connection: new TcpConnectionConfig(
                database: $database,
                host: '127.0.0.1',
                port: 5432,
                user: $username,
                password: $password
            ),
            schema: 'public',
            queryCache: true
        );
    }
}
