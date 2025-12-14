<?php

declare(strict_types=1);

use NeoCore\Support\Collection;
use NeoCore\Support\LazyCollection;

if (!function_exists('collect')) {
    /**
     * Create a new collection
     */
    function collect(mixed $value = []): Collection
    {
        return new Collection($value);
    }
}

if (!function_exists('lazy')) {
    /**
     * Create a new lazy collection
     */
    function lazy(callable|array|\Generator $source): LazyCollection
    {
        return new LazyCollection($source);
    }
}

if (!function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation
     */
    function data_get(mixed $target, string|array|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $segment) {
            if (is_array($target)) {
                if (!array_key_exists($segment, $target)) {
                    return $default;
                }
                $target = $target[$segment];
            } elseif (is_object($target)) {
                if (!isset($target->{$segment})) {
                    return $default;
                }
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }
}

if (!function_exists('data_set')) {
    /**
     * Set an item on an array or object using "dot" notation
     */
    function data_set(mixed &$target, string|array $key, mixed $value, bool $overwrite = true): mixed
    {
        $segments = is_array($key) ? $key : explode('.', $key);
        $segment = array_shift($segments);

        if (empty($segments)) {
            if (is_array($target)) {
                if ($overwrite || !array_key_exists($segment, $target)) {
                    $target[$segment] = $value;
                }
            } elseif (is_object($target)) {
                if ($overwrite || !isset($target->{$segment})) {
                    $target->{$segment} = $value;
                }
            }
        } else {
            if (is_array($target)) {
                if (!isset($target[$segment]) || !is_array($target[$segment])) {
                    $target[$segment] = [];
                }
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif (is_object($target)) {
                if (!isset($target->{$segment}) || !is_object($target->{$segment})) {
                    $target->{$segment} = new \stdClass();
                }
                data_set($target->{$segment}, $segments, $value, $overwrite);
            }
        }

        return $target;
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value
     */
    function value(mixed $value, ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}
