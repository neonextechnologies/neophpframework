<?php

declare(strict_types=1);

use NeoCore\Auth\Access\Gate;
use NeoCore\Auth\Access\AuthorizationException;

if (!function_exists('can')) {
    /**
     * Determine if the current user has a given ability
     */
    function can(string $ability, $arguments = []): bool
    {
        return app(Gate::class)->allows($ability, $arguments);
    }
}

if (!function_exists('cannot')) {
    /**
     * Determine if the current user does not have a given ability
     */
    function cannot(string $ability, $arguments = []): bool
    {
        return app(Gate::class)->denies($ability, $arguments);
    }
}

if (!function_exists('authorize')) {
    /**
     * Authorize a given ability or throw an exception
     */
    function authorize(string $ability, $arguments = []): void
    {
        app(Gate::class)->authorize($ability, $arguments);
    }
}

if (!function_exists('policy')) {
    /**
     * Get a policy instance for the given class
     */
    function policy($class): ?object
    {
        $gate = app(Gate::class);
        // This would need implementation in Gate to retrieve policy
        return null;
    }
}

if (!function_exists('gate')) {
    /**
     * Get the Gate instance
     */
    function gate(): Gate
    {
        return app(Gate::class);
    }
}
