<?php

declare(strict_types=1);

namespace NeoCore\Database;

use Cycle\Database\DatabaseManager as CycleDatabaseManager;
use Cycle\Database\Config\DatabaseConfig;

/**
 * Database Connection Manager
 * 
 * Manages multiple database connections
 */
class ConnectionManager
{
    protected CycleDatabaseManager $manager;
    protected array $connections = [];
    protected string $defaultConnection;

    public function __construct(DatabaseConfig $config, string $defaultConnection = 'default')
    {
        $this->manager = new CycleDatabaseManager($config);
        $this->defaultConnection = $defaultConnection;
    }

    /**
     * Get database connection
     */
    public function connection(?string $name = null): \Cycle\Database\DatabaseInterface
    {
        $name = $name ?? $this->defaultConnection;
        return $this->manager->database($name);
    }

    /**
     * Get default connection
     */
    public function getDefaultConnection(): string
    {
        return $this->defaultConnection;
    }

    /**
     * Set default connection
     */
    public function setDefaultConnection(string $name): void
    {
        $this->defaultConnection = $name;
    }

    /**
     * Switch to a different connection temporarily
     */
    public function using(string $connection, callable $callback): mixed
    {
        $previousConnection = $this->defaultConnection;
        $this->defaultConnection = $connection;

        try {
            return $callback($this->connection());
        } finally {
            $this->defaultConnection = $previousConnection;
        }
    }

    /**
     * Get all connection names
     */
    public function getConnections(): array
    {
        return array_keys($this->connections);
    }

    /**
     * Check if connection exists
     */
    public function hasConnection(string $name): bool
    {
        return isset($this->connections[$name]);
    }

    /**
     * Purge a connection instance
     */
    public function purge(?string $name = null): void
    {
        $name = $name ?? $this->defaultConnection;
        unset($this->connections[$name]);
    }

    /**
     * Disconnect all connections
     */
    public function disconnect(): void
    {
        $this->connections = [];
    }

    /**
     * Get database manager
     */
    public function getManager(): CycleDatabaseManager
    {
        return $this->manager;
    }

    /**
     * Begin transaction on connection
     */
    public function transaction(callable $callback, ?string $connection = null): mixed
    {
        $db = $this->connection($connection);
        
        $db->begin();
        
        try {
            $result = $callback($db);
            $db->commit();
            return $result;
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Execute query on connection
     */
    public function select(string $query, array $bindings = [], ?string $connection = null): array
    {
        return $this->connection($connection)->query($query, $bindings)->fetchAll();
    }

    /**
     * Execute insert query
     */
    public function insert(string $query, array $bindings = [], ?string $connection = null): int
    {
        return $this->connection($connection)->execute($query, $bindings);
    }

    /**
     * Execute update query
     */
    public function update(string $query, array $bindings = [], ?string $connection = null): int
    {
        return $this->connection($connection)->execute($query, $bindings);
    }

    /**
     * Execute delete query
     */
    public function delete(string $query, array $bindings = [], ?string $connection = null): int
    {
        return $this->connection($connection)->execute($query, $bindings);
    }

    /**
     * Execute raw statement
     */
    public function statement(string $query, array $bindings = [], ?string $connection = null): bool
    {
        return $this->connection($connection)->execute($query, $bindings) !== false;
    }
}
