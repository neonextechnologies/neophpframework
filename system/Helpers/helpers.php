<?php

/**
 * Helper Functions for NeoCore
 * 
 * Simple utility functions. No magic.
 */

if (!function_exists('env')) {
    /**
     * Get environment variable
     */
    function env(string $key, $default = null)
    {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Parse boolean values
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        
        return $value;
    }
}

if (!function_exists('base_path')) {
    /**
     * Get base path
     */
    function base_path(string $path = ''): string
    {
        return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get storage path
     */
    function storage_path(string $path = ''): string
    {
        return STORAGE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('config_path')) {
    /**
     * Get config path
     */
    function config_path(string $path = ''): string
    {
        return CONFIG_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die
     */
    function dd(...$vars): void
    {
        foreach ($vars as $var) {
            var_dump($var);
        }
        die(1);
    }
}

if (!function_exists('logger')) {
    /**
     * Simple file logger
     */
    function logger(string $message, string $level = 'info'): void
    {
        $logFile = STORAGE_PATH . '/logs/app.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('response_json')) {
    /**
     * Quick JSON response
     */
    function response_json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('abort')) {
    /**
     * Abort with error response
     */
    function abort(int $statusCode = 404, string $message = 'Not Found'): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => $message,
            'status' => $statusCode
        ]);
        exit;
    }
}

if (!function_exists('view')) {
    /**
     * Render a view
     */
    function view(string $template, array $data = []): string
    {
        return \NeoCore\System\Core\ViewService::render($template, $data);
    }
}

if (!function_exists('orm')) {
    /**
     * Get ORM instance
     */
    function orm(): \Cycle\ORM\ORM
    {
        return \NeoCore\System\Core\ORMService::getORM();
    }
}

if (!function_exists('repository')) {
    /**
     * Get entity repository
     */
    function repository(string $entity): \Cycle\ORM\RepositoryInterface
    {
        return \NeoCore\System\Core\ORMService::getRepository($entity);
    }
}

if (!function_exists('entity_manager')) {
    /**
     * Get entity manager
     */
    function entity_manager(): \Cycle\ORM\EntityManagerInterface
    {
        return \NeoCore\System\Core\ORMService::getEntityManager();
    }
}
