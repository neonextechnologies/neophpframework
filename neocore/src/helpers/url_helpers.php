<?php

declare(strict_types=1);

if (!function_exists('url')) {
    /**
     * Generate a URL to a path
     */
    function url(?string $path = null, mixed $parameters = [], ?bool $secure = null): string
    {
        if ($path === null) {
            return app('url')->to('/');
        }

        return app('url')->to($path, $parameters, $secure);
    }
}

if (!function_exists('route')) {
    /**
     * Generate a URL to a named route
     */
    function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return app('url')->route($name, $parameters, $absolute);
    }
}

if (!function_exists('asset')) {
    /**
     * Generate a URL to an asset
     */
    function asset(string $path, ?bool $secure = null): string
    {
        return app('url')->asset($path, $secure);
    }
}

if (!function_exists('secure_url')) {
    /**
     * Generate a secure URL to a path
     */
    function secure_url(string $path, mixed $parameters = []): string
    {
        return url($path, $parameters, true);
    }
}

if (!function_exists('secure_asset')) {
    /**
     * Generate a secure URL to an asset
     */
    function secure_asset(string $path): string
    {
        return asset($path, true);
    }
}

if (!function_exists('current_url')) {
    /**
     * Get the current URL
     */
    function current_url(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return $protocol . $host . $uri;
    }
}

if (!function_exists('previous_url')) {
    /**
     * Get the previous URL
     */
    function previous_url(?string $default = null): string
    {
        return $_SERVER['HTTP_REFERER'] ?? $default ?? url('/');
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a URL
     */
    function redirect(?string $to = null, int $status = 302, array $headers = []): void
    {
        $to = $to ?? url('/');

        header("Location: {$to}", true, $status);

        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }

        exit;
    }
}

if (!function_exists('redirect_back')) {
    /**
     * Redirect back to previous URL
     */
    function redirect_back(?string $default = null, int $status = 302): void
    {
        redirect(previous_url($default), $status);
    }
}

if (!function_exists('redirect_route')) {
    /**
     * Redirect to a named route
     */
    function redirect_route(string $name, mixed $parameters = [], int $status = 302): void
    {
        redirect(route($name, $parameters), $status);
    }
}
