<?php

declare(strict_types=1);

namespace NeoCore\Support;

use Generator;
use IteratorAggregate;
use Traversable;

/**
 * Lazy Collection
 * 
 * Generator-based memory-efficient collection
 */
class LazyCollection implements IteratorAggregate
{
    protected $source;

    public function __construct(callable|array|Generator $source)
    {
        if (is_array($source)) {
            $this->source = function () use ($source) {
                yield from $source;
            };
        } elseif ($source instanceof Generator) {
            $this->source = function () use ($source) {
                yield from $source;
            };
        } else {
            $this->source = $source;
        }
    }

    /**
     * Create a new lazy collection
     */
    public static function make(callable|array|Generator $source): static
    {
        return new static($source);
    }

    /**
     * Create from range
     */
    public static function range(int $from, int $to): static
    {
        return new static(function () use ($from, $to) {
            for ($i = $from; $i <= $to; $i++) {
                yield $i;
            }
        });
    }

    /**
     * Create from times
     */
    public static function times(int $count, ?callable $callback = null): static
    {
        return new static(function () use ($count, $callback) {
            for ($i = 1; $i <= $count; $i++) {
                yield $callback ? $callback($i) : $i;
            }
        });
    }

    /**
     * Get all items as array
     */
    public function all(): array
    {
        return iterator_to_array($this->getIterator());
    }

    /**
     * Chunk the collection
     */
    public function chunk(int $size): static
    {
        return new static(function () use ($size) {
            $chunk = [];

            foreach ($this as $key => $value) {
                $chunk[$key] = $value;

                if (count($chunk) === $size) {
                    yield new Collection($chunk);
                    $chunk = [];
                }
            }

            if (!empty($chunk)) {
                yield new Collection($chunk);
            }
        });
    }

    /**
     * Collapse multi-dimensional collection
     */
    public function collapse(): static
    {
        return new static(function () {
            foreach ($this as $values) {
                if (is_array($values) || $values instanceof Traversable) {
                    foreach ($values as $value) {
                        yield $value;
                    }
                }
            }
        });
    }

    /**
     * Collect into a collection
     */
    public function collect(): Collection
    {
        return new Collection($this->all());
    }

    /**
     * Check if contains value
     */
    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            if (is_callable($key)) {
                foreach ($this as $item) {
                    if ($key($item)) {
                        return true;
                    }
                }
                return false;
            }

            foreach ($this as $item) {
                if ($item === $key) {
                    return true;
                }
            }
            return false;
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Count items
     */
    public function count(): int
    {
        $count = 0;

        foreach ($this as $item) {
            $count++;
        }

        return $count;
    }

    /**
     * Execute callback for each item
     */
    public function each(callable $callback): static
    {
        foreach ($this as $key => $value) {
            if ($callback($value, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Filter items
     */
    public function filter(?callable $callback = null): static
    {
        if ($callback === null) {
            return new static(function () {
                foreach ($this as $key => $value) {
                    if ($value) {
                        yield $key => $value;
                    }
                }
            });
        }

        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                if ($callback($value, $key)) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * Get first item
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            foreach ($this as $item) {
                return $item;
            }
            return $default;
        }

        foreach ($this as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Flatten the collection
     */
    public function flatten(int $depth = INF): static
    {
        return new static(function () use ($depth) {
            foreach ($this as $item) {
                if (!is_array($item) && !$item instanceof Traversable) {
                    yield $item;
                } elseif ($depth === 1) {
                    yield from $item;
                } else {
                    yield from (new static($item))->flatten($depth - 1);
                }
            }
        });
    }

    /**
     * Map over items
     */
    public function map(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    /**
     * Flat map over items
     */
    public function flatMap(callable $callback): static
    {
        return $this->map($callback)->flatten(1);
    }

    /**
     * Pluck an array of values
     */
    public function pluck(string $value, ?string $key = null): static
    {
        return new static(function () use ($value, $key) {
            foreach ($this as $item) {
                $itemValue = data_get($item, $value);

                if ($key === null) {
                    yield $itemValue;
                } else {
                    $itemKey = data_get($item, $key);
                    yield $itemKey => $itemValue;
                }
            }
        });
    }

    /**
     * Reject items
     */
    public function reject(callable $callback): static
    {
        return $this->filter(function ($value, $key) use ($callback) {
            return !$callback($value, $key);
        });
    }

    /**
     * Reduce the collection
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;

        foreach ($this as $key => $value) {
            $carry = $callback($carry, $value, $key);
        }

        return $carry;
    }

    /**
     * Skip items
     */
    public function skip(int $count): static
    {
        return new static(function () use ($count) {
            $index = 0;

            foreach ($this as $key => $value) {
                if ($index++ >= $count) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * Take items
     */
    public function take(int $limit): static
    {
        if ($limit < 0) {
            return $this->collect()->take($limit)->lazy();
        }

        return new static(function () use ($limit) {
            $count = 0;

            foreach ($this as $key => $value) {
                if ($count++ >= $limit) {
                    break;
                }

                yield $key => $value;
            }
        });
    }

    /**
     * Tap into the collection
     */
    public function tap(callable $callback): static
    {
        $callback($this);

        return $this;
    }

    /**
     * Get unique items
     */
    public function unique(string|callable|null $key = null, bool $strict = false): static
    {
        return new static(function () use ($key, $strict) {
            $exists = [];

            foreach ($this as $k => $item) {
                if ($key === null) {
                    $id = $item;
                } elseif (is_callable($key)) {
                    $id = $key($item, $k);
                } else {
                    $id = data_get($item, $key);
                }

                $id = is_object($id) ? spl_object_hash($id) : $id;

                if (!in_array($id, $exists, $strict)) {
                    yield $k => $item;
                    $exists[] = $id;
                }
            }
        });
    }

    /**
     * Get values
     */
    public function values(): static
    {
        return new static(function () {
            foreach ($this as $value) {
                yield $value;
            }
        });
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
        $values = $this->getArrayableItems($values);

        return $this->filter(function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Filter by where not in
     */
    public function whereNotIn(string $key, mixed $values, bool $strict = false): static
    {
        $values = $this->getArrayableItems($values);

        return $this->filter(function ($item) use ($key, $values, $strict) {
            return !in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Zip with another array
     */
    public function zip(mixed ...$items): static
    {
        $iterators = array_map(function ($items) {
            if (is_array($items)) {
                return new \ArrayIterator($items);
            }
            if ($items instanceof self) {
                return $items->getIterator();
            }
            return $items;
        }, func_get_args());

        return new static(function () use ($iterators) {
            foreach ($this as $key => $value) {
                $result = [$value];

                foreach ($iterators as $iterator) {
                    if ($iterator->valid()) {
                        $result[] = $iterator->current();
                        $iterator->next();
                    } else {
                        break 2;
                    }
                }

                yield $key => new Collection($result);
            }
        });
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
     * Get items as array
     */
    protected function getArrayableItems(mixed $items): array
    {
        if (is_array($items)) {
            return $items;
        }

        if ($items instanceof Collection) {
            return $items->all();
        }

        if ($items instanceof self) {
            return $items->all();
        }

        if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }

    /**
     * Get iterator
     */
    public function getIterator(): Traversable
    {
        return ($this->source)();
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return $this->all();
    }

    /**
     * Convert to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->all(), $options);
    }
}
