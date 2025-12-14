<?php

declare(strict_types=1);

if (!function_exists('dd')) {
    /**
     * Dump and die
     */
    function dd(mixed ...$vars): never
    {
        foreach ($vars as $var) {
            var_dump($var);
        }

        die(1);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump variables
     */
    function dump(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            var_dump($var);
        }
    }
}

if (!function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value
     */
    function tap(mixed $value, ?callable $callback = null): mixed
    {
        if ($callback === null) {
            return new class($value)
            {
                public function __construct(public mixed $target)
                {
                }

                public function __call(string $method, array $parameters): mixed
                {
                    $this->target->{$method}(...$parameters);

                    return $this->target;
                }
            };
        }

        $callback($value);

        return $value;
    }
}

if (!function_exists('retry')) {
    /**
     * Retry a callback a given number of times
     */
    function retry(int $times, callable $callback, int $sleep = 0): mixed
    {
        $attempts = 0;

        beginning:
        $attempts++;

        try {
            return $callback($attempts);
        } catch (Throwable $e) {
            if ($attempts >= $times) {
                throw $e;
            }

            if ($sleep > 0) {
                usleep($sleep * 1000);
            }

            goto beginning;
        }
    }
}

if (!function_exists('optional')) {
    /**
     * Provide access to optional object
     */
    function optional(?object $value = null, ?callable $callback = null): mixed
    {
        if ($callback === null) {
            return new class($value)
            {
                public function __construct(protected ?object $target)
                {
                }

                public function __get(string $key): mixed
                {
                    return $this->target->{$key} ?? null;
                }

                public function __call(string $method, array $parameters): mixed
                {
                    if ($this->target === null) {
                        return null;
                    }

                    return $this->target->{$method}(...$parameters);
                }
            };
        }

        return $value ? $callback($value) : null;
    }
}

if (!function_exists('with')) {
    /**
     * Return the given value, optionally passed through a callback
     */
    function with(mixed $value, ?callable $callback = null): mixed
    {
        return $callback === null ? $value : $callback($value);
    }
}

if (!function_exists('transform')) {
    /**
     * Transform a value if it is present
     */
    function transform(mixed $value, callable $callback, mixed $default = null): mixed
    {
        if (filled($value)) {
            return $callback($value);
        }

        return $default;
    }
}

if (!function_exists('blank')) {
    /**
     * Determine if the given value is "blank"
     */
    function blank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (!function_exists('filled')) {
    /**
     * Determine if a value is "filled"
     */
    function filled(mixed $value): bool
    {
        return !blank($value);
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class basename
     */
    function class_basename(object|string $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('class_uses_recursive')) {
    /**
     * Get all traits used by a class recursively
     */
    function class_uses_recursive(object|string $class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (!function_exists('trait_uses_recursive')) {
    /**
     * Get all traits used by a trait recursively
     */
    function trait_uses_recursive(string $trait): array
    {
        $traits = class_uses($trait) ?: [];

        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}

if (!function_exists('windows_os')) {
    /**
     * Determine whether the current environment is Windows based
     */
    function windows_os(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}

if (!function_exists('throw_if')) {
    /**
     * Throw the given exception if the given condition is true
     */
    function throw_if(mixed $condition, Throwable|string $exception = 'RuntimeException', mixed ...$parameters): mixed
    {
        if ($condition) {
            if (is_string($exception) && class_exists($exception)) {
                $exception = new $exception(...$parameters);
            }

            throw is_string($exception) ? new RuntimeException($exception) : $exception;
        }

        return $condition;
    }
}

if (!function_exists('throw_unless')) {
    /**
     * Throw the given exception unless the given condition is true
     */
    function throw_unless(mixed $condition, Throwable|string $exception = 'RuntimeException', mixed ...$parameters): mixed
    {
        throw_if(!$condition, $exception, ...$parameters);

        return $condition;
    }
}
