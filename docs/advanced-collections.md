# Collections

Powerful array manipulation with fluent interface.

## Creating Collections

### From Array

```php
use NeoPhp\Support\Collection;

$collection = collect([1, 2, 3, 4, 5]);

$collection = new Collection([1, 2, 3]);

// From range
$numbers = collect(range(1, 10));
```

## Available Methods

### all()

```php
$collection = collect([1, 2, 3]);
$collection->all(); // [1, 2, 3]
```

### map()

```php
$collection = collect([1, 2, 3, 4, 5]);

$multiplied = $collection->map(function ($item) {
    return $item * 2;
});
// [2, 4, 6, 8, 10]

// With keys
$users = collect($users)->map(function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
```

### filter()

```php
$collection = collect([1, 2, 3, 4, 5]);

$filtered = $collection->filter(function ($value) {
    return $value > 2;
});
// [3, 4, 5]

// Remove null values
$collection = collect([1, null, 2, null, 3]);
$filtered = $collection->filter(); // [1, 2, 3]
```

### reduce()

```php
$collection = collect([1, 2, 3, 4, 5]);

$sum = $collection->reduce(function ($carry, $item) {
    return $carry + $item;
}, 0);
// 15
```

### each()

```php
$collection = collect([1, 2, 3]);

$collection->each(function ($item) {
    echo $item;
});

// Stop iteration
$collection->each(function ($item) {
    if ($item === 3) {
        return false;
    }
    echo $item;
});
```

### pluck()

```php
$users = collect([
    ['id' => 1, 'name' => 'John'],
    ['id' => 2, 'name' => 'Jane'],
]);

$names = $users->pluck('name');
// ['John', 'Jane']

// With keys
$names = $users->pluck('name', 'id');
// [1 => 'John', 2 => 'Jane']
```

### where()

```php
$collection = collect([
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25],
    ['name' => 'Bob', 'age' => 30],
]);

$filtered = $collection->where('age', 30);
// [['name' => 'John', 'age' => 30], ['name' => 'Bob', 'age' => 30]]

// Operators
$filtered = $collection->where('age', '>', 25);
$filtered = $collection->where('age', '>=', 30);
```

### first() / last()

```php
$collection = collect([1, 2, 3, 4, 5]);

$first = $collection->first(); // 1
$last = $collection->last(); // 5

// With callback
$first = $collection->first(function ($value) {
    return $value > 2;
}); // 3
```

### take() / skip()

```php
$collection = collect([1, 2, 3, 4, 5]);

$taken = $collection->take(3); // [1, 2, 3]
$skipped = $collection->skip(2); // [3, 4, 5]

// Negative
$taken = $collection->take(-2); // [4, 5]
```

### chunk()

```php
$collection = collect([1, 2, 3, 4, 5, 6]);

$chunks = $collection->chunk(2);
// [[1, 2], [3, 4], [5, 6]]
```

### groupBy()

```php
$collection = collect([
    ['name' => 'John', 'department' => 'Sales'],
    ['name' => 'Jane', 'department' => 'IT'],
    ['name' => 'Bob', 'department' => 'Sales'],
]);

$grouped = $collection->groupBy('department');
/*
[
    'Sales' => [
        ['name' => 'John', 'department' => 'Sales'],
        ['name' => 'Bob', 'department' => 'Sales'],
    ],
    'IT' => [
        ['name' => 'Jane', 'department' => 'IT'],
    ],
]
*/
```

### sort() / sortBy()

```php
$collection = collect([5, 3, 1, 4, 2]);

$sorted = $collection->sort(); // [1, 2, 3, 4, 5]
$sortedDesc = $collection->sortDesc(); // [5, 4, 3, 2, 1]

// Sort by key
$users = collect([
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25],
]);

$sorted = $users->sortBy('age');
$sorted = $users->sortByDesc('age');
```

### unique()

```php
$collection = collect([1, 2, 2, 3, 3, 4]);

$unique = $collection->unique(); // [1, 2, 3, 4]

// By key
$users = collect([
    ['id' => 1, 'email' => 'john@example.com'],
    ['id' => 2, 'email' => 'john@example.com'],
]);

$unique = $users->unique('email');
```

### flatten()

```php
$collection = collect([
    'name' => 'John',
    'languages' => ['PHP', 'JavaScript'],
]);

$flattened = $collection->flatten();
// ['John', 'PHP', 'JavaScript']

// Depth
$collection = collect([
    [1, [2, [3, 4]]],
]);

$flattened = $collection->flatten(1);
// [1, 2, [3, 4]]
```

### merge()

```php
$collection = collect(['a' => 1, 'b' => 2]);
$merged = $collection->merge(['b' => 3, 'c' => 4]);
// ['a' => 1, 'b' => 3, 'c' => 4]
```

### sum() / avg() / min() / max()

```php
$collection = collect([1, 2, 3, 4, 5]);

$sum = $collection->sum(); // 15
$avg = $collection->avg(); // 3
$min = $collection->min(); // 1
$max = $collection->max(); // 5

// By key
$items = collect([
    ['price' => 10],
    ['price' => 20],
]);

$total = $items->sum('price'); // 30
```

### contains()

```php
$collection = collect([1, 2, 3, 4, 5]);

$collection->contains(3); // true
$collection->contains(6); // false

// With callback
$collection->contains(function ($value) {
    return $value > 5;
}); // false

// By key-value
$users = collect([
    ['name' => 'John', 'age' => 30],
]);

$users->contains('name', 'John'); // true
```

### isEmpty() / isNotEmpty()

```php
$collection = collect([]);
$collection->isEmpty(); // true
$collection->isNotEmpty(); // false
```

### count()

```php
$collection = collect([1, 2, 3]);
$collection->count(); // 3
```

### toArray() / toJson()

```php
$collection = collect([1, 2, 3]);

$array = $collection->toArray(); // [1, 2, 3]
$json = $collection->toJson(); // "[1,2,3]"
```

## Higher Order Messages

### Shorthand Methods

```php
$users = collect([
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25],
]);

// Instead of
$names = $users->map(function ($user) {
    return $user['name'];
});

// Use higher order message
$names = $users->map->name;

// Method calls
$users->each->markAsRead();
```

## Lazy Collections

### Memory Efficient

```php
use NeoPhp\Support\LazyCollection;

// Process large file
$lines = LazyCollection::make(function () {
    $handle = fopen('large-file.txt', 'r');
    
    while (($line = fgets($handle)) !== false) {
        yield $line;
    }
    
    fclose($handle);
});

$processed = $lines
    ->filter(function ($line) {
        return strlen($line) > 10;
    })
    ->map(function ($line) {
        return strtoupper($line);
    })
    ->take(1000);
```

## Chaining Methods

### Fluent Interface

```php
$users = collect($users)
    ->where('active', true)
    ->sortBy('name')
    ->take(10)
    ->map(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    })
    ->values();
```

## Best Practices

1. **Immutability** - Most methods return new collection
2. **Lazy Loading** - Use LazyCollection for large datasets
3. **Chaining** - Chain methods for readability
4. **Type Safety** - Document collection contents
5. **Performance** - Be aware of multiple iterations
6. **Higher Order** - Use shorthand when possible
7. **Memory** - Use lazy collections for large data

## See Also

- [Helpers](helpers.md)
- [Database Query Builder](../database/query-builder.md)
- [Eloquent Collections](../database/models.md)
