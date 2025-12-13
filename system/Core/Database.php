<?php

namespace NeoCore\System\Core;

use PDO;
use PDOException;

/**
 * Database - Database connection manager
 * 
 * Simple PDO wrapper. No ORM magic.
 */
class Database
{
    private static ?PDO $instance = null;
    private static array $connections = [];

    /**
     * Get default database connection
     */
    public static function connection(?string $name = null): PDO
    {
        $name = $name ?? 'default';

        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        $config = Config::get("database.$name");

        if (!$config) {
            throw new \Exception("Database configuration not found: $name");
        }

        self::$connections[$name] = self::createConnection($config);
        
        return self::$connections[$name];
    }

    /**
     * Create PDO connection
     */
    private static function createConnection(array $config): PDO
    {
        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        $charset = $config['charset'] ?? 'utf8mb4';

        try {
            $dsn = "$driver:host=$host;port=$port;dbname=$database;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            return new PDO($dsn, $username, $password, $options);

        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Close connection
     */
    public static function disconnect(?string $name = null): void
    {
        $name = $name ?? 'default';
        
        if (isset(self::$connections[$name])) {
            self::$connections[$name] = null;
            unset(self::$connections[$name]);
        }
    }

    /**
     * Close all connections
     */
    public static function disconnectAll(): void
    {
        foreach (array_keys(self::$connections) as $name) {
            self::disconnect($name);
        }
    }
}
