<?php

declare(strict_types=1);

namespace NeoCore\Support;

use ArrayAccess;

/**
 * Array Helper Class
 * 
 * Provides array manipulation utilities
 */
class Arr
{
    /**
     * Get an item from an array using "dot" notation
     */
    public static function get(array|ArrayAccess $array, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (!str_contains($key, '.')) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Set an array item using "dot" notation
     */
    public static function set(array &$array, string|int|null $key, mixed $value): array
    {
        if ($key === null) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Check if an item exists in an array using "dot" notation
     */
    public static function has(array|ArrayAccess $array, string|array $keys): bool
    {
        $keys = (array) $keys;

        if (empty($keys)) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (static::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determine if the given key exists in the array
     */
    public static function exists(array|ArrayAccess $array, string|int $key): bool
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Get a subset of the items from the array
     */
    public static function only(array $array, array|string $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Get all items except for those with the specified keys
     */
    public static function except(array $array, array|string $keys): array
    {
        static::forget($array, $keys);

        return $array;
    }

    /**
     * Remove one or many array items using "dot" notation
     */
    public static function forget(array &$array, array|string $keys): void
    {
        $original = &$array;
        $keys = (array) $keys;

        if (empty($keys)) {
            return;
        }

        foreach ($keys as $key) {
            if (static::exists($array, $key)) {
                unset($array[$key]);
                continue;
            }

            $parts = explode('.', $key);
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Flatten a multi-dimensional array into a single level
     */
    public static function flatten(array $array, int $depth = INF): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, array_values($item));
            } else {
                $result = array_merge($result, static::flatten($item, $depth - 1));
            }
        }

        return $result;
    }

    /**
     * Flatten array with keys into a single level using "dot" notation
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Expand a flattened "dot" notation array
     */
    public static function undot(array $array): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            static::set($results, $key, $value);
        }

        return $results;
    }

    /**
     * Pluck an array of values from an array
     */
    public static function pluck(array $array, string|array $value, string|array|null $key = null): array
    {
        $results = [];

        [$value, $key] = static::explodePluckParameters($value, $key);

        foreach ($array as $item) {
            $itemValue = data_get($item, $value);

            if ($key === null) {
                $results[] = $itemValue;
            } else {
                $itemKey = data_get($item, $key);

                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string) $itemKey;
                }

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Filter the array using the given callback
     */
    public static function where(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Filter items by the given key value pair
     */
    public static function whereKey(array $array, string $key, mixed $operator = null, mixed $value = null): array
    {
        return static::where($array, static::operatorForWhere(...func_get_args()));
    }

    /**
     * Filter items by the given key value pair using strict comparison
     */
    public static function whereStrict(array $array, string $key, mixed $value): array
    {
        return static::where($array, function ($item) use ($key, $value) {
            return data_get($item, $key) === $value;
        });
    }

    /**
     * Filter items where the value is not null
     */
    public static function whereNotNull(array $array, ?string $key = null): array
    {
        return static::where($array, function ($item) use ($key) {
            return data_get($item, $key) !== null;
        });
    }

    /**
     * Get the first element in an array
     */
    public static function first(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if (empty($array)) {
                return $default;
            }

            foreach ($array as $item) {
                return $item;
            }
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get the last element in an array
     */
    public static function last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return empty($array) ? $default : end($array);
        }

        return static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Push an item onto the beginning of an array
     */
    public static function prepend(array $array, mixed $value, mixed $key = null): array
    {
        if ($key === null) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    /**
     * Add an element to an array if it doesn't exist
     */
    public static function add(array $array, string|int $key, mixed $value): array
    {
        if (!static::exists($array, $key)) {
            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * Divide an array into two arrays
     */
    public static function divide(array $array): array
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Collapse an array of arrays into a single array
     */
    public static function collapse(array $array): array
    {
        $results = [];

        foreach ($array as $values) {
            if (!is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * Cross join arrays
     */
    public static function crossJoin(array ...$arrays): array
    {
        $results = [[]];

        foreach ($arrays as $index => $array) {
            $append = [];

            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;
                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

    /**
     * Get a random value from an array
     */
    public static function random(array $array, ?int $number = null): mixed
    {
        $requested = $number ?? 1;
        $count = count($array);

        if ($requested > $count) {
            throw new \InvalidArgumentException(
                "You requested {$requested} items, but there are only {$count} items available."
            );
        }

        if ($number === null) {
            return $array[array_rand($array)];
        }

        if ($number === 0) {
            return [];
        }

        $keys = array_rand($array, $number);

        $results = [];

        foreach ((array) $keys as $key) {
            $results[] = $array[$key];
        }

        return $results;
    }

    /**
     * Shuffle array
     */
    public static function shuffle(array $array, ?int $seed = null): array
    {
        if ($seed === null) {
            shuffle($array);
        } else {
            mt_srand($seed);
            shuffle($array);
            mt_srand();
        }

        return $array;
    }

    /**
     * Sort array
     */
    public static function sort(array $array, ?callable $callback = null): array
    {
        $callback ? uasort($array, $callback) : asort($array);

        return $array;
    }

    /**
     * Sort array recursively
     */
    public static function sortRecursive(array $array, int $options = SORT_REGULAR, bool $descending = false): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = static::sortRecursive($value, $options, $descending);
            }
        }

        if (static::isAssoc($array)) {
            $descending
                ? krsort($array, $options)
                : ksort($array, $options);
        } else {
            $descending
                ? rsort($array, $options)
                : sort($array, $options);
        }

        return $array;
    }

    /**
     * Wrap value in array if not already array
     */
    public static function wrap(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Determine if array is associative
     */
    public static function isAssoc(array $array): bool
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    /**
     * Determine if array is a list (sequential)
     */
    public static function isList(array $array): bool
    {
        return !static::isAssoc($array);
    }

    /**
     * Join array elements with a string
     */
    public static function join(array $array, string $glue, string $finalGlue = ''): string
    {
        if ($finalGlue === '') {
            return implode($glue, $array);
        }

        if (count($array) === 0) {
            return '';
        }

        if (count($array) === 1) {
            return end($array);
        }

        $finalItem = array_pop($array);

        return implode($glue, $array) . $finalGlue . $finalItem;
    }

    /**
     * Check if value is accessible as array
     */
    public static function accessible(mixed $value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Explode pluck parameters
     */
    protected static function explodePluckParameters(string|array $value, string|array|null $key): array
    {
        $value = is_string($value) ? explode('.', $value) : $value;
        $key = $key === null || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * Get operator for where
     */
    protected static function operatorForWhere(array $array, string $key, mixed $operator = null, mixed $value = null): callable
    {
        if (func_num_args() === 2) {
            $value = true;
            $operator = '=';
        }

        if (func_num_args() === 3) {
            $value = $operator;
            $operator = '=';
        }

        return function ($item) use ($key, $operator, $value) {
            $retrieved = data_get($item, $key);

            return match ($operator) {
                '=', '==' => $retrieved == $value,
                '!=', '<>' => $retrieved != $value,
                '<' => $retrieved < $value,
                '>' => $retrieved > $value,
                '<=' => $retrieved <= $value,
                '>=' => $retrieved >= $value,
                '===' => $retrieved === $value,
                '!==' => $retrieved !== $value,
                default => false,
            };
        };
    }
}
