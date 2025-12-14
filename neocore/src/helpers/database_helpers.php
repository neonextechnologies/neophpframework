<?php

if (!function_exists('broadcast')) {
    /**
     * Broadcast an event
     */
    function broadcast(string $channel, string $event, array $data = []): void
    {
        app(\NeoCore\Broadcasting\BroadcastManager::class)->broadcast($channel, $event, $data);
    }
}

if (!function_exists('db')) {
    /**
     * Get database connection
     */
    function db(?string $connection = null): \Cycle\Database\DatabaseInterface
    {
        return app(\NeoCore\Database\ConnectionManager::class)->connection($connection);
    }
}

if (!function_exists('query_log')) {
    /**
     * Get query logger
     */
    function query_log(): \NeoCore\Database\QueryLogger
    {
        return app(\NeoCore\Database\QueryLogger::class);
    }
}
