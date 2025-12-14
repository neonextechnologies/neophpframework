<?php

declare(strict_types=1);

namespace NeoCore\Support;

/**
 * Higher Order Collection Proxy
 * 
 * Allows collection methods to be called on each item
 * Example: $collection->each->method()
 */
class HigherOrderCollectionProxy
{
    protected Collection $collection;
    protected string $method;

    public function __construct(Collection $collection, string $method)
    {
        $this->collection = $collection;
        $this->method = $method;
    }

    /**
     * Proxy accessing an attribute onto the collection items
     */
    public function __get(string $key): mixed
    {
        return $this->collection->{$this->method}(function ($value) use ($key) {
            return is_array($value) ? $value[$key] : $value->{$key};
        });
    }

    /**
     * Proxy a method call onto the collection items
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->collection->{$this->method}(function ($value) use ($method, $parameters) {
            return $value->{$method}(...$parameters);
        });
    }
}
