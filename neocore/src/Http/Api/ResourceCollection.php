<?php

declare(strict_types=1);

namespace NeoCore\Http\Api;

use JsonSerializable;
use IteratorAggregate;
use ArrayIterator;
use Traversable;

/**
 * Resource Collection
 * 
 * Transform collections of models/entities into API responses
 */
class ResourceCollection implements JsonSerializable, IteratorAggregate
{
    protected iterable $collection;
    protected string $resourceClass;
    protected array $additional = [];

    public function __construct(iterable $collection, string $resourceClass)
    {
        $this->collection = $collection;
        $this->resourceClass = $resourceClass;
    }

    /**
     * Transform the collection into an array
     */
    public function toArray(): array
    {
        $data = [];

        foreach ($this->collection as $item) {
            $resource = new $this->resourceClass($item);
            $data[] = $resource->toArray();
        }

        return $data;
    }

    /**
     * Get additional data
     */
    public function with(): array
    {
        return [];
    }

    /**
     * Add additional metadata
     */
    public function additional(array $data): self
    {
        $this->additional = array_merge($this->additional, $data);
        return $this;
    }

    /**
     * Convert the collection to JSON
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Get iterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * Convert the collection to a response
     */
    public function toResponse(int $status = 200): \NeoCore\Http\JsonResponse
    {
        $data = [
            'success' => true,
            'data' => $this->toArray(),
        ];

        if (!empty($this->additional)) {
            $data['meta'] = $this->additional;
        }

        if (!empty($this->with())) {
            $data = array_merge($data, $this->with());
        }

        return new \NeoCore\Http\JsonResponse($data, $status);
    }

    /**
     * Create a paginated collection
     */
    public static function paginated(
        iterable $items,
        string $resourceClass,
        int $total,
        int $perPage,
        int $currentPage
    ): self {
        $collection = new self($items, $resourceClass);

        $collection->additional([
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => (int) ceil($total / $perPage),
                'from' => ($currentPage - 1) * $perPage + 1,
                'to' => min($currentPage * $perPage, $total),
            ],
        ]);

        return $collection;
    }

    /**
     * Count items in collection
     */
    public function count(): int
    {
        if (is_array($this->collection)) {
            return count($this->collection);
        }

        if ($this->collection instanceof \Countable) {
            return $this->collection->count();
        }

        return iterator_count($this->getIterator());
    }

    /**
     * Check if collection is empty
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Convert to JSON string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
