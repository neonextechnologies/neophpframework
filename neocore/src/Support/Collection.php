<?php

declare(strict_types=1);

namespace NeoCore\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Collection
 * 
 * Array wrapper with fluent interface
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    protected array $items = [];

    /**
     * Methods that support higher order messages
     */
    protected static array $proxies = [
        'average', 'avg', 'contains', 'each', 'every', 'filter', 'first',
        'flatMap', 'map', 'partition', 'reject', 'sortBy', 'sortByDesc',
        'sum', 'unique',
    ];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Create a new collection
     */
    public static function make(mixed $items = []): static
    {
        return new static(static::getArrayableItems($items));
    }

    /**
     * Get all items
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get the average value
     */
    public function avg(?callable $callback = null): float|int|null
    {
        $count = $this->count();

        if ($count === 0) {
            return null;
        }

        $items = $callback ? $this->map($callback) : $this;

        return array_sum($items->all()) / $count;
    }

    /**
     * Chunk the collection
     */
    public function chunk(int $size): static
    {
        $chunks = [];

        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * Collapse the collection
     */
    public function collapse(): static
    {
        $results = [];

        foreach ($this->items as $values) {
            if ($values instanceof static) {
                $values = $values->all();
            } elseif (!is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return new static($results);
    }

    /**
     * Combine the collection
     */
    public function combine(mixed $values): static
    {
        return new static(array_combine($this->all(), static::getArrayableItems($values)));
    }

    /**
     * Concatenate values
     */
    public function concat(mixed $source): static
    {
        $result = new static($this->items);

        foreach (static::getArrayableItems($source) as $item) {
            $result->push($item);
        }

        return $result;
    }

    /**
     * Check if collection contains a value
     */
    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            if ($this->useAsCallable($key)) {
                return $this->first($key) !== null;
            }

            return in_array($key, $this->items);
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Count items
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Diff the collection
     */
    public function diff(mixed $items): static
    {
        return new static(array_diff($this->items, static::getArrayableItems($items)));
    }

    /**
     * Execute callback for each item
     */
    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Check if collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Check if collection is not empty
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Filter items
     */
    public function filter(?callable $callback = null): static
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }

        return new static(array_filter($this->items));
    }

    /**
     * Get first item
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if (empty($this->items)) {
                return $default;
            }

            foreach ($this->items as $item) {
                return $item;
            }
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get last item
     */
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return empty($this->items) ? $default : end($this->items);
        }

        return $this->reverse()->first($callback, $default);
    }

    /**
     * Flatten the collection
     */
    public function flatten(int $depth = INF): static
    {
        return new static($this->flattenArray($this->items, $depth));
    }

    /**
     * Flip the items
     */
    public function flip(): static
    {
        return new static(array_flip($this->items));
    }

    /**
     * Get an item by key
     */
    public function get(mixed $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        return $default;
    }

    /**
     * Group items by a key
     */
    public function groupBy(callable|string $groupBy, bool $preserveKeys = false): static
    {
        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKey = is_callable($groupBy) ? $groupBy($value, $key) : data_get($value, $groupBy);

            if (!array_key_exists($groupKey, $results)) {
                $results[$groupKey] = new static();
            }

            $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
        }

        return new static($results);
    }

    /**
     * Check if key exists
     */
    public function has(mixed $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Implode items
     */
    public function implode(string $value, ?string $glue = null): string
    {
        if ($glue === null) {
            return implode($value, $this->items);
        }

        return implode($glue, $this->pluck($value)->all());
    }

    /**
     * Intersect the collection
     */
    public function intersect(mixed $items): static
    {
        return new static(array_intersect($this->items, static::getArrayableItems($items)));
    }

    /**
     * Join items with a string
     */
    public function join(string $glue, string $finalGlue = ''): string
    {
        if ($finalGlue === '') {
            return $this->implode($glue);
        }

        $count = $this->count();

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $this->last();
        }

        $collection = new static($this->items);
        $finalItem = $collection->pop();

        return $collection->implode($glue) . $finalGlue . $finalItem;
    }

    /**
     * Get keys
     */
    public function keys(): static
    {
        return new static(array_keys($this->items));
    }

    /**
     * Map over items
     */
    public function map(callable $callback): static
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);

        return new static(array_combine($keys, $items));
    }

    /**
     * Merge with another collection
     */
    public function merge(mixed $items): static
    {
        return new static(array_merge($this->items, static::getArrayableItems($items)));
    }

    /**
     * Get max value
     */
    public function max(callable|string|null $callback = null): mixed
    {
        if ($callback === null) {
            return max($this->items);
        }

        return $this->map($callback)->max();
    }

    /**
     * Get min value
     */
    public function min(callable|string|null $callback = null): mixed
    {
        if ($callback === null) {
            return min($this->items);
        }

        return $this->map($callback)->min();
    }

    /**
     * Get nth items
     */
    public function nth(int $step, int $offset = 0): static
    {
        $new = [];
        $position = 0;

        foreach ($this->items as $key => $item) {
            if ($position % $step === $offset) {
                $new[$key] = $item;
            }

            $position++;
        }

        return new static($new);
    }

    /**
     * Get only specified keys
     */
    public function only(mixed $keys): static
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        return new static(array_intersect_key($this->items, array_flip($keys)));
    }

    /**
     * Pluck an array of values
     */
    public function pluck(string $value, ?string $key = null): static
    {
        $results = [];

        foreach ($this->items as $item) {
            $itemValue = data_get($item, $value);

            if ($key === null) {
                $results[] = $itemValue;
            } else {
                $itemKey = data_get($item, $key);
                $results[$itemKey] = $itemValue;
            }
        }

        return new static($results);
    }

    /**
     * Pop an item
     */
    public function pop(int $count = 1): mixed
    {
        if ($count === 1) {
            return array_pop($this->items);
        }

        if ($this->isEmpty()) {
            return new static();
        }

        $results = [];
        $collectionCount = $this->count();

        foreach (range(1, min($count, $collectionCount)) as $item) {
            $results[] = array_pop($this->items);
        }

        return new static($results);
    }

    /**
     * Push an item onto the collection
     */
    public function push(mixed ...$values): static
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }

        return $this;
    }

    /**
     * Prepend an item
     */
    public function prepend(mixed $value, mixed $key = null): static
    {
        if ($key === null) {
            array_unshift($this->items, $value);
        } else {
            $this->items = [$key => $value] + $this->items;
        }

        return $this;
    }

    /**
     * Put an item in the collection
     */
    public function put(mixed $key, mixed $value): static
    {
        $this->items[$key] = $value;

        return $this;
    }

    /**
     * Reduce the collection
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Reject items
     */
    public function reject(callable $callback): static
    {
        return $this->filter(function ($item, $key) use ($callback) {
            return !$callback($item, $key);
        });
    }

    /**
     * Reverse items
     */
    public function reverse(): static
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * Search for a value
     */
    public function search(mixed $value, bool $strict = false): int|string|false
    {
        if (!$this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }

        foreach ($this->items as $key => $item) {
            if ($value($item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Shift an item off
     */
    public function shift(int $count = 1): mixed
    {
        if ($count === 1) {
            return array_shift($this->items);
        }

        if ($this->isEmpty()) {
            return new static();
        }

        $results = [];

        foreach (range(1, min($count, $this->count())) as $item) {
            $results[] = array_shift($this->items);
        }

        return new static($results);
    }

    /**
     * Shuffle items
     */
    public function shuffle(): static
    {
        $items = $this->items;
        shuffle($items);

        return new static($items);
    }

    /**
     * Slice the collection
     */
    public function slice(int $offset, ?int $length = null): static
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Sort items
     */
    public function sort(?callable $callback = null): static
    {
        $items = $this->items;

        $callback ? uasort($items, $callback) : asort($items);

        return new static($items);
    }

    /**
     * Sort by key
     */
    public function sortBy(callable|string $callback, int $options = SORT_REGULAR, bool $descending = false): static
    {
        $results = [];

        foreach ($this->items as $key => $value) {
            $results[$key] = is_callable($callback) ? $callback($value, $key) : data_get($value, $callback);
        }

        $descending ? arsort($results, $options) : asort($results, $options);

        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        return new static($results);
    }

    /**
     * Sort by descending
     */
    public function sortByDesc(callable|string $callback, int $options = SORT_REGULAR): static
    {
        return $this->sortBy($callback, $options, true);
    }

    /**
     * Sort keys
     */
    public function sortKeys(int $options = SORT_REGULAR, bool $descending = false): static
    {
        $items = $this->items;

        $descending ? krsort($items, $options) : ksort($items, $options);

        return new static($items);
    }

    /**
     * Sum of values
     */
    public function sum(callable|string|null $callback = null): int|float
    {
        if ($callback === null) {
            return array_sum($this->items);
        }

        return $this->map($callback)->sum();
    }

    /**
     * Take the first or last items
     */
    public function take(int $limit): static
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * Transform each item
     */
    public function transform(callable $callback): static
    {
        $this->items = $this->map($callback)->all();

        return $this;
    }

    /**
     * Get unique items
     */
    public function unique(string|callable|null $key = null, bool $strict = false): static
    {
        if ($key === null) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }

        $exists = [];
        $items = [];

        foreach ($this->items as $k => $item) {
            $value = is_callable($key) ? $key($item, $k) : data_get($item, $key);
            $id = is_object($value) ? spl_object_hash($value) : $value;

            if (!in_array($id, $exists, $strict)) {
                $exists[] = $id;
                $items[$k] = $item;
            }
        }

        return new static($items);
    }

    /**
     * Get values
     */
    public function values(): static
    {
        return new static(array_values($this->items));
    }

    /**
     * Filter by where clause
     */
    public function where(string $key, mixed $operator = null, mixed $value = null): static
    {
        return $this->filter($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Filter by where in
     */
    public function whereIn(string $key, mixed $values, bool $strict = false): static
    {
        $values = static::getArrayableItems($values);

        return $this->filter(function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Filter by where not in
     */
    public function whereNotIn(string $key, mixed $values, bool $strict = false): static
    {
        $values = static::getArrayableItems($values);

        return $this->filter(function ($item) use ($key, $values, $strict) {
            return !in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Zip with another array
     */
    public function zip(mixed ...$items): static
    {
        $arrayableItems = array_map(function ($items) {
            return static::getArrayableItems($items);
        }, func_get_args());

        $params = array_merge([function (...$items) {
            return new static($items);
        }, $this->items], $arrayableItems);

        return new static(array_map(...$params));
    }

    /**
     * Get items as array
     */
    protected static function getArrayableItems(mixed $items): array
    {
        if (is_array($items)) {
            return $items;
        }

        if ($items instanceof self) {
            return $items->all();
        }

        if ($items instanceof JsonSerializable) {
            return $items->jsonSerialize();
        }

        if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }

    /**
     * Flatten array
     */
    protected function flattenArray(array $array, int $depth): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, array_values($item));
            } else {
                $result = array_merge($result, $this->flattenArray($item, $depth - 1));
            }
        }

        return $result;
    }

    /**
     * Get operator for where
     */
    protected function operatorForWhere(string $key, mixed $operator = null, mixed $value = null): callable
    {
        if (func_num_args() === 1) {
            $value = true;
            $operator = '=';
        }

        if (func_num_args() === 2) {
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

    /**
     * Check if value is callable
     */
    protected function useAsCallable(mixed $value): bool
    {
        return !is_string($value) && is_callable($value);
    }

    // ArrayAccess implementation
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    // IteratorAggregate implementation
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    // JsonSerializable implementation
    public function jsonSerialize(): array
    {
        return $this->items;
    }

    // Convert to JSON
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    // Convert to lazy collection
    public function lazy(): LazyCollection
    {
        return new LazyCollection($this->items);
    }

    // Convert to string
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Dynamically access collection proxies
     */
    public function __get(string $key): mixed
    {
        if (!in_array($key, static::$proxies)) {
            throw new \Exception("Property [{$key}] does not exist on this collection instance.");
        }

        return new HigherOrderCollectionProxy($this, $key);
    }
}
