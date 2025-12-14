<?php

declare(strict_types=1);

namespace NeoCore\Http\Api;

use JsonSerializable;

/**
 * API Resource
 * 
 * Transform models/entities into API responses
 */
abstract class Resource implements JsonSerializable
{
    protected mixed $resource;

    public function __construct(mixed $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transform the resource into an array
     */
    abstract public function toArray(): array;

    /**
     * Create a new resource instance
     */
    public static function make(mixed $resource): static
    {
        return new static($resource);
    }

    /**
     * Create a resource collection
     */
    public static function collection(iterable $resources): ResourceCollection
    {
        return new ResourceCollection($resources, static::class);
    }

    /**
     * Get additional data that should be returned with the resource array
     */
    public function with(): array
    {
        return [];
    }

    /**
     * Get the resource's additional metadata
     */
    public function additional(): array
    {
        return [];
    }

    /**
     * Convert the resource to an array (for JSON serialization)
     */
    public function jsonSerialize(): mixed
    {
        return $this->resolve();
    }

    /**
     * Resolve the resource to an array
     */
    public function resolve(): array
    {
        $data = $this->toArray();

        if (!empty($this->with())) {
            $data = array_merge($data, $this->with());
        }

        return $data;
    }

    /**
     * Convert the resource to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the resource to a response
     */
    public function toResponse(int $status = 200): \NeoCore\Http\JsonResponse
    {
        $data = [
            'success' => true,
            'data' => $this->resolve(),
        ];

        if (!empty($this->additional())) {
            $data['meta'] = $this->additional();
        }

        return new \NeoCore\Http\JsonResponse($data, $status);
    }

    /**
     * When a resource is conditional
     */
    public function when(bool $condition, mixed $value, mixed $default = null): mixed
    {
        return $condition ? $value : $default;
    }

    /**
     * Merge data conditionally
     */
    public function mergeWhen(bool $condition, array $data): array
    {
        return $condition ? $data : [];
    }

    /**
     * Get attribute from resource
     */
    protected function getAttribute(string $key): mixed
    {
        if (is_array($this->resource)) {
            return $this->resource[$key] ?? null;
        }

        if (is_object($this->resource)) {
            return $this->resource->$key ?? null;
        }

        return null;
    }
}
